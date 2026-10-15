<x-layout>
    <div class="row justify-content-center align-items-center" style="min-height: 65vh;">
        <div class="col-md-5">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light text-del-purple rounded-circle p-3 mb-2 shadow-sm">
                            <i class="bi bi-shield-lock fs-1"></i>
                        </div>
                        <h4 class="fw-bold text-del-purple mb-1">Masuk Pustakawan</h4>
                        <p class="text-muted small">SIPUS-Del — Institut Teknologi Del</p>
                    </div>

                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf

                        <!-- Alamat Surel -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Alamat Surel <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       placeholder="pustakawan@del.ac.id" 
                                       value="{{ old('email', $savedEmail) }}" required autofocus>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kata Sandi -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Kata Sandi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" id="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="••••••••" required>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Opsi Remember Me -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                                   {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-muted" for="remember">
                                Ingat sesi pustakawan pada perangkat ini
                            </label>
                        </div>

                        <!-- Tombol Submit -->
                        <button type="submit" class="btn btn-del-purple w-100 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <a href="{{ route('buku.index') }}" class="text-decoration-none small text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog Publik
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout> 