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
 *   tingkat_kesulitan, status, warna_status, status_label, visibilitas,
 *   pakai_kode, kode_akses, saya, boleh_rujukan, catatan_admin,
 *   sisa_pengajuan, dibuat_pada,
 *   tautan, tautan_edit, tautan_hapus, tautan_mulai, tautan_mulai_sesi,
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
                'thumbnail' => BerkasQuiz::url($item->thumbnail),
                'durasi' => (int) ($item->durasi ?? 0),
                'jumlah_soal' => $item->jumlahSoal(),
                'tingkat_kesulitan' => (string) ($item->tingkat_kesulitan ?? ''),
                'status' => (string) $item->status,
                'warna_status' => $item->warnaStatus(),
                'status_label' => $item->labelStatus(),
                'visibilitas' => (string) $item->visibilitas,
                'pakai_kode' => $item->pakaiKode(),
                'kode_akses' => $item->kodeGabung(),
                'saya' => $item->dimilikiOleh($idPengguna),
                /*
                 * Halaman detail quiz menolak quiz yang belum terbit kecuali
                 * untuk pemiliknya, jadi hanya pemilik yang boleh diberi tautan.
                 * Kartu di "Karya Saya" memakai ini untuk menyembunyikan tombol
                 * "Lihat" pada quiz yang belum tayang.
                 */
                'boleh_rujukan' => $item->status === Quiz::STATUS_PUBLISHED
                    || $item->dimilikiOleh($idPengguna),
                'catatan_admin' => $item->catatan_admin,
                'sisa_pengajuan' => $item->sisaPengajuan(),
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
                /*
                 * Tautan dibentuk dari modelnya, bukan dari getKey(): route
                 * model binding memakai kunci utama quiz, jadi
                 * route('user.quiz.detail', $item) menghasilkan URL yang
                 * memuat id quiz, bukan slug judul.
                 */
                'tautan' => route('user.quiz.detail', $item),
                'tautan_edit' => route('user.quiz.edit', $item),
                'tautan_hapus' => route('user.quiz.destroy', $item),
                // Tombol "Mulai Quiz" di halaman detail: pengguna yang boleh
                // membuka quiz ini bisa langsung mulai mengerjakannya sendiri.
                'tautan_mulai' => route('user.quiz.mulai', $item),
                'tautan_mulai_sesi' => route('user.sesi.buka', $item),
            ];
        }

        return $hasil;
    }
}
