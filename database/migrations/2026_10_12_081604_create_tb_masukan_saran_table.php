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
     * daftar maupun riwayat. Kolom "status" hanya diurus admin lewat halaman
     * masukan di area admin, bukan oleh pengirimnya.
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

            /*
             * Status tindak lanjut dari sisi admin. Nilainya "baru",
             * "diproses", atau "selesai" (lihat konstanta di model
             * MasukanSaran). Bawaannya "baru" karena setiap kiriman memang
             * dimulai belum ditangani.
             */
            $table->string('status', 20)->default('baru');

            $table->timestamps();

            // Baris selalu dibaca per pengguna (mis. menelusuri kiriman
            // satu orang), jadi kolomnya berdiri sendiri sebagai indeks.
            $table->index('pengguna_id');

            // Daftar admin bisa disaring per status, jadi kolomnya juga
            // berdiri sendiri sebagai indeks.
            $table->index('status');
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
