<?php

namespace App\Support;

use App\Models\Pelajaran;
use App\Models\Quiz;

/**
 * Mengubah model Quiz menjadi array polos yang sudah berbentuk "siap pakai"
 * untuk komponen tampilan.
 *
 * Bentuk ini sengaja dibuat sama persis dengan yang dipakai
 * App\Support\DaftarMateri, sehingga kartu quiz dan kartu materi punya
 * sumber data alike. Komponen di resources/views/components/quiz tidak
 * tahu-menahu soal Eloquent: mereka hanya menerima array. Nanti kalau data
 * quiz diambil dari API, cukup ganti isi petakan() tanpa menyentuh markup.
 *
 * Bentuk array per kartu:
 *   id, slug, judul, deskripsi, thumbnail, durasi, jumlah_soal,
 *   status, status_label, visibilitas, saya, dibuat_pada,
 *   tautan, tautan_edit, tautan_hapus, tautan_mulai_sesi,
 *   kategori => [nama, slug, ikon, warna, warna_gelap],
 *   pembuat => [nama, inisial, warna, warna_gelap]
 *
 * Kartu di halaman Quiz hanya memakai sebagian dari kunci ini; sisanya dipakai
 * kartu pengelolaan di "Karya Saya".
 */
final class DaftarQuiz
{
    /**
     * Jumlah quiz per halaman.
     *
     * Dua belas, bukan delapan: grid halaman Quiz memakai 2, 3, dan 4
     * kolom (lihat components/quiz/grid), dan 12 adalah kelipatan
     * terkecil ketiganya. Delapan menyisakan satu sel kosong di baris
     * terakhir pada grid tiga kolom.
     */
    public static function perHalaman(): int
    {
        return 12;
    }

    /**
     * Petakan sekumpulan quiz ke bentuk array yang dipakai komponen.
     *
     * @param  iterable<int, Quiz>  $quiz
     * @param  int|null  $idPengguna  dipakai untuk menandai kartu milik
     *                                pengguna yang sedang login
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $quiz, ?int $idPengguna = null): array
    {
        $hasil = [];

        foreach ($quiz as $item) {
            $pelajaran = $item->pelajaran;
            $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
            $pembuat = $item->pembuat;
            $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

            $hasil[] = [
                'id' => $item->getKey(),
                'slug' => $item->slug,
                'judul' => $item->judul,
                'deskripsi' => (string) $item->deskripsi,
                'thumbnail' => filled($item->thumbnail)
                    ? asset('storage/'.basename($item->thumbnail))
                    : null,
                'durasi' => (int) ($item->durasi ?? 0),
                'jumlah_soal' => $item->jumlahSoal(),
                'status' => (string) $item->status,
                'status_label' => $item->labelStatus(),
                'visibilitas' => (string) $item->visibilitas,
                'saya' => $item->dimilikiOleh($idPengguna),
                'dibuat_pada' => $item->created_at,
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
                'tautan' => route('user.quiz.detail', $item->getKey()),
                'tautan_edit' => route('user.quiz.edit', $item->getKey()),
                'tautan_hapus' => route('user.quiz.destroy', $item->getKey()),
                'tautan_mulai_sesi' => route('user.sesi.buka', $item->getKey()),
            ];
        }

        return $hasil;
    }
}
