<?php

namespace App\Support;

/**
 * Format angka untuk tampilan, memakai penulisan Indonesia.
 *
 * Dua kebutuhan halaman Hasil:
 *   1. Angka desimal dengan koma, misalnya 80.5 ditulis "80,5", dan
 *      nol di belakang koma dibuang supaya "80,0" jadi "80".
 *   2. Geometri lingkaran untuk progress indicator, supaya lingkaran
 *      kecil di baris riwayat dan donat di sidebar memakai rumus sama.
 *
 * Dipisah dari view supaya tidak ada trigonometri yang ditulis ulang di
 * beberapa berkas Blade.
 */
final class Angka
{
    /**
     * Tulis angka dengan pemisah desimal koma dan tanpa nol di belakang
     * koma.
     *
     * Contoh: 80.0 => "80", 85.25 => "85,3", 100 => "100".
     */
    public static function teks(float $nilai, int $desimal = 1): string
    {
        $teks = number_format($nilai, $desimal, ',', '');

        if ($desimal > 0) {
            $teks = rtrim(rtrim($teks, '0'), ',');
        }

        return $teks === '' || $teks === '-' ? '0' : $teks;
    }

    /**
     * Angka pendek untuk metadata, misalnya jumlah tampilan materi.
     *
     * 999 => "999", 2400 => "2.4k", 12500 => "12.5k", 2400000 => "2.4jt".
     * Angka di bawah sepuluh ribu tidak dipotong supaya jarak pandangnya
     * tetap terasa nyata ("128 tampilan" lebih jujur daripada "128").
     */
    public static function ringkas(int $nilai): string
    {
        $nilai = max(0, $nilai);

        if ($nilai < 1000) {
            return (string) $nilai;
        }

        if ($nilai < 1_000_000) {
            return self::pendek($nilai / 1000).'k';
        }

        return self::pendek($nilai / 1_000_000).'jt';
    }

    /**
     * Satu angka desimal tanpa nol di belakang koma: 2.0 => "2", 2.4 => "2.4".
     */
    private static function pendek(float $nilai): string
    {
        return rtrim(rtrim(number_format($nilai, 1, '.', ''), '0'), '.');
    }

    /**
     * Panjang busur lingkaran untuk persentase tertentu.
     *
     * Keliling lingkaran (2 * PI * r) dikalikan persentase, lalu
     * dipotong dari lingkaran atas (12 o'clock) dan bertambah searah
     * jarum jam. Markup hasilnya memakai stroke-dasharray.
     *
     * Persentase selalu dijepit di 0-100 supaya nilai rusak dari database
     * tidak membuat busur keluar dari lingkaran.
     *
     * @return array{persen: float, jari: float, keliling: float, panjang: float, teks: string}
     */
    public static function cincin(float $persen, float $jari = 54.0): array
    {
        $persen = max(0.0, min(100.0, $persen));
        $keliling = 2 * M_PI * $jari;

        return [
            'persen' => $persen,
            'jari' => $jari,
            'keliling' => round($keliling, 2),
            'panjang' => round($keliling * ($persen / 100), 2),
            'teks' => self::teks($persen),
        ];
    }

    /**
     * Ubah selisih menit menjadi label waktu yang enak dibaca.
     *
     * Dipakai kartu "Waktu Belajar" dan pembanding mingguannya, jadi
     * keduanya menulis "1,3 jam" atau "45 menit" dengan aturan sama.
     */
    public static function durasi(float $menit): string
    {
        if ($menit >= 60) {
            return self::teks($menit / 60).' jam';
        }

        return self::teks($menit, 0).' menit';
    }

    /**
     * Hitung mundur dalam bentuk MM:SS.
     *
     * Dipakai timer di halaman mengerjakan quiz, jadi jam selalu dua digit
     * dan menit tidak pernah melebihi 59: 75 detik ditulis "01:15", bukan
     * "75" dan bukan "60:15". Nilai negatif dijepit ke "00:00" supaya
     * waktu yang sudah lewat tidak pernah tampil sebagai angka ganjil.
     */
    public static function waktu(int $detik): string
    {
        $detik = max(0, $detik);

        return sprintf('%02d:%02d', intdiv($detik, 60), $detik % 60);
    }
}
