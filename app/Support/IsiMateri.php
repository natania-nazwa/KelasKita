<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mengubah isi materi (kolom tb_materi.isi) menjadi struktur seksi + blok
 * yang bisa langsung dirender halaman detail.
 *
 * Isi materi disimpan sebagai teks polos supaya admin bisa mengetiknya
 * tanpa alat edit khusus. Supaya teks polos itu tetap bisa jadi halaman
 * baca yang enak, IsiMateri mengenali beberapa penanda sederhana:
 *
 *   # Judul Seksi      -> seksi baru (bikin entri Daftar Isi + id untuk scroll)
 *   Bab N: Judul       -> seksi baru, penanda yang ditulis form Tambah Materi
 *   ## Subjudul        -> subjudul di dalam seksi
 *   ```bahasa         -> blok kode, ditutup ``` di baris berikutnya
 *   - butir            -> daftar berbutir
 *   > catatan          -> kotak catatan
 *   teks biasa         -> paragraf, **tebal** dan `kode` tetap dikenali
 *
 * Materi yang tidak memakai penanda apa pun (semua materi lama) tetap
 * aman: seluruh teksnya jadi satu seksi, jadi tidak ada halaman kosong.
 */
final class IsiMateri
{
    /**
     * Kata kunci pada judul seksi yang menandai bagian latihan. Seksi
     * seperti ini dirender sebagai kartu latihan, bukan paragraf biasa.
     *
     * Dibatasi tanda kata supaya judul seperti "Pengujian dan Deployment"
     * tidak ikut terbaca sebagai bagian latihan hanya karena mengandung
     * huruf "ujian".
     */
    private const KATA_LATIHAN = '\b(?:latihan|tugas|soal|ujian|evaluasi|kuis)\b';

    /**
     * Pecah isi materi menjadi daftar seksi.
     *
     * @param  string|null  $isi  Isi mentah dari kolom tb_materi.isi.
     * @param  string  $judulCadangan  Judul seksi tunggal kalau isinya tidak punya penanda seksi.
     * @return array<int, array{
     *     nomor: int,
     *     slug: string,
     *     judul: string,
     *     latihan: bool,
     *     blok: array<int, array<string, mixed>>
     * }>
     */
    public static function seksi(?string $isi, string $judulCadangan = 'Isi Materi'): array
    {
        $kelompok = self::kelompok((string) $isi, $judulCadangan);

        if ($kelompok === []) {
            $kelompok[] = ['judul' => $judulCadangan, 'blok' => []];
        }

        $hasil = [];
        $pakaiSlug = [];

        foreach ($kelompok as $nomor => $seksi) {
            $judul = trim((string) $seksi['judul']) !== '' ? trim((string) $seksi['judul']) : $judulCadangan;
            $slug = self::slugUnik(Str::slug($judul) ?: 'seksi-'.($nomor + 1), $pakaiSlug);

            $hasil[] = [
                'nomor' => $nomor + 1,
                'slug' => $slug,
                'judul' => $judul,
                'latihan' => (bool) preg_match('/'.self::KATA_LATIHAN.'/i', $judul),
                'blok' => array_map(self::siapkan(...), $seksi['blok']),
            ];
        }

        return $hasil;
    }

    /**
     * Kumpulkan blok blok kode, lalu kelompokkan per seksi.
     *
     * Blok yang sedang ditampung ($blok) selalu milik seksi terakhir. Jadi
     * setiap kali "# Judul" baru ditemukan, blok lama dipindahkan ke seksi
     * sebelumnya dan $blok dikosongkan.
     *
     * @return array<int, array{judul: string, blok: array<int, array<string, mixed>>}>
     */
    private static function kelompok(string $isi, string $judulCadangan): array
    {
        $baris = preg_split('/\R/u', str_replace("\t", '    ', $isi)) ?: [];

        $kelompok = [];
        $blok = [];
        $paragraf = [];
        $butir = [];
        $catatan = [];

        /*
         * Tiga pembungkus teks: paragraf, daftar butir, dan catatan. Yang
         * sedang ditampung harus ditutup dulu sebelum jenis blok yang lain
         * mulai, karena urutannya ikut menentukan urutan blok di halaman.
         */
        $tutupParagraf = function () use (&$blok, &$paragraf): void {
            if ($paragraf !== []) {
                $blok[] = ['tipe' => 'paragraf', 'teks' => implode("\n", $paragraf)];
                $paragraf = [];
            }
        };

        $tutupDaftar = function () use (&$blok, &$butir): void {
            if ($butir !== []) {
                $blok[] = ['tipe' => 'daftar', 'butir' => $butir];
                $butir = [];
            }
        };

        $tutupCatatan = function () use (&$blok, &$catatan): void {
            if ($catatan !== []) {
                $blok[] = ['tipe' => 'catatan', 'teks' => implode("\n", $catatan)];
                $catatan = [];
            }
        };

        $tutupSemua = function () use ($tutupParagraf, $tutupDaftar, $tutupCatatan): void {
            $tutupParagraf();
            $tutupDaftar();
            $tutupCatatan();
        };

        // Pindahkan blok yang sudah terkumpul ke seksi terakhir, lalu mulai
        // tampungan baru.
        $tutupSeksi = function () use (&$kelompok, &$blok, $judulCadangan): void {
            if ($kelompok === []) {
                /*
                 * Belum ada seksi sama sekali. Teks yang muncul sebelum
                 * "# Judul" pertama tetap perlu tempat, jadi ia memakai
                 * judul cadangan. Kalau memang tidak ada teks apa pun,
                 * tidak perlu membuat seksi kosong.
                 */
                if ($blok !== []) {
                    $kelompok[] = ['judul' => $judulCadangan, 'blok' => $blok];
                }
            } else {
                $kelompok[array_key_last($kelompok)]['blok'] = $blok;
            }

            $blok = [];
        };

        for ($i = 0, $jumlah = count($baris); $i < $jumlah; $i++) {
            $barisNow = rtrim($baris[$i]);

            // Blok kode: baca sampai pagar penutup atau baris terakhir.
            if (preg_match('/^```(\w*)\s*$/', $barisNow, $muka) === 1) {
                $tutupSemua();
                $isiKode = [];

                while (++$i < $jumlah) {
                    if (rtrim($baris[$i]) === '```') {
                        break;
                    }

                    $isiKode[] = $baris[$i];
                }

                $blok[] = [
                    'tipe' => 'kode',
                    'bahasa' => $muka[1] !== '' ? strtolower($muka[1]) : 'teks',
                    'kode' => rtrim(implode("\n", $isiKode)),
                ];

                continue;
            }

            // Baris kosong menutup paragraf, daftar, dan catatan yang berjalan.
            if (trim($barisNow) === '') {
                $tutupSemua();

                continue;
            }

            /*
             * Dua penanda seksi baru: "Bab N: Judul" ditulis form Tambah
             * Materi (lihat susunIsi di resources/js/materi-tambah.js), dan
             * "# Judul" ditulis pengarang yang mengetik Markdown. Keduanya
             * sudah dibaca App\Support\BabMateri untuk form edit dan untuk
             * Materi::jumlahBab(). Kalau parser ini hanya mengenal "#",
             * materi buatan form tidak pernah punya Daftar Isi karena seluruh
             * babnya jatuh jadi satu seksi, padahal jumlah babnya lebih dari
             * satu. Nomor "Bab N" tidak ikut jadi judul supaya yang tampil di
             * kartu Daftar Isi nama babnya saja, sama seperti di form edit.
             */
            if (preg_match('/^Bab\s+\d+\s*:\s*(.*)$/u', $barisNow, $muka) === 1) {
                $tutupSemua();
                $tutupSeksi();

                $judul = trim($muka[1]);

                $kelompok[] = [
                    'judul' => $judul !== '' ? $judul : 'Bab '.(count($kelompok) + 1),
                    'blok' => [],
                ];

                continue;
            }

            // "# Judul" memulai seksi baru.
            if (preg_match('/^#\s+(.+)$/u', $barisNow, $muka) === 1) {
                $tutupSemua();
                $tutupSeksi();

                $kelompok[] = ['judul' => $muka[1], 'blok' => []];

                continue;
            }

            // "## Subjudul" tetap di dalam seksi yang sedang terbuka.
            if (preg_match('/^#{2,}\s+(.+)$/u', $barisNow, $muka) === 1) {
                $tutupSemua();
                $blok[] = ['tipe' => 'sub', 'judul' => $muka[1]];

                continue;
            }

            /*
             * Butir dan catatan boleh beruntun. Karena itu hanya pembungkus
             * yang lain yang ditutup, bukan pembungkus sejenisnya.
             */
            if (preg_match('/^[-*]\s+(.+)$/u', $barisNow, $muka) === 1) {
                $tutupParagraf();
                $tutupCatatan();
                $butir[] = $muka[1];

                continue;
            }

            if (preg_match('/^>\s?(.+)$/u', $barisNow, $muka) === 1) {
                $tutupParagraf();
                $tutupDaftar();
                $catatan[] = $muka[1];

                continue;
            }

            // Teks biasa: menutup daftar dan catatan, lalu jadi paragraf.
            $tutupDaftar();
            $tutupCatatan();
            $paragraf[] = $barisNow;
        }

        $tutupSemua();
        $tutupSeksi();

        // Buang seksi kosong di bagian akhir (mis. "# Judul" tanpa isi).
        if ($kelompok !== [] && $kelompok[array_key_last($kelompok)]['blok'] === []) {
            array_pop($kelompok);
        }

        return $kelompok;
    }

    /**
     * Lengkapi satu blok dengan bentuk yang siap dipakai komponen.
     *
     * @param  array<string, mixed>  $blok
     * @return array<string, mixed>
     */
    private static function siapkan(array $blok): array
    {
        if ($blok['tipe'] === 'kode') {
            // Pewarna dihitung sekali di sini, bukan tiap render.
            $blok['sorot'] = SorotKode::sorot($blok['kode'], $blok['bahasa']);

            return $blok;
        }

        if ($blok['tipe'] === 'daftar') {
            $blok['butir'] = array_map(self::format(...), $blok['butir']);

            return $blok;
        }

        if ($blok['tipe'] === 'sub') {
            $blok['judul'] = trim($blok['judul']);

            return $blok;
        }

        $blok['html'] = self::format($blok['teks']);

        return $blok;
    }

    /**
     * Ubah teks polos paragraf jadi HTML yang aman.
     *
     * Teks di-escape dulu, baru penanda **tebal** dan `kode` diganti jadi
     * tag. Urutan itu penting: karena e() dijalankan lebih dulu, penanda dari
     * pengguna tidak bisa menyisipkan HTML apa pun.
     */
    private static function format(string $teks): string
    {
        $aman = e(trim($teks));

        $aman = preg_replace('/\*\*(?!\s)(.+?)(?<!\s)\*\*/s', '<strong>$1</strong>', $aman) ?? $aman;
        $aman = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $aman) ?? $aman;

        return nl2br($aman);
    }

    /**
     * Slug yang pasti unik di dalam satu halaman.
     *
     * @param  array<string, true>  $pakai
     */
    private static function slugUnik(string $dasar, array &$pakai): string
    {
        $slug = $dasar;
        $urutan = 2;

        while (isset($pakai[$slug])) {
            $slug = $dasar.'-'.$urutan++;
        }

        $pakai[$slug] = true;

        return $slug;
    }
}
