<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SesiAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Sesi Login" dan "Logout dari Semua Perangkat" di area Pengaturan admin.
 *
 * Data perangkatnya dibaca dari tabel sessions, bukan dari data tiruan: tabel
 * itu memang dipakai driver sesi aktif aplikasi ini, dan tiap permintaan yang
 * masuk memperbarui barisnya. Browser dan sistem operasi dibaca dari
 * user_agent yang tersimpan; kalau tidak dikenali, halaman mengatakannya tidak
 * diketahui, bukan menebak.
 *
 * Aksi logout dari semua perangkat menghapus baris sesi milik admin itu di
 * server, bukan sekadar membersihkan penyimpanan lokal peramban sekarang.
 * Cookie di peramban lain tetap ada, tapi tidak lagi cocok dengan baris mana
 * pun sehingga ditolak pada permintaan berikutnya. Sesi yang sedang dipakai
 * halaman ini dikecualikan supaya admin tidak ikut terlempar keluar.
 */
class SesiController extends Controller
{
    /**
     * Daftar perangkat yang sedang login.
     *
     * Kalau driver sesi bukan database, halamannya tetap dirender dengan
     * catatan bahwa daftar perangkat tidak tersedia pada driver ini. Halaman
     * tidak pernah menampilkan baris perangkat contoh.
     */
    public function index(Request $request): View
    {
        $admin = $request->user();

        return view('admin.pengaturan.sesi', [
            'admin' => $admin,
            'tersedia' => SesiAdmin::tersimpanDiDatabase(),
            'perangkat' => SesiAdmin::daftar($admin, $request->session()->getId()),
            'perangkatLain' => SesiAdmin::hitungLain($admin, $request->session()->getId()),
        ]);
    }

    /**
     * Akhiri semua sesi milik admin ini kecuali yang sedang dipakai.
     */
    public function hapusSemua(Request $request): RedirectResponse
    {
        $jumlah = SesiAdmin::akhiri($request->user(), $request->session()->getId());

        return redirect()
            ->route('admin.pengaturan.sesi')
            ->with('sukses', $jumlah > 0
                ? $jumlah.' perangkat lain sudah dikeluarkan.'
                : 'Tidak ada perangkat lain yang sedang login.')
            ->with('suksesDetail', 'Sesi di perangkat ini tetap aktif. Perubahan kata sandi juga akan mengeluarkan perangkat lain.');
    }
}
