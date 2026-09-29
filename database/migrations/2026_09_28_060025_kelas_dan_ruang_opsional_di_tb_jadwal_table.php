<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Kolom "kelas" dan "ruang" sebelumnya tidak nullable dan punya nilai
     * default, jadi jadwal yang tidak mengisinya tetap tersimpan "Kelas 11 RPL
     * 2" dan "Ruang Kelas 3B". Akibatnya halaman selalu menampilkan kedua
     * field itu, walau penggunanya memang tidak pernah mengisinya.
     *
     * Sekarang keduanya boleh kosong, supaya halaman bisa membedakan "diisi"
     * dari "tidak diisi" dan hanya menampilkan yang benar-benar ada isinya.
     *
     * Baris yang sudah ada tidak diubah: nilainya tetap utuh, migration ini
     * cuma melonggarkan aturan kolomnya.
     */
    public function up(): void
    {
        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->string('kelas', 60)->nullable()->default(null)->change();
            $table->string('ruang', 60)->nullable()->default(null)->change();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        // Baris yang kelas atau ruangnya kosong harus diisi dulu, kalau tidak
        // pemulatan ini gagal karena kolomnya kembali tidak nullable.
        // Pakai query builder, bukan model: migration tidak boleh bergantung
        // pada kelas aplikasi yang bisa berubah sewaktu-waktu.
        DB::table('tb_jadwal')->whereNull('kelas')->update(['kelas' => 'Kelas 11 RPL 2']);
        DB::table('tb_jadwal')->whereNull('ruang')->update(['ruang' => 'Ruang Kelas 3B']);

        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->string('kelas', 60)->default('Kelas 11 RPL 2')->change();
            $table->string('ruang', 60)->default('Ruang Kelas 3B')->change();
        });
    }
};
