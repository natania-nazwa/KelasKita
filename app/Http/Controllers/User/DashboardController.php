<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pelajaran;
use App\Models\User;
use App\Support\DaftarJadwal;
use App\Support\Ikon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Halaman dashboard untuk pengguna (siswa).
 *
 * Semua data di bawah sengaja dibuat sebagai array polos supaya komponen di
 * resources/views/components/dashboard tidak tahu-menahu soal Eloquent. Nanti
 * tinggal ganti isi tiap method dengan hasil query (Model / API) tanpa
 * menyentuh markup di user/dashboard.blade.php.
 *
 * Bentuk yang dipakai tiap kelompok data:
 *   ringkasan     => [label, nilai, perubahan, ikon, warna]
 *   aksiCepat     => [judul, deskripsi, ikon, warna, warna_gelap, tautan, sorot]
 *   aksesCepat    => [judul, deskripsi, ikon, warna, warna_gelap, tautan]
 *   materiTerbaru => [slug, judul, deskripsi, jumlah_materi, kategori[], pembuat[]]
 *   quizTerbaru   => [slug, judul, jumlah_soal, durasi, kategori[], tautan]
 *   jadwal        => hasil App\Support\DaftarJadwal::hariIni()
 *   peringkat     => [peringkat, skor, nama, inisial, warna, warna_gelap, ...]
 *   kalender      => [nama_bulan, nama_hari, sel[], sebelumnya, berikutnya]
 *
 * Path ikon diambil dari App\Support\Ikon supaya tiap path hanya ditulis sekali.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('user.dashboard', [
            'pengguna' => $request->user(),

            'ringkasan' => $this->ringkasan(),
            'aksiCepat' => $this->aksiCepat(),
            'aksesCepat' => $this->aksesCepat(),
            'materiTerbaru' => $this->materiTerbaru(),
            'quizTerbaru' => $this->quizTerbaru(),
            'jadwal' => $this->jadwal($request),
            'peringkat' => $this->peringkat($request->user()),
            'kalender' => $this->kalender($request),
            'streak' => $this->streak(),
        ]);
    }

    /**
     * Empat kartu ringkasan di bawah banner selamat datang.
     */
    private function ringkasan(): array
    {
        return [
            [
                'label' => 'Total Materi',
                'nilai' => '8',
                'perubahan' => '+2% dari bulan lalu',
                'ikon' => Ikon::path('buku'),
                'warna' => 'hijau',
            ],
            [
                'label' => 'Total Quiz',
                'nilai' => '5',
                'perubahan' => '+1% dari bulan lalu',
                'ikon' => Ikon::path('benar'),
                'warna' => 'kuning',
            ],
            [
                'label' => 'Rata-rata Nilai',
                'nilai' => '85%',
                'perubahan' => '+5% dari bulan lalu',
                'ikon' => Ikon::path('piala'),
                'warna' => 'oranye',
            ],
            [
                'label' => 'Progress Belajar',
                'nilai' => '60%',
                'perubahan' => '+10% dari bulan lalu',
                'ikon' => Ikon::path('grafik'),
                'warna' => 'pink',
            ],
        ];
    }

    /**
     * Tiga kartu aksi yang membawanya ke halaman inti aplikasi.
     * "sorot" menandai kartu pertama supaya jadi titik masuk paling menonjol.
     */
    private function aksiCepat(): array
    {
        return [
            [
                'judul' => 'Jelajahi Materi',
                'deskripsi' => 'Temukan materi menarik dari guru dan teman-temanmu.',
                'ikon' => Ikon::path('buku'),
                'warna' => '#a78bfa',
                'warna_gelap' => '#6c4de6',
                'tautan' => route('user.materi'),
                'sorot' => true,
            ],
            [
                'judul' => 'Buat Quiz',
                'deskripsi' => 'Uji pemahamanmu dengan membuat atau mengerjakan quiz.',
                'ikon' => Ikon::path('dokumen'),
                'warna' => '#8b80e6',
                'warna_gelap' => '#5a4cc9',
                'tautan' => route('user.quiz'),
                'sorot' => false,
            ],
            [
                'judul' => 'Masukkan Kode',
                'deskripsi' => 'Gabung ke quiz yang sudah dibuat dengan kode.',
                'ikon' => Ikon::path('gembok'),
                'warna' => '#b39ef5',
                'warna_gelap' => '#6a45c9',
                'tautan' => route('user.sesi.gabung'),
                'sorot' => false,
            ],
        ];
    }

    /**
     * Dua tombol ringkas di panel "Akses Cepat" sidebar. Menunjuk ke fitur
     * yang paling sering dipakai; bedanya dengan kartu aksi cepat di kolom
     * utama hanya tampilan bodynya yang lebih ringkas.
     */
    private function aksesCepat(): array
    {
        return [
            [
                'judul' => 'Buat Quiz',
                'deskripsi' => 'Buat kuis baru dengan soal sendiri',
                'ikon' => Ikon::path('tambah'),
                'warna' => '#a78bfa',
                'warna_gelap' => '#6c4de6',
                'tautan' => route('user.quiz'),
            ],
            [
                'judul' => 'Masukkan Kode',
                'deskripsi' => 'Gabung ke quiz dengan kode',
                'ikon' => Ikon::path('kode'),
                'warna' => '#8b80e6',
                'warna_gelap' => '#5a4cc9',
                'tautan' => route('user.sesi.gabung'),
            ],
        ];
    }

    /**
     * Empat materi terbaru. Bentuk array-nya sengaja dibuat sama persis
     * dengan App\Support\DaftarMateri supaya kartu yang dirender
     * (x-materi.kartu) tidak perlu tahu asal datanya.
     */
    private function materiTerbaru(): array
    {
        $daftar = [
            ['kategori' => 'pemrograman', 'judul' => 'HTML Dasar', 'deskripsi' => 'Materi dasar HTML untuk pemula.', 'pembuat' => 'Admin', 'jumlah' => 10],
            ['kategori' => 'pemrograman', 'judul' => 'CSS Dasar', 'deskripsi' => 'Membuat tampilan web lebih menarik.', 'pembuat' => 'Natania', 'jumlah' => 8],
            ['kategori' => 'desain-web', 'judul' => 'UI/UX Design', 'deskripsi' => 'Mengenal dasar desain antarmuka.', 'pembuat' => 'Keyla', 'jumlah' => 6],
            ['kategori' => 'pemrograman', 'judul' => 'JavaScript Dasar', 'deskripsi' => 'Logika dan interaksi pada website.', 'pembuat' => 'Irma', 'jumlah' => 12],
        ];

        return array_map(function (array $baris, int $urut) {
            return [
                'id' => $urut + 1,
                'slug' => Str::slug($baris['judul']),
                'judul' => $baris['judul'],
                'deskripsi' => $baris['deskripsi'],
                // Belum ada kolom gambar, jadi banner kartu memakai gradasi
                // warna kategori (lihat .kartu-materi__gambar).
                'thumbnail' => null,
                'tingkat_kesulitan' => 'Mudah',
                'jumlah_materi' => $baris['jumlah'],
                'tautan' => route('user.materi'),
                'kategori' => Pelajaran::warna($baris['kategori']),
                'pembuat' => $this->orang($baris['pembuat']),
            ];
        }, $daftar, array_keys($daftar));
    }

    /**
     * Empat quiz terbaru. Durasi disimpan sebagai angka menit supaya
     * komponen bebas memformatnya sendiri.
     */
    private function quizTerbaru(): array
    {
        $daftar = [
            ['kategori' => 'pemrograman', 'judul' => 'Pengantar HTML', 'soal' => 5, 'durasi' => 2],
            ['kategori' => 'pemrograman', 'judul' => 'Dasar-Dasar CSS', 'soal' => 5, 'durasi' => 3],
            ['kategori' => 'pemrograman', 'judul' => 'Logika Pemrograman', 'soal' => 10, 'durasi' => 5],
            ['kategori' => 'desain-web', 'judul' => 'Desain UI/UX', 'soal' => 8, 'durasi' => 4],
        ];

        return array_map(fn (array $baris) => [
            'slug' => Str::slug($baris['judul']),
            'judul' => $baris['judul'],
            'jumlah_soal' => $baris['soal'],
            'durasi' => $baris['durasi'],
            'tautan' => route('user.quiz'),
            'kategori' => Pelajaran::warna($baris['kategori']),
        ], $daftar);
    }

    /**
     * Jadwal pelajaran hari ini.
     *
     * Datanya sengaja dibaca dari App\Support\DaftarJadwal, bukan ditulis
     * ulang di sini, supaya baris pada kartu dashboard dijamin sama dengan
     * baris di halaman /user/jadwal untuk tanggal yang sama. Kartu di
     * dashboard memakai sebagian kecil dari kunci yang dikembalikan kelas
     * itu (jam, judul, kelas, ikon, warna, status); sisanya dipakai di sana.
     */
    private function jadwal(Request $request): array
    {
        return DaftarJadwal::hariIni($request->user()?->getKey());
    }

    /**
     * Lima peringkat teratas. Baris milik pengguna yang sedang login ditandai
     * lewat flag "saya" supaya bisa disorot di komponen.
     */
    private function peringkat(User $pengguna): array
    {
        $daftar = [
            ['nama' => 'Keyla', 'skor' => 980],
            ['nama' => 'Khanif', 'skor' => 960],
            ['nama' => 'Irma', 'skor' => 940],
            ['nama' => 'Heysell', 'skor' => 920],
            ['nama' => 'Natania', 'skor' => 890],
        ];

        return array_map(function (array $baris, int $urut) use ($pengguna) {
            return [
                ...$this->orang($baris['nama']),
                'peringkat' => $urut + 1,
                'skor' => $baris['skor'],
                'medali' => match ($urut + 1) {
                    1 => 'emas',
                    2 => 'perak',
                    3 => 'perunggu',
                    default => null,
                },
                'saya' => $baris['nama'] === $pengguna->nama,
            ];
        }, $daftar, array_keys($daftar));
    }

    /**
     * Kalender bulanan untuk sidebar.
     *
     * Grid dihitung di server (bukan JavaScript) supaya tombol sebelumnya /
     * berikutnya cukup berupa link biasa (?bulan=YYYY-MM) dan tetap jalan
     * walau JavaScript dimatikan.
     */
    private function kalender(Request $request): array
    {
        $bulan = $this->bulanTerpilih($request->query('bulan'));

        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth();
        $jumlahHari = (int) $akhir->day;

        // Acara dummy, dikunci ke tanggal 3/12/19/26. Angkanya dibatasi dengan
        // min() supaya tidak keluar dari bulan yang hanya punya 28/29/30 hari.
        $acara = [
            min(3, $jumlahHari) => 'Kuis Pemrograman',
            min(12, $jumlahHari) => 'Ulangan Matematika',
            min(19, $jumlahHari) => 'Deadline Project',
            min(26, $jumlahHari) => 'Diskusi Kelompok',
        ];

        // Hari dalam seminggu dimulai dari Minggu (0). Sel kosong di depan
        // supaya tanggal 1 jatuh di kolom yang benar, dan baris terakhir
        // dilengkapi ke tujuh kolom.
        $geser = (int) $awal->dayOfWeek;
        $jumlahSel = (int) (ceil(($geser + $jumlahHari) / 7) * 7);

        // Jangkar grid adalah tanggal 1 bulan ini yang digeser mundur sebanyak
        // kolom kosong. Kalau memakai tanggal hari ini sebagai jangkar, grid
        // akan selalu mulai dari tanggal yang sama, berapa pun bulan yang
        // sedang dibuka.
        $jangkar = $awal->copy()->subDays($geser);

        $sel = [];
        for ($i = 0; $i < $jumlahSel; $i++) {
            $hari = $jangkar->copy()->addDays($i);
            $dalamBulan = $hari->month === $awal->month;
            $hariIni = $hari->isSameDay(now());

            $sel[] = [
                'angka' => (int) $hari->day,
                'dalam_bulan' => $dalamBulan,
                'hari_ini' => $hariIni,
                'acara' => match (true) {
                    ! $dalamBulan => null,
                    isset($acara[$hari->day]) => $acara[$hari->day],
                    $hariIni => 'Belajar hari ini',
                    default => null,
                },
            ];
        }

        return [
            'nama_bulan' => $bulan->translatedFormat('F Y'),
            'nama_hari' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            'sel' => $sel,
            'sebelumnya' => route('user.dashboard', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')]),
            'berikutnya' => route('user.dashboard', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')]),
        ];
    }

    /**
     * Membaca parameter ?bulan=YYYY-MM. Nilai di luar kalender yang nyata
     * diabaikan dan diganti bulan berjalan supaya halaman tidak error.
     */
    private function bulanTerpilih(mixed $nilai): Carbon
    {
        $nilai = is_string($nilai) ? $nilai : '';

        $cocok = preg_match('/^(\d{4})-(\d{2})$/', $nilai, $bagian) === 1
            && checkdate((int) $bagian[2], 1, (int) $bagian[1]);

        return $cocok
            ? Carbon::createFromFormat('Y-m', $nilai)->startOfMonth()
            : now()->startOfMonth();
    }

    /**
     * Avatar + nama untuk penulis materi, pembuat quiz, atau peringkat.
     *
     * Warnanya diambil dari User::warnaAvatar() supaya warna avatar konsisten
     * di seluruh aplikasi tanpa kolom avatar di database.
     *
     * Catatan: warnaAvatar() saat ini mengembalikan daftar posisional
     * ([0 => terang, 1 => gelap]), bukan ['warna' =>, 'warna_gelap' =>]
     * seperti di docblock-nya. Helper ini menerima dua-duanya supaya tetap
     * aman kalau nanti bentuknya dirapikan.
     */
    private function orang(string $nama): array
    {
        $user = new User(['nama' => $nama]);
        $warna = $user->warnaAvatar();

        return [
            'nama' => $nama,
            'inisial' => $user->inisial(),
            'warna' => $warna['warna'] ?? $warna[0] ?? '#a78bfa',
            'warna_gelap' => $warna['warna_gelap'] ?? $warna[1] ?? '#6c4de6',
        ];
    }

    /**
     * Streak belajar harian.
     * "aktif" = true saat pengguna membaca materi atau mengerjakan
     * soal pada hari ini. Kalau sehari penuh tidak ada aktivitas
     * (misal 1 hari beruntun kosong) streak-nya padam dan tampil abu.
     */
    private function streak(): array
    {
        return [
            'jumlah' => 1,
            'aktif' => true,
        ];
    }
}
