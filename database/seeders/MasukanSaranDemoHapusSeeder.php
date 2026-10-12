<?php

namespace Database\Seeders;

/**
 * Kebalikan dari MasukanSaranDemoSeeder: menghapus data contoh masukan & saran.
 *
 *     php artisan db:seed --class=MasukanSaranDemoHapusSeeder
 *
 * Kelas ini ada terpisah, bukan sebagai opsi pada seeder pembuatan, karena
 * Artisan membuat seeder lewat container tanpa mengirim argumen baris
 * perintahnya. Yang dipisah cuma satu flag, jadi daftar akun dan kiriman
 * demo tetap terpusat di satu tempat.
 */
class MasukanSaranDemoHapusSeeder extends MasukanSaranDemoSeeder
{
    protected bool $hapus = true;
}
