<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Riwayat login (tabel "tb_riwayat_login").
     *
     * Satu baris = satu kali login BERHASIL.Tabel ini dibaca grafik
     * "Aktivitas Login Mingguan" di dashboard admin, jadi yang perlu
     * disimpan adalah kejadian login-nya, bukan sesi yang sedang hidup.
     *
     * Kenapa tidak membaca dari tabel "sessions" seperti kartu "Pengguna
     * Aktif" di halaman statistik: tabel itu hanya menyimpan
     * last_activity, jadi satu sesi yang berumur beberapa hari selalu
     * terhitung di minggu terakhir aktivitasnya, dan berapa kali orang
     * itu sebenarnya masuk sudah tidak diketahui. Untuk grafik "jumlah
     * login" itu akan berbohong, jadi login yang berhasil dicatat di
     * sini.
     *
     * Tabel ini hanya bertambah, tidak pernah dihitung ulang atau
     * diedit: tidak ada updated_at yang dipakai, dan hapus akun ikut
     * menghapus riwayatnya lewat cascade.
     */
    public function up(): void
    {
        Schema::create('tb_riwayat_login', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            $table->timestamps();

            // Grafik mingguan selalu menyaring "created_at di antara awal
            // dan akhir minggu", jadi kolomnya perlu diindeks supaya
            // jumlah baris yang dibaca tidak ikut tumbuh.
            $table->index('created_at');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_riwayat_login');
    }
};
