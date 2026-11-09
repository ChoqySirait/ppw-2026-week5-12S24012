<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /**
     * Menampilkan daftar transaksi peminjaman buku dengan filter status, NIM, dan pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Peminjaman::with(['buku', 'buku.kategori']);

        // Filter status: dipinjam / dikembalikan
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter NIM peminjam
        if ($request->filled('nim')) {
            $query->where('nim_peminjam', strtoupper(trim($request->input('nim'))));
        } elseif ($request->filled('nim_peminjam')) {
            $query->where('nim_peminjam', strtoupper(trim($request->input('nim_peminjam'))));
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $peminjamans = $query->latest()->paginate($perPage)->withQueryString();

        return $this->paginatedResourceResponse(
            PeminjamanResource::collection($peminjamans),
            'Data riwayat peminjaman berhasil diambil.'
        );
    }

    /**
     * Menampilkan detail satu transaksi peminjaman.
     */
    public function show(int $id): JsonResponse
    {
        $peminjaman = Peminjaman::with(['buku', 'buku.kategori'])->find($id);

        if (! $peminjaman) {
            abort(404, "Transaksi peminjaman dengan ID {$id} tidak ditemukan.");
        }

        return $this->successResponse(
            new PeminjamanResource($peminjaman),
            'Detail transaksi peminjaman berhasil diambil.'
        );
    }

    /**
     * Transaksi ACID: Meminjam buku dengan Concurrency Locking (lockForUpdate).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'nim_peminjam' => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'durasi_hari' => 'nullable|integer|min:1|max:30',
            'tanggal_pinjam' => 'nullable|date',
            'tanggal_jatuh_tempo' => 'nullable|date|after_or_equal:tanggal_pinjam',
        ]);

        $bukuId = (int) $validated['buku_id'];
        $nim = strtoupper(trim($validated['nim_peminjam']));
        $nama = trim($validated['nama_peminjam']);
        $durasiHari = (int) ($validated['durasi_hari'] ?? 7);

        $tanggalPinjam = $validated['tanggal_pinjam'] ?? now()->toDateString();
        $tanggalJatuhTempo = $validated['tanggal_jatuh_tempo'] ?? now()->addDays($durasiHari)->toDateString();

        $peminjaman = DB::transaction(function () use ($bukuId, $nim, $nama, $tanggalPinjam, $tanggalJatuhTempo) {
            // 1. Kunci baris buku di level basis data (SELECT ... FOR UPDATE)
            $buku = Buku::where('id', $bukuId)->lockForUpdate()->first();

            if (! $buku) {
                abort(404, "Buku dengan ID {$bukuId} tidak ditemukan.");
            }

            // 2. Evaluasi kuota stok secara atomik
            if ($buku->stok < 1) {
                abort(422, "Stok buku '{$buku->judul}' telah habis. Peminjaman dibatalkan.");
            }

            // 3. Validasi aturan bisnis: mahasiswa tidak boleh meminjam buku yang sama jika belum dikembalikan
            $sedangMeminjam = Peminjaman::where('buku_id', $bukuId)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->lockForUpdate()
                ->exists();

            if ($sedangMeminjam) {
                abort(409, "Mahasiswa dengan NIM {$nim} masih meminjam buku ini dan belum mengembalikannya.");
            }

            // 4. Dekremen kuota stok secara atomik
            $buku->decrement('stok');

            // 5. Catat riwayat peminjaman baru
            $kodePinjam = 'PJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

            $baru = Peminjaman::create([
                'kode_pinjam' => $kodePinjam,
                'buku_id' => $buku->id,
                'nim_peminjam' => $nim,
                'nama_peminjam' => $nama,
                'tanggal_pinjam' => $tanggalPinjam,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'status' => 'dipinjam',
            ]);

            $baru->load(['buku', 'buku.kategori']);

            return $baru;
        }, 3);

        return $this->successResponse(
            new PeminjamanResource($peminjaman),
            'Transaksi peminjaman buku berhasil diproses secara atomik.',
            201,
            ['Location' => url("/api/v1/peminjamans/{$peminjaman->id}")]
        );
    }

    /**
     * Transaksi ACID: Mengembalikan buku dan memulihkan stok fisik (lockForUpdate).
     */
    public function kembalikan(int $id): JsonResponse
    {
        $peminjaman = DB::transaction(function () use ($id) {
            // 1. Kunci baris transaksi peminjaman
            $transaksi = Peminjaman::where('id', $id)->lockForUpdate()->first();

            if (! $transaksi) {
                abort(404, "Transaksi peminjaman dengan ID {$id} tidak ditemukan.");
            }

            // 2. Periksa apakah status sudah dikembalikan (idempotency guard)
            if ($transaksi->status !== 'dipinjam') {
                abort(409, 'Transaksi peminjaman ini sudah pernah dikembalikan sebelumnya.');
            }

            // 3. Kunci baris buku yang bersangkutan
            $buku = Buku::where('id', $transaksi->buku_id)->lockForUpdate()->firstOrFail();

            // 4. Perbarui status peminjaman menjadi 'dikembalikan'
            $transaksi->update([
                'status' => 'dikembalikan',
                'tanggal_kembali' => now()->toDateString(),
            ]);

            // 5. Pulihkan stok buku tepat satu unit
            $buku->increment('stok');

            $transaksi->load(['buku', 'buku.kategori']);

            return $transaksi;
        }, 3);

        return $this->successResponse(
            new PeminjamanResource($peminjaman),
            'Buku berhasil dikembalikan dan kuota stok telah dipulihkan secara atomik.'
        );
    }
}
