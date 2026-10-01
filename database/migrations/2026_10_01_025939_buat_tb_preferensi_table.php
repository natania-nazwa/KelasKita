<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Preferensi admin (tabel tb_preferensi).
     *
     * Satu baris untuk satu pengguna. Tabelnya terpisah dari tb_pengguna,
     * bukan menambah kolom di sana, karena yang disimpan di sini bukan bagian
     * dari identitas akun: mengganti email atau nama tidak boleh ikut menghapus
     * atau mengubah preferensinya.
     *
     * Yang disimpan:
     *
     *   - tema                  = terang / gelap, mengikuti localStorage
     *                              "kk-tema" milik area user supaya satu
     *                              account punya satu tema di seluruh aplikasi.
     *   - notifikasi_*          = saklar jenis notifikasi yang diterima admin.
     *                              Pengaturan ini hanya dibaca oleh pemanggil
     *                              yang membuat notifikasi admin; notifikasi
     *                              untuk pengguna saat admin menerbitkan konten
     *                              tidak pernah ikut terpengaruh.
     *   - konfirmasi_publikasi  = tampilkan dialog konfirmasi sebelum publish.
     *   - status_konten_default = status yang dipakai saat admin menekan
     *                              "Simpan Draft" maupun "Publish Sekarang" pada
     *                              form. Tidak ada alur persetujuan di aplikasi
     *                              ini: admin bisa langsung menerbitkan karyanya.
     *
     * Kolomnya dibuat dengan nilai bawaan yang sama persis dengan yang dilihat
     * model Preferensi, jadi pengguna yang belum pernah membuka halaman
     * Pengaturan tetap punya perilaku yang masuk akal.
     */
    public function up(): void
    {
        Schema::create('tb_preferensi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->unique()
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            $table->string('tema', 16)->default('terang');

            // Notifikasi konten: kabar tentang karyanya sendiri.
            $table->boolean('notifikasi_konten_terbit')->default(true);
            $table->boolean('notifikasi_konten_draft')->default(true);

            // Notifikasi aktivitas: kabar tentang orang lain.
            $table->boolean('notifikasi_aktivitas_kuis')->default(true);
            $table->boolean('notifikasi_aktivitas_konten')->default(true);

            $table->boolean('konfirmasi_publikasi')->default(true);

            $table->string('status_konten_default', 16)->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_preferensi');
    }
};
