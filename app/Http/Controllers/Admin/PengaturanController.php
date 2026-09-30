<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Pengaturan" area admin.
 *
 * Halaman ini menampilkan akun admin yang sedang login, dan tidak
 * menyimpan apa pun.
 *
 * Alasan form ubah nama, email, dan password tidak diduplikasi di sini:
 * kemampuan itu sudah ada dan sudah lengkap validasinya di halaman
 * Profil (/user/profil) milik pengguna yang sama, dengan aturan
 * password, verifikasi email, dan penanganan kata sandi lama. Menulis
 * ulang form yang sama di sini berarti menyalin aturan itu ke tempat
 * kedua, dan dua tempat itu pasti akan berbeda satu saat.
 *
 * Jadi kartu di bawah hanya memberi tahu di mana pengaturannya
 * dilakukan, lalu mengarahkan ke halaman yang sudah itu.
 */
class PengaturanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $admin = $request->user();

        return view('admin.pengaturan', [
            'admin' => $admin,
            'ringkasan' => [
                'materi' => (int) Materi::query()->count(),
                'quiz' => (int) Quiz::query()->count(),
                'pengguna' => (int) User::query()->count(),
            ],
            'bergabung' => $admin?->created_at,
            'terakhirMasuk' => $admin?->updated_at,
        ]);
    }
}
