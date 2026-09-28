<?php

namespace App\Support;

use App\Models\SesiQuiz;

/**
 * Membuat kode join untuk sesi quiz.
 *
 * Bentuk kode enam karakter: tiga huruf lalu tiga angka, misalnya "ABC123".
 * Panjang enam dipakai supaya ruang tebakan tidak terlalu kecil, dan
 * pola huruf-angka membuat kode enak dibaca pelan-pelan saat dibacakan di
 * depan kelas.
 *
 * Huruf I, O, dan angka 0 sengaja tidak dipakai karena mudah tertukar saat
 * peserta mengetik.
 *
 * Kode yang sudah dipakai sesi lain akan diacak ulang, sehingga biasanya
 * kode selalu langsung bisa dipakai tanpa perlu penanganan benturan.
 */
final class KodeSesi
{
    /** Huruf yang boleh dipakai. */
    private const HURUF = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** Angka yang boleh dipakai. */
    private const ANGKA = '23456789';

    /** Berapa kali mencoba membuat kode baru sebelum menyerah. */
    private const PERCOBAAN_MAKS = 25;

    /** Panjang kode. */
    private const PANJANG = 6;

    /** Berapa huruf di depan kode. Sisanya angka. */
    private const JUMLAH_HURUF = 3;

    /**
     * Kode yang belum dipakai sesi mana pun.
     */
    public static function baru(): string
    {
        for ($percobaan = 0; $percobaan < self::PERCOBAAN_MAKS; $percobaan++) {
            $kode = self::acak();

            if (! SesiQuiz::query()->where('kode', $kode)->exists()) {
                return $kode;
            }
        }

        /*
         * Semua percobaan habis, hampir tidak mungkin terjadi karena kodenya
         * acak. Fallback ini tetap dipakai supaya fungsi selalu mengembalikan
         * kode, dan index unique di database tetap menjadi penjaga terakhir.
         */
        return 'Q'.now()->format('His');
    }

    /**
     * Acak satu kode: huruf di depan, angka di belakang.
     */
    private static function acak(): string
    {
        $huruf = str_shuffle(str_repeat(self::HURUF, 4));
        $angka = str_shuffle(str_repeat(self::ANGKA, 4));

        $kode = '';

        for ($i = 0; $i < self::PANJANG; $i++) {
            $kode .= $i < self::JUMLAH_HURUF ? $huruf[$i] : $angka[$i - self::JUMLAH_HURUF];
        }

        return $kode;
    }
}
