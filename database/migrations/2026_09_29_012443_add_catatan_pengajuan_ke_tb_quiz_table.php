<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Melengkapi alur persetujuan quiz supaya sama persis dengan materi.
 *
 * tb_quiz sudah punya kolom status, catatan_admin, dan dipublish_pada, tapi
 * belum punya dua kolom yang dipakai Materi untuk keputusan "tolak lalu
 * mengajukan ulang":
 *
 *   catatan_pengajuan  penjelasan pemilik kenapa perbaikannya kini layak
 *                      tayang. Wajib diisi saat quiz yang ditolak mengajukan
 *                      ulang, jadi admin punya alasan menilai.
 *   jumlah_ditolak     berapa kali quiz ini ditolak, dipakai untuk membatasi
 *                      jumlah pengajuan ulang (lihat Quiz::BATAS_PENGAJUAN_ULANG).
 *
 * Hanya menambah kolom, jadi baris quiz yang sudah ada tidak perlu dipindahkan:
 * yang belum pernah ditolak otomatis punya jumlah_ditolak = 0 dan catatan
 * kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_quiz', function (Blueprint $table) {
            // Penjelasan pemilik saat mengajukan ulang quiz yang ditolak.
            $table->text('catatan_pengajuan')
                ->nullable()
                ->after('catatan_admin');

            $table->unsignedTinyInteger('jumlah_ditolak')
                ->default(0)
                ->after('catatan_pengajuan');
        });
    }

    public function down(): void
    {
        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->dropColumn(['catatan_pengajuan', 'jumlah_ditolak']);
        });
    }
};
