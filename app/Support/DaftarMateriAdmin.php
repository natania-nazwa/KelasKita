<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;

/**
 * Mengubah model Materi menjadi array polos untuk halaman kelola Materi
 * di area admin.
 *
 * Halaman ini menampilkan seluruh materi dari semua status (bukan hanya yang
 * menunggu persetujuan), jadi bentuknya mengikuti TinjauanMateri tetapi tanpa
 * hal yang hanya dibutuhkan saat menilai: isi mentah, tautan setujui/tolak,
 * dan angka penolakan. Yang dibutuhkan justru metadata ringkas per baris
 * tabel dan rincian lengkap untuk halaman detail.
 */
final class DaftarMateriAdmin
{
    /**
     * Jumlah materi per halaman.
     *
     * Tabel kelola butuh lima sampai tujuh kolom, jadi barisnya ringkas dan
     * dua belas baris sudah memenuhi satu layar tanpa menggulir terlalu jauh.
     */
    public static function perHalaman(): int
    {
        return 12;
    }

    /**
     * Petakan sekumpulan materi ke bentuk array yang dipakai tabel dan kartu
     * di halaman kelola.
     *
     * Bentuk array per baris:
     *   id, slug, judul, status, status_label, jumlah_bab, waktu_baca,
     *   tingkat_kesulitan,
     *   tanggal => Carbon (terbit kalau sudah terbit, selainnya tanggal dibuat),
     *   tanggal_label, dibuat_pada, dipublish_pada, tautan_detail,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     *
     * @param  iterable<int, Materi>  $materi
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $materi): array
    {
        $hasil = [];

        foreach ($materi as $item) {
            $pelajaran = $item->pelajaran;
            $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
            $pembuat = $item->pembuat;
            $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

            $hasil[] = [
                'id' => $item->getKey(),
                'slug' => $item->slug,
                'judul' => $item->nama,
                'status' => (string) $item->status,
                'status_label' => $item->labelStatus(),
                'jumlah_bab' => $item->jumlahBab(),
                'waktu_baca' => $item->waktuBaca(),
                'tingkat_kesulitan' => (string) $item->tingkat_kesulitan,
                'tanggal' => $item->dipublish_pada ?? $item->created_at,
                'tanggal_label' => DetailMateri::tanggal($item->dipublish_pada ?? $item->created_at),
                'dibuat_pada' => $item->created_at,
                'dipublish_pada' => $item->dipublish_pada,
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
                'tautan_detail' => route('admin.materi.show', $item->slug),
            ];
        }

        return $hasil;
    }

    /**
     * Rincian satu materi untuk halaman detail admin.
     *
     * Isi materi dipecah jadi seksi + blok (lewat IsiMateri) supaya view
     * tinggal menampilkan, tidak perlu mem-parsing markup. Ditambah semua
     * metadata yang relevan untuk admin: status, tanggal terbit, dan catatan
     * yang tertulis saat menolak/mengajukan ulang.
     *
     * @return array<string, mixed>
     */
    public static function petikan(Materi $item): array
    {
        $pelajaran = $item->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
        $pembuat = $item->pembuat;
        $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

        return [
            'judul' => $item->nama,
            'slug' => $item->slug,
            'deskripsi' => (string) $item->deskripsi,
            'tingkat_kesulitan' => (string) $item->tingkat_kesulitan,
            'jumlah_bab' => $item->jumlahBab(),
            'waktu_baca' => $item->waktuBaca(),
            'status' => (string) $item->status,
            'status_label' => $item->labelStatus(),
            'tanggal_dibuat' => DetailMateri::tanggal($item->created_at),
            'tanggal_terbit' => DetailMateri::tanggal($item->dipublish_pada),
            'catatan_admin' => $item->catatan_admin,
            'catatan_pengajuan' => $item->catatan_pengajuan,
            'jumlah_ditolak' => (int) $item->jumlah_ditolak,
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
            'seksi' => IsiMateri::seksi($item->isi, $item->nama),
            'tautan_daftar' => route('admin.materi'),
        ];
    }
}
