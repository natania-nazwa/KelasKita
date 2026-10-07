<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status aktif pengguna berubah dari kolom yang diubah manual admin
 * ("aktif") menjadi turunan dari kapan terakhir kali pengguna membuka
 * aplikasi ("terakhir_aktivitas").
 *
 * Kolom lama tetap ditambahkan dulu dan diisi dari updated_at supaya akun
 * yang sudah ada tidak mendadak dianggap nonaktif, baru kolom "aktif"
 * dilepas karena sudah tidak ada yang membacanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_pengguna', function (Blueprint $table) {
            $table->timestamp('terakhir_aktivitas')->nullable();
        });

        // Akun lama belum punya jejak kunjungan, jadi waktu terakhir
        // barisnya disentuh dipakai sebagai titik awal.
        DB::table('tb_pengguna')->update([
            'terakhir_aktivitas' => DB::raw('updated_at'),
        ]);

        Schema::table('tb_pengguna', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('tb_pengguna', function (Blueprint $table) {
            $table->boolean('aktif')->default(true);
        });

        Schema::table('tb_pengguna', function (Blueprint $table) {
            $table->dropColumn('terakhir_aktivitas');
        });
    }
};
