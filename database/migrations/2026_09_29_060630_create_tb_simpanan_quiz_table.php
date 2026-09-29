<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Daftar quiz yang disimpan pengguna ("Simpan" / bookmark), kembaran
     * dari tb_simpanan_materi supaya keduanya punya perilaku yang sama.
     *
     * Unique constraint pada pasangan pengguna + quiz adalah penegak
     * utama: satu pengguna tidak pernah bisa menyimpan quiz yang sama
     * dua kali, walau tombolnya diklik dua kali berbarengan.
     */
    public function up(): void
    {
        Schema::create('tb_simpanan_quiz', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            $table->foreignId('quiz_id')
                ->constrained('tb_quiz')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'pengguna_id',
                'quiz_id',
            ]);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_simpanan_quiz');
    }
};
