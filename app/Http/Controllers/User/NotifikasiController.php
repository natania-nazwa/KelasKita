<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Support\NotifikasiKonten;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi milik pengguna yang sedang login.
 *
 * Satu-satunya isinya: menandai notifikasi sudah dibaca. Daftar notifikasi
 * sendiri dirender di lonceng topbar (components/app/topbar), jadi tidak ada
 * halaman notifikasi terpisah yang harusBolak-balik.
 *
 * Yang dijaga di sini hanya satu: notifikasi yang ditandai hanya boleh milik
 * pengguna yang sedang login. Tanpa itu, mengetik angka id orang lain di URL
 * akan membuat notifikasi orang itu terbaca dan titik merah di loncengnya ikut
 * hilang.
 */
class NotifikasiController extends Controller
{
    /**
     * Tandai satu notifikasi sudah dibaca.
     *
     * Dipakai sebagai fetch dari lonceng notifikasi: begitu notifikasi diklik,
     * titik merahnya langsung hilang walaupun halamannya belum selesai dimuat.
     * Tanpa JavaScript, tautan notifikasi tetap membuka halaman kontennya
     * seperti biasa dan hanya tandanya yang belum hilang.
     */
    public function baca(Request $request, Notifikasi $notifikasi): JsonResponse|RedirectResponse
    {
        abort_unless(
            (int) $notifikasi->pengguna_id === (int) $request->user()->getKey(),
            403
        );

        $notifikasi->tandaiDibaca();

        if ($request->expectsJson()) {
            return response()->json([
                'terbaca' => true,
                'sisa' => NotifikasiKonten::belumDibaca($request->user()),
            ]);
        }

        return back();
    }

    /**
     * Tandai semua notifikasi milik pengguna yang sedang login sudah dibaca.
     *
     * Dipanggil begitu panel lonceng dibuka, bukan saat satu baris diklik:
     * pembaca yang membuka lonceng memang sedang melihat daftarnya, jadi
     * membiarkan titiknya menyala hanya karena satu baris belum diklik akan
     * membuatnya selalu terasa belum dibaca.
     *
     * Setelah ini titik lonceng hilang sampai notifikasi baru benar-benar
     * datang, karena itulah satu-satunya kejadian yang menambahnya lagi.
     */
    public function bacaSemua(Request $request): JsonResponse|RedirectResponse
    {
        Notifikasi::tandaiSemuaDibaca((int) $request->user()->getKey());

        if ($request->expectsJson()) {
            return response()->json([
                'terbaca' => true,
                'sisa' => NotifikasiKonten::belumDibaca($request->user()),
            ]);
        }

        return back();
    }
}
