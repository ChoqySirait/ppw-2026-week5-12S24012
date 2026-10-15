<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BukuController extends Controller
{
    /**
     * Menampilkan katalog buku dengan kartu analitik DB::table,
     * filter pencarian dinamis when(), dan agregasi withCount('peminjamans').
     */
    public function index(Request $request): View
    {
        // 1. Rekapitulasi Analitik Berbasis Fluent Query Builder (DB::table)
        $stats = [
            'total_judul' => DB::table('bukus')->count(),
            'total_stok' => (int) DB::table('bukus')->sum('stok'),
            'total_dipinjam' => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'total_kembali' => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
        ];

        // 2. Simpan kata kunci pencarian ke dalam session state
        if ($request->filled('search')) {
            session(['last_buku_search' => $request->search]);
        }

        // 3. Eager Loading kategori, withCount peminjamans, & Filter Dinamis when()
        $bukus = Buku::with('kategori')
            ->withCount('peminjamans')
            ->when($request->filled('kategori_id'), function ($query) use ($request) {
                $query->where('kategori_id', $request->kategori_id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('judul', 'like', "%{$search}%")
                      ->orWhere('penulis', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('buku.index', compact('bukus', 'kategoris', 'stats'));
    }

    /**
     * Menampilkan formulir penambahan buku baru.
     */
    public function create(): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.create', compact('kategoris'));
    }

    /**
     * Menyimpan data buku baru ke basis data.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'isbn' => 'required|string|max:30|unique:bukus,isbn',
            'judul' => 'required|string|min:5|max:255',
            'penulis' => 'required|string|max:150',
            'penerbit' => 'required|string|max:150',
            'tahun_terbit' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string',
        ]);

        Buku::create($validated);

        return redirect()->route('buku.index')
            ->with('success', 'Koleksi buku baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail informasi lengkap satu buku.
     */
    public function show(Buku $buku): View
    {
        $buku->load(['kategori', 'peminjamans' => fn($q) => $q->latest()->limit(5)]);
        return view('buku.show', compact('buku'));
    }

    /**
     * Menampilkan formulir pengubahan data buku.
     */
    public function edit(Buku $buku): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.edit', compact('buku', 'kategoris'));
    }

    /**
     * Memperbarui data buku di basis data.
     */
    public function update(Request $request, Buku $buku): RedirectResponse
    {
        $validated = $request->validate([
            'isbn' => 'required|string|max:30|unique:bukus,isbn,' . $buku->id,
            'judul' => 'required|string|min:5|max:255',
            'penulis' => 'required|string|max:150',
            'penerbit' => 'required|string|max:150',
            'tahun_terbit' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string',
        ]);

        $buku->update($validated);

        return redirect()->route('buku.index')
            ->with('success', 'Data buku berhasil diperbarui.');
    }

    /**
     * Menghapus buku dari basis data.
     */
    public function destroy(Buku $buku): RedirectResponse
    {
        $buku->delete();

        return redirect()->route('buku.index')
            ->with('success', 'Buku berhasil dihapus dari katalog perpustakaan.');
    }
}