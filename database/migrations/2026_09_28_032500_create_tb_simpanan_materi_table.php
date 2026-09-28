<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Daftar materi yang disimpan pengguna ("Simpan" / bookmark).
     *
     * Unique constraint pada pasangan pengguna + materi adalah penegak
     * utama: satu pengguna tidak pernah bisa menyimpan materi yang sama
     * dua kali, walau tombolnya diklik dua kali berbarengan.
     */
    public function up(): void
    {
        Schema::create('tb_simpanan_materi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            $table->foreignId('materi_id')
                ->constrained('tb_materi')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'pengguna_id',
                'materi_id',
            ]);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_simpanan_materi');
    }
};
