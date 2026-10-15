<x-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold text-del-purple">
                        <i class="bi bi-journal-plus me-2"></i>Pencatatan Peminjaman Buku Baru
                    </h5>
                    <p class="text-muted small mb-0">Transaksi ini dilindungi oleh mekanisme Pessimistic Locking (lockForUpdate) untuk mencegah stok minus.</p>
                </div>

                <div class="card-body">
                    <form action="{{ route('peminjaman.store') }}" method="POST">
                        @csrf

                        <!-- Pilihan Buku -->
                        <div class="mb-3">
                            <label for="buku_id" class="form-label fw-semibold">Pilih Buku yang Dipinjam <span class="text-danger">*</span></label>
                            <select name="buku_id" id="buku_id" class="form-select @error('buku_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Buku Tersedia --</option>
                                @foreach($bukus as $b)
                                    <option value="{{ $b->id }}" 
                                        {{ (old('buku_id') == $b->id || (isset($bukuDipilih) && $bukuDipilih->id == $b->id)) ? 'selected' : '' }}>
                                        {{ $b->judul }} (Sisa Stok Fisik: {{ $b->stok }})
                                    </option>
                                @endforeach
                            </select>
                            @error('buku_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- NIM Peminjam -->
                            <div class="col-md-6">
                                <label for="nim_peminjam" class="form-label fw-semibold">NIM Mahasiswa <span class="text-danger">*</span></label>
                                <input type="text" name="nim_peminjam" id="nim_peminjam" 
                                       class="form-control @error('nim_peminjam') is-invalid @enderror" 
                                       placeholder="Contoh: 12S24012" 
                                       value="{{ old('nim_peminjam', session('last_borrower_nim')) }}" required>
                                @error('nim_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nama Peminjam -->
                            <div class="col-md-6">
                                <label for="nama_peminjam" class="form-label fw-semibold">Nama Lengkap Mahasiswa <span class="text-danger">*</span></label>
                                <input type="text" name="nama_peminjam" id="nama_peminjam" 
                                       class="form-control @error('nama_peminjam') is-invalid @enderror" 
                                       placeholder="Masukkan nama mahasiswa..." 
                                       value="{{ old('nama_peminjam', session('last_borrower_name')) }}" required>
                                @error('nama_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Tanggal Pinjam -->
                            <div class="col-md-6">
                                <label for="tanggal_pinjam" class="form-label fw-semibold">Tanggal Pinjam <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" 
                                       class="form-control @error('tanggal_pinjam') is-invalid @enderror" 
                                       value="{{ old('tanggal_pinjam', date('Y-m-d')) }}" required>
                                @error('tanggal_pinjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tanggal Jatuh Tempo -->
                            <div class="col-md-6">
                                <label for="tanggal_jatuh_tempo" class="form-label fw-semibold">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo" 
                                       class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror" 
                                       value="{{ old('tanggal_jatuh_tempo', date('Y-m-d', strtotime('+7 days'))) }}" required>
                                @error('tanggal_jatuh_tempo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-del-purple">
                                <i class="bi bi-check-circle me-1"></i> Proses Transaksi Peminjaman (ACID)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>