<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;

/**
 * Mengubah model Materi menjadi array polos untuk halaman "Materi" di area
 * admin.
 *
 * Halaman ini hanya mengelola materi yang sudah terbit: materi yang masih
 * menunggu keputusan ada di halaman Verifikasi, dan yang ditolak atau masih
 * draft dikelola pemiliknya di "Karya Saya". Karena itu barisnya hanya perlu
 * metadata ringkas untuk kartu di daftar.
 *
 * Isi materi tidak ikut dipetakan di sini. Halaman detail admin memakai
 * App\Support\DetailMateri, bentuk array yang sama persis dengan halaman
 * detail milik pengguna, supaya isi satu materi tidak pernah punya dua
 * bentuk berbeda tergantung siapa yang membukanya.
 */
final class DaftarMateriAdmin
{
    /**
     * Jumlah materi per halaman.
     *
     * Dua puluh, sama seperti halaman Materi milik pengguna
     * (DaftarMateri::perHalaman). Daftarnya sekarang grid empat kolom, dan
     * 20 = 5 baris penuh: tidak pernah ada kartu yatim di baris terakhir,
     * dan tiap baris masih muat di layar tanpa perlu menggulir untuk melihat
     * baris berikutnya. Memakai angka yang sama dengan halaman user juga
     * membuat jumlah halaman di kedua halaman langsung bisa dibandingkan.
     */
    public static function perHalaman(): int
    {
        return 20;
    }

    /**
     * Petakan sekumpulan materi ke bentuk array yang dipakai kartu di daftar.
     *
     * Bentuk array per baris:
     *   id, slug, judul, thumbnail, tingkat_kesulitan,
     *   jumlah_bab, waktu_baca, jumlah_dilihat, dilihat,
     *   tanggal => Carbon, tanggal_label, tanggal_jam,
     *   status, status_label, boleh_edit,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap],
     *   tautan_detail, tautan_edit, tautan_hapus
     *
     * Isi materi tidak ikut dipetakan di sini. Kartu di daftar menampilkan
     * kategori, jumlah bab, pembuat, tanggal, dan status; isi lengkapnya ada
     * di halaman detail, jadi memetakannya hanya menambah pekerjaan tanpa
     * dipakai.
     *
     * Tidak ada deskripsi materi di sini: kartu di daftar admin tidak
     * menampilkannya (isi lengkapnya ada di halaman detail), jadi memetakannya
     * hanya menambah pekerjaan tanpa dipakai.
     *
     * Tanggal yang ditampilkan adalah tanggal terbit kalau materi sudah
     * tayang, bukan tanggal pembuatannya: di halaman ini yang relevan adalah
     * kapan materi mulai dilihat pembacanya.
     *
     * @param  iterable<int, Materi>  $materi
     * @param  int|null  $idAdmin  id admin yang sedang masuk, dipakai untuk
     *                             menentukan boleh_edit. Null = tidak ada
     *                             admin yang bisa diedit, jadi tidak satu
     *                             pun baris menampilkan tombol Edit.
     * @return array<int, array<string, mixed>>
     */
    public static function petikan(iterable $materi, ?int $idAdmin = null): array
    {
        $hasil = [];

        foreach ($materi as $item) {
            $hasil[] = self::baris($item, $idAdmin);
        }

        return $hasil;
    }

    /**
     * Tautan aksi untuk satu materi, dipakai kartu di daftar.
     *
     * @return array{tautan_detail: string, tautan_edit: string, tautan_hapus: string}
     */
    public static function tautan(Materi $item): array
    {
        return [
            'tautan_detail' => route('admin.materi.show', $item->slug),
            'tautan_edit' => route('admin.materi.edit', $item->slug),
            'tautan_hapus' => route('admin.materi.destroy', $item->slug),
        ];
    }

    /**
     * Satu baris daftar.
     *
     * @return array<string, mixed>
     */
    private static function baris(Materi $item, ?int $idAdmin = null): array
    {
        $pelajaran = $item->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
        $pembuat = $item->pembuat;
        $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];
        $terbit = $item->dipublish_pada ?? $item->created_at;
        $jumlahDilihat = (int) ($item->jumlah_dilihat ?? 0);

        return [
            'id' => $item->getKey(),
            'slug' => $item->slug,
            'judul' => $item->nama,
            'thumbnail' => BerkasMateri::url($item->thumbnail),
            'tingkat_kesulitan' => (string) $item->tingkat_kesulitan,
            'jumlah_bab' => $item->jumlahBab(),
            'waktu_baca' => $item->waktuBaca(),
            'jumlah_dilihat' => $jumlahDilihat,
            'dilihat' => Angka::ringkas($jumlahDilihat),
            'tanggal' => $terbit,
            'tanggal_label' => DetailMateri::tanggal($terbit),
            'tanggal_jam' => $terbit?->format('H:i'),
            'status' => (string) $item->status,
            'status_label' => $item->labelStatus(),

            /*
             * Edit hanya untuk materi yang dibuat admin yang sedang login.
             * Materi buatan pengguna lain tetap bisa dibaca dan dihapus dari
             * sini, tapi tidak bisa diedit: isinya milik penulisnya, dan
             * supaya menu ini tidak menawarkan sesuatu yang akan ditolak 403
             * begitu diklik. Aturan yang sama ditegakkan ulang oleh
             * Admin\MateriKelolaController, bukan hanya disembunyikan di UI.
             */
            'boleh_edit' => $idAdmin !== null && $item->dimilikiOleh($idAdmin),
            'kategori' => [
                'nama' => $kategori['nama'],
                'ikon' => $kategori['ikon'],
                'warna' => $kategori['warna'],
                'warna_gelap' => $kategori['warna_gelap'],
            ],
            'pembuat' => [
                'nama' => $pembuat?->nama ?? 'Tanpa nama',
                'inisial' => $pembuat?->inisial() ?? '?',
                'warna' => $avatar['warna'],
                'warna_gelap' => $avatar['warna_gelap'],
            ],
            ...self::tautan($item),
        ];
    }
}
