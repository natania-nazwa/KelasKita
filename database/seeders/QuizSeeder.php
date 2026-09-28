<?php

namespace Database\Seeders;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Isi halaman Quiz: quiz beserta soal-soalnya dan pembuatnya.
 *
 * Seeder ini idempoten dan bergantung pada MateriSeeder, karena memakai
 * pengguna dan kategori yang sama supaya nama di kartu materi dan kartu quiz
 * berasal dari satu pengguna saja. Quiz dicocokkan lewat "judul" dan soal
 * lewat (quiz_id, urutan), jadi aman dijalankan berulang.
 *
 * Bentuk tiap soal di dalam QUIZ:
 *   [pertanyaan, pilihan a, b, c, d, huruf jawaban, pembahasan]
 */
class QuizSeeder extends Seeder
{
    /**
     * Nama pembuat => email di MaterialSeeder.
     *
     * @var array<string, string>
     */
    private const PENGGUNA = [
        'Admin' => 'admin@kelaskita.test',
        'Natania' => 'natania@kelaskita.test',
        'Irma' => 'irma@kelaskita.test',
        'Keyla' => 'keyla@kelaskita.test',
        'Khanif' => 'khanif@kelaskita.test',
        'Heysell' => 'heysell@kelaskita.test',
    ];

    /**
     * @var array<int, array<string, mixed>>
     */
    private const QUIZ = [
        [
            'kategori' => 'pemrograman',
            'pembuat' => 'Admin',
            'judul' => 'HTML & CSS Dasar',
            'deskripsi' => 'Kuis untuk menguji pemahaman dasar HTML dan CSS.',
            'durasi' => 15,
            'soal' => [
                ['HTML singkatan dari apa?', 'Hyperlink Text Mode Language', 'High Text Markup Language', 'HyperText Markup Language', 'Home Tool Markup Language', 'C', 'HTML adalah kerangka halaman web yang dibaca browser lalu diubah menjadi elemen.'],
                ['Tag apa yang dipakai untuk menulis satu paragraf?', '<p>', '<br>', '<span>', '<div>', 'A', 'Tag <p> menandai satu paragraf.'],
                ['Atribut mana yang menunjuk gambar ke berkas lain?', 'src', 'href', 'alt', 'link', 'A', 'src berisi alamat berkas gambar, sedangkan href dipakai untuk tautan.'],
                ['Tag img punya atribut wajib apa supaya gambarnya punya teks alternatif?', 'title', 'alt', 'label', 'caption', 'B', 'alt menjelaskan gambar untuk pembaca layar dan muncul saat gambar gagal dimuat.'],
                ['Apa kepanjangan dari CSS?', 'Cascading Style Sheets', 'Central Style System', 'Creative Site Structure', 'Cascading Simple Syntax', 'A', 'CSS mengatur tampilan tanpa mengubah isi halaman.'],
                ['Properti CSS apa yang menambah jarak di dalam border elemen?', 'margin', 'padding', 'gap', 'inset', 'B', 'padding menambah ruang di dalam border elemen.'],
                ['Properti CSS apa yang menambah jarak di luar elemen?', 'padding', 'margin', 'border', 'float', 'B', 'margin menambah ruang di luar border elemen.'],
                ['Bagaimana cara menulis komentar di CSS?', '// komentar', '/* komentar */', '# komentar', '<!-- komentar -->', 'B', 'CSS memakai /* */, sama seperti bahasa C.'],
                ['Selektor yang diawali tanda titik berarti?', 'Elemen dengan id tertentu', 'Kelas', 'Pseudo-kelas', 'Atribut', 'B', 'Tanda titik menandai class, tanda pagar menandai id.'],
                ['Nilai mana yang membuat warna teks jadi putih?', 'color: white', 'font: white', 'text: #fff', 'background-color: #fff', 'A', 'Properti color mengatur warna teks.'],
            ],
        ],
        [
            'kategori' => 'pemrograman',
            'pembuat' => 'Irma',
            'judul' => 'JavaScript Dasar',
            'deskripsi' => 'Kuis tentang konsep dasar JavaScript.',
            'durasi' => 20,
            'soal' => [
                ['Kata kunci apa untuk variabel yang nilainya tidak berubah?', 'var', 'let', 'const', 'static', 'C', 'const untuk nilai final, let kalau nanti berubah.'],
                ['Kata kunci apa untuk variabel yang nanti berubah?', 'const', 'var', 'let', 'change', 'B', 'let masih dipakai, tapi const lebih disarankan kalau nilai sudah final.'],
                ['Operator mana yang dipakai untuk sisa pembagian?', '%', '/', '//', 'mod', 'A', 'Tanda persen dipakai untuk sisa pembagian di banyak bahasa.'],
                ['Bagaimana menulis perbandingan strict di JavaScript?', '=', '==', '===', 'equals', 'C', '=== membandingkan nilai sekaligus tipe datanya.'],
                ['Struktur mana yang mengulang kode selama kondisi terpenuhi?', 'for', 'if', 'switch', 'function', 'A', 'for dan while melakukan pengulangan, if dan switch melakukan percabangan.'],
                ['Struktur mana yang memilih satu dari beberapa nilai?', 'if', 'switch', 'loop', 'goto', 'B', 'switch cocok untuk satu nilai yang punya banyak kemungkinan.'],
                ['Bagaimana menulis fungsi yang mengembalikan nilai?', 'function kali() { return a * b; }', 'function kali() { echo a * b; }', 'var kali = a * b;', 'function kali(a, b);', 'A', 'return mengembalikan nilai ke pemanggil fungsi.'],
                ['Apa yang dilakukan metode push() pada array?', 'Menghapus elemen terakhir', 'Menambah elemen di akhir', 'Mengubah urutan array', 'Menyalin array', 'B', 'push menambah elemen baru di ujung array.'],
                ['Bagaimana menulis arrow function?', 'function (a) { return a; }', '(a) => a', 'a -> a', 'lambda a', 'B', 'Arrow function ditulis const kali = (a, b) => a * b.'],
                ['Event apa yang dipanggil saat pengguna mengeklik tombol?', 'onchange', 'onclick', 'onsubmit', 'onload', 'B', 'onclick berjalan sekali saat elemen diklik.'],
                ['Apa fungsi setTimeout()?', 'Menjalankan kode setelah jeda waktu', 'Menjalankan kode tiap interval', 'Menunggu input pengguna', 'Menyimpan data di browser', 'A', 'setTimeout menunda pemanggilan fungsi sebanyak milidetik tertentu.'],
                ['Objek bawaan browser untuk menyimpan data antar halaman adalah?', 'localStorage', 'sessionStorage', 'cacheStorage', 'indexDB', 'A', 'localStorage menyimpan data sampai browser ditutup atau dihapus manual.'],
            ],
        ],
        [
            'kategori' => 'desain-web',
            'pembuat' => 'Keyla',
            'judul' => 'UI/UX Design',
            'deskripsi' => 'Kuis tentang prinsip desain antarmuka pengguna.',
            'durasi' => 12,
            'soal' => [
                ['UI dan UX sama-sama menjelaskan apa?', 'Kode program', 'Tampilan dan pengalaman memakai aplikasi', 'Kecepatan server', 'Struktur database', 'B', 'UI soal antarmuka yang dilihat, UX soal pengalaman memakai aplikasinya.'],
                ['Prinsip desain yang mengelompokkan elemen berdekatan disebut apa?', 'Kontras', 'Kedekatan', 'Pengulangan', 'Keselarasan', 'B', 'Kedekatan membuat mata otomatis mengelompokkan elemen yang berkaitan.'],
                ['Apa gunanya kontras dalam desain?', 'Membuat elemen mudah dibedakan', 'Memperbesar teks', 'Menghapus border', 'Mempercepat halaman', 'A', 'Kontras memisahkan elemen supaya tidak tercampur satu sama lain.'],
                ['Kenapa sebaiknya tidak mengandalkan warna saja untuk menyampaikan informasi?', 'Warna tidak bisa dipakai', 'Sebagian pengguna buta warna', 'Warna mudah dihapus', 'Browser tidak mendukung warna', 'B', 'Tambahkan bentuk, ikon, atau teks supaya informasinya tetap terbaca.'],
                ['Apa yang dimaksud hierarki visual?', 'Urutan file', 'Penataan yang mengarahkan mata ke elemen penting', 'Ukuran file gambar', 'Warna border', 'B', 'Hierarki diatur lewat ukuran, ketebalan, dan jarak teks.'],
                ['Apa perbedaan UX dan UI?', 'Tidak ada bedanya', 'UX soal pengalaman, UI soal tampilan', 'UI untuk mobile, UX untuk web', 'UX untuk admin, UI untuk siswa', 'B', 'Keduanya tumpang tindih tapi bukan hal yang sama.'],
                ['Apa fungsi empty state pada sebuah daftar?', 'Menyembunyikan pesan error', 'Memberi arahan saat belum ada data', 'Memperlambat tampilan', 'Mengganti tema', 'B', 'Empty state menjelaskan kondisi kosong lalu menawarkan aksi berikutnya.'],
                ['Mengapa ukuran tombol penting dalam desain?', 'Agar warnanya konsisten', 'Supaya mudah diklik, termasuk oleh jari yang besar', 'Agar lebih murah dibuat', 'Supaya tidak perlu ikon', 'B', 'Target sentuh yang nyaman mengurangi salah klik.'],
            ],
        ],
        [
            'kategori' => 'database',
            'pembuat' => 'Khanif',
            'judul' => 'Basis Data',
            'deskripsi' => 'Kuis tentang database dan SQL dasar.',
            'durasi' => 18,
            'soal' => [
                ['Perintah SQL untuk membaca data adalah?', 'INSERT', 'SELECT', 'UPDATE', 'DELETE', 'B', 'SELECT membaca baris dari tabel.'],
                ['Perintah SQL untuk menambah baris baru adalah?', 'INSERT', 'SELECT', 'ALTER', 'JOIN', 'A', 'INSERT menambah baris baru ke dalam tabel.'],
                ['Perintah SQL untuk mengubah data yang sudah ada adalah?', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'A', 'UPDATE mengubah nilai pada baris yang cocok.'],
                ['Perintah SQL untuk menghapus baris adalah?', 'DELETE', 'REMOVE', 'DROP', 'CLEAR', 'A', 'DELETE menghapus baris, sedangkan DROP menghapus seluruh tabel.'],
                ['Klausa apa yang membatasi baris yang ikut terbaca?', 'WHERE', 'ORDER BY', 'GROUP BY', 'LIMIT', 'A', 'WHERE menyaring baris sebelum data diambil.'],
                ['Klausa apa yang mengurutkan hasil?', 'ORDER BY', 'GROUP BY', 'BY', 'SORT', 'A', 'ORDER BY mengurutkan, GROUP BY mengelompokkan.'],
                ['Kolom unik pembeda setiap baris disebut apa?', 'Foreign key', 'Primary key', 'Index', 'Column', 'B', 'Primary key tidak boleh kosong dan tidak boleh kembar.'],
                ['Kolom yang menunjuk primary key tabel lain disebut apa?', 'Foreign key', 'Primary key', 'Alternate key', 'Surrogate key', 'A', 'Foreign key menjaga hubungan antar tabel.'],
                ['Apa gunanya normalisasi?', 'Mempercepat query', 'Mengurangi data duplikat', 'Menambah jumlah tabel', 'Menghapus kolom', 'B', 'Normalisasi memecah data supaya tidak diulang di banyak tempat.'],
                ['Perintah CREATE TABLE berguna untuk apa?', 'Membuat tabel baru', 'Mengisi tabel', 'Membaca tabel', 'Menghapus tabel', 'A', 'CREATE TABLE membuat struktur tabel beserta kolomnya.'],
            ],
        ],
        [
            'kategori' => 'teknologi',
            'pembuat' => 'Heysell',
            'judul' => 'Pengembangan Web',
            'deskripsi' => 'Kuis dari desain hingga deployment.',
            'durasi' => 22,
            'soal' => [
                ['Apa kepanjangan dari API?', 'Applied Program Interface', 'Application Programming Interface', 'Automated Process Integration', 'Advanced Page Index', 'B', 'API adalah antarmuka untuk mengomunikasikan dua program.'],
                ['Metode HTTP untuk mengambil data adalah?', 'POST', 'GET', 'PUT', 'DELETE', 'B', 'GET dipakai untuk membaca data dari server.'],
                ['Metode HTTP untuk mengirim data baru adalah?', 'GET', 'POST', 'HEAD', 'TRACE', 'B', 'POST mengirim data untuk membuat atau memproses sesuatu.'],
                ['Status HTTP 404 berarti apa?', 'Berhasil', 'Tidak ditemukan', 'Server mengalami kesalahan', 'Tidak diizinkan', 'B', '404 Not Found dipakai saat alamat atau datanya tidak ada.'],
                ['Status HTTP 500 berarti apa?', 'Server mengalami kesalahan', 'Halaman kosong', 'Wajib login', 'Dialihkan', 'A', '500 Internal Server Error berarti kesalahan di sisi server.'],
                ['Apa fungsi version control seperti Git?', 'Mempercepat server', 'Menyimpan riwayat perubahan kode', 'Menyusun database', 'Menyembunyikan kode', 'B', 'Git menyimpan riwayat sehingga perubahan bisa dikembalikan.'],
                ['Docker adalah?', 'Bahasa pemrograman', 'Alat menjalankan aplikasi di dalam kontainer', 'Basis data', 'Framework CSS', 'B', 'Docker membungkus aplikasi beserta dependensinya agar jalan sama di mana saja.'],
                ['Environment variable dipakai untuk apa?', 'Menyimpan data pengguna', 'Menyimpan konfigurasi di luar kode', 'Mengganti database', 'Menulis dokumentasi', 'B', 'Konfigurasi dipisah dari kode supaya aman di tiap lingkungan.'],
                ['Apa yang dilakukan proses continuous integration?', 'Menjalankan pengujian otomatis setiap ada perubahan', 'Membersihkan berkas', 'Mengganti nama domain', 'Mencetak laporan', 'A', 'Continuous integration menjaga kode tetap bisa dijalankan.'],
                ['Tahap paling awal dalam membangun aplikasi web adalah?', 'Deployment', 'Menggali kebutuhan pengguna', 'Pengujian', 'Perawatan', 'B', 'Semua tahap lain bergantung pada kebutuhan yang sudah digali dengan jelas.'],
                ['Apa kepanjangan dari HTML?', 'Hyperlink Text Mode Language', 'High Text Markup Language', 'HyperText Markup Language', 'Home Tool Markup Language', 'C', 'HTML singkatan dari HyperText Markup Language.'],
                ['Metode HTTP untuk mengubah data yang sudah ada adalah?', 'POST', 'PUT', 'PATCH', 'GET', 'B', 'PUT mengganti keseluruhan sumber daya, sedangkan PATCH hanya sebagian.'],
                ['Apa gunanya HTTPS dibanding HTTP?', 'Halamannya lebih ringan', 'Data terenkripsi selama perjalanan', 'Server lebih ringan', 'Gambarnya lebih ringan', 'B', 'HTTPS membungkus lalu lintas data dengan enkripsi TLS.'],
                ['Apa yang dilakukan continuous delivery?', 'Menerbitkan perubahan ke server secara otomatis setelah lolos pengujian', 'Mengganti basis data', 'Mencatat pengguna', 'Menyusun dokumentasi', 'A', 'Rilis yang sering dan kecil risikonya membuat bug lebih cepat ditemukan.'],
                ['Sebelum rilis, hal apa yang sebaiknya disiapkan?', 'Mematikan seluruh server', 'Cadangan data dan cara rollback', 'Menghapus log', 'Mengganti nama domain', 'B', 'Punya cadangan dan jalur balik membuat rilis aman.'],
            ],
        ],
        [
            'kategori' => 'teknologi',
            'pembuat' => 'Natania',
            'judul' => 'Seputar Teknologi',
            'deskripsi' => 'Kuis umum tentang dunia teknologi.',
            'durasi' => 15,
            'soal' => [
                ['Perangkat keras adalah?', 'Bagian fisik komputer', 'Perangkat lunak', 'Jaringan internet', 'Sistem operasi', 'A', 'Hardware adalah bagian yang bisa disentuh, seperti monitor dan keyboard.'],
                ['Perangkat lunak adalah?', 'Bagian fisik komputer', 'Program yang berjalan di perangkat', 'Kabel jaringan', 'Baterai laptop', 'B', 'Software adalah program, termasuk sistem operasi dan aplikasi.'],
                ['Peran utama sistem operasi adalah?', 'Mengelola sumber daya perangkat keras', 'Menggambar dokumen', 'Menyediakan listrik', 'Memasang kabel', 'A', 'Sistem operasi mengatur memori, CPU, dan perangkat-perangkatnya.'],
                ['Satuan dasar kecepatan transfer data adalah?', 'Bit per detik', 'Gigabyte', 'Merahi', 'Inci', 'A', 'Satuannya bps, kb/s, atau Mb/s.'],
                ['Manajemen data pribadi paling aman dilakukan dengan?', 'Password pendek yang sama di semua akun', 'Password panjang dan berbeda tiap akun', 'Menuliskan password di catatan', 'Berbagi akun', 'B', 'Password panjang dan unik supaya risiko satu akun bocor tidak langsung ikut semua akun.'],
                ['Jaringan nirkabel yang biasa dipakai di rumah disebut?', 'Ethernet', 'Wi-Fi', 'Bluetooth', 'NFC', 'B', 'Wi-Fi menghubungkan perangkat tanpa kabel.'],
                ['Apa gunanya antivirus atau pemindaian keamanan?', 'Mempercepat internet', 'Mendeteksi program berbahaya', 'Mengganti RAM', 'Membersihkan monitor', 'B', 'Perangkat lunak keamanan mencari tanda-jejak program berbahaya.'],
                ['Saat bepergian, koneksi mana yang paling aman untuk Banking?', 'Wi-Fi publik tanpa sandi', 'Jaringan seluler', 'Wi-Fi gratis mana pun', 'Koneksi apa saja sama amannya', 'B', 'Jaringan seluler terenkripsi, sementara Wi-Fi publik mudah dipantau.'],
                ['Apa itu cadangan data atau backup?', 'Salinan data disimpan terpisah', 'Menghapus data lama', 'Menyembunyikan folder', 'Mengganti nama berkas', 'A', 'Backup penting supaya data tidak hilang kalau perangkat rusak.'],
                ['Kebiasaan digital yang baik adalah?', 'Membagikan kata sandi', 'Memperbarui aplikasi secara rutin', 'Membuka berkas dari orang tak dikenal', 'Menyimpan semuanya di flash drive', 'B', 'Pembaruan biasanya membawa perbaikan keamanan.'],
            ],
        ],
    ];

    public function run(): void
    {
        $idPengguna = $this->pengguna();

        foreach (self::QUIZ as $baris) {
            $quiz = $this->simpanQuiz($baris, $idPengguna);
            $this->simpanSoal($quiz, $baris['soal']);
        }
    }

    /**
     * Pastikan semua pembuat ada, lalu kembalikan nama => id.
     *
     * @return array<string, int>
     */
    private function pengguna(): array
    {
        $id = [];

        foreach (self::PENGGUNA as $nama => $email) {
            $ada = User::query()->where('email', $email)->first();

            if ($ada) {
                $id[$nama] = $ada->getKey();

                continue;
            }

            $id[$nama] = User::query()->create([
                'nama' => $nama,
                'email' => $email,
                'kata_sandi' => 'password',
                'peran' => $nama === 'Admin' ? User::PERAN_ADMIN : User::PERAN_USER,
                'aktif' => true,
            ])->getKey();
        }

        return $id;
    }

    /**
     * Simpan satu quiz. Dicocokkan lewat "judul" supaya aman dijalankan
     * berulang; soal-soalnya dibersihkan dulu supaya tidak terduplikasi.
     *
     * @param  array<string, mixed>  $baris
     * @param  array<string, int>  $idPengguna
     */
    private function simpanQuiz(array $baris, array $idPengguna): Quiz
    {
        $quiz = Quiz::query()->updateOrCreate(
            ['judul' => $baris['judul']],
            [
                'pelajaran_id' => $this->pelajaranId($baris['kategori']),
                'dibuat_oleh' => $idPengguna[$baris['pembuat']],
                'slug' => str($baris['judul'])->slug()->toString(),
                'deskripsi' => $baris['deskripsi'],
                'durasi' => $baris['durasi'],
                'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
                'status' => Quiz::STATUS_PUBLISHED,
                'dipublish_pada' => now(),
            ]
        );

        $quiz->soal()->delete();

        return $quiz;
    }

    /**
     * Id pelajaran dari slug kategori. KATEGORI di MateriSeeder sudah pasti
     * ada karena seeder itu dijalankan lebih dulu.
     */
    private function pelajaranId(string $slug): int
    {
        return (int) Pelajaran::query()->where('slug', $slug)->value('id');
    }

    /**
     * Simpan soal-soalan sebuah quiz. Baris yang kosong dilewati, dan nomor
     * urut dihitung ulang supaya selalu berurutan dari satu.
     *
     * @param  array<int, array<int, string>>  $soal
     */
    private function simpanSoal(Quiz $quiz, array $soal): void
    {
        $urutan = 0;

        foreach ($soal as $item) {
            [$pertanyaan, $a, $b, $c, $d, $benar, $pembahasan] = $item;

            Soal::query()->create([
                'quiz_id' => $quiz->getKey(),
                'pertanyaan' => $pertanyaan,
                'pilihan_a' => $a,
                'pilihan_b' => $b,
                'pilihan_c' => $c,
                'pilihan_d' => $d,
                'jawaban_benar' => $benar,
                'pembahasan' => $pembahasan,
                'urutan' => ++$urutan,
                'tingkat_kesulitan' => 'Mudah',
                'aktif' => true,
            ]);
        }
    }
}
