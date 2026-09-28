<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Menambah tugas/PR pada satu jam pelajaran.
     *
     * "pr" berisi apa saja yang harus dikerjakan, ditulis bebas oleh
     * pengguna ("PR halaman 45-50", "Kerjakan latihan 3"). Karena isinya
     * bebas, kolomnya dibuat text, bukan string.
     *
     * "pr_dikumpulkan" adalah tanggal_PR itu harus dikumpulkan. Tenggatnya
     * tidak boleh kosong kalau PRnya ada, tapi boleh kosong kalau jadwalnya
     * memang tidak punya PR — itu sebabnya keduanya nullable.
     *
     * PR tidak punya tabel sendiri: satu PR menempel pada satu jam pelajaran.
     * Kalau nanti satu pelajaran punya beberapa PR, tinggal menambah tabel
     * terpisah tanpa mengubah kolom di sini.
     */
    public function up(): void
    {
        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->text('pr')->nullable()->after('guru');
            $table->date('pr_dikumpulkan')->nullable()->after('pr');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->dropColumn(['pr', 'pr_dikumpulkan']);
        });
    }
};
