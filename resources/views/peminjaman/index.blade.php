<x-layout>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h4 class="mb-1 fw-bold text-del-purple">
                        <i class="bi bi-arrow-left-right me-2"></i>Sirkulasi Peminjaman & Pengembalian Buku
                    </h4>
                    <p class="text-muted small mb-0">Manajemen pencatatan transaksi sirkulasi perpustakaan berbasis ACID.</p>
                </div>
                @auth
                    <a href="{{ route('peminjaman.create') }}" class="btn btn-del-purple d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle"></i> Catat Peminjaman Baru
                    </a>
                @endauth
            </div>

            <!-- Filter Status dan Pencarian Transaksi -->
            <form action="{{ route('peminjaman.index') }}" method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari Kode Pinjam, NIM, atau Nama Mahasiswa..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">-- Semua Status Transaksi --</option>
                        <option value="dipinjam" {{ request('status') === 'dipinjam' ? 'selected' : '' }}>Sedang Dipinjam</option>
                        <option value="dikembalikan" {{ request('status') === 'dikembalikan' ? 'selected' : '' }}>Sudah Dikembalikan</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-del flex-grow-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Sirkulasi -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 5%">No</th>
                        <th style="width: 15%">Kode Transaksi</th>
                        <th style="width: 25%">Buku yang Dipinjam</th>
                        <th style="width: 20%">Data Peminjam</th>
                        <th style="width: 15%">Periode Pinjam</th>
                        <th class="text-center" style="width: 10%">Status</th>
                        <th class="text-center" style="width: 10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($peminjamans as $index => $pinjam)
                        <tr>
                            <td class="text-center text-muted">
                                {{ $peminjamans->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $pinjam->kode_pinjam }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $pinjam->buku->judul ?? '[Buku Dihapus]' }}</div>
                                <div class="small text-muted">ISBN: {{ $pinjam->buku->isbn ?? '-' }}</div>
                            </td>
                            <td>
                                <div><strong>{{ $pinjam->nama_peminjam }}</strong></div>
                                <div class="small text-muted font-monospace">NIM: {{ $pinjam->nim_peminjam }}</div>
                            </td>
                            <td class="small">
                                <div>Pinjam: {{ $pinjam->tanggal_pinjam->format('d/m/Y') }}</div>
                                <div class="text-danger">Tempo: {{ $pinjam->tanggal_jatuh_tempo->format('d/m/Y') }}</div>
                                @if($pinjam->tanggal_kembali)
                                    <div class="text-success">Kembali: {{ $pinjam->tanggal_kembali->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($pinjam->status === 'dipinjam')
                                    <span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill">
                                        <i class="bi bi-clock me-1"></i>Dipinjam
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">
                                        <i class="bi bi-check2-circle me-1"></i>Dikembalikan
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @auth
                                    @if($pinjam->status === 'dipinjam')
                                        <form action="{{ route('peminjaman.kembalikan', $pinjam->id) }}" method="POST"
                                              onsubmit="return confirm('Proses pengembalian buku ini sekarang?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1 mx-auto" title="Kembalikan Buku">
                                                <i class="bi bi-arrow-down-left-circle"></i> Kembalikan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small"><i class="bi bi-check-all"></i> Selesai</span>
                                    @endif
                                @else
                                    <span class="text-muted small">-</span>
                                @endauth
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                Belum ada riwayat transaksi peminjaman buku yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($peminjamans->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Menampilkan {{ $peminjamans->firstItem() }} sampai {{ $peminjamans->lastItem() }} dari {{ $peminjamans->total() }} transaksi
                    </div>
                    <div>
                        {{ $peminjamans->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>