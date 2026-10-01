<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom "kelas" untuk materi dan quiz.
 *
 * Satu kolom, dua tabel, dan tidak ada tabel baru.
 *
 * Kenapa perlu: menu "Konten Pembelajaran" di area admin menyaring dan
 * menandai konten per kelas (dropdown "Semua Kelas" dan lencana kelas di
 * setiap baris), sementara sebelum ini tidak ada satu pun tempat yang
 * menyimpan kelas tujuan sebuah konten. Yang paling dekat — kolom "kelas"
 * di tb_jadwal — milik jadwal ingreskripsi, bukan isi pembelajaran, jadi
 * tidak bisa dipinjam: menjadikannya rujukan bersama akan membuat
 * "kelas" pada konten ikut berubah begitu seseorang menyunting jadwal.
 *
 * Karena itu kelas di sini disimpan bebas sebagai teks, bukan foreign key ke
 * tabel master: daftar kelas pada sekolah bisa bertambah kapan saja, dan
 * konten yang sudah terbit tidak boleh ikut bermasalah saat daftar itu
 * berubah. Dropdown di form dan filter di daftar sama-sama diambil dari
 * nilai yang benar-benar ada di database (lihat App\Support\KelasKonten),
 * jadi kelas baru bisa muncul begitu dipakai, tanpa tabel extra.
 *
 * Nullable dan tanpa default: konten lama tidak tiba-tiba dipaksa punya
 * kelas, dan konten yang belum ditentukkan kelasnya tetap bisa terbit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->string('kelas', 60)->nullable()->after('pelajaran_id')->index();
        });

        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->string('kelas', 60)->nullable()->after('pelajaran_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });

        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });
    }
};
