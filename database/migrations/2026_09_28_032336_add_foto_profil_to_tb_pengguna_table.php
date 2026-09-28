<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Foto profil disimpan sebagai path relatif di disk publik
     * (storage/app/public/foto-profil). Nilai kosong berarti pengguna belum
     * memasang foto, dan di situ avatar memakai inisial nama depan, jadi
     * kolomnya boleh kosong dan tidak perlu nilai bawaan.
     */
    public function up(): void
    {
        Schema::table('tb_pengguna', function (Blueprint $table) {
            $table->string('foto_profil')->nullable();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_pengguna', function (Blueprint $table) {
            $table->dropColumn('foto_profil');
        });
    }
};
