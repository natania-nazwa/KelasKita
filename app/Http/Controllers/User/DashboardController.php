<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('user.dashboard', [
            'pengguna' => $request->user(),

            /*
             * Data di bawah masih dummy dan sengaja dibuat sebagai array
             * di view. Nanti tinggal ganti isi arraynya dengan hasil query
             * (Model / API) tanpa mengubah struktur di dashboard.blade.php.
             */
            'ringkasan' => [
                [
                    'label' => 'Total Materi',
                    'nilai' => '8',
                    'perubahan' => '+2% dari bulan lalu',
                    'ikon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                    'warna' => 'hijau',
                ],
                [
                    'label' => 'Total Quiz',
                    'nilai' => '5',
                    'perubahan' => '+1% dari bulan lalu',
                    'ikon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'warna' => 'kuning',
                ],
                [
                    'label' => 'Rata-rata Nilai',
                    'nilai' => '85%',
                    'perubahan' => '+5% dari bulan lalu',
                    'ikon' => 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 10.5c.372 0 .741.101 1.06.286m6.345-.286a7.454 7.454 0 0 0-1.06-.286M6.75 18a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z',
                    'warna' => 'oranye',
                ],
                [
                    'label' => 'Progress Belajar',
                    'nilai' => '60%',
                    'perubahan' => '+10% dari bulan lalu',
                    'ikon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                    'warna' => 'pink',
                ],
            ],

            'lanjutkan' => [
                'judul' => 'JavaScript Dasar',
                'deskripsi' => 'Pahami konsep variabel, function, array, dan DOM.',
                'progress' => 70,
                'ikon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
            ],

            'materiTerbaru' => [
                [
                    'kode' => '<>',
                    'judul' => 'HTML Dasar',
                    'kategori' => 'Kategori: HTML & CSS',
                    'waktu' => '2 hari yang lalu',
                    'warna' => 'pink',
                ],
                [
                    'kode' => '<?',
                    'judul' => 'Mengenal PHP',
                    'kategori' => 'Kategori: PHP',
                    'waktu' => '3 hari yang lalu',
                    'warna' => 'ungu',
                ],
                [
                    'kode' => 'lr',
                    'judul' => 'Routing Laravel',
                    'kategori' => 'Kategori: Laravel',
                    'waktu' => '5 hari yang lalu',
                    'warna' => 'merah',
                ],
            ],

            /*
             * Streak belajar harian.
             * "aktif" = true saat pengguna membaca materi atau mengerjakan
             * soal pada hari ini. Kalau sehari penuh tidak ada aktivitas
             * (misal 1 hari beruntun kosong) streak-nya padam dan tampil abu.
             */
            'streak' => [
                'jumlah' => 1,
                'aktif' => true,
            ],
        ]);
    }
}