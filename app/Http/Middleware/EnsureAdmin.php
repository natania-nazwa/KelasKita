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
         * "aktif" dimuat dari database, bukan dari input form.
         * Nilai NULL dianggap TIDAK aktif (fail-closed) supaya akun
         * yang bermasalah tidak pernah bisa masuk area admin.
         */
        if (! $user->isAktif() || ! $user->isAdmin()) {
            abort(403, 'Halaman ini hanya untuk admin.');
        }

        return $next($request);
    }
}
