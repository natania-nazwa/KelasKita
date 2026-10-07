<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Materi mana yang sudah dibaca pengguna (tb_materi_dibaca).
     *
     * Tabel ini menjawab satu pertanyaan yang tidak bisa dijawab tabel lain:
     * "materi mana yang sudah dibaca pengguna ini?". Yang tersedia sebelumnya
     * cuma tb_materi.jumlah_dilihat, yaitu banyaknya opening dari semua orang
     * sekaligus, bukan siapa yang membuka dan bukan materi yang mana.
     * Angka itu tidak bisa jadi pembilang progress per pengguna, karena 100
     * tampilan bisa berarti satu orang membaca 100 materi atau 100 orang
     * membaca satu materi yang sama.
     *
     * Bedanya dengan tb_simpanan_materi (bookmark) sengaja dijaga: menyimpan
     * adalah keputusan pengguna sebelum membaca, sedangkan tabel ini mencatat
     * siapa yang benar-benar membuka halamannya. Satu baris ditulis ulang kalau
     * materinya dibuka lagi, jadi membuka lima kali tetap satu baris.
     *
     * Yang menulisnya hanya App\Support\MateriDibaca, dipanggil dari halaman
     * baca materi setelah halaman dirender, bukan dari daftar materi. Jadi
     * materi yang hanya terlihat sekilas di daftar tidak ikut terhitung sebagai
     * sudah dibaca.
     */
    public function up(): void
    {
        Schema::create('tb_materi_dibaca', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            $table->foreignId('materi_id')
                ->constrained('tb_materi')
                ->cascadeOnDelete();

            $table->timestamps();

            // Satu materi satu baris per pengguna. Inilah yang membuat
            // pencatatan tetap aman dipanggil setiap kali halaman dibuka.
            $table->unique(['pengguna_id', 'materi_id']);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_materi_dibaca');
    }
};
