<x-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-del mb-1">Daftar Buku Perpustakaan</h3>
            <p class="text-muted mb-0">Kelola katalog dan inventaris koleksi buku Institut Teknologi Del</p>
        </div>
        <a href="{{ route('buku.create') }}" class="btn btn-del shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru
        </a>
    </div>

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
                            <th>Stok</th>
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
                                    @if($buku->stok > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $buku->stok }} eks</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Habis</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
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
                                    Belum ada data buku yang tersimpan dalam sistem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        {{ $bukus->links() }}
    </div>
</x-layout>
