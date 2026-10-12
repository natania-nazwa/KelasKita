<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Masukan dan saran yang dikirim pengguna (tb_masukan_saran).
     *
     * Satu baris untuk satu kiriman dari halaman "Masukan & Saran". Isinya
     * murni catatan masuk: penggunanya tidak pernah membaca kembali baris
     * ini di dalam aplikasi, karena halamannya hanya punya form kirim, tanpa
     * daftar, riwayat, atau status.
     *
     * "pengguna_id" diambil dari sesi login dan tidak pernah dari field
     * form, jadi tidak ada jalan mengirim masukan atas nama orang lain.
     * Barisnya ikut terhapus bersama akunnya, sama seperti notifikasi.
     *
     * "lampiran" menyimpan path relatif di disk publik (lihat
     * App\Support\BerkasMasukan) dan boleh null karena lampiran memang
     * pilihan. Menghapus baris di sini tidak menyentuh berkasnya; kalau
     * suatu saat diperlukan, pemanggilnya yang membersihkan berkas.
     */
    public function up(): void
    {
        Schema::create('tb_masukan_saran', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            $table->string('judul', 150);

            $table->text('detail');

            // Path relatif di disk publik, atau null kalau tanpa lampiran.
            $table->string('lampiran')->nullable();

            $table->timestamps();

            // Baris selalu dibaca per pengguna (mis. menelusuri kiriman
            // satu orang), jadi kolomnya berdiri sendiri sebagai indeks.
            $table->index('pengguna_id');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_masukan_saran');
    }
};
