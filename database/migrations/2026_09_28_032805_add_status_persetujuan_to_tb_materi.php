<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Mengganti kolom boolean "aktif" pada tb_materi dengan alur persetujuan
 * admin, supaya materi tidak bisa langsung tayang hanya karena pemiliknya
 * menekan tombol publikasi.
 *
 * Nilai lama dipindahkan dulu sebelum kolom "aktif" dihapus: materi yang
 * sudah tayang (aktif = true) menjadi "published", sisanya menjadi "draft".
 * Kalau tidak dipindahkan, seluruh materi lama akan hilang dari halaman
 * Materi begitu migrasi ini jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            /*
             * Status materi, sama seperti status quiz:
             *
             * draft     = baru dibuat, belum diajukan ke admin
             * pending   = menunggu persetujuan admin
             * published = sudah disetujui, tampil untuk semua pengguna
             * rejected  = ditolak admin (lihat catatan_admin)
             */
            $table->string('status')
                ->default('draft')
                ->index();

            // Alasan admin saat menolak, dibaca kembali pemiliknya.
            $table->text('catatan_admin')
                ->nullable();

            // Penjelasan pemilik saat mengajukan ulang materi yang ditolak.
            $table->text('catatan_pengajuan')
                ->nullable();

            $table->timestamp('dipublish_pada')
                ->nullable();

            // Berapa kali materi ini ditolak. Dipakai untuk membatasi
            // jumlah pengajuan ulang (lihat Materi::BATAS_PENGAJUAN_ULANG).
            $table->unsignedTinyInteger('jumlah_ditolak')
                ->default(0);

            $table->index(['dibuat_oleh', 'status']);
        });

        $waktuSekarang = now();

        DB::table('tb_materi')
            ->where('aktif', true)
            ->update([
                'status' => 'published',
                'dipublish_pada' => $waktuSekarang,
            ]);

        DB::table('tb_materi')
            ->where('aktif', false)
            ->update(['status' => 'draft']);

        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('tb_materi', function (Blueprint $table) {
            $table->boolean('aktif')
                ->default(true);
        });

        /*
         * Hanya materi yang statusnya "published" yang kembali tayang.
         * Draft, menunggu, dan ditolak menjadi aktif = false supaya tidak
         * ikut muncul di halaman Materi setelah rollback.
         */
        DB::table('tb_materi')
            ->where('status', 'published')
            ->update(['aktif' => true]);

        DB::table('tb_materi')
            ->where('status', '!=', 'published')
            ->update(['aktif' => false]);

        Schema::table('tb_materi', function (Blueprint $table) {
            $table->dropIndex(['dibuat_oleh', 'status']);
            $table->dropIndex('tb_materi_status_index');

            $table->dropColumn([
                'status',
                'catatan_admin',
                'catatan_pengajuan',
                'dipublish_pada',
                'jumlah_ditolak',
            ]);
        });
    }
};
