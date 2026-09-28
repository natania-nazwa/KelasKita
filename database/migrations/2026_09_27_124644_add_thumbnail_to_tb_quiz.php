<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Thumbnail disimpan sebagai path relatif di disk publik
     * (storage/app/public), sama seperti thumbnail tb_materi, jadi cukup
     * berupa string yang boleh kosong. Quiz tanpa thumbnail tetap tampil
     * memakai gradasi warna kategori (lihat .kartu-quiz__gambar).
     */
    public function up(): void
    {
        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->string('thumbnail')->nullable();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_quiz', function (Blueprint $table) {
            $table->dropColumn('thumbnail');
        });
    }
};
