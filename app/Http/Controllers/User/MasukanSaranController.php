<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasukanSaranRequest;
use App\Models\MasukanSaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Halaman "Masukan & Saran": satu form untuk mengirim masukan atau saran
 * kepada pengelola aplikasi.
 *
 * Halaman ini sengaja tidak punya daftar, riwayat, atau status — pengguna
 * hanya perlu mengirim, bukan membaca kembali kirimannya. Jadi tidak ada
 * route show/edit/hapus dan tidak ada request untuk membacanya.
 *
 * Seluruh halaman dijaga middleware "auth" bersama halaman user lain, dan
 * kepemilikan baris ditentukan dari sesi login, bukan dari field form.
 */
class MasukanSaranController extends Controller
{
    public function __invoke(): View
    {
        return view('user.masukan-saran');
    }

    public function store(MasukanSaranRequest $request): RedirectResponse
    {
        /*
         * Baris langsung dimiliki pengguna yang sedang login, bukan dari
         * field request, supaya isian "pengguna_id" palsu tidak bisa dipakai
         * untuk mengirim masukan atas nama orang lain.
         */
        MasukanSaran::create([
            ...$request->isian(),
            'pengguna_id' => $request->user()->getKey(),
        ]);

        return redirect()
            ->route('user.masukan-saran')
            ->with('sukses', 'Terima kasih! Masukan dan saranmu sudah kami terima.');
    }
}
