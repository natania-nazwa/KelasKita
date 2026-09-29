<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan soal-soal milik sebuah quiz.
 *
 * Satu helper untuk dua pemakai: quiz yang baru dibuat (belum punya soal)
 * dan quiz yang sedang diedit (soal lamanya diganti seluruhnya). Karena itu
 * penghapusan dan penambahan sengaja dibungkus satu transaksi supaya quiz
 * tidak pernah tertinggal tanpa soal di tengah proses.
 *
 * Setiap baris form bisa bertipe berbeda (lihat App\Models\Soal::tipeTersedia),
 * jadi cara menyimpannya ikut berbeda:
 *
 *   - tipe berdaftar (pilihan ganda, pilihan banyak, benar/salah,
 *     dropdown) menyimpan barisnya sebagai pilihan di tb_soal_pilihan,
 *     dan huruf yang ditandai benar disimpan lewat kolom "benar" per baris;
 *   - tipe teks (jawaban singkat, paragraf) menyimpan kuncinya di
 *     tb_soal.jawaban_teks dan tidak punya pilihan sama sekali.
 *
 * Kolom pilihan_a sampai pilihan_d di tb_soal tetap diisi, dan hanya itu,
 * karena kolomnya NOT NULL dan sudah dipakai pembaca lama. Isian itu bukan
 * sumber kebenaran lagi; tb_soal_pilihan yang jadi acuan.
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

                $urutan++;

                $simpan = self::baris($baris, $urutan);

                $soalTersimpan = $quiz->soal()->create($simpan['soal']);

                foreach ($simpan['pilihan'] as $pilihan) {
                    $soalTersimpan->pilihanSoal()->create($pilihan);
                }
            }
        });
    }

    /**
     * Pecah satu baris form menjadi data kolom tb_soal dan baris-baris
     * tb_soal_pilihan-nya.
     *
     * @param  array<string, mixed>  $baris
     * @return array{soal: array<string, mixed>, pilihan: array<int, array<string, mixed>>}
     */
    private static function baris(array $baris, int $urutan): array
    {
        $tipe = Soal::normalkanTipe($baris['tipe'] ?? null);

        $pakaiPilihan = in_array($tipe, [
            Soal::TIPE_PILIHAN_GANDA,
            Soal::TIPE_PILIHAN_BANYAK,
            Soal::TIPE_BENAR_SALAH,
            Soal::TIPE_DROPDOWN,
        ], true);

        /*
         * Pilihan yang teksnya kosong dibuang, dan huruf yang ditandai benar
         * hanya boleh menunjuk pilihan yang memang ada. Tanpa filter terakhir
         * ini, jawaban benar bisa menunjuk pilihan yang sudah dihapus.
         */
        $masukanPilihan = (array) ($baris['pilihan'] ?? []);

        /*
         * Tipe Benar / Salah selalu berisi dua pilihan yang sama. Form
         * builder mengirim keduanya, tapi kalau isian datang dari luar
         * (format lama, kiriman API) daftarnya dibuat dari sini supaya soal
         * tidak pernah tersimpan tanpa pilihan sama sekali.
         */
        if ($tipe === Soal::TIPE_BENAR_SALAH
            && array_filter($masukanPilihan, fn ($teks) => trim((string) $teks) !== '') === []
        ) {
            $masukanPilihan = array_combine(['A', 'B'], Soal::PILIHAN_BENAR_SALAH);
        }

        $pilihan = [];

        foreach ($masukanPilihan as $huruf => $teks) {
            $huruf = strtoupper(trim((string) $huruf));
            $teks = trim((string) $teks);

            if ($teks === '' || ! in_array($huruf, Soal::hurufTersedia(), true)) {
                continue;
            }

            $pilihan[$huruf] = $teks;
        }

        ksort($pilihan);

        $benar = array_values(array_intersect(
            array_map('strval', (array) ($baris['benar'] ?? [])),
            array_keys($pilihan),
        ));

        $hurufBenar = $benar[0] ?? Soal::hurufTersedia()[0];

        /*
         * Kolom pilihan_a sampai pilihan_d NOT NULL, jadi harus selalu diisi
         * string. Untuk tipe teks tidak ada pilihan sama sekali, maka semuanya
         * string kosong.
         */
        $cadangan = [];

        foreach (['A', 'B', 'C', 'D'] as $huruf) {
            $cadangan[Soal::kolomPilihan($huruf)] = $pakaiPilihan
                ? ($pilihan[$huruf] ?? '')
                : '';
        }

        $barisPilihan = [];
        $posisi = 0;

        foreach ($pilihan as $huruf => $teks) {
            $barisPilihan[] = [
                'huruf' => $huruf,
                'teks' => $teks,
                'urutan' => ++$posisi,
                'benar' => in_array($huruf, $benar, true),
            ];
        }

        return [
            'soal' => [
                'pertanyaan' => $baris['pertanyaan'],
                'tipe' => $tipe,
                ...$cadangan,
                /*
                 * Kolom ini varchar(1) jadi hanya muat satu huruf. Untuk tipe
                 * pilihan banyak, huruf pertama yang benar yang disimpan di
                 * sini; daftar lengkapnya tetap ada di tb_soal_pilihan.
                 */
                'jawaban_benar' => $hurufBenar,
                'jawaban_teks' => $pakaiPilihan ? null : ($baris['jawaban_teks'] ?? null),
                'tococok_persis' => filter_var(
                    $baris['tococok_persis'] ?? true,
                    FILTER_VALIDATE_BOOLEAN,
                ),
                'pembahasan' => $baris['pembahasan'] ?? null,
                'urutan' => $urutan,
                'tingkat_kesulitan' => $baris['tingkat_kesulitan'] ?? Quiz::TINGKAT_MUDAH,
                'aktif' => true,
            ],
            'pilihan' => $barisPilihan,
        ];
    }
}
