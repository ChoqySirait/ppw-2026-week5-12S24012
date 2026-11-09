<?php

use App\Http\Controllers\Api\V1\AuthApiController;
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
});
