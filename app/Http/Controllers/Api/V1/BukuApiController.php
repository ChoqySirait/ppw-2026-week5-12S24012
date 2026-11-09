<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class BukuApiController extends Controller
{
    use ApiResponse;

    /**
     * Whitelist kolom pengurutan yang diizinkan untuk mencegah injection.
     */
    protected array $allowedSortColumns = [
        'id',
        'isbn',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'stok',
        'created_at',
    ];

    /**
     * Menampilkan katalog buku dengan pencarian, filter kategori, ketersediaan, pengurutan, dan pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Buku::with('kategori')->withCount('peminjamans');

        // Filter pencarian: judul, penulis, isbn
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->input('kategori_id'));
        }

        // Filter ketersediaan (tersedia=1 -> stok > 0)
        if ($request->boolean('tersedia')) {
            $query->where('stok', '>', 0);
        }

        // Whitelist Sorting
        $sortParam = $request->input('sort', 'created_at');
        $direction = strtolower($request->input('direction', 'desc'));

        if (str_starts_with($sortParam, '-')) {
            $column = substr($sortParam, 1);
            $direction = 'desc';
        } else {
            $column = $sortParam;
        }

        if (in_array($column, $this->allowedSortColumns)) {
            $validDirection = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
            $query->orderBy($column, $validDirection);
        } else {
            $query->latest();
        }

        // Batasi jumlah per_page antara 1 sampai 100
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $bukus = $query->paginate($perPage)->withQueryString();

        return $this->paginatedResourceResponse(
            BukuResource::collection($bukus),
            'Data katalog buku berhasil diambil.'
        );
    }

    /**
     * Menampilkan detail satu buku lengkap dengan kategori dan riwayat peminjaman publik.
     */
    public function show(int $id): JsonResponse
    {
        $buku = Buku::with([
            'kategori',
            'peminjamans' => fn ($q) => $q->latest()->limit(10),
        ])
            ->withCount('peminjamans')
            ->find($id);

        if (! $buku) {
            abort(404, "Buku dengan ID {$id} tidak ditemukan.");
        }

        return $this->successResponse(
            new BukuResource($buku),
            'Detail informasi buku berhasil diambil.'
        );
    }

    /**
     * Menambahkan data koleksi buku baru (POST).
     */
    public function store(Request $request): JsonResponse
    {
        $currentYear = (int) date('Y');

        $validated = $request->validate([
            'isbn' => 'required|string|max:30|unique:bukus,isbn',
            'judul' => 'required|string|min:3|max:255',
            'penulis' => 'required|string|max:150',
            'penerbit' => 'required|string|max:150',
            'tahun_terbit' => "required|integer|min:1900|max:{$currentYear}",
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string',
        ]);

        $buku = Buku::create($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            new BukuResource($buku),
            'Koleksi buku baru berhasil ditambahkan.',
            201,
            ['Location' => url("/api/v1/bukus/{$buku->id}")]
        );
    }

    /**
     * Mengganti seluruh representasi buku secara penuh (PUT).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            abort(404, "Buku dengan ID {$id} tidak ditemukan.");
        }

        $currentYear = (int) date('Y');

        // PUT mewajibkan seluruh atribut utama dikirimkan
        $validated = $request->validate([
            'isbn' => "required|string|max:30|unique:bukus,isbn,{$buku->id}",
            'judul' => 'required|string|min:3|max:255',
            'penulis' => 'required|string|max:150',
            'penerbit' => 'required|string|max:150',
            'tahun_terbit' => "required|integer|min:1900|max:{$currentYear}",
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string',
        ]);

        $buku->update($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            new BukuResource($buku),
            'Data buku berhasil diperbarui secara penuh (PUT).'
        );
    }

    /**
     * Memperbarui representasi buku secara parsial (PATCH).
     */
    public function patch(Request $request, int $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            abort(404, "Buku dengan ID {$id} tidak ditemukan.");
        }

        $currentYear = (int) date('Y');

        // PATCH mengizinkan pembaruan sebagian menggunakan sometimes
        $validated = $request->validate([
            'isbn' => "sometimes|required|string|max:30|unique:bukus,isbn,{$buku->id}",
            'judul' => 'sometimes|required|string|min:3|max:255',
            'penulis' => 'sometimes|required|string|max:150',
            'penerbit' => 'sometimes|required|string|max:150',
            'tahun_terbit' => "sometimes|required|integer|min:1900|max:{$currentYear}",
            'kategori_id' => 'sometimes|required|exists:kategoris,id',
            'stok' => 'sometimes|required|integer|min:0',
            'sinopsis' => 'sometimes|nullable|string',
        ]);

        $buku->update($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            new BukuResource($buku),
            'Data buku berhasil diperbarui secara parsial (PATCH).'
        );
    }

    /**
     * Menghapus buku dari katalog (DELETE).
     */
    public function destroy(int $id): Response
    {
        $buku = Buku::find($id);

        if (! $buku) {
            abort(404, "Buku dengan ID {$id} tidak ditemukan.");
        }

        // Validasi: Tolak penghapusan jika ada peminjaman dengan status 'dipinjam'
        $hasActiveLoans = $buku->peminjamans()->where('status', 'dipinjam')->exists();

        if ($hasActiveLoans) {
            abort(409, 'Buku tidak dapat dihapus karena masih ada transaksi peminjaman aktif.');
        }

        $buku->delete();

        return response()->noContent();
    }

    /**
     * Mengambil metadata buku eksternal dari OpenLibrary API berdasarkan ISBN.
     */
    public function referensiEksternal(int $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            abort(404, "Buku dengan ID {$id} tidak ditemukan.");
        }

        // Normalisasi format ISBN (hilangkan tanda hubung dan karakter non-alfanumerik)
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $buku->isbn ?? '');

        if (empty($cleanIsbn)) {
            return $this->successResponse([
                'buku_id' => $buku->id,
                'isbn' => $buku->isbn,
                'clean_isbn' => null,
                'status_sinkronisasi' => 'isbn_invalid',
                'pesan' => 'Format ISBN buku tidak valid untuk pencarian eksternal.',
                'metadata' => null,
            ], 'ISBN buku tidak valid.', 200);
        }

        try {
            $response = Http::timeout(8)->get("https://openlibrary.org/isbn/{$cleanIsbn}.json");

            if ($response->status() === 404) {
                return $this->successResponse([
                    'buku_id' => $buku->id,
                    'isbn' => $buku->isbn,
                    'clean_isbn' => $cleanIsbn,
                    'status_sinkronisasi' => 'not_found',
                    'pesan' => 'Metadata buku tidak ditemukan pada repositori OpenLibrary.',
                    'metadata' => null,
                ], 'Metadata eksternal tidak ditemukan.', 200);
            }

            if (! $response->successful()) {
                return $this->successResponse([
                    'buku_id' => $buku->id,
                    'isbn' => $buku->isbn,
                    'clean_isbn' => $cleanIsbn,
                    'status_sinkronisasi' => 'upstream_error',
                    'pesan' => 'Layanan OpenLibrary mengembalikan status error: '.$response->status(),
                    'metadata' => null,
                ], 'Layanan eksternal mengalami kendala sementara.', 200);
            }

            $data = $response->json();

            if (! is_array($data)) {
                return $this->successResponse([
                    'buku_id' => $buku->id,
                    'isbn' => $buku->isbn,
                    'clean_isbn' => $cleanIsbn,
                    'status_sinkronisasi' => 'invalid_response',
                    'pesan' => 'Format respons dari OpenLibrary tidak sesuai spesifikasi.',
                    'metadata' => null,
                ], 'Format respons eksternal tidak valid.', 200);
            }

            $metadata = [
                'judul' => $data['title'] ?? $buku->judul,
                'subjudul' => $data['subtitle'] ?? null,
                'jumlah_halaman' => $data['number_of_pages'] ?? null,
                'penerbit' => $data['publishers'] ?? [],
                'tanggal_terbit' => $data['publish_date'] ?? null,
                'works_key' => $data['works'][0]['key'] ?? null,
                'openlibrary_url' => "https://openlibrary.org/isbn/{$cleanIsbn}",
                'covers' => $data['covers'] ?? [],
            ];

            return $this->successResponse([
                'buku_id' => $buku->id,
                'isbn' => $buku->isbn,
                'clean_isbn' => $cleanIsbn,
                'status_sinkronisasi' => 'synced',
                'pesan' => 'Metadata eksternal berhasil disinkronkan dari OpenLibrary.',
                'metadata' => $metadata,
            ], 'Metadata eksternal berhasil diambil.');
        } catch (ConnectionException $e) {
            return $this->successResponse([
                'buku_id' => $buku->id,
                'isbn' => $buku->isbn,
                'clean_isbn' => $cleanIsbn,
                'status_sinkronisasi' => 'timeout',
                'pesan' => 'Koneksi ke OpenLibrary melebihi batas waktu (timeout 8 detik).',
                'metadata' => null,
            ], 'Gagal terhubung ke layanan eksternal (timeout).', 200);
        } catch (Throwable $e) {
            return $this->successResponse([
                'buku_id' => $buku->id,
                'isbn' => $buku->isbn,
                'clean_isbn' => $cleanIsbn,
                'status_sinkronisasi' => 'failed',
                'pesan' => 'Terjadi kesalahan sistem saat menghubungi OpenLibrary: '.$e->getMessage(),
                'metadata' => null,
            ], 'Pencarian metadata eksternal gagal diproses.', 200);
        }
    }
}
