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
            'thumbnail' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Apa Itu Huruf Hijaiyah

            Huruf hijaiyah adalah alfabet yang dipakai untuk menulis bahasa Arab. Kalau alfabet Latin punya 26 huruf, hijaiyah punya 28 huruf yang disusun berdasarkan bentuknya.

            Hijaiyah tidak punya huruf besar dan kecil. Yang berubah hanya bentuknya tergantung posisi di dalam kata: awal, tengah, atau akhir.

            ## Empat huruf pertama

            - **ا (alif)**: tegak, menempel pada huruf sebelah kanannya.
            - **ل (lam)**: mirip huruf L yang diputar.
            - **م (mim)**: bulat kecil dengan ekor yang turun.
            - **ن (nun)**: seperti mangkuk dengan satu titik di atas.

            > Tulis urutan ini berulang setiap hari selama sepuluh menit. Pengulangan jauh lebih berguna daripada menghafal 28 huruf sekaligus.

            # Membaca Kata Sederhana

            Setelah hafal bentuk dasar, langkah berikutnya adalah membaca kata yang sudah dikenal. Mulai dari benda sehari-hari: rumah, buku, air, dan kunci.

            Urutan belajarnya:

            - Hafalkan bentuk **posisi awal** setiap huruf lebih dulu.
            - Sambungkan dua huruf, lalu tambah menjadi tiga huruf.
            - Baca kata utuh pelan-pelan, tanpa terburu-buru.

            ```text
            كِتَابُ    ->  kitaabu  ->  buku
            بَيْتُ     ->  baytu    ->  rumah
            مَاءٌ      ->  maa'u    ->  air
            ```

            # Menulis dengan Benar

            Menulis hijaiyah mengikuti arah kanan ke kiri, jadi mulailah dari sisi kanan huruf. Goresan panjang ditulis dulu, baru bagian yang menggantung seperti titik dan harakat.

            Latihan menulis yang paling efektif: salin satu kata tiga kali sambil menyebut bunyinya. Saat tangan dan telinga terlatih bersama, ingatannya jauh lebih kuat.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'bahasa-jepang',
            'nama' => 'Mengenal Hiragana',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Dasar menulis bahasa Jepang dengan huruf hiragana.',
            'thumbnail' => 'https://images.unsplash.com/photo-1547981609-4b6bfe67ca0b?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Apa Itu Hiragana

            Hiragana adalah salah satu dari tiga jenis huruf dalam bahasa Jepang. Jumlahnya 46 huruf dan semuanya relatif mudah dipelajari karena bentuknya memang dibuat untuk ditulis cepat.

            Dua huruf lain yang akan kamu temui belakangan: katakana untuk kata serapan, dan kanji untuk kata yang punya makna sendiri.

            ## Lima huruf pertama

            - **あ (a)**, **い (i)**, **う (u)**, **え (e)**, **お (o)** — lima vokal yang jadi dasar seluruh hiragana.
            - Urutannya sama persis dengan a i u e o di bahasa Indonesia, jadi tinggal dipindahkan bunyinya.
            - Bentuknya ditulis satu arah: dari atas ke bawah, lalu dari kiri ke kanan.

            > Jangan menghafal 46 huruf sekaligus. Lima huruf setiap hari sudah cukup, dan dalam sepuluh hari seluruh gojuon selesai.

            # Membaca Kata Pertama

            Latih kecepatan membaca dengan kata yang pendek dan sering muncul, bukan dengan kalimat panjang.

            ```text
            さくら      ->  sakura    ->  bunga sakura
            ありがとう   ->  arigatou  ->  terima kasih
            こんにちは   ->  konnichiha ->  selamat siang
            ```

            Setelah huruf per huruf terbaca, baca satu kata penuh tanpa berhenti di tengah. Kalau masih tersendat, ulangi lagi dari awal kata itu.

            # Menulis Hiragana

            Hiragana ditulis mengikuti urutan goresan yang sudah ditetapkan. Goresan yang salah membuat huruf terasa janggal meskipun bentuknya hampir sama.

            - Mulai dari goresan yang berada paling atas.
            - Goresan mendatang selalu ditulis setelah goresan mendatar.
            - Berhenti sejenak di ujung goresan berhenti, jangan ditarik memanjang.

            Paling mudah dilatih dengan menulis nama diri sendiri dalam hiragana setiap hari.

            # Latihan Belajar

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'matematika',
            'nama' => 'Pecahan dan Pecahan Campuran',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Mengenal pecahan biasa, pecahan campuran, dan cara membandingkannya.',
            'thumbnail' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Mengenal Pecahan

            Pecahan menyatakan bagian dari keseluruhan. Ada dua angka di dalamnya: pembilang di atas yang menyatakan berapa bagian yang diambil, dan penyebut di bawah yang menyatakan berapa bagian penyusun keseluruhan.

            ```text
            3/4  ->  pembilang 3, penyebut 4  ->  tiga bagian dari empat
            ```

            Penyebut yang sama membuat dua pecahan langsung bisa dibandingkan, jadi menyamakan penyebut selalu langkah pertama.

            ## Pecahan campuran

            Pecahan campuran adalah pecahan yang bagian bulatnya lebih dari satu. Contohnya satu setengah ditulis 1 1/2.

            Cara mengubah pecahan campuran menjadi pecahan biasa: kalikan bilangan bulat dengan penyebut, lalu tambahkan pembilangnya.

            ```text
            1 1/2  =  (1 x 2 + 1) / 2  =  3/2
            2 3/4  =  (2 x 4 + 3) / 4  =  11/4
            ```

            > Tiga per empat ditulis 3/4, bukan 0 3/4. Pecahan campuran selalu punya bilangan bulat di depan pecahannya.

            # Membandingkan Dua Pecahan

            Untuk membandingkan, samakan penyebutnya dulu. Tiga kasus yang paling sering muncul:

            - **1/2 dan 2/3** -> samakan jadi 3/6 dan 4/6, jadi 2/3 lebih besar.
            - **5/8 dan 5/10** -> pembilang sama, penyebut kecil berarti potongannya lebih besar, jadi 5/8 menang.
            - **2/5 dan 3/7** -> kalikan silang: 2 x 7 = 14 dan 3 x 5 = 15, jadi 3/7 lebih besar.

            # Mengubah Bentuk Pecahan

            Pecahan bisa dipindah bentuk tanpa nilainya berubah:

            - Pecahan biasa menjadi pecahan desimal: bagi pembilang dengan penyebut.
            - Pecahan desimal menjadi persen: kalikan dengan 100.
            - Persen menjadi pecahan: tulis sebagai pecahan berpenyebut 100 lalu sederhanakan.

            ```text
            3/4 = 3 : 4 = 0,75 = 75%
            1/5 = 1 : 5 = 0,20 = 20%
            ```

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'ipa',
            'nama' => 'Sistem Tata Surya dan Geraknya',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Mengenal planet-planet, urutan dari Matahari, dan gerak revolusi.',
            'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Isi Tata Surya

            Tata Surya adalah kumpulan planet yang mengorbit sebuah bintang, yaitu Matahari. Ada delapan planet yang urutannya dari Matahari: Merkurius, Venus, Bumi, Mars, Jupiter, Saturnus, Uranus, dan Neptunus.

            Planet terbagi dua kelompok:

            - **Planet dalam** (Merkurius, Venus, Bumi, Mars) — berselimut batuan dan berukuran kecil.
            - **Planet luar** (Jupiter, Saturnus, Uranus, Neptunus) — berselimut gas dan berukuran jauh lebih besar.

            > Jupiter adalah planet terbesar, sedangkan Merkurius sekaligus yang paling kecil dan paling dekat dengan Matahari.

            ## Gerak revolusi

            Setiap planet mengelilingi Matahari pada lintasan berbentuk elips. Waktu satu putaran penuh inilah yang kita sebut tahun.

            ```text
            Bumi     ->  365,25 hari
            Mars     ->  687 hari
            Jupiter  ->  11,9 tahun
            ```

            # Rotasi dan Revolusi

            Planet bergerak dua cara sekaligus, dan keduanya sering tertukar:

            - **Rotasi** — berputar pada porosnya sendiri, memakan waktu sekitar 24 jam untuk Bumi, dan inilah penyebab siang serta malam.
            - **Revolusi** — mengelilingi Matahari, memakan waktu satu tahun untuk Bumi.

            Bulan sendiri berputar pada porosnya dengan waktu yang sama dengan waktu revolusinya terhadap Bumi, sehingga satu sisi Bulan selalu menghadap ke Bumi.

            # Bumi yang Kita Tempati

            Bumi adalah satu-satunya planet yang punya air cair dan kehidupan. Tiga syaratnya saling mendukung: jaraknya dari Matahari tepat untuk menjaga suhu, atmosfernya menahan panas, dan inti besinya menghasilkan medan magnet.

            ```text
            jarak Bumi ke Matahari  =  150 juta km (1 SA)
            suhu rata-rata          =  15 derajat celsius
            umur                    =  4,5 miliar tahun
            ```

            Angka jarak itulah yang membuat Bumi masuk zona layak huni: terlalu dekat seperti Venus airnya mendidih, terlalu jauh seperti Mars airnya membeku.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'pemrograman',
            'nama' => 'HTML Dasar',
            'tingkat_kesulitan' => 'Mudah',
            'deskripsi' => 'Materi dasar HTML untuk pemula.',
            'pembuat' => 'admin@kelaskita.test',
            'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1120&h=600&q=75',

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
            'thumbnail' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Apa Itu CSS

            CSS mengatur tampilan tanpa menyentuh isi halaman. Satu aturan punya tiga bagian: selektor, properti, dan nilai.

            ```css
            h1 {
              color: #6c4de6;
            }
            ```

            Aturan di atas membuat semua judul `h1` berwarna ungu. Selektor menentukan elemen mana yang diubah, properti menentukan sisi mana yang diubah, dan nilai menentukan hasil akhirnya.

            ## Jenis selektor

            - **Nama tag** — `p` berlaku untuk semua paragraf.
            - **Kelas dengan titik** — `.kartu` dipakai ketika aturan yang sama dipakai di banyak elemen.
            - **Id dengan pagar** — `#judul-utama` hanya untuk satu elemen di halaman.

            > Kelas lebih sering dipakai daripada id untuk styling sehari-hari, karena satu halaman boleh memakai kelas yang sama berkali-kali.

            # Warna dan Panjang

            Warna ditulis sebagai kode enam digit diawali tanda pagar. Untuk warna dengan transparansi, tambahkan alphanya di belakangnya.

            ```css
            .bagian {
              background: #6c4de6;
              opacity: 0.75;
              padding: 16px 24px;
            }
            ```

            Perbedaan dua angka pada `padding` perlu dihafal:

            - `margin` adalah jarak **di luar** elemen.
            - `padding` adalah jarak **di dalam** elemen.
            - Dua angka berarti atas-bawah lalu kiri-kanan, satu angka berlaku untuk keempat sisinya.

            # Mengatur Tata Letak

            Flexbox dipakai untuk menyusun elemen dalam satu arah, grid untuk dua arah sekaligus.

            ```css
            .daftar {
              display: flex;
              gap: 16px;
              justify-content: space-between;
            }
            ```

            Dengan `justify-content: space-between`, anak-anaknya menempel di ujung kiri dan kanan sementara sisa ruangnya dibagi rata di tengah. `gap: 16px` menjaga jaraknya tetap seragam tanpa menulis margin satu per satu.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'desain-web',
            'nama' => 'UI/UX Design',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Mengenal dasar desain antarmuka pengguna.',
            'pembuat' => 'keyla@kelaskita.test',
            'thumbnail' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # UI dan UX

            UI adalah bagian yang dilihat pengguna: tombol, menu, kartu, dan warna. UX adalah pengalaman memakai aplikasi secara keseluruhan, termasuk seberapa mudah seseorang menyelesaikan tugasnya.

            UI yang cantik dengan UX yang buruk tetap terasa merepotkan. Karena itu keduanya dievaluasi bersama, bukan terpisah.

            ## Empat prinsip dasar

            - **Kedekatan** — elemen yang berhubungan ditempatkan berdekatan.
            - **Kontras** — hal penting dibuat berbeda dari hal yang ada di sekitarnya.
            - **Pengulangan** — pola yang sama dipakai ulang di seluruh halaman.
            - **Keselarasan** — setiap elemen punya acuan garis yang sama.

            > Kalau satu layar terasa ramai, biasanya bukan karena terlalu banyak elemen, melainkan karena jarak antar elemen tidak jelas.

            # Aksesibilitas

            Warna teks utama harus punya kontras yang cukup terhadap latarnya. Jangan mengandalkan warna saja untuk menyampaikan informasi, tambahkan bentuk atau teks pendukung.

            ```text
            kontras minimum teks biasa  :  4,5 : 1
            kontras minimum teks besar  :  3 : 1
            ukuran sentuh tombol        :  44 x 44 piksel
            ```

            Setiap gambar yang membawa makna perlu teks alternatif, dan setiap form perlu label yang benar-benar terhubung ke kolom isian lewat atribut `for`.

            # Menyusun Alur

            Alur yang baik dimulai dari satu pertanyaan: pengguna datang untuk apa, dan langkah terakhir apa yang ia butuhkan.

            - Tuliskan alurnya sebagai daftar langkah sebelum mulai menggambar.
            - Kurangi jumlah langkah setiap kali ada pilihan yang bisa dilewati.
            - Uji kebingungan dengan memberi tugas pada orang yang belum pernah memakai aplikasimu.

            Formulir yang panjang sebaiknya dipisah menjadi beberapa langkah pendek. Pengguna menyelesaikan langkah pertama, lalu merasa sudah jauh sebelum sampai di akhir.

            # Latihan Praktik

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'pemrograman',
            'nama' => 'JavaScript Dasar',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Logika dan interaksi pada website.',
            'pembuat' => 'irma@kelaskita.test',
            'thumbnail' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # JavaScript di Browser

            JavaScript membuat halaman web bisa merespons aksi pengguna: klik, ketik, dan geser. Semua kode di browser dimulai dari tiga hal ini: variabel, operator, dan kondisi.

            ## Cara menyambungkan ke HTML

            Cara paling cepat menyambungkan JavaScript ke HTML adalah atribut `onclick`:

            ```html
            <button type="button" onclick="hitung()">Tambah</button>
            <span id="angka">0</span>
            ```

            > Atribut inline memang praktis untuk percobaan. Untuk proyek yang lebih besar, pindahkan ke berkas `.js` terpisah supaya logika dan tampilan tidak tercampur.

            # Variabel dan Kondisi

            Variabel dideklarasikan dengan `let` atau `const`. Gunakan `let` kalau nilainya nanti berubah, `const` kalau nilainya sudah final sejak awal.

            ```javascript
            let jumlah = 0;
            const nama = "Kelas Kita";

            function hitung() {
              jumlah = jumlah + 1;
              document.getElementById("angka").textContent = jumlah;
            }
            ```

            Kondisi memakai `if`, `else if`, dan `else`. Operator perbandingannya `===` untuk setara dan `!==` untuk tidak setara, karena keduanya membandingkan nilai sekaligus tipe datanya.

            # Array dan Perulangan

            Kumpulan data disimpan di array, lalu dibaca berulang dengan perulangan.

            ```javascript
            const nilai = [80, 90, 75];
            let total = 0;

            for (const n of nilai) {
              total += n;
            }
            ```

            - `for ... of` membaca nilai tiap baris.
            - `forEach` dipakai ketika urutannya harus persis seperti aslinya.
            - `map` menghasilkan array baru, `filter` menyaring, dan `reduce` menjumlahkan.

            `total` memakai `let` karena nilainya berubah pada setiap putaran. Kalau nilai sebuah variabel memang tidak boleh berubah, pakai `const` sejak awal supaya tidak ada yang mengubahnya tanpa sengaja.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'database',
            'nama' => 'Basis Data',
            'tingkat_kesulitan' => 'Sedang',
            'deskripsi' => 'Pengenalan database dan SQL dasar.',
            'pembuat' => 'khanif@kelaskita.test',
            'thumbnail' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Tabel, Baris, dan Kolom

            Database menyimpan data dalam bentuk tabel. Baris adalah satu record, kolom adalah atribut yang dimiliki setiap record.

            Primary key membedakan setiap baris supaya tidak kembar. Biasanya dipakai kolom `id` yang bernilai unik dan tidak pernah diisi ulang.

            ```sql
            CREATE TABLE siswa (
              id     INTEGER PRIMARY KEY,
              nama   TEXT NOT NULL,
              kelas  TEXT NOT NULL
            );
            ```

            ## Jenis data yang umum

            - `INTEGER` untuk angka bulat seperti nilai dan tahun.
            - `TEXT` untuk teks seperti nama dan alamat.
            - `REAL` untuk angka pecahan seperti rata-rata.
            - `DATETIME` untuk tanggal dan waktu.

            > Pilih jenis data yang paling sempit. Kolom `nilai` sebaiknya `INTEGER`, bukan `TEXT`, supaya bisa langsung diurutkan dan dibandingkan.

            # Empat Perintah Utama

            SQL punya empat perintah yang menyusun hampir semua pekerjaan sehari-hari:

            - **SELECT** — membaca data.
            - **INSERT** — menambah data baru.
            - **UPDATE** — mengubah data yang sudah ada.
            - **DELETE** — menghapus data.

            ```sql
            INSERT INTO siswa (id, nama, kelas) VALUES (1, 'Natania', '10A');

            SELECT nama, kelas FROM siswa WHERE kelas = '10A';

            UPDATE siswa SET kelas = '10B' WHERE id = 1;
            ```

            > Pakai klausa `WHERE` setiap kali menulis `SELECT`, `UPDATE`, atau `DELETE`. Tanpa `WHERE`, seluruh baris di tabel ikut kena.

            # Menggabungkan Dua Tabel

            Saat dua tabel saling berhubungan, datanya dipasangkan lewat kunci asing.

            ```sql
            SELECT s.nama, n.nilai
              FROM siswa s
              JOIN nilai n ON n.siswa_id = s.id
             WHERE n.nilai >= 75;
            ```

            - `INNER JOIN` hanya mengambil baris yang cocok di dua sisi.
            - `LEFT JOIN` tetap mengambil baris kiri meski tidak punya pasangan.
            - Selalu beri alias tabel seperti `s` dan `n`, supaya kueri tetap terbaca.

            Mengurutkan hasilnya pakai `ORDER BY`, dan membatasi jumlah baris pakai `LIMIT`.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
        ],
        [
            'kategori' => 'teknologi',
            'nama' => 'Pengembangan Web',
            'tingkat_kesulitan' => 'Sulit',
            'deskripsi' => 'Dari desain hingga deployment.',
            'pembuat' => 'heysell@kelaskita.test',
            'thumbnail' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=1120&h=600&q=75',
            'isi' => <<<'ISI'
            # Tahapan Membangun Aplikasi

            Membangun aplikasi web punya tahap yang berurutan dan sebaiknya tidak dilompati begitu saja:

            - **Kebutuhan** — diputuskan dulu siapa penggunanya dan masalah apa yang diselesaikan.
            - **Desain** — alur dan tampilannya digambar sebelum kodenya ditulis.
            - **Pengembangan** — baris kode ditulis mengikuti desain yang sudah disepakati.
            - **Pengujian** — kesalahan dicari sebelum sampai ke pengguna.
            - **Deployment** — aplikasi diunggah ke server dan dijalankan.

            > Sering kali bagian paling mahal adalah mengubah kebutuhan setelah pengembangan dimulai. Karena itu kebutuhan dibahas sampai tuntas sebelum baris kode pertama ditulis.

            ## Mengapa urutannya penting

            Tahap yang dilompati selalu kembali tagih dengan bunga. Desain yang dilewati membuat pengembang menebak-nebak, pengujian yang dilewati memindahkan biaya perbaikan ke pengguna, dan deployment tanpa persiapan membuat rilis gagal di menit-menit pertama.

            # Version Control dengan Git

            Git dipakai sejak awal supaya setiap perubahan punya riwayat dan bisa dikembalikan bila ada kesalahan.

            ```bash
            git init
            git add .
            git commit -m "Tambahkan halaman materi"
            git push -u origin main
            ```

            - Satu commit sebaiknya mengerjakan satu hal dan bisa dijelaskan dalam satu baris pesan.
            - Cabang baru dibuat untuk fitur yang berisiko, lalu digabung lewat pull request.
            - Berkas rahasia tidak boleh masuk riwayat; simpan nilainya di environment variable.

            # Pengujian dan Deployment

            Pengujian minimal mencakup dua sisi: cek tampilan di beberapa ukuran layar, dan cek alur yang paling sering dipakai pengguna.

            ```bash
            npm run build
            php artisan test --compact
            ```

            Deployment berarti mengunggah berkas terbaru ke server, menjalankan migrasi basis data, lalu mengatur environment variable seperti kunci API dan alamat basis data.

            Setelah aplikasi jalan, pantau dulu sebelum diumumkan: lihat log kesalahan, cek halaman paling sering dikunjungi, dan pastikan tidak ada permintaan yang gagal. Perbaikan menyusul jauh lebih murah daripada memperbaiki setelah banyak pengguna datang.

            # Latihan Praktik

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia.
            ISI,
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
                    // Gambar contoh diambil dari URL penuh, bukan berkas
                    // unggahan, jadi tidak perlu ada berkas di disk publik.
                    'thumbnail' => $materi['thumbnail'],
                    'tingkat_kesulitan' => $materi['tingkat_kesulitan'],
                    /*
                     * Materi bawaan seeder dianggap sudah disetujui admin:
                     * kalau tidak, halaman publik Materi akan kosong begitu
                     * database diisi ulang.
                     */
                    'status' => Materi::STATUS_PUBLISHED,
                    'dipublish_pada' => now(),
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
