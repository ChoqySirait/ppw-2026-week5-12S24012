<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIPUS-Del - Sistem Informasi Perpustakaan Institut Teknologi Del' }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --del-purple: #4C1D95;
            --del-purple-hover: #3B0764;
            --del-purple-light: #F3E8FF;
        }
        .bg-del-purple { background-color: var(--del-purple) !important; }
        .text-del-purple { color: var(--del-purple) !important; }
        .btn-del-purple {
            background-color: var(--del-purple);
            color: #ffffff;
            border: none;
        }
        .btn-del-purple:hover {
            background-color: var(--del-purple-hover);
            color: #ffffff;
        }
        .btn-outline-del {
            color: var(--del-purple);
            border-color: var(--del-purple);
        }
        .btn-outline-del:hover {
            background-color: var(--del-purple);
            color: #ffffff;
        }
        .navbar-del {
            background-color: var(--del-purple);
            box-shadow: 0 2px 8px rgba(76, 29, 149, 0.25);
        }
        .nav-link.active {
            font-weight: 700;
            border-bottom: 2px solid #ffffff;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Bilah Navigasi Utama -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-del sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('buku.index') }}">
                <i class="bi bi-book-half fs-4"></i>
                <span class="fw-bold tracking-wide">SIPUS-Del</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('buku.*') ? 'active' : '' }}" href="{{ route('buku.index') }}">
                            <i class="bi bi-journal-bookmark me-1"></i> Katalog Buku
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kategori.*') ? 'active' : '' }}" href="{{ route('kategori.index') }}">
                            <i class="bi bi-tags me-1"></i> Kategori
                        </a>
                    </li>
                    @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('peminjaman.*') ? 'active' : '' }}" href="{{ route('peminjaman.index') }}">
                            <i class="bi bi-arrow-left-right me-1"></i> Sirkulasi Peminjaman
                        </a>
                    </li>
                    @endauth
                </ul>

                <!-- Indikator Akses: Pustakawan vs Mode Tamu -->
                <div class="d-flex align-items-center gap-2">
                    @auth
                        <span class="navbar-text text-white small me-2">
                            <i class="bi bi-person-badge-fill text-warning me-1"></i>
                            Pustakawan: <strong>{{ Auth::user()->name ?? 'Pustakawan' }}</strong>
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1">
                                <i class="bi bi-box-arrow-right"></i> Keluar
                            </button>
                        </form>
                    @else
                        <span class="badge bg-light text-del-purple py-2 px-3 me-2">
                            <i class="bi bi-person-circle me-1"></i> Mode Tamu / Mahasiswa
                        </span>
                        <a href="{{ route('login') }}" class="btn btn-sm btn-light text-del-purple fw-semibold d-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-shield-lock-fill"></i> Masuk Pustakawan
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Konten Halaman -->
    <main class="container my-4 flex-grow-1">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
        <div class="container">
            <p class="mb-0">&copy; {{ date('Y') }} <strong>SIPUS-Del</strong> — Institut Teknologi Del. Praktikum Pemrograman & Pengujian Web.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>