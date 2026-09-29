<?php

namespace App\Support;

use App\Models\Soal;

/**
 * Satu-satunya tempat memutuskan benar atau salahnya sebuah jawaban.
 *
 * Enam tipe soal, lima aturan berbeda, dan semuanya dikumpulkan di
 * sini supaya tidak ada file lain yang perlu tahu bedanya:
 *
 *   multiple_choice  satu huruf harus sama dengan salah satu kunci.
 *   dropdown         sama seperti multiple_choice; bedanya cuma bentuk
 *                    antarmukanya, bukan cara menilai.
 *   true_false       sama seperti multiple_choice; pilihannya selalu
 *                    "Benar" dan "Salah", jadi tidak ada yang bisa
 *                    berubah bentuk di tengah jalan.
 *   multiple_select  himpunan huruf peserta harus sama persis dengan
 *                    himpunan kunci. Satu huruf lebih atau kurang sudah
 *                    salah, karena soal seperti "pilih semua yang benar"
 *                    tidak bisa dihitung sebagian benar.
 *   short_answer     teks dibandingkan dengan kunci teks soal. Saklar
 *                    "sama persis" di builder menentukan seberapa ketat
 *                    perbandingannya.
 *   paragraph        belum dinilai otomatis. Jawaban peserta tetap
 *                    disimpan utuh supaya bisa dinilai guru nanti, tapi
 *                    tidak dihitung benar maupun salah dulu.
 *
 * Yang dikembalikan sudah berbentuk kolom tb_jawaban_quiz, jadi
 * SesiKerjakanController tinggal menyimpan hasilnya tanpa perlu tahu
 * aturan penilaian masing-masing tipe.
 */
final class Penilaian
{
    /**
     * Nilai satu jawaban peserta terhadap satu soal.
     *
     * @param  array{jawaban?: mixed, jawaban_teks?: mixed}  $input  Isian yang lolos validasi.
     * @return array{jawaban_dipilih: ?string, jawaban_teks: ?string, jawaban_benar: ?string, benar: bool, dinilai: bool}
     */
    public static function nilai(Soal $soal, array $input): array
    {
        return $soal->tipeTeks()
            ? self::teks($soal, $input)
            : self::pilihan($soal, $input);
    }

    /**
     * Tipe yang punya daftar pilihan: pilihan ganda, pilihan banyak,
     * benar/salah, dan dropdown.
     *
     * @param  array{jawaban?: mixed}  $input
     * @return array{jawaban_dipilih: ?string, jawaban_teks: ?string, jawaban_benar: ?string, benar: bool, dinilai: bool}
     */
    private static function pilihan(Soal $soal, array $input): array
    {
        $hurufBenar = $soal->hurufBenar();

        $dipilih = $soal->tipeBanyakBenar()
            ? self::huruf(array_filter((array) ($input['jawaban'] ?? []), 'is_string'))
            : [(string) ($input['jawaban'] ?? '')];

        // Huruf yang tidak ada di soal ini dibuang, bukan ditolak. Validasi
        // sudah menahannya di sisi server; di sini dibuang lagi supaya
        // kunci jawaban yang sudah diubah setelah peserta menjawab tidak
        // membuat perbandingan gagal diam-diam.
        $pilihanSoal = array_keys($soal->pilihan());
        $dipilih = array_values(array_filter(
            $dipilih,
            fn (string $huruf) => in_array($huruf, $pilihanSoal, true)
        ));

        sort($dipilih);

        $samaDenganKunci = $dipilih !== [] && $dipilih === self::urut($hurufBenar);

        return [
            // Tanpa pemisah, bukan "A,C": sepuluh pilihan yang semuanya
            // dicentang jadi "ABCDEFGHIJ" (sepuluh karakter), masih muat
            // di varchar(16) yang tersedia.
            'jawaban_dipilih' => $dipilih === [] ? null : implode('', $dipilih),
            'jawaban_teks' => null,
            'jawaban_benar' => $hurufBenar[0] ?? '',
            'benar' => $samaDenganKunci,
            'dinilai' => true,
        ];
    }

    /**
     * Tipe yang jawabannya teks bebas: jawaban singkat dan paragraf.
     *
     * @param  array{jawaban_teks?: mixed}  $input
     * @return array{jawaban_dipilih: ?string, jawaban_teks: ?string, jawaban_benar: ?string, benar: bool, dinilai: bool}
     */
    private static function teks(Soal $soal, array $input): array
    {
        $jawaban = trim((string) ($input['jawaban_teks'] ?? ''));
        $kunci = $soal->kunciTeks();

        /*
         * Paragraf tidak dinilai otomatis. Menandainya benar atau salah
         * tanpa bacaan manusia akan mencabut nilai peserta, jadi
         * "dinilai" false supaya hitungannya tidak ikut masuk benar maupun
         * salah, sementara teks jawabannya tetap tersimpan utuh.
         */
        if ($soal->tipe() === Soal::TIPE_PARAGRAF) {
            return [
                'jawaban_dipilih' => null,
                'jawaban_teks' => $jawaban,
                // Kolom ini varchar(1) dan NOT NULL. String kosong dipakai
                // sebagai tanda "tidak ada kunci huruf", karena kunci teksnya
                // ada di tb_soal.jawaban_teks dan tidak perlu disalin ke sini.
                'jawaban_benar' => '',
                'benar' => false,
                'dinilai' => false,
            ];
        }

        return [
            'jawaban_dipilih' => null,
            'jawaban_teks' => $jawaban,
            'jawaban_benar' => '',
            'benar' => self::teksCocok($jawaban, $kunci, (bool) $soal->tococok_persis),
            'dinilai' => true,
        ];
    }

    /**
     * Apakah jawaban teks peserta sama dengan kunci.
     *
     * Mode persis membandingkan apa adanya setelah spasi ujungnya dirapikan.
     * Mode longgar dibuat lebih longgar: huruf jadi kecil, spasi berulang
     * jadi satu, dan tanda baca diabaikan, jadi "Cascading Style Sheets."
     * tetap dianggap sama dengan "cascading style sheets".
     */
    public static function teksCocok(string $jawaban, string $kunci, bool $persis): bool
    {
        if ($persis) {
            return trim($jawaban) === trim($kunci);
        }

        return self::rapikan($jawaban) === self::rapikan($kunci);
    }

    /**
     * Bentuk baku untuk perbandingan longgar.
     */
    private static function rapikan(string $teks): string
    {
        $hurufKecil = mb_strtolower(trim($teks));

        // Tanda baca dan spasi berulang dibuang, supaya "HTML, CSS" dan
        // "html css" tidak terbaca sebagai dua jawaban berbeda.
        $tanpaTanda = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $hurufKecil) ?? $hurufKecil;

        return trim(preg_replace('/\s+/u', ' ', $tanpaTanda) ?? $tanpaTanda);
    }

    /**
     * Huruf peserta yang sudah dibersihkan, unik, dan terurut.
     *
     * @param  array<int, string>  $huruf
     * @return array<int, string>
     */
    private static function huruf(array $huruf): array
    {
        $sah = array_values(array_filter(
            $huruf,
            fn (string $satu) => in_array($satu, Soal::hurufTersedia(), true)
        ));

        $unik = array_values(array_unique($sah));

        sort($unik);

        return $unik;
    }

    /**
     * Kunci jawaban sebagai huruf yang unik dan terurut, supaya dibandingkan
     * sebagai himpunan dan bukan sebagai string yang urutannya berpengaruh.
     *
     * @param  array<int, string>  $huruf
     * @return array<int, string>
     */
    private static function urut(array $huruf): array
    {
        $unik = array_values(array_unique($huruf));

        sort($unik);

        return $unik;
    }
}
