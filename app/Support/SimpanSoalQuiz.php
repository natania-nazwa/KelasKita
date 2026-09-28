<?php

namespace App\Support;

use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan soal-soal milik sebuah quiz.
 *
 * Satu helper untuk dua pemakai: quiz yang baru dibuat (belum punya soal)
 * dan quiz yang sedang diedit (soal lamanya diganti seluruhnya). Karena itu
 * penghapusan dan penambahan sengaja dibungkus satu transaksi supaya quiz
 * tidak pernah tertinggal tanpa soal di tengah proses.
 */
final class SimpanSoalQuiz
{
    /**
     * Ganti seluruh soal sebuah quiz dengan daftar baru.
     *
     * Urutan diambil dari posisi baris di form, bukan dari nama field, jadi
     * baris yang dihapus di tengah tidak membuat nomor meleset.
     *
     * @param  array<int, array<string, mixed>>  $soal
     */
    public static function ganti(Quiz $quiz, array $soal): void
    {
        DB::transaction(function () use ($quiz, $soal) {
            $quiz->soal()->delete();

            $urutan = 0;

            foreach ($soal as $baris) {
                if (! is_array($baris) || blank($baris['pertanyaan'] ?? null)) {
                    continue;
                }

                $quiz->soal()->create([
                    'pertanyaan' => $baris['pertanyaan'],
                    'pilihan_a' => $baris['pilihan_a'] ?? '',
                    'pilihan_b' => $baris['pilihan_b'] ?? '',
                    'pilihan_c' => $baris['pilihan_c'] ?? '',
                    'pilihan_d' => $baris['pilihan_d'] ?? '',
                    'jawaban_benar' => $baris['jawaban_benar'] ?? 'A',
                    'pembahasan' => $baris['pembahasan'] ?? null,
                    'urutan' => ++$urutan,
                    'tingkat_kesulitan' => $baris['tingkat_kesulitan'] ?? 'Mudah',
                    'aktif' => true,
                ]);
            }
        });
    }
}
