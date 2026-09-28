<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Satu baris = satu jam pelajaran milik satu pengguna.
     *
     * Jadwal disimpan per pengguna, bukan global: setiap orang bisa menyusun
     * jadwalnya sendiri lewat tombol "Tambah Jadwal", dan halamannya hanya
     * menampilkan jadwal miliknya sendiri. Baris yang sudah dihapus ikut
     * terhapus bersama akunnya, karena tidak ada gunanya menyimpan jadwal
     * pengguna yang sudah tidak ada.
     *
     * "hari" memakai angka 0-6 dengan urutan yang sama seperti Carbon::dayOfWeek
     * (0 = Minggu), supaya tidak perlu konversi bolak-balik antara database
     * dan kodingan.
     *
     * "pelajaran" menyimpan slug dari App\Models\Pelajaran::KATALOG, bukan id:
     * warna dan ikon jadwal diambil dari katalog itu, dan katalog bisa berisi
     * pelajaran yang belum pernah masuk ke tb_pelajaran.
     *
     * Jam tidak boleh menumpuk, tapi itu divalidasi di form, bukan dengan
     * unique index:unique index hanya bisa menolak jam mulai yang sama persis,
     * sedangkan yang perlu ditolak adalah jam yang saling tumpang tindih.
     */
    public function up(): void
    {
        Schema::create('tb_jadwal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dibuat_oleh')->constrained('tb_pengguna')->cascadeOnDelete();

            // 0 = Minggu ... 6 = Sabtu, sama seperti Carbon::dayOfWeek.
            $table->unsignedTinyInteger('hari')->index();

            $table->time('mulai');
            $table->time('selesai');

            // Slug pelajaran, lihat App\Models\Pelajaran::KATALOG.
            $table->string('pelajaran', 40);

            $table->string('judul', 100);
            $table->string('kelas', 60)->default('Kelas 11 RPL 2');
            $table->string('ruang', 60)->default('Ruang Kelas 3B');
            $table->string('guru', 60)->default('Guru KelasKita');

            $table->timestamps();

            // Halaman jadwal selalu menanyakan satu hari milik satu pengguna,
            // jadi index gabungan ini yang dipakai query-nya.
            $table->index(['dibuat_oleh', 'hari', 'mulai'], 'tb_jadwal_milik_hari_mulai');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_jadwal');
    }
};
