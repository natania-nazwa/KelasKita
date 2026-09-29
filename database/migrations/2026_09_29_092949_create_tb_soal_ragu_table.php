<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Tabel penanda "ragu": soal mana yang ingin peserta tinjau lagi
     * setelah menjawab (tb_soal_ragu).
     *
     * Kenapa tabel sendiri, bukan kolom boolean di tb_jawaban_quiz:
     * tanda ini boleh ada untuk soal yang belum dijawab sama sekali,
     * sedangkan baris tb_jawaban_quiz selalu berarti "sudah dijawab".
     * Menitipkan tanda ragu di sana akan membuat hitungan jumlah_dijawab
     * ikut menghitung soal yang isinya masih kosong.
     *
     * Yang diikat ke pengerjaan_quiz, bukan ke pengguna dan quiz
     * terpisah, karena satu orang boleh mengerjakan quiz yang sama beberapa
     * kali: menandai ragu pada percobaan pertama tidak boleh muncul lagi
     * pada percobaan kedua.
     *
     * Unique constraint pada pasangan pengerjaan + soal adalah penegak
     * utama: satu soal tidak pernah bisa ditandai ragu dua kali walau
     * tombolnya diklik dua kali berbarengan.
     */
    public function up(): void
    {
        Schema::create('tb_soal_ragu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengerjaan_quiz_id')
                ->constrained('tb_pengerjaan_quiz')
                ->cascadeOnDelete();

            $table->foreignId('soal_id')
                ->constrained('tb_soal')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'pengerjaan_quiz_id',
                'soal_id',
            ]);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_soal_ragu');
    }
};
