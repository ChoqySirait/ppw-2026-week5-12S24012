<x-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-del mb-0"><i class="bi bi-book-half me-2"></i>Detail Informasi Buku</h5>
                    <span class="badge badge-del">{{ $buku->kategori->nama_kategori }}</span>
                </div>
                <div class="card-body p-4">
                    <h4 class="fw-bold text-dark mb-3">{{ $buku->judul }}</h4>
                    <div class="row mb-4">
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block">ISBN</small>
                            <span class="font-monospace fw-semibold">{{ $buku->isbn }}</span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block">Penulis</small>
                            <span class="fw-semibold">{{ $buku->penulis }}</span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block">Penerbit</small>
                            <span class="fw-semibold">{{ $buku->penerbit }}</span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block">Tahun Terbit</small>
                            <span class="fw-semibold">{{ $buku->tahun_terbit }}</span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block">Ketersediaan Stok</small>
                            <span class="badge {{ $buku->stok > 0 ? 'bg-success' : 'bg-danger' }}">{{ $buku->stok }} eksemplar</span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark">Sinopsis</h6>
                    <div class="p-3 bg-light rounded-3 text-secondary border">
                        {{ $buku->sinopsis ?? 'Tidak ada sinopsis yang dicantumkan untuk buku ini.' }}
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('buku.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i> Edit Buku
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout> 
