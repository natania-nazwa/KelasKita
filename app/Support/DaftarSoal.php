<?php

namespace App\Support;

use App\Models\Soal;

/**
 * Mengubah model Soal menjadi array polos yang sudah berbentuk "siap pakai"
 * untuk komponen tampilan.
 *
 * Bentuknya sengaja dibuat sama persis dengan App\Support\DaftarMateri dan
 * App\Support\DaftarQuiz: komponen di resources/views/components/quiz tidak
 * tahu-menahu soal Eloquent, mereka hanya menerima array. Kalau nanti data soal
 * diambil dari API, cukup ganti isi petakan() tanpa menyentuh markup.
 *
 * Bentuk array per soal:
 *   nomor            = urutan tampil, dimulai dari 1
 *   pertanyaan       = teks soal
 *   pilihan          = ['A' => ..., 'B' => ...], hanya memuat huruf yang
 *                      isinya benar-benar dipakai (lihat Soal::pilihan())
 *   tingkat_kesulitan =MUDAH / Sedang / Sulit, atau string kosong kalau
 *                      kolomnya belum diisi
 *
 * jawaban_benar dan pembahasan SENGAJA tidak ikut dipetakan. Halaman detail
 * quiz menampilkan soal-soalnya sebelum quiz dikerjakan, jadi kunci jawaban
 * tidak boleh ikut terbawa ke sana. Keduanya baru dibutuhkan setelahquiz
 * selesai, dan untuk saat itu sudah ada pemetaan sendiri di
 * App\Support\DaftarHasil.
 */
final class DaftarSoal
{
    /**
     * Petakan sekumpulan soal ke bentuk array yang dipakai komponen.
     *
     * Urutan yang dipakai adalah urutan pemanggil, bukan kolom "urutan" milik
     * model, supaya nomor di tampilan selalu berurutan dari satu walau ada soal
     * yang belum diberi urutan.
     *
     * @param  iterable<int, Soal>  $soal
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $soal): array
    {
        $hasil = [];
        $nomor = 0;

        foreach ($soal as $item) {
            $hasil[] = [
                'nomor' => ++$nomor,
                'pertanyaan' => (string) $item->pertanyaan,
                'pilihan' => $item->pilihan(),
                'tingkat_kesulitan' => (string) ($item->tingkat_kesulitan ?? ''),
            ];
        }

        return $hasil;
    }
}
