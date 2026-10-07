<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;
use App\Support\AktivitasHarian;
use App\Support\Angka;
use App\Support\DaftarJadwal;
use App\Support\DaftarMateri;
use App\Support\DaftarQuiz;
use App\Support\Ikon;
use App\Support\MateriDibaca;
use App\Support\PeringkatGlobal;
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
 *   materiTerbaru => hasil App\Support\DaftarMateri::petikan()
 *   quizTerbaru   => hasil App\Support\DaftarQuiz::petikan()
 *   jadwal        => hasil App\Support\DaftarJadwal::hariIni()
 *   peringkat     => hasil App\Support\PeringkatGlobal::daftar()
 *   kalender      => [nama_bulan, nama_hari, sel[], sebelumnya, berikutnya]
 *   streak        => hasil App\Support\AktivitasHarian::streak()
 *
 * Tidak ada angka yang diketik langsung di kelas ini. Semua yang tampil di
 * dashboard dibaca dari database, jadi kartu tidak pernah menampilkan angka
 * yang tidak ada di tabel.
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

            'ringkasan' => $this->ringkasan($pengguna),
            'aksiCepat' => $this->aksiCepat(),
            'aksesCepat' => $this->aksesCepat(),
            'materiTerbaru' => $this->materiTerbaru(),
            'quizTerbaru' => $this->quizTerbaru($pengguna?->getKey()),
            'jadwal' => $this->jadwal($request),
            'peringkat' => $this->peringkat($pengguna),
            'kalender' => $this->kalender($request),
            'streak' => $this->streak($pengguna),
        ]);
    }

    /**
     * Empat kartu ringkasan di bawah banner selamat datang.
     *
     * Semuanya dihitung dari database, bukan angka yang diketik di sini:
     *
     *   - Total Materi     => berapa materi yang sudah tayang.
     *   - Total Quiz       => berapa quiz yang pernah ia kerjakan sampai selesai.
     *   - Rata-rata Nilai  => rata-rata nilai dari pengerjaan yang selesai.
     *   - Progress Belajar => (materi dibaca + quiz selesai) / (semua materi +
     *                         semua quiz yang terbit).
     *
     * "Total Materi" menghitung seluruh materi yang tayang, bukan yang milik
     * pengguna ini saja, karena labelnya memang total isi perpustakaan. Yang
     * per-pengguna ada di kartu Progress Belajar.
     */
    private function ringkasan(?User $pengguna): array
    {
        $totalMateri = Materi::query()->terbit()->count();

        $materiBulanIni = Materi::query()
            ->terbit()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $pengerjaan = $this->pengerjaanRingkas($pengguna);
        $progress = MateriDibaca::progress($pengguna);

        return [
            [
                'label' => 'Total Materi',
                'nilai' => (string) $totalMateri,
                'perubahan' => $this->perubahanJumlah($materiBulanIni),
                'ikon' => Ikon::path('buku'),
                'warna' => 'hijau',
            ],
            [
                'label' => 'Total Quiz',
                'nilai' => (string) $pengerjaan['jumlah'],
                'perubahan' => $this->perubahanJumlah($pengerjaan['bulan_ini']),
                'ikon' => Ikon::path('benar'),
                'warna' => 'kuning',
            ],
            [
                'label' => 'Rata-rata Nilai',
                'nilai' => $pengerjaan['rata_nilai'] === null
                    ? '—'
                    : Angka::teks($pengerjaan['rata_nilai']).'%',
                'perubahan' => $pengerjaan['jumlah'] === 0
                    ? 'Belum ada nilai'
                    : 'Dari '.$pengerjaan['jumlah'].' quiz selesai',
                'ikon' => Ikon::path('piala'),
                'warna' => 'oranye',
            ],
            [
                'label' => 'Progress Belajar',
                'nilai' => Angka::teks($progress['persen']).'%',
                'perubahan' => $progress['total'] === 0
                    ? 'Belum ada materi atau quiz'
                    : $progress['selesai'].' dari '.$progress['total'].' konten selesai',
                'ikon' => Ikon::path('grafik'),
                'warna' => 'pink',
            ],
        ];
    }

    /**
     * Berapa quiz yang sudah pernah ia selesaikan, dan rata-ratanya.
     *
     * Hanya pengerjaan yang sudah selesai. Pengerjaan yang masih berjalan
     * punya nilai 0, jadi menghitungnya membuat kartu "Rata-rata Nilai"
     * terlihat rendah padahal orang itu belum selesai menjawab.
     *
     * Jumlah dan jumlah bulan ini menghitung quiz yang berbeda, bukan
     * pengerjaannya. Satu orang boleh mengerjakan quiz yang sama berulang
     * kali, dan label kartunya berbunyi "Total Quiz", jadi tiga percobaan
     * atas satu quiz yang sama harus tetap dibaca sebagai satu quiz.
     * Menghitung baris pengerjaan akan membuat angkanya naik setiap kali
     * orang mengulang, padahal isi halaman yang ditampilkan tidak bertambah.
     *
     * Rata-ratanya tetap dijumlahkan dari semua pengerjaan yang selesai,
     * karena "rata-rata nilai" memang artinya nilai yang dikumpulkan orang
     * itu, bukan rata-rata per quiz. Angka ini sengaja dibiarkan begitu
     * agar mengulang quiz untuk memperbaiki nilai tetap memperbaiki
     * rata-ratanya.
     *
     * @return array{jumlah: int, bulan_ini: int, rata_nilai: float|null}
     */
    private function pengerjaanRingkas(?User $pengguna): array
    {
        $kosong = ['jumlah' => 0, 'bulan_ini' => 0, 'rata_nilai' => null];

        if ($pengguna === null) {
            return $kosong;
        }

        $selesai = PengerjaanQuiz::query()
            ->milik($pengguna->getKey())
            ->selesai();

        $rata = (clone $selesai)->avg('nilai');

        return [
            'jumlah' => (clone $selesai)->distinct()->count('quiz_id'),
            'bulan_ini' => (clone $selesai)
                ->where('selesai_pada', '>=', now()->startOfMonth())
                ->distinct()
                ->count('quiz_id'),
            'rata_nilai' => $rata === null ? null : round((float) $rata, 1),
        ];
    }

    /**
     * Baris "perubahan" untuk kartu yang menghitung jumlah.
     *
     * Ditulis sebagai selisih jumlah, bukan persentase: 8 materi jadi 10
     * berarti "+2 bulan ini", bukan "+25%", yang terdengar seperti nilai yang
     * melonjak padahal hanya bertambah dua.
     */
    private function perubahanJumlah(int $jumlah): string
    {
        return $jumlah === 0
            ? 'Belum ada tambahan bulan ini'
            : '+'.$jumlah.' bulan ini';
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
     * Leaderboard di sidebar.
     *
     * Datanya diambil dari App\Support\PeringkatGlobal, bukan ditulis ulang
     * di sini, supaya aturan urutannya (soal dijawab dulu, nilai tertinggi
     * kedua, nama sebagai pemutus terakhir) hanya ada di satu tempat. Baris
     * milik pengguna yang sedang login sudah ditandai "saya" di sana.
     */
    private function peringkat(?User $pengguna): array
    {
        return PeringkatGlobal::daftar($pengguna?->getKey());
    }

    /**
     * Kalender bulanan untuk sidebar.
     *
     * Grid dihitung di server (bukan JavaScript) supaya tombol sebelumnya /
     * berikutnya cukup berupa link biasa (?bulan=YYYY-MM) dan tetap jalan
     * walau JavaScript dimatikan.
     *
     * Titik-titik pada tanggal diambil dari jadwal milik pengguna yang sedang
     * login, lewat peta per hari milik App\Support\DaftarJadwal — sama persis
     * dengan sumber yang dipakai kartu "Jadwal Hari Ini" dan halaman Jadwal.
     * Karena jadwal disimpan sebagai "hari dalam seminggu" plus jam, satu tanggal
     * di kalender hanya bisa diisi dari jadwal di hari dalam seminggu yang sama;
     * itu sebabnya peta per hari cukup, tanpa satu query per tanggal.
     *
     * Hanya jadwal milik pengguna sendiri yang tampil. Kalender di dashboard
     * bukan pengingat jadwal orang lain, jadi tidak ada yang perlu disembunyikan
     * dengan filter di sini.
     */
    private function kalender(Request $request): array
    {
        $bulan = $this->bulanTerpilih($request->query('bulan'));

        $peta = DaftarJadwal::jadwalMinggu($request->user()?->getKey());

        $awal = $bulan->copy()->startOfMonth();
        $jumlahHari = (int) $awal->copy()->endOfMonth()->day;

        // Hari dalam seminggu dimulai dari Minggu (0). Sel kosong di depan
        // supaya tanggal 1 jatuh di kolom yang benar, dan baris terakhir
        // dilengkapi ke tujuh kolom.
        $geser = (int) $awal->dayOfWeek;
        $jumlahSel = (int) (ceil(($geser + $jumlahHari) / 7) * 7);

        // Jangkar grid adalah tanggal 1 bulan ini yang digeser mundur sebanyak
        // kolom kosong. Kalau memakai tanggal hari ini sebagai jangkar, grid akan
        // selalu mulai dari tanggal yang sama, berapa pun bulan yang sedang
        // dibuka.
        $jangkar = $awal->copy()->subDays($geser);

        $sel = [];

        for ($i = 0; $i < $jumlahSel; $i++) {
            $hari = $jangkar->copy()->addDays($i);
            $dalamBulan = $hari->month === $awal->month;
            $jadwalHari = $dalamBulan ? ($peta[(int) $hari->dayOfWeek] ?? []) : [];

            $sel[] = [
                'angka' => (int) $hari->day,
                'dalam_bulan' => $dalamBulan,
                'hari_ini' => $hari->isSameDay(now()),
                'acara' => $jadwalHari === [] ? null : $this->ringkasAcara($jadwalHari),
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
     * Satu kalimat ringkas untuk tooltip tanggal yang ada jadwalnya.
     *
     * Sel kalender cuma punya ruang untuk beberapa karakter, jadi judul semua
     * jadwal di hari itu digabung. Kalau hanya satu, judulnya tampil utuh.
     *
     * @param  array<int, array<string, mixed>>  $jadwal
     */
    private function ringkasAcara(array $jadwal): string
    {
        $judul = array_map(fn (array $baris) => $baris['judul'], $jadwal);
        $sisa = count($judul) - 1;

        return $sisa > 0
            ? $judul[0].' +'.$sisa
            : $judul[0];
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
     * Streak belajar harian.
     *
     * Dihitung oleh App\Support\AktivitasHarian dari tabel yang ditulis setiap kali
     * pengguna membuka halaman baca materi atau menyimpan jawaban soal. Jadi
     * angkanya benar-benar berasal dari kegiatannya, bukan sekadar tanggal hari
     * ini: "aktif" hanya true kalau kegiatan terakhirnya masih dalam 24 jam
     * terakhir.
     *
     * Setelah 24 jam penuh tanpa kegiatan, streak() mengembalikan 0 dan aktif
     * false, sehingga angka yang masih terlihat di banner langsung hilang dan
     * tampil abu.
     */
    private function streak(?User $pengguna): array
    {
        return AktivitasHarian::streak($pengguna);
    }
}
