<?php

namespace App\Support;

/**
 * Mengubah isi materi (kolom tb_materi.isi) kembali menjadi daftar bab.
 *
 * Kebalikannya dari yang dilakukan resources/js/materi-tambah.js saat form
 * disimpan: JavaScript merangkai semua bab menjadi satu teks polos, dan
 * database hanya menyimpan teks itu. Karena itu form edit perlu memecah
 * teksnya kembali supaya daftar babnya muncul lagi di halaman edit.
 *
 * Dua gaya penanda yang dipakai aplikasi:
 *   "Bab 2: Mengenal Blade"  -> ditulis form Tambah Materi
 *   "# Judul"                -> penanda seksi, sama dengan yang dibaca IsiMateri
 *
 * Sengaja hanya "#" satu tanda yang memulai bab. "## Subjudul" tetap
 * subjudul di dalam bab; kalau ikut dianggap bab, strukturnya rusak begitu
 * materi disimpan ulang lewat form.
 *
 * Baris di dalam blok kode tidak pernah dibaca sebagai penanda, jadi
 * komentar gaya "# catatan" di dalam contoh kode tidak memecah bab.
 *
 * Isi tanpa penanda apa pun dianggap satu bab, jadi hasilnya tidak pernah
 * kosong.
 */
final class BabMateri
{
    /**
     * Penanda yang dibaca, dari yang paling spesifik. Pola di sini tidak
     * pakai modifier "m", karena pencariannya dilakukan baris per baris.
     *
     * @var array<int, string>
     */
    private const PENANDA = [
        '/^Bab\s+\d+\s*:\s*(.*)$/u',
        '/^#\s+(.+)$/u',
    ];

    /**
     * Baris yang menandai awal atau akhir blok kode.
     */
    private const PAGAR_KODE = '/^```(\w*)\s*$/';

    /**
     * Daftar bab dari isi materi, siap dikirim ke JavaScript sebagai JSON.
     *
     * @return array<int, array{id: string, title: string, content: string}>
     */
    public static function dariIsi(?string $isi): array
    {
        $teks = trim((string) $isi);

        if ($teks === '') {
            return [['id' => 'bab-1', 'title' => 'Pendahuluan', 'content' => '']];
        }

        $baris = preg_split('/\R/u', str_replace("\t", '    ', $teks)) ?: [];

        foreach (self::PENANDA as $pola) {
            $bagian = self::pecah($baris, $pola);

            if ($bagian !== []) {
                return $bagian;
            }
        }

        return [[
            'id' => 'bab-1',
            'title' => 'Pendahuluan',
            'content' => self::paragraf(implode("\n", $baris)),
        ]];
    }

    /**
     * Potong daftar baris pada setiap baris yang cocok dengan pola.
     *
     * @param  array<int, string>  $baris
     * @return array<int, array{id: string, title: string, content: string}>
     */
    private static function pecah(array $baris, string $pola): array
    {
        $penanda = [];
        $diDalamKode = false;

        foreach ($baris as $index => $barisNow) {
            if (preg_match(self::PAGAR_KODE, rtrim($barisNow)) === 1) {
                $diDalamKode = ! $diDalamKode;

                continue;
            }

            if (! $diDalamKode && preg_match($pola, $barisNow, $muka) === 1) {
                $penanda[$index] = trim((string) ($muka[1] ?? ''));
            }
        }

        if ($penanda === []) {
            return [];
        }

        $bagian = [];

        // Teks sebelum penanda pertama tetap jadi bab sendiri, supaya tidak
        // ada isi yang diam-diam hilang.
        $awal = array_key_first($penanda);
        $pembuka = trim(implode("\n", array_slice($baris, 0, $awal)));

        if ($pembuka !== '') {
            $bagian[] = [
                'id' => 'bab-1',
                'title' => 'Pendahuluan',
                'content' => self::paragraf($pembuka),
            ];
        }

        $urutan = array_keys($penanda);

        foreach ($urutan as $posisi => $index) {
            $akhir = $urutan[$posisi + 1] ?? count($baris);
            $isiBab = trim(implode("\n", array_slice($baris, $index + 1, $akhir - $index - 1)));
            $judul = $penanda[$index];

            $bagian[] = [
                'id' => 'bab-'.(count($bagian) + 1),
                'title' => $judul !== '' ? $judul : 'Bab '.(count($bagian) + 1),
                'content' => self::paragraf($isiBab),
            ];
        }

        return $bagian;
    }

    /**
     * Teks polos satu bab jadi isi editor.
     *
     * Editor memakai contenteditable, jadi teksnya dibungkus paragraf dan
     * <br> supaya baris baru tetap terlihat. Saat form disimpan lagi,
     * JavaScript mengubahnya kembali menjadi teks polos.
     */
    private static function paragraf(string $teks): string
    {
        $teks = trim($teks);

        return $teks === '' ? '' : trim(nl2br(e($teks)));
    }
}
