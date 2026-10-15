<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PeminjamanController extends Controller
{
    /**
     * Menampilkan daftar riwayat sirkulasi peminjaman buku.
     */
    public function index(Request $request): View
    {
        $peminjamans = Peminjaman::with('buku')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('kode_pinjam', 'like', "%{$request->search}%")
                      ->orWhere('nim_peminjam', 'like', "%{$request->search}%")
                      ->orWhere('nama_peminjam', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('peminjaman.index', compact('peminjamans'));
    }

    /**
     * Menampilkan formulir peminjaman untuk buku tertentu.
     */
    public function create(Request $request): View
    {
        $bukuId = $request->query('buku_id');
        $bukuDipilih = $bukuId ? Buku::find($bukuId) : null;
        $bukus = Buku::where('stok', '>', 0)->orderBy('judul')->get();

        return view('peminjaman.create', compact('bukus', 'bukuDipilih'));
    }

    /**
     * Transaksi ACID: Meminjam buku dengan proteksi Concurrency Locking (lockForUpdate).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'nim_peminjam' => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'tanggal_pinjam' => 'required|date',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:tanggal_pinjam',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));
        $bukuId = (int) $validated['buku_id'];

        try {
            DB::transaction(function () use ($validated, $bukuId, $nim) {
                // 1. Kunci baris buku di level database (SELECT ... FOR UPDATE)
                $buku = Buku::where('id', $bukuId)->lockForUpdate()->firstOrFail();

                // 2. Evaluasi stok atomik: cegah race condition hingga minus
                if ($buku->stok < 1) {
                    throw new RuntimeException("Stok buku '{$buku->judul}' telah habis. Peminjaman dibatalkan.");
                }

                // 3. Validasi aturan bisnis: mahasiswa tidak boleh meminjam buku sama yang belum dikembalikan
                $sedangMeminjam = Peminjaman::where('buku_id', $bukuId)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->lockForUpdate()
                    ->exists();

                if ($sedangMeminjam) {
                    throw new RuntimeException("Mahasiswa dengan NIM {$nim} masih meminjam buku ini dan belum mengembalikannya.");
                }

                // 4. Dekremen stok buku secara atomik
                $buku->decrement('stok');

                // 5. Catat riwayat peminjaman ke tabel peminjamans
                Peminjaman::create([
                    'kode_pinjam' => 'PJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'buku_id' => $buku->id,
                    'nim_peminjam' => $nim,
                    'nama_peminjam' => $validated['nama_peminjam'],
                    'tanggal_pinjam' => $validated['tanggal_pinjam'],
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                    'status' => 'dipinjam',
                ]);
            }, 3);

            // Simpan riwayat peminjam terakhir ke session state
            session([
                'last_borrower_nim' => $nim,
                'last_borrower_name' => $validated['nama_peminjam'],
            ]);

            return redirect()->route('peminjaman.index')
                ->with('success', 'Transaksi peminjaman buku berhasil diproses secara atomik.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error("[DB EXCEPTION PEMINJAMAN]: " . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kegagalan integritas basis data saat memproses peminjaman.');
        }
    }

    /**
     * Transaksi ACID: Mengembalikan buku dan memulihkan kuota stok fisik.
     */
    public function kembalikan(int $id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id) {
                // 1. Kunci baris peminjaman
                $peminjaman = Peminjaman::where('id', $id)
                    ->where('status', 'dipinjam')
                    ->lockForUpdate()
                    ->firstOrFail();

                // 2. Kunci baris buku yang bersangkutan
                $buku = Buku::where('id', $peminjaman->buku_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 3. Perbarui status peminjaman
                $peminjaman->update([
                    'status' => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                // 4. Pulihkan stok buku secara atomik
                $buku->increment('stok');
            }, 3);

            return redirect()->route('peminjaman.index')
                ->with('success', 'Buku telah berhasil dikembalikan dan stok buku dipulihkan.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error("[DB EXCEPTION PENGEMBALIAN]: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memperbarui status pengembalian.');
        }
    }
}