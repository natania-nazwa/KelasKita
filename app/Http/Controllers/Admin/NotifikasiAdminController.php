<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Support\NotifikasiAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi milik admin yang sedang login.
 *
 * Satu-satunya isinya: menandai notifikasi sudah dibaca. Daftar notifikasi
 * sendiri dirender di lonceng topbar (components/admin/topbar-kanan), jadi
 * tidak ada halaman notifikasi terpisah yang harus bolak-balik.
 *
 * Yang dijaga di sini hanya satu: notifikasi yang ditandai hanya boleh milik
 * admin yang sedang login. Tanpa itu, mengetik angka id orang lain di URL akan
 * membuat notifikasi orang itu terbaca dan titik merah di loncengnya ikut
 * hilang. Tuannya tidak membuat celah baru, karena dua lonceng memakai satu
 * tabel yang sama dan notifikasi untuk pengguna punya barisnya sendiri.
 */
class NotifikasiAdminController extends Controller
{
    /**
     * Tandai satu notifikasi sudah dibaca.
     *
     * Dipakai sebagai fetch dari lonceng notifikasi: begitu notifikasi diklik,
     * titik merahnya langsung hilang walaupun halamannya belum selesai dimuat.
     * Tanpa JavaScript, tautan notifikasi tetap membuka halaman tujuannya
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
                'sisa' => NotifikasiAdmin::belumDibaca($request->user()),
            ]);
        }

        return back();
    }
}
