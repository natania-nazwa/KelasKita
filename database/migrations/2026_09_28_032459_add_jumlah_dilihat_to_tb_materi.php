<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Pengali tampilan tiap materi. Dinaikkan sekali tiap kali halaman
     * detail dibuka, jadi angka yang tampil di kepala materi selalu angka
     * asli dari database, bukan angka hiasan.
     */
    public function up(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_dilihat')
                ->default(0);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn('jumlah_dilihat');
        });
    }
};
