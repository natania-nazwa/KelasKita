<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Jejak aktivitas belajar harian (tb_aktivitas_harian).
     *
     * Satu baris = satu jenis kegiatan pada satu hari untuk satu pengguna.
     * Yang mencatatnya hanya App\Support\AktivitasHarian, dipanggil dari dua
     * tempat: halaman baca materi (membuka materi) dan halaman menjawab soal
     * (menyimpan jawaban).
     *
     * Tabel ini ada karena tidak ada satu pun kolom yang bisa menjawab soal
     * "apakah pengguna belajar hari ini". tb_materi.jumlah_dilihat cuma
     * menyimpan banyaknya opening, bukan siapa yang membuka dan kapan, jadi
     * membacanya untuk menghitung streak tidak akan pernah menghasilkan
     * jawaban yang benar. Satu baris per hari dan per jenis cukup untuk
     * menghitung streak, dan unique constraint di bawah yang menjaganya:
     * membuka materi yang sama sepuluh kali dalam sehari tetap satu baris.
     *
     * Kolom "tanggal" sengaja dipisah dari created_at. Yang dihitung streak
     * adalah hari kalender, bukan jam, jadi kolom tanggal yang bisa dibaca
     * langsung tanpa timezone gymnastics. created_at tetap dipakai untuk
     * menjawab pertanyaan "kapan terakhir ada aktivitas", karena itulah yang
     * menentukan masih menyala atau sudah padam setelah 24 jam.
     */
    public function up(): void
    {
        Schema::create('tb_aktivitas_harian', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            // Hari kalender dalam bentuk Y-m-d. Ditulis sebagai string supaya
            // perbandingan antarhari tetap bisa dilakukan langsung di SQL dan
            // tidak bergantung pada zona waktu database.
            $table->date('tanggal');

            // Apa yang dilakukan: baca materi atau menjawab soal.
            $table->string('jenis', 32);

            $table->timestamps();

            // Satu jenis kegiatan satu hari untuk satu pengguna. Inilah yang
            // membuat pencatatan tidak perlu cek "sudah ada belum" lebih dulu,
            // dan sekaligus alasan aman memakai updateOrCreate di pemanggilnya.
            $table->unique(['pengguna_id', 'tanggal', 'jenis'], 'tb_aktivitas_harian_unik');

            // Menghitung streak selalu menyaring satu pengguna lalu membaca
            // tanggal-tanggalnya turun, jadi urutan ini yang dipakai.
            $table->index(['pengguna_id', 'tanggal']);
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_aktivitas_harian');
    }
};
