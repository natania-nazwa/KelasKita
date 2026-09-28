<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Thumbnail dan audio disimpan sebagai path relatif di disk publik
     * (storage/app/public), jadi cukup berupa string yang boleh kosong.
     */
    public function up(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->string('thumbnail')->nullable();

            $table->string('audio')->nullable();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn(['thumbnail', 'audio']);
        });
    }
};
