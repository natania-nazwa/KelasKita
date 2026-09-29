<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Dua kolom ini dipakai form "Buat Quiz":
     *
     *   - tingkat_kesulitan   Difficulty pilihan pengguna saat menyusun
     *                          quiz. Berbeda dari tb_soal.tingkat_kesulitan
     *                          yang per soal, kolom ini untuk quiznya
     *                          seluruhnya, jadi tidak akan pernah kosong:
     *                          default "Mudah".
     *   - tunjukkan_jawaban    Saklar "Tampilkan Jawaban Setelah Selesai".
     *                          Default aktif (true) supaya quiz lama dan
     *                          quiz tanpa saklar tetap menampilkan kunci
     *                          seperti sebelumnya.
     */
    public function up(): void
    {
        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->string('tingkat_kesulitan')->default('Mudah')->after('deskripsi');
            $table->boolean('tampilkan_jawaban')->default(true)->after('kode_akses');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->dropColumn(['tingkat_kesulitan', 'tampilkan_jawaban']);
        });
    }
};
