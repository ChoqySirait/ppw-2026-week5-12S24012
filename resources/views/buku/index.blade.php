<x-layout>
    <!-- Header Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-del mb-1">Daftar Buku Perpustakaan</h3>
            <p class="text-muted mb-0">Kelola katalog dan inventaris koleksi buku Institut Teknologi Del</p>
        </div>
        <a href="{{ route('buku.create') }}" class="btn btn-del shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru
        </a>
    </div>

    <!-- Kartu Statistik Koleksi & Kategori -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Total Judul Terdaftar</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ \App\Models\Buku::count() }}</h3>
                    </div>
                    <div class="rounded-circle p-3 bg-primary-subtle text-primary">
                        <i class="bi bi-journal-album fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Total Eksemplar Fisik</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ \App\Models\Buku::sum('stok') }}</h3>
                    </div>
                    <div class="rounded-circle p-3 bg-success-subtle text-success">
                        <i class="bi bi-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-purple" style="border-left-color: #4C1D95 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Klasifikasi Kategori</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ \App\Models\Kategori::count() }}</h3>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background-color: #4C1D95;">
                        <i class="bi bi-bookmarks-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Katalog Koleksi Buku -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>ISBN</th>
                            <th>Judul Buku</th>
                            <th>Penulis</th>
                            <th>Kategori</th>
                            <th>Tahun</th>
                            <th>Status Stok</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bukus as $index => $buku)
                            <tr>
                                <td class="ps-4 text-muted">{{ $bukus->firstItem() + $index }}</td>
                                <td><span class="badge bg-secondary font-monospace">{{ $buku->isbn }}</span></td>
                                <td class="fw-semibold text-dark">{{ $buku->judul }}</td>
                                <td>{{ $buku->penulis }}</td>
                                <td>
                                    <span class="badge badge-del">{{ $buku->kategori->nama_kategori }}</span>
                                </td>
                                <td>{{ $buku->tahun_terbit }}</td>
                                <td>
                                    @if($buku->stok > 5)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i> {{ $buku->stok }} Tersedia
                                        </span>
                                    @elseif($buku->stok > 0)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-exclamation-circle me-1"></i> Sisa {{ $buku->stok }} (Kritis)
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            <i class="bi bi-x-circle me-1"></i> Habis
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-info" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-warning" title="Ubah Data">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini dari katalog perpustakaan?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Buku">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Belum ada data buku yang tersimpan dalam sistem perpustakaan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Navigasi Halaman (Pagination) -->
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <span class="text-muted small">
            Menampilkan {{ $bukus->firstItem() ?? 0 }} sampai {{ $bukus->lastItem() ?? 0 }} dari total {{ $bukus->total() }} buku
        </span>
        <div>
            {{ $bukus->links() }}
        </div>
    </div>
</x-layout>
