<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Notifikasi untuk pengguna (tb_notifikasi).
     *
     * Satu baris untuk satu notifikasi milik satu pengguna. Yang menulisnya
     * hanya admin, lewat App\Support\NotifikasiKonten, setiap kali ia
     * menerbitkan materi atau quiz dari menu "Konten Pembelajaran".
     *
     * Dua pasang kolom yang sengaja dipisah, bukan digabung jadi satu:
     *
     *   - jenis            = apa yang terjadi ("materi_baru" / "quiz_baru"),
     *                         dipakai untuk memilih ikon dan kalimat pada
     *                         lonceng notifikasi.
     *   - konten_tipe +
     *     konten_id        = konten yang jadi sumber pesan, supaya notifikasi
     *                         bisa langsung membawa pembaca ke halaman materi
     *                         atau halaman quiz yang dimaksud.
     *
     * Kolom yang tidak ada isinya sengaja dibiarkan null, bukan diisi nilai
     * kosong: "kontennya sudah dihapus" dan "kontennya belum pernah ada" harus
     * bisa dibedakan, dan lonceng notifikasi menampilkan tautan yang sudah
     * tidak berlaku sebagai teks biasa, bukan sebagai tautan rusak.
     */
    public function up(): void
    {
        Schema::create('tb_notifikasi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            // Jenis notifikasi, bukan jenis konten: keduanya kebetulan sama
            // untuk dua kasus yang sekarang ada, tapi pemanggilnya yang
            // menentukan gayanya.
            $table->string('jenis', 32);

            $table->string('judul', 160);

            $table->text('pesan');

            // Konten yang jadi sumber pesan. Tidak ada foreign key ke
            // tb_materi / tb_quiz karena keduanya bertipe berbeda dan kolomnya
            // dipakai bersama; menghapusnya ditangani di model (lihat
            // App\Models\Notifikasi::konten()), jadi notifikasi lama tidak ikut
            // hilang bersama materinya.
            $table->unsignedBigInteger('konten_id')->nullable();
            $table->string('konten_tipe', 32)->nullable();

            // Kapan notifikasi itu dibaca. Null berarti belum dibaca, dan
            // itulah yang membuat titiknya di lonceng masih menyala.
            $table->timestamp('dibaca_pada')->nullable();

            $table->timestamps();

            // Daftar notifikasi satu pengguna selalu dibaca "belum dibaca
            // dulu, terbaru kemudian", jadi kedua kolom ini dipakai bersama
            // di setiap query.
            $table->index(['pengguna_id', 'dibaca_pada']);

            // Pencarian notifikasi lama milik satu konten, dipakai supaya
            // menerbitkan ulang konten yang sama tidak menumpuk notifikasi
            // yang isinya identik tanpa batas.
            $table->index(['konten_tipe', 'konten_id']);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_notifikasi');
    }
};
