<x-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-del mb-1">Kategori: {{ $kategori->nama_kategori }}</h3>
            <span class="badge bg-secondary font-monospace">{{ $kategori->kode_kategori }}</span>
        </div>
        <a href="{{ route('kategori.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Kategori
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0">Koleksi Buku Dalam Kategori Ini</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>ISBN</th>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Penerbit</th>
                            <th>Tahun</th>
                            <th>Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kategori->bukus as $index => $buku)
                            <tr>
                                <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                <td><span class="badge bg-secondary font-monospace">{{ $buku->isbn }}</span></td>
                                <td class="fw-semibold">{{ $buku->judul }}</td>
                                <td>{{ $buku->penulis }}</td>
                                <td>{{ $buku->penerbit }}</td>
                                <td>{{ $buku->tahun_terbit }}</td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle">{{ $buku->stok }} eks</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada buku terdaftar di kategori ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
