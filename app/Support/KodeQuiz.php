<?php

namespace App\Support;

use App\Models\Quiz;

/**
 * Kode akses quiz (kode yang diketik peserta untuk membuka quiz privat).
 *
 * Abjad dan panjangnya disimpan di sini, lalu dikirim ke
 * resources/js/quiz-tambah.js lewat atribut data- pada halaman form.
 * Tombol "Generate Kode" di sisi browser lalu memakai abjad yang sama,
 * jadi kode yang dibuat di layar dan kode yang dibuat di server tidak
 * bisa berbeda aturan.
 */
final class KodeQuiz
{
    /**
     * Abjad yang dipakai untuk kode.
     *
     * Huruf I, L, O, dan angka 0 serta 1 sengaja tidak ada karena mudah
     * tertukar saat dibaca atau diketik peserta.
     */
    public const ABJAD = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Jumlah karakter kode yang dihasilkan. */
    public const PANJANG = 6;

    /**
     * Bentuk baku kode: huruf besar, tanpa spasi, tanda hubung, dan garis
     * bawah.
     *
     * Ini satu-satunya aturan normalisasi kode gabung, dipakai quiz dan sesi
     * sekaligus. Dipisah di satu tempat supaya kode yang diketik peserta,
     * kode yang disimpan di tb_quiz.kode_akses, dan kode yang tampil di lobby
     * tidak bisa berbeda bentuk.
     */
    public static function normalisasi(?string $kode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $kode) ?? '');
    }

    /**
     * Kode acak sepanjang PANJANG tanpa memeriksa database.
     *
     * Dipakai controller untuk mengisi kolom kode supaya form tidak pernah
     * terbuka dengan kotak kosong, dan aturan yang sama dikirim ke
     * JavaScript lewat App\Support\KodeQuiz::ABJAD.
     */
    public static function acak(int $panjang = self::PANJANG): string
    {
        $abjad = str_split(self::ABJAD);
        $kode = '';

        for ($i = 0; $i < $panjang; $i++) {
            $kode .= $abjad[random_int(0, count($abjad) - 1)];
        }

        return $kode;
    }

    /**
     * Kode acak yang belum dipakai quiz lain.
     *
     * tb_quiz.kode_akses punya unique index, jadi kodenya dicek berulang
     * kali sampai benar-benar belum ada baris yang memakainya.
     */
    public static function unik(int $panjang = self::PANJANG): string
    {
        for ($percobaan = 0; $percobaan < 10; $percobaan++) {
            $kode = self::acak($panjang);

            if (! Quiz::query()->where('kode_akses', $kode)->exists()) {
                return $kode;
            }
        }

        // Kalau sepuluh tebakan sudah dipakai, kode terakhir dikembalikan
        // supaya form tetap punya isi; validasi unik di server tetap
        // menolak kode yang benar-benar menabrak.
        return self::acak($panjang);
    }
}
