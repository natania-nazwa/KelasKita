<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Quiz dulu selalu punya tepat empat pilihan (A sampai D). Form "Buat
     * Soal" sekarang bisa menambah pilihan sendiri, jadi tb_soal diberi
     * dua kolom tambahan: pilihan_e dan pilihan_f.
     *
     * Kolomnya sengaja dibuat nullable, bukan NOT NULL seperti pilihan_a
     * sampai pilihan_d. Sebagian besar soal tetap memakai empat pilihan,
     * dan kolom kosong lebih jujur disimpan sebagai NULL daripada string
     * kosong.
     *
     * jawaban_benar tidak ikut diubah: huruf jawaban selalu satu karakter,
     * jadi kolom varchar(1) di tb_soal maupun tb_jawaban_quiz masih cukup
     * sampai huruf F.
     */
    public function up(): void
    {
        Schema::table('tb_soal', function (Blueprint $table) {
            $table->text('pilihan_e')->nullable()->after('pilihan_d');
            $table->text('pilihan_f')->nullable()->after('pilihan_e');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_soal', function (Blueprint $table) {
            $table->dropColumn(['pilihan_e', 'pilihan_f']);
        });
    }
};
