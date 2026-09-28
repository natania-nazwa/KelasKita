<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Satu pengerjaan = satu orang yang sedang mengerjakan quiz. Kolom ini
     * ditambahkan supaya pengerjaan bisa dikaitkan ke sesi yang sedang
     * dibuka, tanpa membuat tabel baru: jawaban dan nilainya tetap
     * disimpan di tb_jawaban_quiz dan kolom yang sudah ada di sini.
     *
     * Nullable karena pengerjaan manual (dari halaman detail quiz) tidak
     * selalu lewat sesi.
     */
    public function up(): void
    {
        Schema::table('tb_pengerjaan_quiz', function (Blueprint $table) {
            $table->foreignId('sesi_id')
                ->nullable()
                ->after('quiz_id')
                ->constrained('tb_sesi_quiz')
                ->nullOnDelete();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_pengerjaan_quiz', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sesi_id');
        });
    }
};
