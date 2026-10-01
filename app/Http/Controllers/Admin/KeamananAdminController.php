<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KataSandiRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman dan aksi "Keamanan" di area Pengaturan admin.
 *
 * Aturan passwordnya bukan ditulis di sini: form request-nya KataSandiRequest
 * yang sama dengan halaman Profil milik pengguna, termasuk pemeriksaan password
 * lama lewat aturan "current_password" dan batas minimal 8 karakter. Satu
 * tempat untuk aturan ini, jadi halaman ini dan halaman Profil tidak mungkin
 * berbeda.
 *
 * Password lama wajib diperiksa karena kata sandi adalah satu-satunya bukti
 * bahwa orang yang sedang mengubahnya memang pemilik akun. Tanpa pemeriksaan
 * itu, siapa pun yang sempat membuka sesi admin di peramban lain bisa mengganti
 * kata sandi tanpa meninggalkan jejak.
 *
 * Isi password tidak pernah dikirim ke view, tidak pernah dibaca JavaScript,
 * dan tidak pernah disimpan plaintext: kolom kata_sandi di-cast "hashed".
 */
class KeamananAdminController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.pengaturan.keamanan', [
            'admin' => $request->user(),
        ]);
    }

    /**
     * Simpan kata sandi baru.
     *
     * Sesi di perangkat lain ikut diakhiri setelah kata sandi berubah
     * (logoutOtherDevices). Ini bukan tambahan yang tidak diminta: begitu kata
     * sandi diganti, sesi yang masih hidup di perangkat lain otomatis kehilangan
     * hak akses, dan itulah gunanya mengganti kata sandi. Sesi yang sedang dipakai
     * halaman ini tidak ikut terputus, jadi admin tidak terlempar keluar dari
     * halamannya sendiri.
     *
     * Urutannya penting: logoutOtherDevices() memeriksa password lama terhadap
     * hash yang tersimpan. Kalau barisnya lebih dulu diganti, pemeriksaannya akan
     * membandingkan password lama dengan hash yang sudah baru dan selalu gagal.
     *
     * Password lamanya sudah dicek oleh KataSandiRequest lewat aturan
     * "current_password" sebelum method ini dipanggil, jadi nilainya bukan lagi
     * rahasia yang belum diperiksa.
     */
    public function update(KataSandiRequest $request): RedirectResponse
    {
        Auth::logoutOtherDevices($request->string('kata_sandi_lama')->value());

        $request->user()->fill([
            'kata_sandi' => $request->string('kata_sandi_baru')->value(),
        ])->save();

        return redirect()
            ->route('admin.pengaturan')
            ->with('sukses', 'Password berhasil diperbarui.')
            ->with('suksesDetail', 'Sesi di perangkat lain sudah diakhiri. Gunakan kata sandi baru saat login berikutnya.');
    }
}
