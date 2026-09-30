<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\StatistikAdmin;
use Illuminate\View\View;

/**
 * Halaman "Hasil & Statistik": rekap pembelajaran di seluruh platform.
 *
 * Semuanya read-only dan semuanya dihitung di StatistikAdmin, kelas
 * yang sama dengan yang dipakai dashboard. Jadi angka total quiz yang
 * dikerjakan di halaman ini selalu sama dengan angka di kartu statistik
 * dashboard, bukan dua hasil hitung yang bisa berbeda.
 *
 * Halaman ini sengaja tidak menyediakan filter per pengguna atau per
 * rentang tanggal yang panjang. Ringkasan platform untuk admin adalah
 * untuk melihat arah, bukan untuk mengaudit; kalau butuh audit,
 * datanya sudah ada di daftar hasil milik masing-masing pengguna.
 */
class StatistikController extends Controller
{
    public function __invoke(): View
    {
        $ringkasan = StatistikAdmin::ringkasan();
        $tren = StatistikAdmin::trenPengguna();
        $aktivitas = StatistikAdmin::aktivitasBelajar();

        return view('admin.statistik', [
            'ringkasan' => $ringkasan,
            'nilai' => StatistikAdmin::nilaiPengerjaan(),

            /*
             * Tiga deret untuk tiga kartu grafik. Semuanya memakai
             * window 7 hari yang sama, jadi garisnya bisa dibandingkan
             * satu sama lain tanpa jedanya tidak sejajar.
             */
            'trenLogin' => array_map(
                fn (array $hari): array => [
                    'label' => $hari['label'],
                    'nilai' => $hari['pengguna_login'],
                ],
                $tren
            ),
            'trenPenggunaBaru' => array_map(
                fn (array $hari): array => [
                    'label' => $hari['label'],
                    'nilai' => $hari['pengguna_baru'],
                ],
                $tren
            ),
            'trenQuiz' => array_map(
                fn (array $hari): array => [
                    'label' => $hari['label'],
                    'nilai' => $hari['quiz_dikerjakan'],
                ],
                $tren
            ),

            'aktivitasBelajar' => $aktivitas,
            'pelajaranTerpopuler' => StatistikAdmin::pelajaranTerpopuler(),
            'isiPelajaran' => StatistikAdmin::isiPerPelajaran(),
        ]);
    }
}
