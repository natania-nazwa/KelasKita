<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom "estimasi_waktu" dan "tips" untuk materi.
 *
 * Kedua isian ini sudah lama ada di form (x-materi.informasi) dan ikut
 * terkirim tiap kali tombol simpan ditekan, tapi tidak pernah punya tempat
 * di database: tidak ada kolomnya, tidak ada aturan validasinya, dan tidak
 * ada controller yang membacanya. Akibatnya isian hilang begitu halaman
 * dimuat ulang — estimasi kembali ke "10 menit" dan tips kembali kosong —
 * sehingga yang diketik pemilik tidak pernah benar-benar tersimpan.
 *
 * Nullable dan tanpa default: materi lama tidak tiba-tiba dipaksa punya
 * estimasi, dan materi tanpa tips tetap bisa terbit. Panjang kolom mengikuti
 * batas yang sudah ditulis di form (maxlength 40 dan 500), supaya batas
 * klien dan batas server tidak lagi berbeda.
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->string('estimasi_waktu', 40)->nullable()->after('tingkat_kesulitan');
            $table->text('tips')->nullable()->after('estimasi_waktu');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn(['estimasi_waktu', 'tips']);
        });
    }
};
