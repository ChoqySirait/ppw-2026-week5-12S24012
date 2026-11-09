<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
}
