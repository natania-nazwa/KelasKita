<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Quiz;

/**
 * Daftar kelas tujuan untuk materi dan quiz.
 *
 * Satu sumber untuk tiga tempat yang harus menampilkan pilihan kelas dengan
 * isi yang sama: dropdown di form materi, dropdown di form quiz, dan filter
 * "Semua Kelas" di daftar Konten Pembelajaran. Kalau ketiganya mengambil
 * sendiri-sendiri, pilihan di form dan pilihan di filter akan cepat berbeda —
 * dan filter yang tidak bisa memilih kelas yang baru saja dipilih di form
 * terlihat seperti filternya rusak.
 *
 * Daftar diambil dari nilai yang benar-benar ada di kedua tabel, bukan dari
 * tabel master. Alasannya kelas di sekolah memang berubah sewaktu-waktu, dan
 * memaksa konten lama ikut memakai daftar yang lebih baru akan berarti konten
 * yang sudah terbit diam-diam kehilangan kelasnya.
 *
 * BAWAAN dipakai supaya dropdown tidak pernah kosong di instalasi yang belum
 * punya satu pun materi atau quiz bertanda kelas. Nilai yang sama persis juga
 * ikut dibaca dari database, jadi begitu dipakai daftar ini menyatu sendiri
 * tanpa perlu disimpan ulang di mana pun.
 */
final class KelasKonten
{
    /**
     * Kelas bawaan yang selalu bisa dipilih.
     *
     * Hanya tiga, dan hanya supaya form dan filter tidak tampak rusak saat
     * belum ada konten bertanda kelas. Kelas lain tetap bisa dipilih dan
     * langsung muncul di kedua dropdown.
     *
     * @var array<int, string>
     */
    public const BAWAAN = ['RPL 1', 'RPL 2', 'RPL 3'];

    /**
     * Semua kelas yang bisa dipilih, sebagai nilai => label.
     *
     * Nilai dan label sengaja sama: kelas disimpan persis seperti yang
     * diketik admin, jadi tidak ada dua bentuk nama untuk satu kelas yang
     * bisa berbeda kapitalisasi atau spasi.
     *
     * Urutannya: dulu bawaan, lalu kelas lain yang benar-benar dipakai,
     * semuanya diurutkan sehingga "RPL 2" tidak mendahului "RPL 10" hanya
     * karena satu karakter lebih pendek.
     *
     * @return array<string, string>
     */
    public static function pilihan(): array
    {
        $daftar = array_values(array_unique(array_merge(self::BAWAAN, self::terpakai())));

        /*
         * Urut natural: strnatcasecmp membandingkan "RPL 2" dengan "RPL 10"
         * sebagai angka, jadi 2 muncul sebelum 10. Perbandingan string biasa
         * akan membalik keduanya.
         */
        usort($daftar, fn (string $a, string $b): int => strnatcasecmp($a, $b));

        $pilihan = [];

        foreach ($daftar as $kelas) {
            $pilihan[$kelas] = $kelas;
        }

        return $pilihan;
    }

    /**
     * Kelas yang benar-benar dipakai materi atau quiz, huruf besar-kecil
     * diperlakukan sama supaya tidak ada dua entri untuk satu kelas.
     *
     * Dua query, bukan satu: materi dan quiz ada di dua tabel terpisah.
     * Penyamaan kapitalisasi dilakukan di sini karena SELECT DISTINCT
     * mengembalikan nilai aslinya yang bisa berbeda kapitalisasi dari input
     * filter, dan dua-duanya akan tampil dua kali di dropdown.
     *
     * @return array<int, string>
     */
    private static function terpakai(): array
    {
        $hasil = [];

        foreach ([Materi::class, Quiz::class] as $model) {
            $baris = $model::query()
                ->whereNotNull('kelas')
                ->where('kelas', '<>', '')
                ->distinct()
                ->pluck('kelas');

            foreach ($baris as $kelas) {
                $kelas = trim((string) $kelas);

                if ($kelas !== '') {
                    $hasil[strtolower($kelas)] = $kelas;
                }
            }
        }

        return array_values($hasil);
    }
}
