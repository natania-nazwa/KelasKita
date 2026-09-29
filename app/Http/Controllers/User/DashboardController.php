<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use App\Support\DaftarJadwal;
use App\Support\DaftarMateri;
use App\Support\DaftarQuiz;
use App\Support\Ikon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman dashboard untuk pengguna (siswa).
 *
 * Semua data di bawah sengaja dibuat sebagai array polos supaya komponen di
 * resources/views/components/dashboard tidak tahu-menahu soal Eloquent. Bentuk
 * array tiap kartu materi dan quiz sengaja sama persis dengan hasil
 * App\Support\DaftarMateri dan App\Support\DaftarQuiz, jadi section
 * "Materi Terbaru" dan "Quiz Terbaru" di dashboard memakai sumber data yang
 * sama dengan halaman Materi dan halaman Quiz.
 *
 * Bentuk yang dipakai tiap kelompok data:
 *   ringkasan     => [label, nilai, perubahan, ikon, warna]
 *   aksiCepat     => [judul, deskripsi, ikon, warna, warna_gelap, tautan, sorot]
 *   aksesCepat    => [judul, deskripsi, ikon, warna, warna_gelap, tautan]
 *   materiTerbaru => hasil App\Support\DaftarMateri::petakan()
 *   quizTerbaru   => hasil App\Support\DaftarQuiz::petakan()
 *   jadwal        => hasil App\Support\DaftarJadwal::hariIni()
 *   peringkat     => [peringkat, skor, nama, inisial, warna, warna_gelap, ...]
 *   kalender      => [nama_bulan, nama_hari, sel[], sebelumnya, berikutnya]
 *
 * Path ikon diambil dari App\Support\Ikon supaya tiap path hanya ditulis sekali.
 */
class DashboardController extends Controller
{
    /**
     * Berapa kartu yang ditampilkan di section materi dan quiz terbaru.
     *
     * Empat, karena grid di dashboard mengizinkan empat kartu satu baris di
     * layar lebar. Kalau someday jadi dua baris, angka ini yang diubah.
     */
    private const JUMLAH_TERBARU = 4;

    public function __invoke(Request $request): View
    {
        $pengguna = $request->user();

        return view('user.dashboard', [
            'pengguna' => $pengguna,

            'ringkasan' => $this->ringkasan(),
            'aksiCepat' => $this->aksiCepat(),
            'aksesCepat' => $this->aksesCepat(),
            'materiTerbaru' => $this->materiTerbaru(),
            'quizTerbaru' => $this->quizTerbaru($pengguna?->getKey()),
            'jadwal' => $this->jadwal($request),
            'peringkat' => $this->peringkat($pengguna),
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
     *
     * Warnanya sengaja memakai tiga hue berbeda (ungu, biru, hijau) dan
     * semuanya dalam versi terang/lembut. Kartu tidak memakai warna pekat
     * penuh supaya tiga kartu ini tampil tenang berdampingan; pewarnaannya
     * diterapkan di resources/css/app.css lewat .dash-aksi yang mencampur --k
     * dengan putih.
     *
     * "sorot" hanya menandai kartu pertama supaya tint-nya sedikit lebih pekat
     * dan jadi titik masuk utama, bukan lagi gradasi solid dengan teks putih.
     *
     * Kartu "Buat Quiz" langsung ke form tambah quiz, bukan ke daftar quiz,
     * supaya sekali klik sudah sampai di tempat mengisinya.
     */
    private function aksiCepat(): array
    {
        return [
            [
                'judul' => 'Jelajahi Materi',
                'deskripsi' => 'Temukan materi menarik dari guru dan teman-temanmu.',
                'ikon' => Ikon::path('buku'),
                'warna' => '#a78bfa',
                'warna_gelap' => '#5b3fd6',
                'tautan' => route('user.materi'),
                'sorot' => true,
            ],
            [
                'judul' => 'Buat Quiz',
                'deskripsi' => 'Uji pemahamanmu dengan membuat atau mengerjakan quiz.',
                'ikon' => Ikon::path('dokumen'),
                'warna' => '#93c5fd',
                'warna_gelap' => '#1d4ed8',
                'tautan' => route('user.quiz.tambah'),
                'sorot' => false,
            ],
            [
                'judul' => 'Masukkan Kode',
                'deskripsi' => 'Gabung ke quiz yang sudah dibuat dengan kode.',
                'ikon' => Ikon::path('gembok'),
                'warna' => '#6ee7b7',
                'warna_gelap' => '#047857',
                'tautan' => route('user.sesi.gabung'),
                'sorot' => false,
            ],
        ];
    }

    /**
     * Dua tombol ringkas di panel "Akses Cepat" sidebar. Menunjuk ke fitur
     * yang paling sering dipakai; bedanya dengan kartu aksi cepat di kolom
     * utama hanya tampilan bodynya yang lebih ringkas.
     *
     * Tombol pertama langsung membuka form tambah materi, bukan daftar
     * materi, supaya sekali klik sudah sampai di tempat mengisinya.
     */
    private function aksesCepat(): array
    {
        return [
            [
                'judul' => 'Buat Materi',
                'deskripsi' => 'Buat materi baru dan bagikan',
                'ikon' => Ikon::path('tambah'),
                'warna' => '#a78bfa',
                'warna_gelap' => '#6c4de6',
                'tautan' => route('user.materi.tambah'),
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
     * Empat materi terbaru yang sudah tayang.
     *
     * Query dan pemetaannya sengaja memakai App\Support\DaftarMateri, sama
     * seperti halaman Materi, jadi kartu di dashboard benar-benar menampilkan
     * materi yang ada di aplikasi dan bentuk datanya tidak akan pernah beda
     * dari kartu di halaman Materi.
     */
    private function materiTerbaru(): array
    {
        $materi = Materi::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->latest('created_at')
            ->latest('id')
            ->take(self::JUMLAH_TERBARU)
            ->get();

        return DaftarMateri::petakan($materi);
    }

    /**
     * Empat quiz terbaru yang sudah tayang.
     *
     * Sama seperti materiTerbaru(): sumber dan pemetaannya diambil dari
     * App\Support\DaftarQuiz supaya kartu dashboard identik dengan kartu di
     * halaman Quiz, termasuk jumlah soal dan durasinya.
     *
     * Id pengguna diteruskan supaya kartu bisa menandai "Quiz Saya" untuk
     * quiz milik orang yang sedang login.
     */
    private function quizTerbaru(?int $idPengguna): array
    {
        $quiz = Quiz::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal as jumlah_soal_termuat' => fn ($soal) => $soal->aktif()])
            ->latest('created_at')
            ->latest('id')
            ->take(self::JUMLAH_TERBARU)
            ->get();

        return DaftarQuiz::petakan($quiz, $idPengguna);
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
