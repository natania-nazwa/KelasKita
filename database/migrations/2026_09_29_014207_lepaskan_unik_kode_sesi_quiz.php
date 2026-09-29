<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Kode yang diketik peserta berpindah dari SESI ke QUIZ.
 *
 * Sebelumnya tiap sesi punya kode sendiri yang dicari peserta lewat
 * SesiQuiz::scopeKode(), jadi kodenya harus unik. Sekarang kode yang dicari
 * peserta adalah kode akses quiz (App\Models\Quiz::scopeKodeAkses): satu quiz
 * mode kode punya satu kode, dan semua yang mengetiknya masuk ke sesi yang
 * sama.
 *
 * Kode sesi tidak lagi jadi kunci pencarian, dan satu kode yang sama boleh
 * dipakai lagi untuk ronde berikutnya, jadi unique index ini tidak dibutuhkan
 * lagi. Index biasa dipertahankan supaya pencarian berdasarkan kode tetap
 * cepat, dan App\Support\KodeSesi tetap menghindari tabrakan antar sesi solo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_sesi_quiz', function (Blueprint $table) {
            $table->dropUnique('tb_sesi_quiz_kode_unique');
            $table->index('kode', 'tb_sesi_quiz_kode_index');
        });
    }

    public function down(): void
    {
        Schema::table('tb_sesi_quiz', function (Blueprint $table) {
            $table->dropIndex('tb_sesi_quiz_kode_index');
            $table->unique('kode', 'tb_sesi_quiz_kode_unique');
        });
    }
};
