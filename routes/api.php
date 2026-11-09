<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\BukuApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SIPUS-Del (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Autentikasi Sanctum Pustakawan
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthApiController::class, 'login'])->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthApiController::class, 'me'])->name('api.v1.auth.me');
            Route::post('logout', [AuthApiController::class, 'logout'])->name('api.v1.auth.logout');
        });
    });

    // Katalog Buku Publik
    Route::get('bukus', [BukuApiController::class, 'index'])->name('api.v1.bukus.index');
    Route::get('bukus/{id}', [BukuApiController::class, 'show'])->name('api.v1.bukus.show');

    // Mutasi Data Buku (Terproteksi Bearer Token Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('bukus', [BukuApiController::class, 'store'])->name('api.v1.bukus.store');
        Route::put('bukus/{id}', [BukuApiController::class, 'update'])->name('api.v1.bukus.update');
        Route::patch('bukus/{id}', [BukuApiController::class, 'patch'])->name('api.v1.bukus.patch');
        Route::delete('bukus/{id}', [BukuApiController::class, 'destroy'])->name('api.v1.bukus.destroy');
    });
});
