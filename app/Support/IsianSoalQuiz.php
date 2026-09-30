<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\Soal;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Support\Collection;

/**
 * Menerjemahkan soal yang sudah tersimpan menjadi baris isian untuk wizard
 * "Buat Quiz" dan "Edit Quiz".
 *
 * Dipakai oleh dua halaman yang memakai wizard yang sama: form milik
 * pengguna (user.quiz-tambah) dan form admin (admin.quiz-edit). Keduanya
 * membuka builder soal dengan isian yang persis sama — kalau salah satunya
 * punya versinya sendiri, quiz yang sama akan tampil berbeda di dua tempat
 * hanya karena dibuka lewat form yang berbeda.
 *
 * Dua hal yang dikembalikan di sini, bukan di markup:
 *
 *   1. Baris isian per soal, dikirim sebagai JSON ke quiz-builder.js. Yang
 *      sengaja ikut serta adalah pembahasan dan tingkat kesulitan per soal:
 *      keduanya tidak tampil di kartu soal, jadi kalau tidak dikirim di sini
 *      keduanya akan hilang begitu form dibuka.
 *
 *   2. Langkah mana yang langsung dibuka setelah validasi server menolak
 *      kiriman. Tanpa ini validate selalu melempar ke langkah pertama walau
 *      masalahnya ada di Pengaturan.
 */
final class IsianSoalQuiz
{
    /**
     * Isian awal tiap soal, untuk dikirim ke builder.
     *
     * Urutannya penting: nomor soal adalah urutan baris di daftar, bukan
     * kolom tersendiri (lihat components/quiz/wizard-soal). Karena itu
     * collection di sini selalu di-reset supaya indeksnya mulai dari 0.
     *
     * @param  iterable<int, Soal>|null  $soal  null saat membuat quiz baru,
     *                                          yang tidak punya soal tersimpan
     * @return array<int, array<string, mixed>>
     */
    public static function baris(?iterable $soal): array
    {
        if ($soal === null) {
            return [];
        }

        return Collection::make($soal)
            ->map(fn (Soal $item) => [
                'pertanyaan' => $item->pertanyaan,
                'tipe' => $item->tipe(),
                'pilihan' => array_values(array_map(
                    fn ($huruf, $teks) => ['huruf' => $huruf, 'teks' => $teks],
                    array_keys($item->pilihan()),
                    array_values($item->pilihan()),
                )),
                'benar' => $item->hurufBenar(),
                'kunciTeks' => $item->kunciTeks(),
                'ceklis' => $item->tococok_persis,
                'pembahasan' => (string) $item->pembahasan,
                'tingkat' => $item->tingkat_kesulitan ?? Quiz::TINGKAT_MUDAH,
            ])
            ->values()
            ->all();
    }

    /**
     * Langkah yang langsung dibuka: 2 kalau galatnya milik soal, selain itu
     * 1 — tempat semua isian selain soal dikumpulkan.
     *
     * Galat soal bisa muncul sebagai kunci "soal" (mis. "Minimal satu soal
     * harus diisi") maupun sebagai "soal.0.pertanyaan", jadi keduanya dicek.
     *
     * @param  MessageProvider  $errors
     */
    public static function langkahAwal($errors): int
    {
        return $errors->has('soal')
            || collect($errors->keys())->contains(fn ($kunci) => str_starts_with((string) $kunci, 'soal.'))
            ? 2
            : 1;
    }
}
