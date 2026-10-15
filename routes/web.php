<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

// 1. Rute Pengalihan Domain Akar
Route::get('/', fn() => redirect()->route('buku.index'));

// 2. Rute Publik (Mode Tamu & Pengunjung Mahasiswa - Hanya Melihat)
Route::get('buku', [BukuController::class, 'index'])->name('buku.index');
Route::get('buku/{buku}', [BukuController::class, 'show'])->name('buku.show');
Route::get('kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::get('kategori/{kategori}', [KategoriController::class, 'show'])->name('kategori.show');

// 3. Rute Tamu (Hanya dapat diakses jika belum login)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
});

// 4. Rute Terproteksi Khusus Pustakawan (Wajib Login via Middleware Auth)
Route::middleware('auth')->group(function () {
    // Keluar Sistem
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Mutasi Data Koleksi Buku
    Route::get('buku/create', [BukuController::class, 'create'])->name('buku.create');
    Route::post('buku', [BukuController::class, 'store'])->name('buku.store');
    Route::get('buku/{buku}/edit', [BukuController::class, 'edit'])->name('buku.edit');
    Route::put('buku/{buku}', [BukuController::class, 'update'])->name('buku.update');
    Route::delete('buku/{buku}', [BukuController::class, 'destroy'])->name('buku.destroy');

    // Mutasi Data Master Kategori
    Route::get('kategori/create', [KategoriController::class, 'create'])->name('kategori.create');
    Route::post('kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::get('kategori/{kategori}/edit', [KategoriController::class, 'edit'])->name('kategori.edit');
    Route::put('kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    // Modul Sirkulasi Peminjaman & Pengembalian Buku Berbasis ACID
    Route::get('peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::post('peminjaman/{id}/kembalikan', [PeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');
});