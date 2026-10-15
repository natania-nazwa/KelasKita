<?php

namespace Database\Seeders;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Antrean materi contoh untuk halaman Verifikasi di area admin.
 *
 *     php artisan db:seed --class=MateriVerifikasiDemoSeeder
 *
 * Yang dibuatnya: delapan materi berstatus "pending" dari delapan akun
 * pengguna yang SUDAH ADA, jadi halaman Verifikasi punya isi untuk diuji
 * (menandai "Diproses", menyetujui, dan menolak) tanpa harus mengunggah satu
 * per satu lewat akun masing-masing.
 *
 * Akun pembuat tidak dibuat oleh seeder ini. Kalau ada email yang belum ada,
 * materi itu dilewati dan dilaporkan di akhir, supaya materi milik akun asli
 * tidak tertimpa atau tertukar. Kalau perlu membuat akunnya, jalankan dulu
 * MateriSeeder (membuat natania/keyla/irma/khanif/heysell) dan
 * MasukanSaranDemoSeeder (membuat budi/rina demo).
 *
 * Aman dijalankan berulang: materi dicocokkan lewat "nama", jadi baris yang
 * sudah ada diperbarui, bukan diduplikasi.
 *
 * Hapus lagi dengan:
 *
 *     php artisan db:seed --class=MateriVerifikasiDemoHapusSeeder
 */
class MateriVerifikasiDemoSeeder extends Seeder
{
    /**
     * Materi. Key "pembuat" menunjuk email akun yang harus sudah ada; key
     * "kategori" menunjuk slug mata pelajaran.
     *
     * @var array<int, array<string, string>>
     */
    private const MATERI = [
        [
            'pembuat' => 'natania@kelaskita.test',
            'kategori' => 'matematika',
            'nama' => 'Persamaan Linear Satu Variabel',
            'tingkat_kesulitan' => 'Sedang',
            'estimasi_waktu' => '1 jam 15 menit',
            'deskripsi' => 'Menyelesaikan persamaan linear satu variabel, dari bentuk dasar sampai penerapannya di soal cerita.',
            'thumbnail' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Selalu periksa hasilnya dengan memasukkan nilai variabel kembali ke persamaan awal.',
            'isi' => <<<'ISI'
            # Apa Itu Persamaan Linear Satu Variabel

            Persamaan linear satu variabel adalah kalimat matematika yang mengandung satu variabel berpangkat satu dan dihubungkan oleh tanda sama dengan. Bentuk umumnya ditulis ax + b = c, dengan a tidak sama dengan nol.

            ```text
            2x + 3 = 11        ->  variabel x, berpangkat satu
            5y - 2 = 18        ->  variabel y, berpangkat satu
            ```

            Kata "linear" menandakan pangkat variabelnya tepat satu, sedangkan "satu variabel" berarti hanya ada satu huruf yang dicari nilainya. Kalau muncul pangkat dua seperti x^2, persamaannya bukan linear lagi melainkan kuadrat.

            Menyelesaikan persamaan berarti mencari semua nilai variabel yang membuat kedua ruas bernilai sama. Nilai itu disebut penyelesaian atau akar persamaan.

            ## Mengapa disebut "satu variabel"

            Persamaan boleh punya banyak angka, tetapi hanya satu jenis huruf. Contoh 2x + 5 = 17 hanya punya huruf x, jadi tetap disebut satu variabel meskipun angka yang muncul lebih dari satu.

            # Membedakan Persamaan, Pertidaksamaan, dan Identitas

            Tiga bentuk berikut sering tertukar karena sama-sama memuat variabel, padahal maksudnya berbeda.

            - **Persamaan** memakai tanda sama dengan (=) dan penyelesaiannya satu nilai pasti.
            - **Pertidaksamaan** memakai tanda kurang dari, lebih dari, atau variannya, dan penyelesaiannya berupa rentang nilai.
            - **Identitas** selalu benar untuk setiap nilai variabel, misalnya 2(x + 1) = 2x + 2.

            ```text
            2x + 3 = 11       ->  persamaan, x = 4
            2x + 3 > 11       ->  pertidaksamaan, x > 4
            2(x + 1) = 2x + 2 ->  identitas, berlaku untuk semua x
            ```

            > Ciri identitas adalah kedua ruasnya berbentuk sama setelah disederhanakan. Kalau setelah dijabarkan ruas kiri dan kanan persis sama, persamaan itu identitas, bukan persamaan bernilai tunggal.

            # Sifat Kesetaraan dan Langkah Penyelesaian

            Prinsip utamanya sederhana: setiap operasi yang dikenakan pada satu ruas harus dikenakan juga pada ruas lainnya, supaya kesetaraan tetap terjaga. Inilah yang disebut sifat kesetaraan.

            Ada dua langkah yang paling sering dipakai:

            - **Menambah atau mengurangi** kedua ruas dengan bilangan yang sama, untuk memindahkan suku.
            - **Mengalikan atau membagi** kedua ruas dengan bilangan yang sama (bukan nol), untuk menghilangkan koefisien.

            Contoh menyelesaikan 2x + 3 = 11:

            ```text
            2x + 3 = 11
            2x + 3 - 3 = 11 - 3        (kurangi kedua ruas dengan 3)
            2x = 8
            x = 4                      (bagi kedua ruas dengan 2)
            ```

            Perhatikan bahwa setiap baris tetap setara dengan baris sebelumnya. Kesalahan paling umum adalah memindahkan suku tanpa mengubah tandanya, sehingga hasilnya meleset satu langkah.

            # Persamaan dengan Variabel di Kedua Ruas

            Sebagian persamaan menaruh variabel di kedua sisi tanda sama dengan. Langkahnya tetap sama: kumpulkan semua suku bervariabel di satu ruas dan semua angka di ruas lainnya.

            Contoh menyelesaikan 5x - 4 = 3x + 8:

            ```text
            5x - 4 = 3x + 8
            5x - 3x = 8 + 4            (pindahkan suku sejenis)
            2x = 12
            x = 6
            ```

            Setelah variabel terkumpul di satu ruas, persoalannya kembali menjadi bentuk sederhana ax = b, yang penyelesaiannya tinggal dibagi koefisiennya.

            # Penerapan di Soal Cerita

            Soal cerita menyembunyikan variabel di balik kata-kata. Kuncinya ada dua: menamai variabel dengan jelas, lalu menerjemahkan setiap keterangan menjadi angka dan operasi.

            Contoh: harga tiga buku tulis dan sebuah pulpen adalah Rp23.000. Jika pulpen berharga Rp5.000, berapa harga satu buku tulis?

            ```text
            misalkan b = harga satu buku tulis
            3b + 5000 = 23000
            3b = 18000
            b = 6000
            ```

            Jadi harga satu buku tulis adalah Rp6.000. Periksa kembali: 3 x 6000 + 5000 = 18000 + 5000 = 23000, cocok dengan keterangan soal.

            > Biasakan menuliskan pemisalan di awal. Tanpa pemisalan, langkah berikutnya mudah tercampur antara harga satu buku dan harga seluruh buku.

            # Memeriksa Jawaban dan Kesalahan Umum

            Memasukkan kembali nilai yang ditemukan ke persamaan awal adalah cara paling cepat memastikan jawaban benar, sekaligus menangkap kesalahan tanda lebih dini.

            Kesalahan yang paling sering muncul:

            - Memindahkan suku tanpa mengubah tandanya (dari +3 menjadi -3).
            - Membagi hanya satu suku, bukan seluruh ruas.
            - Salah membaca koefisien yang tersembunyi, misalnya x yang sebenarnya berarti 1x.
            - Pada soal cerita, menjawab dengan nilai variabel tanpa menuliskan satuannya.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia. Mulai dari persamaan paling sederhana, lalu lanjut ke bentuk yang punya variabel di kedua ruas.
            ISI,
        ],
        [
            'pembuat' => 'keyla@kelaskita.test',
            'kategori' => 'bahasa-indonesia',
            'nama' => 'Teks Berita dan Unsur 5W+1H',
            'tingkat_kesulitan' => 'Mudah',
            'estimasi_waktu' => '1 jam',
            'deskripsi' => 'Mengenal ciri, struktur, dan unsur teks berita serta cara menemukan gagasan utamanya.',
            'thumbnail' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Saat membaca berita, tandai dulu unsur 5W+1H-nya sebelum menilai isinya.',
            'isi' => <<<'ISI'
            # Apa Itu Teks Berita

            Teks berita adalah teks yang melaporkan kejadian nyata dan aktual kepada pembaca. Berita selalu berpijak pada fakta, bukan pendapat penulisnya, sehingga pembaca bisa menyimpulkan sendiri maknanya.

            Berita yang baik ditulis secara berimbang: semua pihak yang berkaitan diberi ruang, dan pemisahan antara fakta dengan opini dijaga jelas.

            # Ciri Bahasa dan Struktur Teks Berita

            Bahasa berita berbeda dari bahasa cerita. Ia lugas, singkat, dan berusaha menghindari kata yang bermakna ganda.

            Tiga ciri kebahasaan yang paling menonjol:

            - **Lugas** — memakai makna sebenarnya, bukan kiasan.
            - **Baku** — mengikuti aturan ejaan dan kata baku.
            - **Sederhana** — kalimatnya tidak berbelit supaya cepat dipahami.

            Kalimat berita biasanya berbentuk kalimat tunggal dengan pola yang jelas: siapa melakukan apa, kapan, dan di mana.

            # Unsur 5W+1H

            Setiap berita dianggap lengkap bila memuat enam unsur berikut. Keenamnya menjawab pertanyaan yang mungkin muncul di benak pembaca.

            ```text
            What   ->  peristiwa apa yang terjadi
            Who    ->  siapa yang terlibat
            Where  ->  di mana kejadiannya
            When   ->  kapan terjadinya
            Why    ->  mengapa hal itu terjadi
            How    ->  bagaimana prosesnya berlangsung
            ```

            Dalam praktiknya, unsur "what, who, where, when" hampir selalu ada di paragraf pertama, sementara "why" dan "how" menyusul di paragraf berikutnya.

            > Kalau salah satu unsur hilang, bagian itu belum tuntas. Misalnya berita tanpa unsur "when" membuat pembaca tidak tahu seberapa baru kejadiannya.

            # Struktur Piramida Terbalik

            Berita disusun memakai pola piramida terbalik: bagian terpenting diletakkan di depan, lalu makin ke bawah makin kurang penting.

            - **Judul** — menarik perhatian dalam satu baris singkat.
            - **Kepala berita (lead)** — memuat inti kejadian di paragraf pertama.
            - **Badan berita** — menjelaskan sebab, akibat, dan rincian.
            - **Ekor berita** — latar belakang dan informasi pelengkap.

            Cara penyusunan ini dipakai karena pembaca sering hanya membaca beberapa baris pertama. Dengan pola ini, informasi yang paling penting tidak pernah tertimbun di akhir.

            # Jenis-Jenis Berita

            Berita bisa dibedakan menurut isinya. Pengelompokan ini membantu pembaca memilih bacaan sesuai kebutuhan.

            ```text
            berita langsung  ->  melaporkan peristiwa terbaru secara singkat
            berita mendalam  ->  membahas latar belakang dan sebab kejadian
            berita opini     ->  berisi analisis dari sudut pandang penulis
            berita ringan    ->  berisi hal menarik yang menghibur
            ```

            Meskipun jenisnya berbeda, semuanya tetap wajib memuat unsur 5W+1H dan memisahkan fakta dari pendapat.

            # Latihan Membaca Berita

            Ambil satu berita dari surat kabar, lalu garis bawahi setiap unsur 5W+1H yang kamu temukan. Setelah itu, coba tulis ulang isinya menjadi satu paragraf pendek tanpa menghilangkan intinya. Langkah ini melatih kemampuan memilah informasi penting dari informasi pelengkap.
            ISI,
        ],
        [
            'pembuat' => 'irma@kelaskita.test',
            'kategori' => 'ipa',
            'nama' => 'Klasifikasi Makhluk Hidup',
            'tingkat_kesulitan' => 'Sedang',
            'estimasi_waktu' => '1 jam 10 menit',
            'deskripsi' => 'Mengelompokkan makhluk hidup berdasarkan cirinya, dari tingkat takson sampai sistem lima kingdom.',
            'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Hafalkan urutan takson dengan jembatan keledai, misalnya "Kami Punya Celana Cokelat Hubungan Keluarga Sultan".',
            'isi' => <<<'ISI'
            # Mengapa Makhluk Hidup Diklasifikasikan

            Jumlah makhluk hidup di bumi sangat banyak dan beragam. Kalau tidak dikelompokkan, mempelajarinya akan sangat sulit karena setiap jenis tampak berdiri sendiri. Klasifikasi menyusun keberagaman itu ke dalam kelompok berdasarkan kesamaan ciri.

            Ada dua manfaat utama. Pertama, mempermudah penemuan hubungan kekerabatan antar makhluk hidup. Kedua, menyederhanakan penamaan jutaan jenis makhluk hidup yang telah ditemukan.

            ## Dasar pengelompokan

            - **Ciri morfologi** — bentuk luar tubuh, jumlah kaki, atau bentuk daun.
            - **Ciri anatomi** — susunan organ penyusun tubuh bagian dalam.
            - **Ciri fisiologi** — cara kerja fungsi tubuh, misalnya pernapasan.
            - **Ciri biokimia** — kandungan zat pada sel, termasuk susunan DNA.

            # Tingkat Takson

            Kelompok dalam klasifikasi disusun bertingkat, dari yang paling umum sampai paling khusus. Semakin rendah tingkatannya, semakin sedikit anggotanya, tetapi semakin banyak kesamaan cirinya.

            ```text
            Kingdom   -> Filum (hewan) / Divisi (tumbuhan)
            Classis   -> kelas
            Ordo      -> bangsa
            Familia   -> suku
            Genus     -> marga
            Species   -> jenis
            ```

            Contoh lengkap untuk tanaman padi: kingdom Plantae, divisi Magnoliophyta, kelas Liliopsida, ordo Poales, familia Poaceae, genus Oryza, spesies Oryza sativa.

            # Sistem Lima Kingdom

            Sistem yang paling banyak dipakai membagi makhluk hidup menjadi lima kingdom. Pembagian ini didasarkan pada ada tidaknya membran inti dan cara memperoleh makanan.

            - **Monera** — tidak bermembran inti, contohnya bakteri dan ganggang biru.
            - **Protista** — bermembran inti tetapi satu sel, contohnya amoeba dan paramecium.
            - **Fungi** — menyerap makanan dari lingkungan, contohnya jamur dan ragi.
            - **Plantae** — berfotosintesis karena punya klorofil.
            - **Animalia** — memakan makhluk hidup lain dan umumnya bergerak aktif.

            > Perbedaan paling mendasar antara Monera dan Protista bukan pada jumlah selnya, melainkan pada ada tidaknya membran inti.

            # Tata Nama Binomial

            Setiap makhluk hidup punya satu nama ilmiah yang dibuat dengan tata nama binomial, diperkenalkan Carolus Linnaeus. Nama terdiri dari dua kata: nama genus dan nama spesies.

            ```text
            Oryza sativa   ->  padi
            Homo sapiens   ->  manusia
            Panthera tigris ->  harimau
            ```

            Aturannya: kata pertama (genus) dimulai huruf besar, kata kedua (spesies) huruf kecil, dan keduanya dicetak miring atau digaris bawahi terpisah.

            # Kunci Determinasi Sederhana

            Kunci determinasi adalah daftar pertanyaan berurutan yang menuntun pada nama suatu makhluk hidup. Jawaban setiap pertanyaan membawa ke pertanyaan berikutnya sampai akhirnya ditemukan jenisnya.

            ```text
            1. ada tulang belakang?   ya -> lanjut ke nomor 2
                                      tidak -> avertebrata
            2. penutup tubuh?         rambut -> mamalia
                                      bulu   -> aves
            ```

            Cara ini membuat pengenalan jenis bisa dilakukan tanpa harus menghafal seluruh daftar makhluk hidup, cukup mengikuti alur pertanyaannya.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia. Satu di antaranya meminta kamu menyusun urutan takson dari yang tertinggi ke terendah.
            ISI,
        ],
        [
            'pembuat' => 'khanif@kelaskita.test',
            'kategori' => 'pplg',
            'nama' => 'Algoritma dan Flowchart',
            'tingkat_kesulitan' => 'Sedang',
            'estimasi_waktu' => '1 jam 20 menit',
            'deskripsi' => 'Menyusun langkah penyelesaian masalah dengan algoritma serta menggambarkannya sebagai flowchart.',
            'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Tulis algoritmanya dalam kalimat biasa dulu sebelum menggambar flowchart-nya.',
            'isi' => <<<'ISI'
            # Apa Itu Algoritma

            Algoritma adalah urutan langkah yang jelas untuk menyelesaikan sebuah masalah. Kata "jelas" penting: setiap langkah harus punya satu arti saja, punya awal dan akhir, dan berhenti pada satu hasil yang bisa diperiksa.

            Algoritma tidak bergantung pada bahasa pemrograman. Langkah yang sama bisa ditulis dalam bahasa Indonesia, lalu diterjemahkan ke bahasa apa pun yang dipakai.

            Contoh paling sederhana, algoritma menukar isi dua gelas:

            - Siapkan gelas kosong sebagai penampung sementara.
            - Pindahkan isi gelas pertama ke gelas kosong.
            - Pindahkan isi gelas kedua ke gelas pertama.
            - Pindahkan isi gelas kosong ke gelas kedua.
            - Selesai.

            # Syarat Algoritma yang Baik

            Tidak semua urutan langkah bisa disebut algoritma. Ada beberapa syarat yang harus dipenuhi supaya langkahnya bisa dipercaya.

            - **Berhingga** — langkahnya berakhir, tidak berputar tanpa henti.
            - **Terbatas** — setiap langkah dapat dikerjakan dengan sumber daya yang ada.
            - **Jelas** — tidak ada langkah yang bermakna ganda.
            - **Ada masukan dan keluaran** — memiliki data awal dan hasil akhir yang bisa diperiksa.
            - **Efektif** — setiap langkah benar-benar menghasilkan kemajuan.

            # Urutan, Pemilihan, dan Pengulangan

            Hampir semua algoritma dibangun dari tiga struktur dasar berikut. Mengenal ketiganya membuat kita lebih mudah menyusun langkah untuk masalah apa pun.

            - **Urutan (sequence)** — langkah dikerjakan berurutan dari atas ke bawah.
            - **Pemilihan (selection)** — ada percabangan berdasarkan suatu kondisi.
            - **Pengulangan (iteration)** — satu blok langkah dikerjakan berkali-kali.

            ```text
            urutan       ->  hitung luas, lalu tampilkan hasilnya
            pemilihan    ->  jika nilai >= 75 maka lulus, selainnya belum
            pengulangan  ->  ulangi untuk setiap siswa di dalam daftar
            ```

            # Simbol Flowchart

            Flowchart adalah gambar algoritma memakai simbol baku, sehingga orang lain dapat membacanya tanpa teks panjang. Bentuk simbol menunjukkan jenis langkahnya.

            ```text
            oval            ->  mulai / selesai
            persegi         ->  proses, mis. hitung total
            belah ketupat   ->  keputusan (ya / tidak)
            jajar genjang   ->  input / output
            panah           ->  arah aliran langkah
            ```

            Aliran gambar selalu dibaca dari atas ke bawah mengikuti arah panah. Setiap belah ketupat harus punya dua jalan keluar yang jelas: satu untuk jawaban ya dan satu untuk tidak.

            # Contoh: Bilangan Ganjil atau Genap

            Langkahnya dimulai dari mengambil angka, menghitung sisanya bila dibagi dua, lalu memutuskan.

            ```text
            mulai
              input(skor)
              jika skor % 2 == 0 maka
                cetak("genap")
              selainnya
                cetak("ganjil")
            selesai
            ```

            Tanda % adalah operasi sisa bagi (modulo). Bilangan yang habis dibagi dua bersisa nol, dan itulah yang kita sebut bilangan genap.

            # Contoh: Nilai Terbesar dari Tiga Angka

            Untuk membandingkan tiga angka, kita bisa memakai variabel penampung sementara yang menyimpan nilai terbesar sejauh ini.

            ```text
            input(a, b, c)
            terbesar = a
            jika b > terbesar maka terbesar = b
            jika c > terbesar maka c > terbesar
            cetak(terbesar)
            ```

            Pola "mulai dari satu nilai, lalu bandingkan satu per satu" ini sangat sering dipakai dan bisa diperluas ke sepuluh atau seratus angka dengan cara yang sama.

            # Latihan Menyusun

            Ubah soal berikut menjadi algoritma lalu gambarkan flowchart-nya: menentukan nilai terbesar dari tiga angka yang diinputkan pengguna. Tulis versinya dalam kalimat biasa lebih dulu, baru dipindahkan ke simbol.
            ISI,
        ],
        [
            'pembuat' => 'heysell@kelaskita.test',
            'kategori' => 'bahasa-inggris',
            'nama' => 'Simple Present Tense',
            'tingkat_kesulitan' => 'Mudah',
            'estimasi_waktu' => '1 jam',
            'deskripsi' => 'Memakai simple present untuk kebiasaan dan fakta, lengkap dengan bentuk negatif dan tanya.',
            'thumbnail' => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Ingat rumusnya: he, she, it menambah -s, sedangkan I, you, we, they tidak.',
            'isi' => <<<'ISI'
            # Kapan Simple Present Dipakai

            Simple present dipakai untuk menyatakan kebiasaan, fakta umum, dan sesuatu yang berlangsung terus-menerus. Waktu kejadiannya tidak ditentukan karena yang ditonjolkan adalah hal yang berulang atau selalu benar.

            - **Kebiasaan** — I study every night.
            - **Fakta umum** — Water boils at 100 degrees.
            - **Jadwal tetap** — The train leaves at seven.
            - **Rasa atau pendapat** — She likes classical music.

            Kalau kejadiannya sedang berlangsung saat ini juga, tenses yang dipakai bukan simple present melainkan present continuous.

            # Rumus dan Penambahan -s

            Untuk subjek I, you, we, dan they, kata kerja ditulis apa adanya. Untuk he, she, dan it, kata kerja ditambah -s atau -es.

            ```text
            I / You / We / They   + verb     ->  They play football.
            He / She / It         + verb-s   ->  She plays football.
            ```

            Aturan penambahan -s/-es:

            - Kata berakhiran -s, -sh, -ch, -x, dan -o ditambah **-es** (goes, watches).
            - Kata berakhiran konsonan + y, huruf y diganti **-ies** (study menjadi studies).
            - Kata berakhiran vokal + y hanya ditambah **-s** (play menjadi plays).
            - Kata kerja have berubah menjadi **has**.

            > Satu kata kerja yang paling sering salah: have. Untuk he, she, it, bentuknya bukan haves, melainkan has.

            # Kalimat Negatif dan Tanya

            Untuk membentuk kalimat negatif dan tanya, dipakai kata bantu do dan does. Bentuk does hanya dipasangkan dengan he, she, dan it.

            ```text
            negatif : subject + do/does + not + verb
            tanya   : do/does + subject + verb ?
            ```

            Contoh penerapannya:

            ```text
            She does not play tennis.
            Do they live here?
            Does he speak Japanese?
            ```

            Perhatikan bahwa saat does dipakai, kata kerjanya kembali ke bentuk dasar tanpa -s. Kalimat "Does he speaks Japanese?" salah karena -s sudah diwakili oleh does.

            # Kata Keterangan Waktu

            Karena menyatakan kebiasaan, simple present sering bertemu dengan kata keterangan waktu tertentu.

            - always, usually, often, sometimes, seldom, never.
            - every day, every week, on Mondays, twice a month.

            ```text
            He (never) forgets his homework.
            We (usually) have breakfast together.
            ```

            Penempatan yang paling umum adalah sebelum kata kerja, tetapi setelah kata kerja bantu be. Contohnya: She is always late, bukan She always is late.

            # Perbedaan dengan Present Continuous

            Dua tenses ini sama-sama berada di masa sekarang, tetapi maksudnya berbeda.

            ```text
            simple present       ->  kebiasaan      : I walk to school every day.
            present continuous   ->  sedang terjadi : I am walking to school now.
            ```

            Kata kerja keadaan seperti know, like, want, dan belong biasanya dipakai dalam simple present meskipun artinya sedang berlangsung, karena tidak menggambarkan kegiatan.

            # Latihan Soal

            Ubah kata kerja di dalam kurung agar kalimat berikut benar: She (watch) the news every morning. Lalu ubah kalimat itu menjadi bentuk negatif dan bentuk tanya, kemudian buat dua kalimat tentang kebiasaanmu sendiri memakai kata always dan never.
            ISI,
        ],
        [
            'pembuat' => 'guru@kelaskita.test',
            'kategori' => 'sejarah',
            'nama' => 'Proklamasi Kemerdekaan Indonesia',
            'tingkat_kesulitan' => 'Sedang',
            'estimasi_waktu' => '1 jam 15 menit',
            'deskripsi' => 'Rangkaian peristiwa sekitar proklamasi 17 Agustus 1945, dari Rengasdengklok sampai maknanya.',
            'thumbnail' => 'https://images.unsplash.com/photo-1461360370896-922624d12aa1?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Susun peristiwanya dalam urutan waktu supaya mudah diingat, dari Rengasdengklok sampai pembacaan teks.',
            'isi' => <<<'ISI'
            # Latar Belakang

            Menjelang pertengahan 1945, posisi Jepang dalam Perang Asia Pasifik semakin terdesak. Berita kekalahan Jepang atas Sekutu membuka peluang bagi bangsa Indonesia untuk menentukan nasibnya sendiri setelah bertahun-tahun dijajah.

            Tekanan untuk segera memproklamasikan kemerdekaan datang terutama dari golongan muda. Mereka menilai kemerdekaan harus direbut sendiri, bukan menunggu hadiah atau janji dari pihak mana pun.

            # Jepang Menyerah dan Kekosongan Kekuasaan

            Pada 15 Agustus 1945, Jepang menyatakan menyerah kepada Sekutu. Peristiwa itu menciptakan kekosongan kekuasaan di Indonesia: Jepang tidak lagi berkuasa penuh, sementara Sekutu belum tiba.

            Golongan muda kemudian mendesak golongan tua supaya proklamasi tidak menunggu kepastian dari Jepang. Perbedaan pandangan soal waktu inilah yang memuncak menjadi peristiwa Rengasdengklok.

            ## Dua golongan yang berhadapan

            - **Golongan muda** — menginginkan proklamasi secepatnya, tanpa campur tangan Jepang.
            - **Golongan tua** — ingin memastikan persiapan matang supaya proklamasi tidak berjalan sia-sia.

            # Peristiwa Rengasdengklok

            Pada 16 Agustus 1945 dini hari, Sukarno dan Hatta dibawa oleh sejumlah pemuda ke Rengasdengklok, Karawang. Tujuannya menjauhkan keduanya dari pengaruh Jepang sekaligus mendesak agar kemerdekaan segera diproklamasikan.

            Setelah berdiskusi, dicapai kesepakatan untuk memproklamasikan kemerdekaan pada 17 Agustus 1945. Setelah kesepakatan itu, rombongan kembali ke Jakarta pada malam harinya.

            # Perumusan Teks di Rumah Maeda

            Malam harinya, teks proklamasi dirumuskan di rumah Laksamana Maeda di Jalan Imam Bonjol. Sukarno, Hatta, dan Ahmad Soebardjo menyusun naskahnya, kemudian Sayuti Melik mengetik naskah yang telah disepakati.

            Beberapa kalimat dalam rancangan awal diubah. Salah satunya adalah ungkapan perpindahan kekuasaan yang akhirnya disepakati berbunyi "Atas nama bangsa Indonesia". Teks juga disepakati ditandatangani oleh Sukarno dan Hatta.

            ```text
            16 Agu 1945 dini hari : peristiwa Rengasdengklok
            16 Agu 1945 malam     : perumusan teks di rumah Maeda
            17 Agu 1945 pagi      : pembacaan teks proklamasi
            ```

            # Pembacaan dan Penyebaran Berita

            Pembacaan teks berlangsung sederhana pada 17 Agustus 1945 pukul 10.00 pagi di halaman rumah Sukarno, Jalan Pegangsaan Timur 56, Jakarta. Acara ini tidak diumumkan secara besar-besaran karena situasi masih menegangkan.

            Setelah dibacakan, bendera Merah Putih dikibarkan oleh Suhud dan Latief Hendraningrat. Berita proklamasi kemudian disebarkan ke seluruh penjuru negeri lewat radio, surat kabar, dan utusan dari mulut ke mulut.

            > Sederhananya acara pembacaan tidak mengurangi maknanya. Justru dari momen singkat itulah berita kemerdekaan menyebar ke seluruh Indonesia.

            # Makna Proklamasi

            Proklamasi bukan sekadar pembacaan selembar naskah. Peristiwa itu menjadi titik awal berdirinya negara Indonesia yang merdeka dan berdaulat.

            - Menjadi sumber hukum berdirinya negara Republik Indonesia.
            - Menaikkan martabat dan kedudukan bangsa di mata dunia.
            - Menjadi puncak perjuangan panjang melawan penjajahan.
            - Menjadi panggilan bagi seluruh rakyat untuk menjaga kemerdekaan yang telah diraih.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia. Satu di antaranya meminta kamu menyusun peristiwa sesuai urutan waktunya.
            ISI,
        ],
        [
            'pembuat' => 'budi.demo@kelaskita.test',
            'kategori' => 'ips',
            'nama' => 'Interaksi Sosial dan Lembaga Sosial',
            'tingkat_kesulitan' => 'Sedang',
            'estimasi_waktu' => '1 jam 10 menit',
            'deskripsi' => 'Bentuk dan faktor interaksi sosial serta peran lembaga sosial dalam masyarakat.',
            'thumbnail' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Contoh interaksi sosial paling gampang ditemukan di sekitar rumah, mulai dari tawar-menawar di pasar.',
            'isi' => <<<'ISI'
            # Interaksi Sosial

            Interaksi sosial adalah hubungan timbal balik antara individu dengan individu, individu dengan kelompok, atau kelompok dengan kelompok. Disebut timbal balik karena kedua pihak saling memengaruhi, bukan hanya satu pihak yang bertindak.

            Interaksi sosial menjadi dasar terbentuknya kehidupan bersama. Tanpa interaksi, tidak ada kerja sama, aturan bersama, maupun kebudayaan yang diwariskan antar generasi.

            ## Ciri interaksi sosial

            - **Ada pelaku** lebih dari satu.
            - **Ada tujuan** yang ingin dicapai tiap pihak.
            - **Ada hubungan timbal balik**, sehingga perilaku satu pihak memengaruhi pihak lain.

            # Syarat Interaksi: Kontak dan Komunikasi

            Suatu hubungan disebut interaksi sosial bila memenuhi dua syarat: ada kontak sosial dan ada komunikasi.

            - **Kontak sosial** — sentuhan fisik atau non-fisik. Berbicara lewat pesan singkat termasuk kontak non-fisik.
            - **Komunikasi** — penyampaian pesan yang dipahami kedua pihak.

            ```text
            kontak primer      ->  bertatap muka langsung
            kontak sekunder    ->  lewat perantara, mis. telepon atau surat
            ```

            Kontak tanpa komunikasi belum cukup. Dua orang yang bersenggolan di jalan tanpa saling menyapa tidak disebut berinteraksi sosial.

            # Bentuk Interaksi: Asosiatif dan Disosiatif

            Secara garis besar, interaksi sosial dibagi menjadi dua kelompok. Proses asosiatif menyatukan masyarakat, sedangkan disosiatif cenderung memecahnya.

            ```text
            asosiatif    ->  kerja sama, akomodasi, asimilasi
            disosiatif   ->  persaingan, kontravensi, konflik
            ```

            - **Kerja sama** — dilakukan untuk mencapai tujuan bersama.
            - **Akomodasi** — usaha meredakan pertentangan, misalnya musyawarah atau mediasi.
            - **Asimilasi** — peleburan dua kebudayaan menjadi kebudayaan baru.
            - **Persaingan** — berlomba mencapai tujuan tanpa benturan fisik.
            - **Kontravensi** — sikap menentang yang tersembunyi, belum sampai konflik terbuka.
            - **Konflik** — pertentangan terbuka yang bisa menimbulkan kerugian.

            > Akomodasi dan asimilasi sama-sama meredakan pertentangan. Bedanya, asimilasi melebur dua kebudayaan menjadi satu, sementara akomodasi hanya membuat keduanya bisa hidup berdampingan.

            # Faktor Pendorong Interaksi

            Interaksi sosial tidak muncul begitu saja. Ada beberapa faktor yang mendorongnya.

            - **Imitasi** — meniru perilaku orang lain.
            - **Sugesti** — menerima pandangan orang lain tanpa berpikir panjang.
            - **Identifikasi** — ingin menyamai tokoh yang dikagumi.
            - **Simpati** — merasa tertarik dan ikut merasakan perasaan orang lain.
            - **Empati** — mampu menempatkan diri pada posisi orang lain.

            # Lembaga Sosial dan Jenisnya

            Lembaga sosial adalah kumpulan norma dan aktivitas yang mapan untuk memenuhi kebutuhan pokok masyarakat. Ia lahir dari kebutuhan yang berulang, lalu dilembagakan supaya teratur dan dapat diwariskan.

            - **Keluarga** — tempat pertama sosialisasi dan pewarisan nilai.
            - **Pendidikan** — menambah pengetahuan dan keterampilan.
            - **Agama** — memberi pedoman perilaku dan ketenangan batin.
            - **Ekonomi** — mengatur produksi, distribusi, dan konsumsi.
            - **Politik** — mengatur kekuasaan dan pengambilan keputusan.

            > Lembaga sosial berbeda dari individu. Ia tetap ada meskipun anggotanya berganti, karena yang diwariskan adalah norma dan perannya, bukan orangnya.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia. Satu di antaranya meminta kamu memilah peristiwa ke dalam proses asosiatif atau disosiatif.
            ISI,
        ],
        [
            'pembuat' => 'rina.demo@kelaskita.test',
            'kategori' => 'database',
            'nama' => 'Normalisasi Basis Data',
            'tingkat_kesulitan' => 'Sedang',
            'estimasi_waktu' => '1 jam 20 menit',
            'deskripsi' => 'Merapikan tabel basis data lewat bentuk normal pertama sampai ketiga beserta contoh kasusnya.',
            'thumbnail' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1120&h=600&q=75',
            'tips' => 'Kalau satu kolom menyimpan dua informasi, biasanya di situlah normalisasi perlu dilakukan.',
            'isi' => <<<'ISI'
            # Mengapa Perlu Normalisasi

            Normalisasi adalah proses menyusun tabel agar data tidak berulang dan tidak saling bertentangan. Tanpa normalisasi, satu informasi bisa tersimpan di beberapa tempat sekaligus, sehingga saat salah satunya diubah, datanya menjadi tidak konsisten.

            Tujuan utamanya ada tiga: menghilangkan duplikasi, menjaga konsistensi data, dan mempermudah pemeliharaan ketika struktur atau isi berubah.

            ## Masalah tabel yang belum rapi

            - **Redundansi** — data yang sama ditulis berkali-kali sehingga memboroskan ruang.
            - **Anomali update** — sebagian baris berubah sementara baris lain tetap, sehingga data bertentangan.
            - **Anomali insert** — tidak bisa menambah data karena data lain yang belum ada.
            - **Anomali delete** — menghapus satu data ikut menghapus data penting lainnya.

            # Bentuk Normal Pertama (1NF)

            Pada 1NF, setiap kolom harus berisi nilai tunggal dan bertipe sama, tidak boleh ada satu sel yang menyimpan beberapa nilai sekaligus.

            ```text
            tidak 1NF  ->  pembeli: "Budi, Rina"
            sudah 1NF  ->  satu baris untuk tiap pembeli
            ```

            Caranya: pecah kolom berisi daftar menjadi beberapa baris, tentukan kunci utama dari tiap tabel, dan pastikan setiap kolom tanggal atau angka memakai tipe yang benar.

            # Bentuk Normal Kedua (2NF)

            2NF dipenuhi bila tabel sudah 1NF dan setiap kolom non-kunci bergantung penuh pada kunci utama, bukan hanya pada sebagiannya.

            Ketergantungan sebagian muncul ketika kunci utamanya berupa gabungan dua kolom, lalu ada kolom yang hanya bergantung pada salah satunya. Kolom seperti itu harus dipindahkan ke tabel tersendiri.

            ```text
            detail_pesanan (pesanan_id, produk_id, jumlah)
            produk         (produk_id, nama_produk, harga)
            ```

            Pada contoh itu, nama produk tidak boleh ditulis di detail_pesanan karena hanya bergantung pada produk_id. Menyimpannya di satu tabel produk membuat perubahan nama cukup dikerjakan sekali.

            # Bentuk Normal Ketiga (3NF)

            3NF dipenuhi bila tabel sudah 2NF dan tidak ada kolom non-kunci yang bergantung pada kolom non-kunci lainnya. Ketergantungan ini disebut ketergantungan transitif.

            ```text
            salah  ->  kolom kota bergantung pada kolom kode_pos
            benar  ->  kota dan kode_pos dipisah ke tabel kode_pos
            ```

            Setelah tabel mencapai 3NF, sebagian besar masalah duplikasi dan anomali sudah hilang, dan tabel siap dipakai untuk keperluan sehari-hari.

            > Normalisasi berlebihan juga tidak baik. Memecah tabel terlalu banyak membuat penggabungan data jadi rumit. Tujuannya menghilangkan duplikasi yang merugikan, bukan memecah tanpa alasan.

            # Contoh Kasus Lengkap

            Sebuah tabel penjualan sederhana awalnya mencampur semua informasi dalam satu tabel:

            ```text
            penjualan (pesanan, pelanggan, kota, produk, harga, jumlah)
            ```

            Tabel itu bermasalah karena pelanggan dan kota berulang di setiap pesanan, sedangkan harga produk juga terus ditulis ulang. Setelah dinormalisasi menjadi beberapa tabel:

            ```text
            pelanggan (id, nama, kode_pos)
            kode_pos  (kode_pos, kota)
            produk    (id, nama, harga)
            pesanan   (id, pelanggan_id, tanggal)
            detail    (id, pesanan_id, produk_id, jumlah)
            ```

            Hasilnya setiap informasi hanya disimpan di satu tempat, dan hubungan antar tabel dijaga lewat kunci asing.

            # Latihan Soal

            Kerjakan lima soal singkat untuk menguji pemahamanmu, lalu bandingkan jawabannya dengan contoh yang tersedia. Satu di antaranya meminta kamu menentukan bentuk normal mana yang dilanggar oleh sebuah tabel.
            ISI,
        ],
    ];

    /**
     * Mode penghapusan.
     *
     * Tidak jadi argumen karena Artisan membuat seeder lewat container tanpa
     * mengirim opsi baris perintahnya. Penghapusan memakai kelas turunannya,
     * MateriVerifikasiDemoHapusSeeder, yang hanya mengubah nilai ini.
     */
    protected bool $hapus = false;

    public function run(): void
    {
        $nama = collect(self::MATERI)->pluck('nama');

        if ($this->hapus) {
            $this->hapusData($nama);

            return;
        }

        $pengguna = $this->pengguna();
        $pelajaran = Pelajaran::query()->get()->keyBy('slug');

        $dibuat = 0;
        $terlewat = [];

        foreach (self::MATERI as $baris) {
            $pembuat = $pengguna->get($baris['pembuat']);
            $kategori = $pelajaran->get($baris['kategori']);

            if ($pembuat === null || $kategori === null) {
                $terlewat[] = $baris['nama'];

                continue;
            }

            /*
             * Dicocokkan lewat "nama" supaya seeder ini aman dijalankan
             * berulang: satu judul hanya punya satu baris. Statusnya selalu
             * dikembalikan ke "pending" supaya antrean Verifikasi terisi lagi
             * setiap seeder dijalankan.
             */
            Materi::query()->updateOrCreate(
                ['nama' => $baris['nama']],
                [
                    'pelajaran_id' => $kategori->getKey(),
                    'dibuat_oleh' => $pembuat->getKey(),
                    'slug' => str($baris['nama'])->slug()->toString(),
                    'deskripsi' => $baris['deskripsi'],
                    'isi' => $baris['isi'],
                    // Gambar contoh diambil dari URL penuh, bukan berkas
                    // unggahan, jadi tidak perlu ada berkas di disk publik
                    // dan kartunya tampil bergambar seperti materi lain.
                    'thumbnail' => $baris['thumbnail'],
                    'tingkat_kesulitan' => $baris['tingkat_kesulitan'],
                    'estimasi_waktu' => $baris['estimasi_waktu'],
                    'tips' => $baris['tips'],
                    'status' => Materi::STATUS_PENDING,
                    'catatan_admin' => null,
                    'catatan_pengajuan' => null,
                    'dipublish_pada' => null,
                    'jumlah_ditolak' => 0,
                ]
            );

            $dibuat++;
        }

        $this->tampilkan($dibuat, $terlewat);
    }

    /**
     * Akun pembuat yang sudah ada, dikunci dengan email.
     *
     * Seeder ini sengaja tidak membuat akun. Email yang tidak ditemukan akan
     * membuat materinya dilewati, bukan memaksa akun baru dibuat dengan nama
     * yang mungkin berbeda dari akun asli pemiliknya.
     *
     * @return Collection<string, User>
     */
    private function pengguna()
    {
        return User::query()
            ->whereIn('email', collect(self::MATERI)->pluck('pembuat')->unique())
            ->get()
            ->keyBy('email');
    }

    /**
     * Hapus materi contoh ini saja.
     *
     * Filter dibuat_oleh ikut dipakai supaya materi milik orang lain yang
     * kebetulan berjudul sama tidak ikut terhapus.
     *
     * @param  Collection<int, string>  $nama
     */
    private function hapusData($nama): void
    {
        $pembuat = $this->pengguna()->pluck('id');

        $jumlah = Materi::query()
            ->whereIn('nama', $nama)
            ->whereIn('dibuat_oleh', $pembuat)
            ->delete();

        $this->saya('');
        $this->saya('  Data contoh materi verifikasi sudah dihapus ('.$jumlah.' materi).');
        $this->saya('');
    }

    /**
     * Cetak ringkasan dan tempat melihatnya.
     *
     * @param  array<int, string>  $terlewat
     */
    private function tampilkan(int $dibuat, array $terlewat): void
    {
        $menunggu = Materi::query()->where('status', Materi::STATUS_PENDING)->count();

        $baris = [
            '',
            '  Data contoh materi untuk Verifikasi sudah dibuat.',
            '',
            '  Materi dibuat : '.$dibuat.' (semua berstatus "pending")',
            '  Menunggu admin: '.$menunggu.' materi',
            '',
            '  CARA LIHAT',
            '',
            '  1. Login sebagai ADMIN, lalu buka:',
            '       '.route('admin.verifikasi'),
            '     Daftar "Menunggu Persetujuan" berisi kedelapan materi ini.',
            '',
            '  2. Atau dari sisi pemilik, login sebagai salah satu akun pembuat',
            '     (kata sandi akun demo: password), lalu buka "Karya Saya".',
            '',
            '  Hapus lagi dengan:',
            '       php artisan db:seed --class=MateriVerifikasiDemoHapusSeeder',
            '',
        ];

        if ($terlewat !== []) {
            $baris[] = '  PERHATIAN: '.count($terlewat).' materi dilewati karena akun/kategori tidak ada:';
            $baris[] = '     - '.implode(PHP_EOL.'     - ', $terlewat);
            $baris[] = '';
        }

        $this->saya(implode(PHP_EOL, $baris));
    }

    private function saya(string $teks): void
    {
        $this->command?->info($teks);
    }
}
