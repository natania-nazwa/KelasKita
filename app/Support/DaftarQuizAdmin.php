<?php

namespace App\Support;

use App\Models\Pelajaran;
use App\Models\Quiz;

/**
 * Mengubah model Quiz menjadi array polos untuk halaman "Quiz" di area
 * admin.
 *
 * Sepadan dengan App\Support\DaftarMateriAdmin: keduanya memetakan baris
 * untuk kartu di halaman daftar admin, keduanya hanya managing konten yang
 * sudah terbit, dan keduanya tidak mengikutsertakan isi quiz. Isinya dibaca
 * di halaman detail, yang memakai pemecah milik pengguna supaya admin
 * membaca quiz persis seperti membacanya pengguna.
 *
 * Bedanya dengan App\Support\TinjauanQuiz, yang lama: itu pemetaan untuk
 * halaman keputusan (kartu ringkas di panel tinjau, lengkap dengan catatan
 * penolakan dan tautan setujui/tolak). Halaman ini tidak punya keputusan
 * apa pun, jadi yang dipetakan hanya metadata untuk kartu.
 */
final class DaftarQuizAdmin
{
    /**
     * Jumlah quiz per halaman.
     *
     * Dua puluh, sama seperti DaftarMateriAdmin::perHalaman() dan
     * DaftarMateri::perHalaman(). Daftarnya grid empat kolom, dan 20 = 5
     * baris penuh: tidak pernah ada kartu yatim di baris terakhir. Memakai
     * angka yang sama dengan halaman Materi juga membuat jumlah halaman di
     * kedua halaman itu langsung bisa dibandingkan.
     */
    public static function perHalaman(): int
    {
        return 20;
    }

    /**
     * Petakan sekumpulan quiz ke bentuk array yang dipakai kartu di daftar.
     *
     * Bentuk array per baris:
     *   id, slug, judul, thumbnail, tingkat_kesulitan, durasi,
     *   durasi_label, jumlah_soal, visibilitas, pakai_kode,
     *   tampilkan_jawaban, tanggal => Carbon, tanggal_label, tanggal_jam,
     *   status, status_label, boleh_edit,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap],
     *   tautan_detail, tautan_edit, tautan_hapus
     *
     * tidak ada deskripsi quiz di sini: kartu di daftar admin tidak
     * menampilkannya (isi lengkapnya ada di halaman detail), jadi memetakannya
     * hanya menambah pekerjaan tanpa dipakai.
     *
     * Tanggal yang ditampilkan adalah tanggal terbit kalau quiz sudah tayang,
     * bukan tanggal pembuatannya: di halaman ini yang relevan adalah kapan
     * quiz mulai dikerjakan pembacanya.
     *
     * @param  iterable<int, Quiz>  $quiz
     * @param  int|null  $idAdmin  id admin yang sedang masuk, dipakai untuk
     *                             menentukan boleh_edit. Null = tidak ada
     *                             admin yang bisa diedit, jadi tidak satu
     *                             pun baris menampilkan tombol Edit.
     * @return array<int, array<string, mixed>>
     */
    public static function petikan(iterable $quiz, ?int $idAdmin = null): array
    {
        $hasil = [];

        foreach ($quiz as $item) {
            $hasil[] = self::baris($item, $idAdmin);
        }

        return $hasil;
    }

    /**
     * Tautan aksi untuk satu quiz, dipakai kartu di daftar.
     *
     * @return array{tautan_detail: string, tautan_edit: string, tautan_hapus: string}
     */
    public static function tautan(Quiz $item): array
    {
        return [
            'tautan_detail' => route('admin.quiz.show', $item),
            'tautan_edit' => route('admin.quiz.edit', $item),
            'tautan_hapus' => route('admin.quiz.destroy', $item),
        ];
    }

    /**
     * Satu baris daftar.
     *
     * @return array<string, mixed>
     */
    private static function baris(Quiz $item, ?int $idAdmin = null): array
    {
        $pelajaran = $item->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
        $pembuat = $item->pembuat;
        $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];
        $terbit = $item->dipublish_pada ?? $item->created_at;
        $durasi = (int) ($item->durasi ?? 0);

        return [
            'id' => $item->getKey(),
            'slug' => $item->slug,
            'judul' => $item->judul,
            'thumbnail' => BerkasQuiz::url($item->thumbnail),
            'tingkat_kesulitan' => (string) $item->tingkat_kesulitan,
            'durasi' => $durasi,

            /*
             * Bentuk label sudah disiapkan di sini, bukan di komponen, karena
             * teks yang sama dipakai di kartu, di dialog hapus, dan di
             * empty state. "Tanpa batas waktu" ditulis penuh, bukan "0 menit":
             * nol di sini berarti tidak dibatasi, dan menulisnya sebagai angka
             * akan menyesatkan.
             */
            'durasi_label' => $durasi > 0 ? $durasi.' menit' : 'Tanpa batas waktu',
            'jumlah_soal' => $item->jumlahSoal(),
            'visibilitas' => (string) $item->visibilitas,
            'pakai_kode' => $item->pakaiKode(),
            'tampilkan_jawaban' => $item->menampilkanJawaban(),
            'tanggal' => $terbit,
            'tanggal_label' => DetailMateri::tanggal($terbit),
            'tanggal_jam' => $terbit?->format('H:i'),
            'status' => (string) $item->status,
            'status_label' => $item->labelStatus(),

            /*
             * Edit hanya untuk quiz yang dibuat admin yang sedang login. Quiz
             * buatan pengguna lain tetap bisa dibaca dan dihapus dari sini,
             * tapi tidak bisa diedit: soalnya milik penulisnya, dan supaya
             * menu ini tidak menawarkan sesuatu yang akan ditolak 403 begitu
             * diklik. Aturan yang sama ditegakkan ulang oleh
             * Admin\QuizKelolaController, bukan hanya disembunyikan di UI.
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
