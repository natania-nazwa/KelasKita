<?php

namespace App\Support;

use App\Models\Pelajaran;
use App\Models\Quiz;

/**
 * Mengubah model Quiz menjadi array polos untuk halaman "Tinjau Quiz" di area
 * admin.
 *
 * Bentuknya mengikuti TinjauanMateri, tapi ditambah hal-hal yang hanya
 * dibutuhkan saat menilai sebuah quiz: jumlah soalnya, cara aksesnya, alasan
 * penolakan sebelumnya, dan catatan pengajuan ulang dari pemilik.
 */
final class TinjauanQuiz
{
    /**
     * Jumlah quiz per halaman.
     *
     * Sepuluh: satu baris review dibuat cukup tinggi karena tiap baris bisa
     * memuat daftar soal dan dua tombol keputusan, jadi sepuluh baris sudah
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
            Quiz::STATUS_PENDING => 'Menunggu Persetujuan',
            Quiz::STATUS_REJECTED => 'Ditolak',
            Quiz::STATUS_PUBLISHED => 'Dipublikasikan',
            Quiz::STATUS_DRAFT => 'Draft',
        ];
    }

    /**
     * Petakan sekumpulan quiz ke bentuk array yang dipakai tabel review.
     *
     * Bentuk array per baris:
     *   id, judul, deskripsi, tingkat_kesulitan, durasi, jumlah_soal,
     *   visibilitas, pakai_kode, status, warna_status, status_label,
     *   catatan_admin, catatan_pengajuan, jumlah_ditolak,
     *   sisa_pengajuan, dibuat_pada, tautan_setujui, tautan_tolak,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     *
     * "visibilitas" dan "pakai_kode" dipakai untuk lencana mode di
     * halaman admin: quiz mode kode tidak pernah butuh persetujuan
     * admin, jadi tampilan harus bisa membedakan keduanya tanpa
     * membuat admin mengira quiz mode kode juga sedang menunggu.
     *
     * @param  iterable<int, Quiz>  $quiz
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $quiz): array
    {
        $hasil = [];

        foreach ($quiz as $item) {
            $pelajaran = $item->pelajaran;
            $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
            $pembuat = $item->pembuat;
            $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

            $hasil[] = [
                'id' => $item->getKey(),
                'judul' => $item->judul,
                'deskripsi' => (string) $item->deskripsi,
                'tingkat_kesulitan' => (string) $item->tingkat_kesulitan,
                'durasi' => (int) ($item->durasi ?? 0),
                'jumlah_soal' => $item->jumlahSoal(),
                'visibilitas' => (string) $item->visibilitas,
                'pakai_kode' => $item->pakaiKode(),
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
                'tautan_setujui' => route('admin.quiz.setujui', $item),
                'tautan_tolak' => route('admin.quiz.tolak', $item),
            ];
        }

        return $hasil;
    }
}
