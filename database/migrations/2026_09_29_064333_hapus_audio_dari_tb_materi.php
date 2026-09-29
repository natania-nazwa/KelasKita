<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Audio pembelajaran sudah dihapus dari aplikasi: field unggahannya di
     * form materi, tombol dengarkan di preview, dan pemutar di halaman
     * detail sudah tidak ada. Kolomnya ikut dibuang supaya tidak ada sisa
     * yang bisa terisi tanpa pernah dibaca.
     */
    public function up(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn('audio');
        });
    }

    /**
     * Balikkan migrasi.
     *
     * Berkas audio yang sudah terlanjur tersimpan tidak dipulihkan; yang
     * kembali hanya kolomnya, dengan nilai kosong untuk semua materi.
     */
    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->string('audio')->nullable();
        });
    }
};
