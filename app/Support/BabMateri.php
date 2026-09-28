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
 *   "# Judul" / "## Subjudul"-> penanda seksi untuk materi yang diketik manual
 *
 * Isi tanpa penanda apa pun dianggap satu bab, jadi hasilnya tidak pernah
 * kosong.
 */
final class BabMateri
{
    /**
     * Penanda yang dibaca, dari yang paling spesifik.
     *
     * @var array<int, string>
     */
    private const PENANDA = [
        '/^Bab\s+\d+\s*:\s*(.*)$/mu',
        '/^\s*#{1,2}\s+(.+)$/mu',
    ];

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

        foreach (self::PENANDA as $pola) {
            $bagian = self::pecah($teks, $pola);

            if ($bagian !== []) {
                return $bagian;
            }
        }

        return [['id' => 'bab-1', 'title' => 'Pendahuluan', 'content' => self::paragraf($teks)]];
    }

    /**
     * Potong teks pada setiap baris yang cocok dengan penanda.
     *
     * @return array<int, array{id: string, title: string, content: string}>
     */
    private static function pecah(string $teks, string $pola): array
    {
        preg_match_all($pola, $teks, $muka, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        if ($muka === []) {
            return [];
        }

        $bagian = [];

        // Teks sebelum penanda pertama tetap jadi bab sendiri, supaya tidak
        // ada isi yang diam-diam hilang.
        $awal = $muka[0][0][1];

        if (trim(substr($teks, 0, $awal)) !== '') {
            $bagian[] = [
                'id' => 'bab-1',
                'title' => 'Pendahuluan',
                'content' => self::paragraf(substr($teks, 0, $awal)),
            ];
        }

        foreach ($muka as $index => $baris) {
            [$penanda, $mulai] = $baris[0];
            $akhir = isset($muka[$index + 1]) ? $muka[$index + 1][0][1] : strlen($teks);

            $isiBab = trim(substr($teks, $mulai + strlen($penanda), max(0, $akhir - $mulai - strlen($penanda))));
            $judul = trim((string) ($baris[1][0] ?? ''));

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
