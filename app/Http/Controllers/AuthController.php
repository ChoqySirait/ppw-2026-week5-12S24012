<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan formulir masuk untuk Pustakawan.
     */
    public function showLoginForm(Request $request): View
    {
        // Membaca cookie surel terakhir jika tersimpan pada peramban
        $savedEmail = $request->cookie('del_last_librarian_email');

        return view('auth.login', compact('savedEmail'));
    }

    /**
     * Memproses autentikasi pustakawan, regenerasi ID sesi, dan penanaman cookie.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // 1. WAJIB: Regenerasi ID Sesi untuk memitigasi serangan Session Fixation
            $request->session()->regenerate();

            // 2. Simpan waktu login pustakawan ke server session state
            session([
                'login_timestamp' => now()->format('d M Y, H:i:s') . ' WIB',
            ]);

            // 3. Pasang HTTP Cookie (aktif 7 hari = 10080 menit) untuk mengingat email
            Cookie::queue('del_last_librarian_email', $request->email, 10080);

            return redirect()->intended(route('buku.index'))
                ->with('success', 'Selamat datang kembali, Pustakawan ' . Auth::user()->name . '. Sesi Anda telah aktif.');
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'Kombinasi alamat surel atau kata sandi tidak valid.');
    }

    /**
     * Memproses keluar sistem, invalidasi total sesi, dan regenerasi token CSRF.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Bersihkan seluruh data sesi dan musnahkan Session ID lama dari server
        $request->session()->invalidate();

        // Terbitkan ulang token CSRF agar sesi peramban kembali steril
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Sesi pustakawan telah diakhiri dengan aman dari server.');
    }
}