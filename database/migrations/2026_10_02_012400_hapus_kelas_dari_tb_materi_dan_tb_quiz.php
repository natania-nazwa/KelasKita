<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus kolom "kelas" dari tb_materi dan tb_quiz.
 *
 * Kebalikannya dari 2026_10_02_010000_tambah_kelas_ke_tb_materi_dan_tb_quiz.
 * Konsep "kelas tujuan" dihapus dari seluruh aplikasi: kartu "Kelas Tujuan"
 * di form Materi dan form Quiz, filter "Semua Kelas" di daftar Konten
 * Pembelajaran, dan lencana kelas di setiap baris. Setelah semua tempat yang
 * bisa mengisi dan membacanya hilang, kolomnya tidak lagi punya pemakai dan
 * menyimpannya hanya bikin orang mengira masih ada filtro berdasarkan kelas.
 *
 * down() sengaja mengembalikan kolomnya apa adanya (string 60, nullable,
 * indexed) supaya migrasi ini benar-benar membatalkan yang ditambahkan
 * migrasi sebelumnya, bukan membuat schema yang tidak pernah ada. Nilai yang
 * sudah hilang tidak dipulihkan — dan tidak ada cara memulihkannya dari sini.
 *
 * Kolom "kelas" di tb_jadwal tidak disentuh: itu milik jadwal ingreskripsi,
 * bukan isi pembelajaran, dan tidak pernah terkait.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Index-nya harus dilepas lebih dulu. Kolom yang masih berindex tidak
         * bisa di-drop di SQLite (errornya "error in index ... after drop
         * column"), jadi melompatinya akan menggagalkan seluruh migrasi.
         * Di Postgres langkah ini tidak wajib, tapi tidak ruinsya juga —
         * dropping index yang memang ikut hilang bersama kolomnya memang
         * hal yang benar.
         */
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropIndex(['kelas']);
        });

        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });

        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->dropIndex(['kelas']);
        });

        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });
    }

    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->string('kelas', 60)->nullable()->after('pelajaran_id')->index();
        });

        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->string('kelas', 60)->nullable()->after('pelajaran_id')->index();
        });
    }
};
