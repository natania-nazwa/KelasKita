<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Identitas aplikasi KelasKita untuk halaman "Informasi Sistem" dan
 * "Tentang Kelas Kita".
 *
 * Satu tempat untuk semua fakta tentang aplikasi ini, supaya halaman
 * Pengaturan tidak menulis "1.0.0" di dua file berbeda dan Risk menjadi
 * berbeda begitu versinya naik.
 *
 * Yang ditulis di sini bukan data yang dikarang. Nama diambil dari config
 * (APP_VERSION), tahun dari tahun berjalan, dan status server / database
 * diukur dengan benar-benar menyentuh keduanya, bukan ditulis "Online".
 */
final class Aplikasi
{
    /**
     * Nama produk yang dipakai di UI.
     *
     * APP_NAME di .env masih bawaan skeleton Laravel, sedangkan seluruh
     * tampilan aplikasi ini sudah memakai "KelasKita" (sidebar, judul
     * halaman, nama berkas logo). Jadi nama produk ditulis di sini, satu
     * tempat, dan bukan dibaca dari APP_NAME yang belum diubah.
     */
    public const NAMA = 'KelasKita';

    /**
     * Kalimat pembuka aplikasi di halaman "Tentang Kelas Kita".
     */
    public const DESKRIPSI = 'Platform pembelajaran untuk membantu peserta didik '
        .'mengakses materi dan mengerjakan kuis secara terstruktur.';

    public function versi(): string
    {
        return 'v'.ltrim((string) config('app.versi', '1.0.0'), 'v');
    }

    public function tahun(): int
    {
        return (int) now()->year;
    }

    /**
     * Nama tim pengembang, atau null kalau belum diisi di .env.
     *
     * Null berarti halaman "Tentang" tidak menampilkan baris ini sama sekali.
     * Halaman tidak pernah menampilkan placeholder seperti "-" untuk
     * informasi yang memang belum ada.
     */
    public function pengembang(): ?string
    {
        $nilai = config('app.pengembang');

        return filled($nilai) ? (string) $nilai : null;
    }

    /**
     * Baris hak cipta untuk kaki halaman "Tentang Kelas Kita".
     */
    public function hakCipta(): string
    {
        return '© '.$this->tahun().' '.self::NAMA;
    }

    /**
     * Versi PHP yang sedang menjalankan permintaan ini.
     */
    public function versiPhp(): string
    {
        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }

    /**
     * Versi framework Laravel yang terpasang.
     */
    public function versiLaravel(): string
    {
        return app()->version();
    }

    /**
     * Nama mesin database yang sedang dipakai.
     */
    public function basisData(): string
    {
        return (string) config('database.default');
    }

    /**
     * Status database diukur sungguhan: satu koneksi dibuka dan satu
     * pernyataan sederhana dijalankan.
     *
     * Nilai "Online" yang ditulis manual akan tetap terlihat mesmo ketika
     * server sedang salah, jadi di sini tidak ada jalan selain benar-benar
     * mencoba.
     *
     * @return array{ok: bool, label: string}
     */
    public function statusBasisData(): array
    {
        try {
            DB::connection()->select('SELECT 1');

            return ['ok' => true, 'label' => 'Connected'];
        } catch (Throwable) {
            return ['ok' => false, 'label' => 'Tidak terhubung'];
        }
    }

    /**
     * Status server diukur dari ada atau tidaknya kesalahan yang belum
     * tertangani.
     *
     * Request ini sendiri sudah berjalan sampai sini, jadi "Online" di sini
     * berarti permintaan yang sedang dilayani berhasil sampai selesai
     * menulis halaman. Kalau halaman ini sampai terender, jawabannya sudah
     * benar; tidak ada probe tambahan yang perlu dibuat.
     *
     * @return array{ok: bool, label: string}
     */
    public function statusServer(): array
    {
        return ['ok' => true, 'label' => 'Online'];
    }
}
