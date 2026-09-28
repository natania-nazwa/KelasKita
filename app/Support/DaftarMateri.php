<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;

/**
 * Mengubah model Materi menjadi array polos yang sudah berbentuk "siap
 * pakai" untuk komponen tampilan.
 *
 * Komponen di resources/views/components/materi tidak tahu-menahu soal
 * Eloquent: mereka hanya menerima array. Makanya pemetaan model ke array
 * dipusatkan di sini, sehingga sumber data bisa diganti ke API atau
 * array statis tanpa menyentuh komponen.
 */
final class DaftarMateri
{
    /**
     * Daftar materi per kategori (jumlah materi aktif di tiap pelajaran).
     * Dipakai untuk mengisi kunci jumlah_materi pada tiap kartu.
     *
     * @return array<int, int> id pelajaran => jumlah materi
     */
    public static function jumlahPerKategori(): array
    {
        return Materi::query()
            ->aktif()
            ->whereNotNull('pelajaran_id')
            ->selectRaw('pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('pelajaran_id')
            ->pluck('jumlah', 'pelajaran_id')
            ->map(fn ($jumlah) => (int) $jumlah)
            ->all();
    }

    /**
     * Jumlah materi per halaman.
     *
     * Dua puluh, supaya pas dengan grid padat empat kolom: 20 = 5 baris
     * penuh di desktop, 10 baris di tablet, dan 10 baris di ponsel (dua
     * kolom), jadi tidak ada kartu yatim di baris terakhir.
     */
    public static function perHalaman(): int
    {
        return 20;
    }

    /**
     * Petakan sekumpulan materi ke bentuk array yang dipakai komponen.
     *
     * Bentuk array per kartu:
     *   id, slug, judul, deskripsi, thumbnail, tingkat_kesulitan,
     *   waktu_baca, jumlah_bab, aktif, status_label, dibuat_pada,
     *   tautan, tautan_edit, tautan_hapus,
     *   kategori => [nama, slug, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap],
     *   jumlah_materi
     *
     * Kartu di halaman Materi hanya memakai sebagian dari kunci ini; sisanya
     * dipakai kartu pengelolaan di "Karya Saya".
     *
     * @param  iterable<int, Materi>  $materi
     * @param  array<int, int>|null  $jumlahPerKategori  hasil jumlahPerKategori()
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $materi, ?array $jumlahPerKategori = null): array
    {
        $jumlahPerKategori ??= self::jumlahPerKategori();

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
                'deskripsi' => $item->ringkasan(120),
                'tingkat_kesulitan' => $item->tingkat_kesulitan,
                'waktu_baca' => $item->waktuBaca(),
                'jumlah_bab' => $item->jumlahBab(),
                'aktif' => (bool) $item->aktif,
                'status_label' => $item->labelStatus(),
                'dibuat_pada' => $item->created_at,
                'thumbnail' => filled($item->thumbnail)
                    ? asset('storage/'.basename($item->thumbnail))
                    : null,
                'kategori' => [
                    'nama' => $kategori['nama'],
                    'slug' => $kategori['slug'],
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
                'jumlah_materi' => (int) ($jumlahPerKategori[$item->pelajaran_id] ?? 0),
                'tautan' => route('user.materi.detail', $item->slug),
                'tautan_edit' => route('user.materi.edit', $item->slug),
                'tautan_hapus' => route('user.materi.destroy', $item->slug),
            ];
        }

        return $hasil;
    }
}
