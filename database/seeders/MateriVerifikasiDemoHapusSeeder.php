<?php

namespace Database\Seeders;

/**
 * Kebalikan dari MateriVerifikasiDemoSeeder: menghapus materi contoh yang
 * menunggu verifikasi.
 *
 *     php artisan db:seed --class=MateriVerifikasiDemoHapusSeeder
 *
 * Kelas ini ada terpisah, bukan sebagai opsi pada seeder pembuatan, karena
 * Artisan membuat seeder lewat container tanpa mengirim argumen baris
 * perintahnya. Yang dipisah cuma satu flag, jadi daftar materi dan akun
 * pembuatnya tetap terpusat di satu tempat.
 */
class MateriVerifikasiDemoHapusSeeder extends MateriVerifikasiDemoSeeder
{
    protected bool $hapus = true;
}
