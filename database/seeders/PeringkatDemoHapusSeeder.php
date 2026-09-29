<?php

namespace Database\Seeders;

/**
 * Kebalikan dari PeringkatDemoSeeder: menghapus data contoh peringkat sesi.
 *
 *     php artisan db:seed --class=PeringkatDemoHapusSeeder
 *
 * Kelas ini ada terpisah, bukan sebagai opsi "--purge" pada seeder pembuatan,
 * karena Artisan membuat seeder lewat container tanpa mengirim argumen baris
 * perintahnya — sehingga tidak ada cara yang bersih membaca opsi tambahan dari
 * dalam seeder. Yang dipisah cuma satu flag, jadi seluruh daftar file, kode
 * quiz, dan akun demo tetap terpusat di satu tempat.
 */
class PeringkatDemoHapusSeeder extends PeringkatDemoSeeder
{
    protected bool $hapus = true;
}
