<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;

/**
 * Mengubah model Materi menjadi array polos untuk halaman "Tinjau Materi"
 * di area admin.
 *
 * Bentuknya mengikuti DaftarMateri (pemetaan model ke array dikumpulkan di
 * Support), tapi ditambah hal-hal yang hanya dibutuhkan saat menilai sebuah
 * materi: isi mentahnya, alasan penolakan sebelumnya, dan catatan pengajuan
 * ulang dari pemilik.
 */
final class TinjauanMateri
{
    /**
     * Jumlah materi per halaman.
     *
     * Sepuluh: satu baris review dibuat cukup tinggi karena tiap baris bisa
     * memuat cuplikan isi dan dua tombol keputusan, jadi sepuluh baris sudah
     * memenuhi satu layar penuh tanpa menggulir terlalu jauh.
     */
    public static function perHalaman(): int
    {
        return 10;
    }

    /**
     * Status yang boleh jadi filter di halaman ini.
     *
     * @return array<string, string>
     */
    public static function pilihanStatus(): array
    {
        return [
            Materi::STATUS_PENDING => 'Menunggu Persetujuan',
            Materi::STATUS_REJECTED => 'Ditolak',
            Materi::STATUS_PUBLISHED => 'Dipublikasikan',
            Materi::STATUS_DRAFT => 'Draft',
        ];
    }

    /**
     * Petakan sekumpulan materi ke bentuk array yang dipakai tabel review.
     *
     * Bentuk array per baris:
     *   id, slug, judul, deskripsi, isi, tingkat_kesulitan, waktu_baca,
     *   jumlah_bab, status, warna_status, status_label, catatan_admin,
     *   catatan_pengajuan, jumlah_ditolak, sisa_pengajuan, dibuat_pada,
     *   tautan_setujui, tautan_tolak,
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
                'deskripsi' => (string) $item->deskripsi,
                'isi' => (string) $item->isi,
                'tingkat_kesulitan' => (string) $item->tingkat_kesulitan,
                'waktu_baca' => $item->waktuBaca(),
                'jumlah_bab' => $item->jumlahBab(),
                'status' => (string) $item->status,
                'warna_status' => $item->warnaStatus(),
                'status_label' => $item->labelStatus(),
                'catatan_admin' => $item->catatan_admin,
                'catatan_pengajuan' => $item->catatan_pengajuan,
                'jumlah_ditolak' => (int) $item->jumlah_ditolak,
                'sisa_pengajuan' => $item->sisaPengajuan(),
                'dibuat_pada' => $item->created_at,
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
                'tautan_setujui' => route('admin.materi.setujui', $item->slug),
                'tautan_tolak' => route('admin.materi.tolak', $item->slug),
            ];
        }

        return $hasil;
    }
}
