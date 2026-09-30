<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RiwayatLogin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        // 'aktif' ikut Dicek supaya akun yang dinonaktifkan tidak bisa masuk
        $credentials['aktif'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        // Cegah session fixation
        $request->session()->regenerate();

        $user = Auth::user();

        /*
         * Catat login-nya di sini, setelah kredensial diterima dan sebelum
         * pengguna diarahkan ke dashboard. Baris ini hanya dibaca grafik
         * "Aktivitas Login Mingguan" di dashboard admin; seluruh aturan
         * masuk tetap sama seperti sebelumnya, tidak ada satu pun
         * pemeriksaan yang digeser.
         */
        RiwayatLogin::catat($user);

        $dashboard = $user->isAdmin()
            ? route('admin.dashboard')
            : route('user.dashboard');

        $intended = $request->session()->pull('url.intended');

        if ($intended !== null && $this->intendedBolehDipakai($intended, $user, $request)) {
            return redirect()->to($intended);
        }

        return redirect()->to($dashboard);
    }

    /**
     * Apakah "url.intended" layak dipakai sebagai tujuan setelah login.
     *
     * Sesi "url.intended" diisi middleware auth setiap kali tamu membuka
     * halaman ber-`auth` lalu dibalikkan ke /login.Tanpa pemeriksaan,
     * URL itu selalu menang atas dashboard sesuai peran: admin yang
     * kebetulan membuka /user/dashboard sebelum login akan tetap
     * mendarat di sana, bukan di /admin/dashboard.
     *
     * URL hanya dipakai kalau:
     *   1. Host-nya sama dengan host request ini, supaya tidak pernah
     *      menjadi open redirect ke domain lain.
     *   2. Segment pertamanya cocok dengan area peran yang sedang login:
     *      "admin" hanya boleh kembali ke /admin/..., "user" hanya ke
     *      /user/....
     */
    private function intendedBolehDipakai(string $intended, User $user, Request $request): bool
    {
        $host = parse_url($intended, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return false;
        }

        $prefix = $user->isAdmin() ? 'admin' : 'user';
        $path = '/'.ltrim((string) parse_url($intended, PHP_URL_PATH), '/');

        return $path === '/'.$prefix || Str::startsWith($path, '/'.$prefix.'/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
