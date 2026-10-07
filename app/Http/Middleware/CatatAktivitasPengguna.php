<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat kapan terakhir seorang pengguna membuka aplikasi.
 *
 * Status aktif pengguna diturunkan dari kolom "terakhir_aktivitas", bukan
 * lagi diubah manual. Middleware ini yang mengisi kolom itu: setiap request
 * yang sudah membawa pengguna login menyentuh waktunya.
 *
 * Penulisan dibatasi satu menit sekali. Tanpa itu, satu halaman yang memuat
 * banyak aset atau panggilan tidak akan menulis berkali-kali hanya untuk
 * memindahkan satu detik, sedangkan ketelitian satu menit sudah cukup untuk
 * pertanyaan "berapa lama sejak terakhir membuka".
 */
class CatatAktivitasPengguna
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna !== null) {
            $terakhir = $pengguna->terakhir_aktivitas;

            if ($terakhir === null || $terakhir->lessThan(now()->subMinute())) {
                $pengguna->forceFill(['terakhir_aktivitas' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
