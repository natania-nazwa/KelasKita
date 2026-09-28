<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Satu baris = satu kali runny quiz. Satu quiz boleh punya banyak sesi,
     * misalnya sesi untuk minggu lalu dan sesi untuk minggu ini, jadi kode
     * yang diketik peserta mencari SESI yang sedang dibuka, bukan quiz-nya.
     *
     * Kolom yang tersimpan:
     *   quiz_id = quiz yang dipakai sesi ini
     *   host_id = pengguna yang membuka sesi dan boleh memulai atau mengakhiri
     *   kode    = kode join yang diketik peserta
     *   status  = waiting | started | finished
     */
    public function up(): void
    {
        Schema::create('tb_sesi_quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('tb_quiz')->cascadeOnDelete();
            $table->foreignId('host_id')->constrained('tb_pengguna')->cascadeOnDelete();
            $table->string('kode', 10)->unique();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('dimulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();

            // Kode sudah unik, tapi sesi yang sedang dibuka juga sering
            // difilter berdasarkan status, jadi status ikut diindeks.
            $table->index('status');
            $table->index(['quiz_id', 'status']);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_sesi_quiz');
    }
};
