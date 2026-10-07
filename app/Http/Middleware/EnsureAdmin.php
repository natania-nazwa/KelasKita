<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang untuk seluruh route admin.
 *
 * Menyembunyikan menu di Blade (if/else) TIDAK cukup, karena siapa pun
 * bisa mengetik URL admin secara manual. Proteksi yang benar ada di sini.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        /*
         * Status aktif tidak lagi menjadi gerbang: akun yang sudah lama tidak
         * membuka aplikasi tetap boleh masuk area admin begitu ia kembali.
         * Yang dijaga di sini hanya perannya.
         */
        if (! $user->isAdmin()) {
            abort(403, 'Halaman ini hanya untuk admin.');
        }

        return $next($request);
    }
}
