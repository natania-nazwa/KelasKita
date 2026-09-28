<?php

namespace App\Support;

/**
 * Pewarna sintaks ringan untuk blok kode di halaman detail materi.
 *
 * Ini bukan parser bahasa pemrograman. Yang dilakukan hanya memecah kode
 * menjadi potongan-potongan kecil (tag, atribut, string, komentar, angka,
 * dan sebangsa), lalu membungkus tiap potongan dengan <span> yang punya
 * kelas warna.
 *
 * Dua hal yang dijaga kelas ini:
 *
 * 1. Teks asli selalu lewat e() sebelum masuk ke HTML, sehingga kode yang
 *    ditulis pengguna tidak pernah berubah jadi markup yang dieksekusi
 *    browser.
 * 2. Penandaan hanya memakai satu lapis regex. Kalau polanya gagal cocok,
 *    kode tetap ditampilkan apa adanya, tidak pernah hilang.
 */
final class SorotKode
{
    /**
     * Nama grup regex => kelas CSS.
     *
     * @var array<string, string>
     */
    private const KELAS = [
        'komentar' => 'kode-komentar',
        'string' => 'kode-string',
        'tag' => 'kode-tag',
        'atribut' => 'kode-atribut',
        'kata' => 'kode-kata',
        'fungsi' => 'kode-fungsi',
        'nilai' => 'kode-nilai',
        'angka' => 'kode-angka',
    ];

    /**
     * Aturan khusus per bahasa.
     *
     * Urutan penting: pola yang lebih spesifik ditulis lebih dulu karena
     * preg_alternation memakai cabang yang paling kiri yang cocok.
     *
     * @var array<string, array<int, array{nama: string, pola: string}>>
     */
    private const ATURAN = [
        'html' => [
            ['nama' => 'komentar', 'pola' => '<!--[\s\S]*?-->'],
            ['nama' => 'tag', 'pola' => '<![A-Za-z][^>]*>|</?[A-Za-z][\w:-]*[ \t]*/?>|/>'],
            ['nama' => 'string', 'pola' => '"[^"\n]*"'],
            ['nama' => 'atribut', 'pola' => '\b[A-Za-z_:][\w:.-]*(?=\s*=)'],
            ['nama' => 'nilai', 'pola' => '&[#\w]+;'],
        ],
        'php' => [
            ['nama' => 'komentar', 'pola' => '\/\*[\s\S]*?\*\/|\/\/[^\n]*|\#[^\n]*'],
            ['nama' => 'tag', 'pola' => '<\?php|<\?=|\?>'],
            ['nama' => 'string', 'pola' => '"[^"\n]*"'],
            ['nama' => 'kata', 'pola' => '\b(?:echo|print|function|return|if|else|elseif|foreach|for|while|do|switch|case|break|continue|new|class|public|private|protected|static|final|const|use|namespace|try|catch|throw|match|fn|true|false|null|and|or|as|isset|unset|empty)\b'],
            ['nama' => 'fungsi', 'pola' => '\b[A-Za-z_][\w]*(?=\s*\()'],
        ],
        'css' => [
            ['nama' => 'komentar', 'pola' => '\/\*[\s\S]*?\*\/'],
            ['nama' => 'string', 'pola' => '"[^"\n]*"'],
            ['nama' => 'kata', 'pola' => '@[A-Za-z-]+'],
            ['nama' => 'nilai', 'pola' => '#[0-9A-Fa-f]{3,8}\b'],
            ['nama' => 'atribut', 'pola' => '(?<![\w#.-])-?[A-Za-z][\w-]*(?=\s*:)'],
        ],
        'js' => [
            ['nama' => 'komentar', 'pola' => '\/\*[\s\S]*?\*\/|\/\/[^\n]*'],
            ['nama' => 'string', 'pola' => '"[^"\n]*"|\'[^\'\n]*\'|`[^`\n]*`'],
            [
                'nama' => 'kata',
                'pola' => '\b(?:const|let|var|function|return|if|else|for|while|do|switch|case|break|continue|'
                    .'new|class|import|export|from|default|async|await|try|catch|finally|throw|typeof|'
                    .'instanceof|delete|of|in|null|undefined|true|false|this)\b',
            ],
            ['nama' => 'fungsi', 'pola' => '\b[A-Za-z_$][\w$]*(?=\s*\()'],
        ],
    ];

    /**
     * Aturan yang berlaku untuk semua bahasa, termasuk bahasa yang tidak
     * dikenali. String dan angka membuat blok kode tetap terbaca meski
     * tidak ada aturan khusus.
     *
     * @var array<int, array{nama: string, pola: string}>
     */
    private const UMUM = [
        ['nama' => 'komentar', 'pola' => '\/\*[\s\S]*?\*\/|\/\/[^\n]*'],
        ['nama' => 'string', 'pola' => '"[^"\n]*"|\'[^\'\n]*\''],
        ['nama' => 'angka', 'pola' => '\b\d+(?:\.\d+)?\b'],
    ];

    /**
     * Warnakan kode sesuai bahasanya.
     *
     * @param  string  $kode  Kode mentah, belum di-escape.
     * @param  string|null  $bahasa  html, css, js, php, atau null kalau tidak diketahui.
     */
    public static function sorot(string $kode, ?string $bahasa = null): string
    {
        $kode = str_replace(["\r\n", "\r"], "\n", $kode);

        $pola = self::gabung(strtolower((string) $bahasa));

        if ($pola === []) {
            return e($kode);
        }

        if (preg_match_all(self::regex($pola), $kode, $cocok, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0) {
            return e($kode);
        }

        $hasil = [];
        $posisi = 0;

        foreach ($cocok as $temuan) {
            $nama = null;
            $awal = 0;
            $panjang = 0;

            foreach (array_keys($pola) as $kunci) {
                // Grup yang tidak ikut cocok bernilai offset -1.
                if (($temuan[$kunci][1] ?? -1) === -1) {
                    continue;
                }

                $nama = $kunci;
                $awal = $temuan[$kunci][1];
                $panjang = strlen($temuan[$kunci][0]);

                break;
            }

            /*
             * Dua pola bisa menghasilkan potongan yang saling tumpang tindih.
             * Potongan yang mulai lebih belakang dilewati supaya teksnya
             * tidak terpotong dua kali.
             */
            if ($nama === null || $awal < $posisi) {
                continue;
            }

            $hasil[] = e(substr($kode, $posisi, $awal - $posisi));
            $hasil[] = '<span class="'.self::KELAS[$nama].'">'.e(substr($kode, $awal, $panjang)).'</span>';

            $posisi = $awal + $panjang;
        }

        $hasil[] = e(substr($kode, $posisi));

        return implode('', $hasil);
    }

    /**
     * Gabungkan aturan bahasa dengan aturan umum.
     *
     * Nama grup dipakai sebagai kunci supaya pola yang sama tidak didaftarkan
     * dua kali; PCRE menolak regex yang punya dua grup dengan nama sama.
     *
     * @return array<string, string> nama grup => pola
     */
    private static function gabung(string $bahasa): array
    {
        $gabung = [];

        foreach ([...(self::ATURAN[$bahasa] ?? []), ...self::UMUM] as $aturan) {
            $gabung[$aturan['nama']] ??= $aturan['pola'];
        }

        return $gabung;
    }

    /**
     * @param  array<string, string>  $pola
     */
    private static function regex(array $pola): string
    {
        $cabang = [];

        foreach ($pola as $nama => $isi) {
            $cabang[] = "(?P<{$nama}>{$isi})";
        }

        // Flag s supaya pola seperti <!--[\s\S]*?--> bebas baris baru.
        return '~'.implode('|', $cabang).'~s';
    }
}
