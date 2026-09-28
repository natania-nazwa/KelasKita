<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Satu baris = satu orang yang sudah mengetik kode sesi dan masuk ke
     * lobby. Yang disimpan adalah pengguna_id, bukan nama, supaya daftar di
     * lobby selalu mengikuti data pengguna terbaru.
     *
     * Pasangan (sesi_id, pengguna_id) unik: satu orang tidak bisa masuk dua
     * kali ke sesi yang sama.
     */
    public function up(): void
    {
        Schema::create('tb_peserta_quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_id')->constrained('tb_sesi_quiz')->cascadeOnDelete();
            $table->foreignId('pengguna_id')->constrained('tb_pengguna')->cascadeOnDelete();
            $table->string('status', 20)->default('lobby');
            $table->timestamp('bergabung_pada')->nullable();
            $table->timestamps();

            $table->unique(['sesi_id', 'pengguna_id']);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_peserta_quiz');
    }
};
