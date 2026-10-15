<?php

namespace Database\Seeders;

/**
 * Kebalikan dari VerifikasiThumbnailDemoSeeder: menghapus satu materi contoh
 * yang menunggu verifikasi beserta berkas thumbnailnya.
 *
 *     php artisan db:seed --class=VerifikasiThumbnailDemoHapusSeeder
 *
 * Kelas ini ada terpisah, bukan sebagai opsi pada seeder pembuatan, karena
 * Artisan membuat seeder lewat container tanpa mengirim argumen baris
 * perintahnya. Yang dipisah cuma satu flag, jadi isi materi contoh dan tempat
 * berkas gambarnya tetap terpusat di satu tempat.
 */
class VerifikasiThumbnailDemoHapusSeeder extends VerifikasiThumbnailDemoSeeder
{
    protected bool $hapus = true;
}
