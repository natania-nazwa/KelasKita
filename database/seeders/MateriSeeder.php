<?php

namespace Database\Seeders;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Isi halaman Materi user: kategori, materi, dan pembuatnya.
 *
 * Seeder ini idempoten. Aman dijalankan berulang: kategori dicocokkan lewat
 * slug, materi lewat nama, dan pengguna lewat email, jadi data yang sudah
 * ada diperbarui, bukan diduplikasi.
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
            'deskripsi' => 'Pecahan, geometri, dan aljebra untuk jenjang dasar.',
            'ikon' => '∑',
        ],
        [
            'nama' => 'IPA',
            'slug' => 'ipa',
            'deskripsi' => 'Fisika, astronomi, dan phenomena alam sehari-hari.',
            'ikon' => '🔬',
        ],
        [
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Dasar-dasar menulis program, dari HTML sampai JavaScript.',
            'ikon' => '</>',
        ],
        [
            'nama' => 'Desain Web',
            'slug' => 'desain-web',
            'deskripsi' => 'Tampilan, tipografi, dan pengalaman antarmuka pengguna.',
            'ikon' => '🎨',
        ],
        [
            'nama' => 'Database',
            'slug' => 'database',
            'deskripsi' => 'Penyimpanan data terstruktur dan bahasa SQL.',
            'ikon' => '🗄️',
        ],
        [
            'nama' => 'Teknologi',
            'slug' => 'teknologi',
            'deskripsi' => 'Alur kerja membangun aplikasi web dari awal sampai selesai.',
            'ikon' => '⚡',
        ],
    ];

    /**
     * Pembuat materi. Email dipakai sebagai kunci unik, jadi aman dijalankan
     * berulang.
     *
     * @var array<int, array<string, string>>
     */
    private const PENGGUNA = [
        ['email' => 'admin@kelaskita.test', 'nama' => 'Admin', 'peran' => User::PERAN_ADMIN],
        ['email' => 'natania@kelaskita.test', 'nama' => 'Natania', 'peran' => User::PERAN_USER],
        ['email' => 'keyla@kelaskita.test', 'nama' => 'Keyla', 'peran' => User::PERAN_USER],
        ['email' => 'irma@kelaskita.test', 'nama' => 'Irma', 'peran' => User::PERAN_USER],
        ['email' => 'khanif@kelaskita.test', 'nama' => 'Khanif', 'peran' => User::PERAN_USER],
        ['email' => 'heysell@kelaskita.test', 'nama' => 'Heysell', 'peran' => User::PERAN_USER],
    ];

    /**
     * Materi. Key "pembuat" menunjuk email di daftar PENGGUNA; materi tanpa
     * key itu memakai pengguna bawaan.
     *
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
        [
            'kategori' => 'pemrograman',
            'nama' => 'HTML Dasar',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Materi dasar HTML untuk pemula.',
            'pembuat' => 'admin@kelaskita.test',

            /*
             * Isi materi boleh memakai penanda sederhana yang dibaca
             * App\Support\IsiMateri saat halaman detail dirender:
             *   #  -> seksi (bikin entri Daftar Isi)
             *   ## -> subjudul
             * ``` -> blok kode
             *   -  -> daftar butir
             *   >  -> catatan
             * Materi yang tidak memakai penanda ini tetap aman: seluruh
             * teksnya menjadi satu seksi.
             */
            'isi' => <<<'ISI'
            # Pengenalan HTML

            HTML (HyperText Markup Language) adalah bahasa markup yang digunakan untuk membuat struktur dasar halaman web. HTML tidak berfungsi memberikan tampilan, tetapi menjadi kerangka atau struktur dari sebuah website.

            ## Contoh kode HTML:

            ```html
            <!DOCTYPE html>
            <html>
              <head>
                <title>Contoh HTML</title>
              </head>
              <body>
                <h1>Halo, Dunia!</h1>
                <p>Ini adalah contoh halaman HTML.</p>
              </body>
            </html>
            ```

            > Browser tidak menampilkan tag HTML apa adanya. Tag dibaca, lalu diubah menjadi tampilan di layar.

            # Struktur Dasar HTML

            Setiap dokumen HTML punya tiga bagian wajib: doctype yang memberi tahu browser bahwa dokumen ini HTML5, elemen `html` sebagai akar, lalu `head` dan `body`.

            Elemen `head` menyimpan informasi yang tidak ditampilkan, seperti judul halaman, meta charset, dan tautan ke berkas CSS. Elemen `body` berisi semua konten yang benar-benar dilihat pengguna.

            ```html
            <!DOCTYPE html>
            <html lang="id">
              <head>
                <meta charset="UTF-8" />
                <title>Judul Halaman</title>
              </head>
              <body>
                <h1>Judul Utama</h1>
              </body>
            </html>
            ```

            > Tambahkan `charset="UTF-8"` supaya huruf seperti "é" dan tanda hubung panjang tetap terbaca dengan benar.

            # Tag dan Element

            Tag adalah penanda di dalam kurung sudut. Pasangan tag pembuka dan tag penutup membentuk satu element. Sebagian element tidak perlu tag penutup karena isinya kosong.

            - `h1` sampai `h6` untuk judul, dengan `h1` dipakai untuk judul utama halaman.
            - `p` untuk paragraf, `strong` untuk tebal, `em` untuk miring.
            - `ul` dan `li` untuk daftar tidak bernomor, `ol` untuk daftar bernomor.
            - `div` untuk blok baru baris, `span` untuk potongan di dalam baris.

            ```html
            <h1>Judul Utama</h1>
            <p>Teks <strong>tebal</strong> dan teks <em>miring</em>.</p>
            <ul>
              <li>Butir pertama</li>
              <li>Butir kedua</li>
            </ul>
            ```

            # Membuat Link

            Tautan dibuat dengan element `a`. Atribut `href` berisi tujuan, dan atribut `target="_blank"` membuka tautan di tab baru.

            ```html
            <a href="https://kelaskita.test">Kunjungi KelasKita</a>
            <a href="materi/css-dasar.html" target="_blank">Materi CSS Dasar</a>
            ```

            > Alamat tanpa `http://` atau `https://` dibaca sebagai berkas lokal. Untuk halaman lain di situs yang sama, cukup tulis nama berkasnya saja.

            # Gambar dan Media

            Gambar disisipkan dengan element `img`. Atribut `src` menunjuk berkas gambarnya, `alt` menjelaskan isi gambar untuk pembaca layar, dan `width` menjaga gambar tidak melebar keluar halaman.

            ```html
            <img src="gambar/kelas.jpg" alt="Ruang kelas" width="640" />
            <video controls width="640">
              <source src="video/pengantar.mp4" type="video/mp4" />
            </video>
            ```

            # Formulir

            Formulir memakai element `form` yang selalu diberikan atribut `action` dan `method`. Setiap isian sebaiknya punya label supaya pengguna tahu apa yang perlu diisi.

            ```html
            <form action="/daftar" method="POST">
              <label for="nama">Nama lengkap</label>
              <input id="nama" name="nama" type="text" required />

              <label for="email">Email</label>
              <input id="email" name="email" type="email" required />

              <button type="submit">Kirim</button>
            </form>
            ```

            # Latihan Soal

            Sudah memahami materi HTML Dasar?

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'pemrograman',
            'nama' => 'CSS Dasar',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Membuat tampilan web lebih menarik.',
            'pembuat' => 'natania@kelaskita.test',
            'isi' => "CSS mengatur tampilan tanpa menyentuh isi halaman. Satu aturan punya selector, properti, dan nilai. Contohnya h1 { color: #6c4de6; } membuat semua judul h1 berwarna ungu.\n\nSelector bisa berupa nama tag, kelas dengan titik, atau id dengan tanda pagar. Kelas dipakai ketika aturan yang sama perlu dipakai di banyak elemen.\n\nWarna ditulis sebagai kode enam digit diawali tanda pagar. Untuk warna dengan transparansi, tambahkan alphanya, misalnya #6c4de633.\n\nLatihan: ubah halaman HTML tadi menjadi dua kolom, lalu atur jarak antar elemen dengan margin dan padding.",
        ],
        [
            'kategori' => 'desain-web',
            'nama' => 'UI/UX Design',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Mengenal dasar desain antarmuka pengguna.',
            'pembuat' => 'keyla@kelaskita.test',
            'isi' => "UI adalah bagian yang dilihat pengguna, misalnya tombol, menu, dan kartu. UX adalah pengalaman memakai aplikasi secara keseluruhan, termasuk seberapa mudah seseorang menyelesaikan tugasnya.\n\nEmpat prinsip dasar yang sering dipakai: kedekatan, kontras, pengulangan, dan keselarasan. Kalau satu layar terasa ramai, biasanya karena jarak antar elemen tidak jelas.\n\nWarna teks utama harus punya kontras cukup terhadap latar. Jangan mengandalkan warna saja untuk menyampaikan informasi, tambahkan bentuk atau teks.\n\nLatihan: gambar ulang satu formulir, lalu perbaiki urutan elemennya supaya pengguna tidak perlu membaca semua label dulu.",
        ],
        [
            'kategori' => 'pemrograman',
            'nama' => 'JavaScript Dasar',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Logika dan interaksi pada website.',
            'pembuat' => 'irma@kelaskita.test',
            'isi' => "JavaScript membuat halaman web bisa merespons aksi pengguna: klik, ketik, dan geser. Semua kode di browser dimulai dari variabel, operator, dan kondisi.\n\nCara paling umum menyambungkan JavaScript ke HTML adalah atribut onclick, misalnya button dengan onclick=\"hitung()\" yang memanggil fungsi hitung.\n\nVariabel dideklarasikan dengan let atau const. Gunakan let kalau nilainya nanti berubah, const kalau sudah final.\n\nLatihan: buat tombol penghitung yang menambah satu setiap diklik, lalu tampilkan angkanya di dalam span.",
        ],
        [
            'kategori' => 'database',
            'nama' => 'Basis Data',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Pengenalan database dan SQL dasar.',
            'pembuat' => 'khanif@kelaskita.test',
            'isi' => "Database menyimpan data dalam bentuk tabel: baris adalah record, kolom adalah atribut. Primary key membedakan setiap baris supaya tidak kembar.\n\nSQL punya empat perintah utama: SELECT untuk membaca, INSERT untuk menambah, UPDATE untuk mengubah, dan DELETE untuk menghapus.\n\nPakai klausa WHERE setiap kali menulis SELECT supaya hanya baris yang benar-benar dibutuhkan yang ikut terbaca.\n\nLatihan: buat tabel siswa dengan kolom id, nama, dan kelas, lalu ambil semua siswa dari kelas 10.",
        ],
        [
            'kategori' => 'teknologi',
            'nama' => 'Pengembangan Web',
            'tingkat_kesulitan' => 'Sulit',
            'deskripsi' => 'Dari desain hingga deployment.',
            'pembuat' => 'heysell@kelaskita.test',
            'isi' => "Membangun aplikasi web punya tahap yang berurutan: kebutuhan, desain, pengembangan, pengujian, lalu deployment.\n\nVersion control dengan Git dipakai sejak awal supaya setiap perubahan punya riwayat dan bisa dikembalikan bila ada kesalahan.\n\nPengujian minimal mencakup dua sisi: cek tampilan di beberapa ukuran layar, dan cek alur yang paling sering dipakai pengguna.\n\nDeployment berarti mengunggah aplikasi ke server lalu menjalankannya dengan environment variable untuk konfigurasi.\n\nLatihan: ambil satu materi dari daftar ini, ubah tampilannya, lalu jalankan versinya sendiri di server.",
        ],
    ];

    public function run(): void
    {
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

        $idPengguna = $this->pengguna();
        $pembuatCadangan = $this->pembuatCadangan();

        foreach (self::MATERI as $materi) {
            /*
             * Dicocokkan lewat "nama", bukan slug. Data lama bisa saja
             * memakai slug yang tidak sama dengan hasil str()->slug()
             * (mis. "sistem-tata-surya" untuk "Sistem Tata Surya dan
             * Geraknya"), sehingga mencocokkan slug akan membuat baris
             * ganda setiap kali seeder dijalankan.
             */
            Materi::query()->updateOrCreate(
                ['nama' => $materi['nama']],
                [
                    'pelajaran_id' => $idKategori[$materi['kategori']],
                    'dibuat_oleh' => $idPengguna[$materi['pembuat'] ?? ''] ?? $pembuatCadangan,
                    'slug' => str($materi['nama'])->slug()->toString(),
                    'deskripsi' => $materi['deskripsi'],
                    'isi' => $materi['isi'],
                    'tingkat_kesulitan' => $materi['tingkat_kesulitan'],
                    'aktif' => true,
                ]
            );
        }
    }

    /**
     * Pastikan semua pembuat ada, lalu kembalikan email => id.
     *
     * @return array<string, int>
     */
    private function pengguna(): array
    {
        $id = [];

        foreach (self::PENGGUNA as $pengguna) {
            $id[$pengguna['email']] = User::query()->updateOrCreate(
                ['email' => $pengguna['email']],
                [
                    'nama' => $pengguna['nama'],
                    'kata_sandi' => Hash::make('password'),
                    'peran' => $pengguna['peran'],
                    'aktif' => true,
                ]
            )->getKey();
        }

        return $id;
    }

    /**
     * Pembuat default untuk materi bawaan seeder lama yang tidak
     * menyebutkan pembuatnya.
     */
    private function pembuatCadangan(): ?int
    {
        return User::query()
            ->where('peran', User::PERAN_USER)
            ->orderBy('id')
            ->first()
            ?->getKey();
    }
}
