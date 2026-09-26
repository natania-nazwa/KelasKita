<?php

namespace Database\Seeders;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Isi minimal halaman Materi user: 4 kategori, 1 materi per kategori.
 *
 * Seeder ini idempoten. Aman dijalankan berulang: kategori dan materi
 * dicocokkan lewat slug, jadi data yang sudah ada diperbarui, bukan
 * diduplikasi.
 */
class MateriSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private const KATEGORI = [
        [
            'nama' => 'Bahasa Indonesia',
            'slug' => 'bahasa-indonesia',
            'deskripsi' => 'Tata bahasa, kosakata, dan keterampilan membaca dan menulis.',
            'ikon' => 'A',
        ],
        [
            'nama' => 'Bahasa Jepang',
            'slug' => 'bahasa-jepang',
            'deskripsi' => 'Hiragana, katakana, kanji, dan kosakata dasar.',
            'ikon' => '日',
        ],
        [
            'nama' => 'Matematika',
            'slug' => 'matematika',
            'deskripsi' => 'Pecahan, geometri, dan aljabar untuk jenjang dasar.',
            'ikon' => '∑',
        ],
        [
            'nama' => 'IPA',
            'slug' => 'ipa',
            'deskripsi' => 'Fisika, astronomi, dan phenomena alam sehari-hari.',
            'ikon' => '🔬',
        ],
    ];

    /**
     * @var array<int, array<string, mixed>>
     */
    private const MATERI = [
        [
            'kategori' => 'bahasa-indonesia',
            'nama' => 'Mengenal Huruf Hijaiyah',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Pengenalan dasar alfabet Arab untuk bahasa Indonesia.',
            'isi' => "Huruf hijaiyah adalah alfabet yang dipakai untuk menulis bahasa Arab. Kalau alfabet Latin punya 26 huruf, hijaiyah punya 28 huruf yang disusun berdasarkan bentuknya.\n\nHuruf yang paling sering dihafal pertama adalah alif, lam, mim, dan nun. Setelah itu belajar hafalan bentuk sambung, karena huruf akan berubah posisi tergantung letaknya.\n\nLatihan terbaik: tulis satu kata sederhana seperti \"rumah\" memakai hijaiyah setiap hari selama sepuluh menit.",
        ],
        [
            'kategori' => 'bahasa-jepang',
            'nama' => 'Mengenal Hiragana',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Dasar menulis bahasa Jepang dengan huruf hiragana.',
            'isi' => "Hiragana adalah salah satu dari tiga jenis huruf dalam bahasa Jepang. Jumlahnya 46 huruf dan semuanya relatif mudah dipelajari.\n\nHuruf hiragana dipakai untuk menulis kata-kata asli bahasa Jepang. Contoh sederhana: a, i, u, e, o.\n\nTips belajar: hafal lima huruf setiap hari, lalu gabungkan menjadi satu kata. Jangan menghafal semua sekaligus karena mudah lupa.",
        ],
        [
            'kategori' => 'matematika',
            'nama' => 'Pecahan dan Pecahan Campuran',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Mengenal pecahan biasa, pecahan campuran, dan cara membandingkannya.',
            'isi' => "Pecahan campuran adalah pecahan yang bagian bulatnya lebih dari satu. Contohnya satu setengah ditulis 1 1/2.\n\nCara mengubah pecahan campuran menjadi pecahan biasa: kalikan bilangan bulat dengan penyebut, lalu tambahkan pembilang. Satu setengah = (1 x 2 + 1) / 2 = 3/2.\n\nSebaliknya, tiga per empat ditulis 3/4, bukan 0 3/4. Membandingkan dua pecahan selalu lakukan dengan menyamakan penyebut dulu.",
        ],
        [
            'kategori' => 'ipa',
            'nama' => 'Sistem Tata Surya dan Geraknya',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Mengenal planet-planet, urutan dari Matahari, dan gerak revolusi.',
            'isi' => "Tata Surya adalah kumpulan planet yang mengorbit sebuah bintang, yaitu Matahari. Ada delapan planet: Merkurius, Venus, Bumi, Mars, Jupiter, Saturnus, Uranus, dan Neptunus.\n\nPlanet bergerak dua cara sekaligus. Revolusi adalah mengelilingi Matahari dan berlangsung sekitar satu tahun. Rotasi adalah berputar pada porosnya sendiri dan berlangsung sekitar 24 jam, yang menyebabkan siang dan malam.\n\nBumi adalah satu-satunya planet yang punya air cair dan kehidupan, letaknya pada jarak yang tepat dari Matahari.",
        ],
    ];

    public function run(): void
    {
        $pembuat = User::query()->where('peran', 'user')->orderBy('id')->first()?->id;

        $idKategori = [];

        foreach (self::KATEGORI as $kategori) {
            $idKategori[$kategori['slug']] = Pelajaran::query()->updateOrCreate(
                ['slug' => $kategori['slug']],
                [
                    'nama' => $kategori['nama'],
                    'deskripsi' => $kategori['deskripsi'],
                    'ikon' => $kategori['ikon'],
                    'aktif' => true,
                ]
            )->id;
        }

        foreach (self::MATERI as $materi) {
            /*
             * Dicocokkan lewat "nama", bukan slug. Data lama bisa saja
             * memakai slug yang tidak sama dengan hasil str()->slug()
             * (mis. "sistem-tata-surya" untuk "Sistem Tata Surya dan
             * Geraknya"), sehingga mencocokkan slug akan membuat baris
             * ganda setiap kali seeder dijalankan.
             */
            $slugBaru = str($materi['nama'])->slug()->toString();

            Materi::query()->updateOrCreate(
                ['nama' => $materi['nama']],
                [
                    'pelajaran_id' => $idKategori[$materi['kategori']],
                    'dibuat_oleh' => $pembuat,
                    'slug' => $slugBaru,
                    'deskripsi' => $materi['deskripsi'],
                    'isi' => $materi['isi'],
                    'tingkat_kesulitan' => $materi['tingkat_kesulitan'],
                    'aktif' => true,
                ]
            );
        }
    }
}
