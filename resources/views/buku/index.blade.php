<x-layout>
    <!-- Kartu Analitik DB::table() -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Total Judul Buku</div>
                        <div class="h3 mb-0 fw-bold text-primary">{{ number_format($stats['total_judul']) }}</div>
                    </div>
                    <div class="fs-1 text-primary opacity-25">
                        <i class="bi bi-bookshelf"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Stok Fisik Tersedia</div>
                        <div class="h3 mb-0 fw-bold text-success">{{ number_format($stats['total_stok']) }}</div>
                    </div>
                    <div class="fs-1 text-success opacity-25">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Sedang Dipinjam</div>
                        <div class="h3 mb-0 fw-bold text-warning">{{ number_format($stats['total_dipinjam']) }}</div>
                    </div>
                    <div class="fs-1 text-warning opacity-25">
                        <i class="bi bi-journal-arrow-up"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Total Dikembalikan</div>
                        <div class="h3 mb-0 fw-bold text-info">{{ number_format($stats['total_kembali']) }}</div>
                    </div>
                    <div class="fs-1 text-info opacity-25">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bilah Filter & Pencarian Dinamis when() -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h4 class="mb-0 fw-bold text-del-purple">
                    <i class="bi bi-journals me-2"></i>Katalog Koleksi Perpustakaan
                </h4>
                @auth
                    <a href="{{ route('buku.create') }}" class="btn btn-del-purple d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle"></i> Tambah Koleksi Buku
                    </a>
                @endauth
            </div>

            <form action="{{ route('buku.index') }}" method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari Judul, Penulis, atau ISBN..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="kategori_id" class="form-select">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($kategoris as $kat)
                            <option value="{{ $kat->id }}" {{ request('kategori_id') == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-del flex-grow-1">
                        <i class="bi bi-funnel"></i> Terapkan Filter
                    </button>
                    @if(request('search') || request('kategori_id'))
                        <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Buku dengan Agregasi withCount('peminjamans') -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 5%">No</th>
                        <th style="width: 15%">ISBN</th>
                        <th style="width: 30%">Judul & Penulis</th>
                        <th style="width: 15%">Kategori</th>
                        <th class="text-center" style="width: 10%">Stok Fisik</th>
                        <th class="text-center" style="width: 10%">Dipinjam</th>
                        <th class="text-center" style="width: 15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bukus as $index => $buku)
                        <tr>
                            <td class="text-center text-muted">
                                {{ $bukus->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $buku->isbn }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $buku->judul }}</div>
                                <div class="small text-muted">
                                    <i class="bi bi-person me-1"></i>{{ $buku->penulis }} ({{ $buku->tahun_terbit }})
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-del-purple text-white">
                                    {{ $buku->kategori->nama_kategori ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($buku->stok > 3)
                                    <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">
                                        {{ $buku->stok }} Tersedia
                                    </span>
                                @elseif($buku->stok > 0)
                                    <span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill">
                                        {{ $buku->stok }} Kritis
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill">
                                        Habis
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">
                                    <i class="bi bi-arrow-repeat me-1"></i>{{ $buku->peminjamans_count }}x
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-info" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @auth
                                        @if($buku->stok > 0)
                                            <a href="{{ route('peminjaman.create', ['buku_id' => $buku->id]) }}" 
                                               class="btn btn-outline-success" title="Pinjamkan Buku Ini">
                                                <i class="bi bi-arrow-up-right-circle"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-outline-secondary" disabled title="Stok Kosong">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        @endif

                                        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endauth
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ditemukan data buku yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bukus->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Menampilkan {{ $bukus->firstItem() }} sampai {{ $bukus->lastItem() }} dari {{ $bukus->total() }} buku
                    </div>
                    <div>
                        {{ $bukus->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>