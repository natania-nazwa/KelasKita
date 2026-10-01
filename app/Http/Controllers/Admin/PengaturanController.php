<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelajaran;
use App\Models\Preferensi;
use App\Support\Aplikasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Pengaturan" area admin: pusat dari semua pengaturan admin.
 *
 * Halaman ini hanya membaca dan mengarahkan. Tidak ada satu pun form yang
 * disimpan di sini:
 *
 *   - Profil dan Keamanan punya halaman masing-masing, karena keduanya punya
 *     berkas (foto profil) atau aturan password yang perlu halaman sendiri.
 *   - Notifikasi, Publikasi, dan Tema punya satu dialog di halaman ini.
 *   - Kelola Mata Pelajaran dan Sesi Login punya halaman sendiri, karena
 *     keduanya punya daftar yang panjang.
 *   - Logout dari semua perangkat dan Keluar dari akun memakai dialog
 *     konfirmasi lalu mengirim form ke route yang sudah ada.
 *
 * Form ubah nama, email, dan kata sandi sengaja memakai ProfilIsianRequest dan
 * KataSandiRequest yang sama dengan halaman Profil milik pengguna, bukan
 * menyalin aturannya. Dua tempat yang masing-masing punya aturan password pasti
 * akan berbeda pada satu saat, dan yang berbeda itu biasanya yang lupa
 * diperbarui.
 */
class PengaturanController extends Controller
{
    public function __construct(private readonly Aplikasi $aplikasi) {}

    public function __invoke(Request $request): View
    {
        $admin = $request->user();

        /*
         * Baris preferensi dibuat sekarang kalau belum ada, supaya setiap
         * saklar di halaman ini punya nilai yang bisa ditampilkan. Halaman ini
         * memang butuh membacanya, jadi tidak ada biaya sia-sia untuk
         * pengguna yang baru sekali saja membuka Pengaturan.
         */
        $preferensi = Preferensi::ambil($admin);

        /*
         * Jumlah mata pelajaran dihitung di sini, bukan dari komponen, supaya
         * angka pada baris "Kelola Mata Pelajaran" dan angka pada halaman
         * kelola berasal dari satu query yang sama.
         *
         * Yang dihitung hanya yang aktif, karena angka itu menggambarkan berapa
         * pilihan yang benar-benar tersedia saat membuat konten.
         */
        return view('admin.pengaturan', [
            'admin' => $admin,
            'preferensi' => $preferensi,
            'jumlahPelajaran' => Pelajaran::query()->aktif()->count(),
            'jumlahPelajaranTotal' => Pelajaran::query()->count(),
            'versi' => $this->aplikasi->versi(),
        ]);
    }
}
