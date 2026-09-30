<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Agregasi read-only untuk halaman admin.
 *
 * Semua angka di dashboard dan halaman "Hasil & Statistik" dihitung di
 * sini, bukan di controller dan bukan di view, supaya:
 *
 *   1. satu query ditulis sekali dan dipakai di beberapa halaman;
 *   2. angka yang sama tidak pernah dihitung dua kali dengan dua cara
 *      berbeda di view yang berbeda.
 *
 * Kelas ini hanya membaca. Tidak ada insert, update, atau delete, dan
 * tidak menyentuh status materi, quiz, atau approval: keputusan admin
 * tetap milik MateriTinjauController dan QuizTinjauController.
 *
 * Warna pelajaran selalu lewat Pelajaran::warna() supaya grafit dan
 * daftar di halaman admin memakai warna yang sama dengan kartu materi
 * dan quiz di halaman pengguna.
 */
final class StatistikAdmin
{
    /**
     * Berapa hari ke belakang yang dipakai untuk grafik tren.
     */
    public static function hariTren(): int
    {
        return 7;
    }

    /**
     * Berapa baris teratas yang dipakai untuk "pelajaran terpopuler".
     */
    public static function batasTerpopuler(): int
    {
        return 6;
    }

    /**
     * Angka utama untuk kartu statistik dashboard.
     *
     * Satu query beruntun, bukan N+1: jumlah materi, quiz, dan pengguna
     * diambil bersama supaya membuka dashboard tetap beberapa query
     * tetap, bukan bertambahnya sebanding dengan isi database.
     *
     * "perubahan" di sini adalah jumlah konten BARU 30 hari terakhir,
     * bukan jumlah seluruh konten. Kartu di dashboard menampilkan
     * nomor itu sebagai "12% dari bulan lalu".
     *
     * @return array{
     *     pengguna: int,
     *     materi: int,
     *     quiz: int,
     *     materi_menunggu: int,
     *     quiz_menunggu: int,
     *     pengguna_aktif: int,
     *     quiz_dikerjakan: int,
     *     rata_nilai: float,
     *     materi_dipelajari: int,
     *     perubahan: array<string, array{total: int, bulan_ini: int, bulan_lalu: int}>
     * }
     */
    public static function ringkasan(): array
    {
        $jumlahMateri = (int) Materi::query()->count();
        $jumlahQuiz = (int) Quiz::query()->count();
        $jumlahPengguna = (int) User::query()->count();

        $materiMenunggu = (int) Materi::query()->menunggu()->count();
        $quizMenunggu = (int) Quiz::query()->menunggu()->count();

        $nilai = PengerjaanQuiz::query()
            ->selesai()
            ->avg('nilai');

        return [
            'pengguna' => $jumlahPengguna,
            'materi' => $jumlahMateri,
            'quiz' => $jumlahQuiz,
            'materi_menunggu' => $materiMenunggu,
            'quiz_menunggu' => $quizMenunggu,
            'pengguna_aktif' => self::penggunaAktif(),
            'quiz_dikerjakan' => (int) PengerjaanQuiz::query()->selesai()->count(),
            'rata_nilai' => round((float) ($nilai ?? 0), 1),
            'materi_dipelajari' => (int) DB::table('tb_simpanan_materi')->distinct()->count('materi_id'),
            'perubahan' => [
                'pengguna' => self::perubahanBaru(User::query()),
                'materi' => self::perubahanBaru(Materi::query()),
                'quiz' => self::perubahanBaru(Quiz::query()),
            ],
        ];
    }

    /**
     * Jumlah konten baru di 30 hari terakhir, 30 hari sebelum itu, dan
     * persentasenya.
     *
     * Ronde 30 hari dipilih, bukan kalender bulan, supaya "bulan lalu"
     * selalu dibandingkan dengan rentang sepanjang yang sama. Kalau
     * pembandingnya nol, persentasenya tidak dihitung dan view
     * menampilkan rise empty.
     *
     * @return array{total: int, bulan_ini: int, bulan_lalu: int, persen: float|null, arah: string}
     */
    public static function perubahanBaru(Builder $query): array
    {
        $total = (int) $query->count();
        $bulanIni = (int) (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        $bulanLalu = (int) (clone $query)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        $selisih = $bulanIni - $bulanLalu;

        return [
            'total' => $total,
            'bulan_ini' => $bulanIni,
            'bulan_lalu' => $bulanLalu,
            'persen' => $bulanLalu > 0 ? round($selisih / $bulanLalu * 100, 1) : null,
            'arah' => $selisih >= 0 ? 'naik' : 'turun',
        ];
    }

    /**
     * Berapa pengguna yang punya sesi hidup dalam 24 jam terakhir.
     *
     * Membaca tabel "sessions" secara langsung: aplikasi memakai driver
     * database dengan lifetime 120 menit, jadi last_activity berisi
     * unix timestamp. Sesi yang sudah kedaluwarsa di luar 24 jam tidak
     * dihitung supaya "pengguna aktif" tidak sama dengan "pernah
     * pernah masuk".
     */
    public static function penggunaAktif(): int
    {
        return (int) DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>', now()->subDay()->getTimestamp())
            ->distinct()
            ->count('user_id');
    }

    /**
     * Berapa minggu yang dipakai untuk grafik "Aktivitas Login Mingguan".
     */
    public static function mingguLogin(): int
    {
        return 6;
    }

    /**
     * Jumlah login per minggu untuk N minggu terakhir, termasuk minggu
     * yang sedang berjalan.
     *
     * Yang dihitung adalah JUMLAH login, bukan jumlah orang: satu
     * pengguna yang masuk lima kali dihitung lima. Hitungan orang unik
     * sudah ada di tempat lain (StatistikAdmin::penggunaAktif) dan tidak
     * boleh dipakai di sini, karena dua angka itu menjawab pertanyaan
     * yang berbeda.
     *
     * Sumbernya tabel "tb_riwayat_login", satu baris per login berhasil.
     * Tabel "sessions" sengaja tidak dipakai: kolomnya hanya
     * last_activity, sehingga satu sesi yang berumur beberapa hari selalu
     * terhitung di minggu terakhir aktivitasnya dan jumlah login
     * aslinya hilang.
     *
     * Minggu dihitung sebagai rentang Senin-Minggu, dan minggu ini ikut
     * ditampilkan walau belum selesai: grafik yang melompati minggu
     * berjalan akan terlihat seperti activity-nya berhenti.
     *
     * Labelnya dihitung dari tanggal, tidak pernah ditulis manual, dan
     * dibentuk dua bagian supaya label panjang tidak menabrak label
     * sebelahnya di sumbu X:
     *
     *   "label"   baris pertama sumbu X ("1–7" atau "29 Sep")
     *   "bulan"   baris kedua ("Sep" atau "5 Okt")
     *   "rentang" teks lengkap untuk tooltip dan pembaca layar
     *   "mulai"   tanggal ISO ("2026-09-27") untuk pembaca lain yang perlu
     *              membandingkan tanggal, bukan membacanya
     *   "selesai" tanggal ISO akhir minggu
     *
     * Setiap minggu dihitung dengan satu COUNT sendiri, bukan satu
     * GROUP BY, supaya query-nya tidak butuh dialek SQL khusus database
     * dan tetap jalan di database pengujian (SQLite) maupun di
     * PostgreSQL. Enam kolom terindeks dibaca, jadi biayanya kecil.
     *
     * @return array<int, array{label: string, bulan: string, rentang: string, mulai: string, selesai: string, nilai: int}>
     */
    public static function loginMingguan(int $minggu = 0): array
    {
        $minggu = $minggu > 0 ? $minggu : self::mingguLogin();
        $awalMingguIni = now()->startOfWeek();

        $hasil = [];

        for ($mundur = $minggu - 1; $mundur >= 0; $mundur--) {
            $mulai = $awalMingguIni->copy()->subWeeks($mundur);
            $selesai = $mulai->copy()->endOfWeek();

            $samaBulan = $mulai->isSameMonth($selesai) && $mulai->isSameYear($selesai);

            $hasil[] = [
                'label' => $samaBulan
                    ? $mulai->format('j').'–'.$selesai->format('j')
                    : $mulai->translatedFormat('j M'),
                'bulan' => $samaBulan
                    ? $mulai->translatedFormat('M')
                    : $selesai->translatedFormat('j M'),
                'rentang' => $samaBulan
                    ? $mulai->translatedFormat('j').'–'.$selesai->translatedFormat('j M Y')
                    : $mulai->translatedFormat('j M').'–'.$selesai->translatedFormat('j M Y'),
                'mulai' => $mulai->toDateString(),
                'selesai' => $selesai->toDateString(),
                'nilai' => (int) DB::table('tb_riwayat_login')
                    ->whereBetween('created_at', [$mulai, $selesai])
                    ->count(),
            ];
        }

        return $hasil;
    }

    /**
     * Deret tren pengguna baru, pengguna unik yang login, dan quiz
     * yang dikerjakan untuk N hari terakhir.
     *
     * Dikembalikan lengkap dengan hari yang nol: kalau ada hari tanpa
     * pengguna baru, hari itu tetap ada di deret dengan nilai 0. Kalau
     * hari kosong dibuang, garis grafik akan melompati tanggal dan
     * grafikVG berbohong soal kapan kejadiannya terjadi.
     *
     * @return array<int, array{label: string, tanggal: string, pengguna_baru: int, pengguna_login: int, quiz_dikerjakan: int}>
     */
    public static function trenPengguna(): array
    {
        $hari = self::hariTren();
        $mulai = now()->startOfDay()->subDays($hari - 1);

        $baris = DB::table('tb_pengguna')
            ->selectRaw('created_at::date as hari, COUNT(*) as jumlah')
            ->where('created_at', '>=', $mulai)
            ->groupBy('created_at::date')
            ->pluck('jumlah', 'hari');

        /*
         * Sesi dibaca per hari, bukan per timestamp: satu orang yang
         * membuka aplikasi sepuluh kali dalam sehari tetap dihitung
         * sebagai satu pengguna aktif untuk hari itu.
         */
        $login = DB::table('sessions')
            ->selectRaw("to_char(to_timestamp(last_activity), 'YYYY-MM-DD') as hari, COUNT(DISTINCT user_id) as jumlah")
            ->where('last_activity', '>=', (int) $mulai->getTimestamp())
            ->whereNotNull('user_id')
            ->groupBy('hari')
            ->pluck('jumlah', 'hari');

        $dikerjakan = DB::table('tb_pengerjaan_quiz')
            ->selectRaw('created_at::date as hari, COUNT(*) as jumlah')
            ->where('created_at', '>=', $mulai)
            ->groupBy('created_at::date')
            ->pluck('jumlah', 'hari');

        $hasil = [];

        for ($i = 0; $i < $hari; $i++) {
            $tanggal = $mulai->copy()->addDays($i);
            $kunci = $tanggal->format('Y-m-d');

            $hasil[] = [
                'label' => $tanggal->translatedFormat('D'),
                'tanggal' => $tanggal->translatedFormat('d M'),
                'pengguna_baru' => (int) ($baris[$kunci] ?? 0),
                'pengguna_login' => (int) ($login[$kunci] ?? 0),
                'quiz_dikerjakan' => (int) ($dikerjakan[$kunci] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Materi dan quiz yang menunggu keputusan admin, digabung jadi satu
     * daftar untuk kartu "Perlu Ditinjau".
     *
     * Batasnya 5: kartu itu ruang baca sekilas, dan kunci lengkapnya
     * ada di halaman Verifikasi.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function perluDitinjau(int $batas = 5): array
    {
        $materi = Materi::query()
            ->menunggu()
            ->with(['pelajaran', 'pembuat'])
            ->latest()
            ->limit($batas)
            ->get()
            ->map(fn (Materi $item): array => self::barisTinjauMateri($item));

        $quiz = Quiz::query()
            ->menunggu()
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->aktif()])
            ->latest()
            ->limit($batas)
            ->get()
            ->map(fn (Quiz $item): array => self::barisTinjauQuiz($item));

        /*
         * Materi dulu lalu quiz, dan tiap kelompok urut dari yang paling
         * baru: admin paling sering bekerja di materi, jadi jangan
         * dikubur oleh antrean quiz.
         */
        return $materi->concat($quiz)->take($batas)->values()->all();
    }

    /**
     * Konversi satu materi yang menunggu jadi baris kartu tinjau.
     *
     * @return array<string, mixed>
     */
    public static function barisTinjauMateri(Materi $materi): array
    {
        $kategori = Pelajaran::warna($materi->pelajaran?->slug, $materi->pelajaran?->nama);
        $avatar = $materi->pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

        return [
            'jenis' => 'materi',
            'label_jenis' => 'Materi',
            'judul' => $materi->nama,
            'rincian' => $materi->jumlahBab().' bab • '.$materi->waktuBaca().' menit baca',
            'deskripsi' => $materi->ringkasan(160) ?: 'Tanpa deskripsi.',
            'isi' => (string) $materi->isi,
            'pembuat' => $materi->pembuat?->nama ?? 'Tanpa nama',
            'inisial' => $materi->pembuat?->inisial() ?? '?',
            'warna' => $avatar['warna'],
            'warna_gelap' => $avatar['warna_gelap'],
            'kategori' => $kategori['nama'],
            'kategori_ikon' => $kategori['ikon'],
            'kategori_warna' => $kategori['warna'],
            'dibuat_pada' => $materi->created_at,
            'tautan' => route('admin.materi'),
            'tautan_setujui' => route('admin.materi.setujui', $materi->slug),
            'tautan_tolak' => route('admin.materi.tolak', $materi->slug),
        ];
    }

    /**
     * Konversi satu quiz yang menunggu jadi baris kartu tinjau.
     *
     * @return array<string, mixed>
     */
    public static function barisTinjauQuiz(Quiz $quiz): array
    {
        $kategori = Pelajaran::warna($quiz->pelajaran?->slug, $quiz->pelajaran?->nama);
        $avatar = $quiz->pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

        return [
            'jenis' => 'quiz',
            'label_jenis' => 'Quiz',
            'judul' => $quiz->judul,
            'rincian' => $quiz->jumlah_soal.' soal • mode '.(strtoupper($quiz->visibilitas) === Quiz::VISIBILITAS_PUBLIK ? 'publik' : 'kode'),
            'deskripsi' => (string) $quiz->deskripsi ?: 'Tanpa deskripsi.',
            // Ringkasan isi buat modal: daftar pertanyaannya, bukan isi
            // mentah, supaya admin cepat menilai tanpa digulir jauh.
            'isi' => self::ringkasanSoal($quiz),
            'pembuat' => $quiz->pembuat?->nama ?? 'Tanpa nama',
            'inisial' => $quiz->pembuat?->inisial() ?? '?',
            'warna' => $avatar['warna'],
            'warna_gelap' => $avatar['warna_gelap'],
            'kategori' => $kategori['nama'],
            'kategori_ikon' => $kategori['ikon'],
            'kategori_warna' => $kategori['warna'],
            'dibuat_pada' => $quiz->created_at,
            'tautan' => route('admin.quiz'),
            'tautan_setujui' => route('admin.quiz.setujui', $quiz->getKey()),
            'tautan_tolak' => route('admin.quiz.tolak', $quiz->getKey()),
        ];
    }

    /**
     * Daftar pertanyaan sebuah quiz, dipotong supaya muat di modal.
     *
     * Maksimal lima pertanyaan pertama.-admin yang menilai biasanya
     * cukup dari pola soal dan tingkat kesulitannya, dan memuat seluruh
     * soal membuat modal perlu digulir jauh.
     */
    public static function ringkasanSoal(Quiz $quiz, int $batas = 5): string
    {
        $soal = $quiz->soal()
            ->aktif()
            ->orderBy('urutan')
            ->limit($batas)
            ->get(['pertanyaan', 'tingkat_kesulitan']);

        if ($soal->isEmpty()) {
            return 'Quiz ini belum punya soal aktif.';
        }

        $baris = $soal->map(function ($item, $nomor): string {
            $pertanyaan = trim(preg_replace('/\s+/', ' ', strip_tags((string) $item->pertanyaan)) ?? '');

            return $nomor.'. '.Str::limit($pertanyaan, 90)
                .'  ('.$item->tingkat_kesulitan.')';
        })->all();

        if ($quiz->jumlah_soal > $batas) {
            $baris[] = '… dan '.($quiz->jumlah_soal - $batas).' soal lainnya.';
        }

        return implode("\n", $baris);
    }

    /**
     * Pelajaran mana yang paling sering dikerjakan siswa.
     *
     * Dihitung dari pengerjaan quiz yang sudah selesai, lalu
     * digabung dengan pelajaran. Pelajaran tanpa pengerjaan tidak
     * masuk: potongan kosong tidak membantu admin memutuskan apa pun.
     *
     * @return array<int, array{label: string, nilai: int, warna: string, persentase: float}>
     */
    public static function pelajaranTerpopuler(?int $batas = null): array
    {
        $batas ??= self::batasTerpopuler();

        $baris = DB::table('tb_pengerjaan_quiz as p')
            ->join('tb_quiz as q', 'q.id', '=', 'p.quiz_id')
            ->join('tb_pelajaran as pa', 'pa.id', '=', 'q.pelajaran_id')
            ->selectRaw('pa.slug, pa.nama, COUNT(*) as jumlah')
            ->groupBy('pa.slug', 'pa.nama')
            ->orderByDesc('jumlah')
            ->limit($batas)
            ->get();

        $total = (float) $baris->sum('jumlah');

        return $baris->map(function ($item) use ($total): array {
            $kategori = Pelajaran::warna($item->slug, $item->nama);

            return [
                'label' => $kategori['nama'],
                'nilai' => (int) $item->jumlah,
                'warna' => $kategori['warna'],
                'persentase' => $total > 0 ? round(((int) $item->jumlah) / $total * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * Sebarannya isi platform per kategori: materi, quiz, dan jumlah
     * pengerjaan per pelajaran.
     *
     * Dipakai di halaman "Hasil & Statistik" sebagai pie chart isi
     * platform. Pelajaran yang ada di katalog tapi belum punya isi
     * apa pun tidak dihitung, jadi potongannya selalu berisi sesuatu.
     *
     * @return array<int, array{label: string, nilai: int, warna: string, materi: int, quiz: int}>
     */
    public static function isiPerPelajaran(int $batas = 6): array
    {
        $baris = DB::table('tb_pelajaran as pa')
            ->leftJoin('tb_materi as m', 'm.pelajaran_id', '=', 'pa.id')
            ->leftJoin('tb_quiz as q', 'q.pelajaran_id', '=', 'pa.id')
            ->selectRaw('pa.slug, pa.nama')
            ->selectRaw('COUNT(DISTINCT m.id) as materi')
            ->selectRaw('COUNT(DISTINCT q.id) as quiz')
            ->groupBy('pa.slug', 'pa.nama')
            ->havingRaw('COUNT(DISTINCT m.id) + COUNT(DISTINCT q.id) > 0')
            ->orderByRaw('COUNT(DISTINCT m.id) + COUNT(DISTINCT q.id) DESC')
            ->limit($batas)
            ->get();

        return $baris->map(function ($item): array {
            $kategori = Pelajaran::warna($item->slug, $item->nama);
            $total = (int) $item->materi + (int) $item->quiz;

            return [
                'label' => $kategori['nama'],
                'nilai' => $total,
                'warna' => $kategori['warna'],
                'materi' => (int) $item->materi,
                'quiz' => (int) $item->quiz,
            ];
        })->all();
    }

    /**
     * Jumlah konten per status, untuk angka di tab filter.
     *
     * Satu query untuk semua status supaya tab tidak butuh N query.
     *
     * @param  array<string, string>  $pilihan
     * @return array<string, int>
     */
    public static function jumlahPerStatus(Builder $query, array $pilihan): array
    {
        $jumlah = (clone $query)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect($pilihan)
            ->map(fn (string $label, string $status): int => (int) ($jumlah[$status] ?? 0))
            ->all();
    }

    /**
     * Rata-rata nilai, nilai tertinggi, dan jumlah pengerjaan dalam N
     * hari terakhir, untuk kartu ringkasan halaman statistik.
     *
     * @return array{jumlah: int, rata: float, tertinggi: int}
     */
    public static function nilaiPengerjaan(int $hari = 30): array
    {
        $query = PengerjaanQuiz::query()
            ->selesai()
            ->where('created_at', '>=', now()->subDays($hari));

        return [
            'jumlah' => (int) (clone $query)->count(),
            'rata' => round((float) ((clone $query)->avg('nilai') ?? 0), 1),
            'tertinggi' => (int) ((clone $query)->max('nilai') ?? 0),
        ];
    }

    /**
     * Aktivitas belajar 7 hari terakhir sebagai deret siap grafik.
     *
     * Tiga deret memakai satu skala: quiz yang dikerjakan, materi yang
     * dibaca, dan pengguna baru. Satu grafik batanginstead tiga grafik
     * supaya perbandingannya terbaca langsung.
     *
     * @return array<int, array{label: string, tanggal: string, quiz_dikerjakan: int, materi_dibaca: int, pengguna_baru: int}>
     */
    public static function aktivitasBelajar(int $hari = 7): array
    {
        $mulai = now()->startOfDay()->subDays($hari - 1);

        $dikerjakan = self::hitungPerHari('tb_pengerjaan_quiz', 'quiz_id', $mulai);
        $dibaca = self::hitungPerHari('tb_materi', 'id', $mulai, 'jumlah_dilihat');
        $pengguna = self::hitungPerHari('tb_pengguna', 'id', $mulai);

        $hasil = [];

        for ($i = 0; $i < $hari; $i++) {
            $tanggal = $mulai->copy()->addDays($i);
            $kunci = $tanggal->format('Y-m-d');

            $hasil[] = [
                'label' => $tanggal->translatedFormat('D'),
                'tanggal' => $tanggal->translatedFormat('d M'),
                'quiz_dikerjakan' => (int) ($dikerjakan[$kunci] ?? 0),
                'materi_dibaca' => (int) ($dibaca[$kunci] ?? 0),
                'pengguna_baru' => (int) ($pengguna[$kunci] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Jumlah baris per hari untuk satu tabel, dikelompokkan berdasarkan
     * created_at::date.
     *
     * Sumi opsional dipakai untuk kolom jumlah_dilihat pada materi:
     * "materi dibaca" di sini berarti jumlah kali materi dibuka,
     * bukan jumlah materi yang berbeda.
     *
     * @return Collection<string, int>
     */
    private static function hitungPerHari(string $tabel, string $kolomUnik, Carbon $mulai, ?string $jumlah = null): Collection
    {
        $query = DB::table($tabel)
            ->selectRaw('created_at::date as hari')
            ->selectRaw($jumlah ? 'COALESCE(SUM('.$jumlah.'), 0) as jumlah' : 'COUNT(DISTINCT '.$kolomUnik.') as jumlah')
            ->where('created_at', '>=', $mulai)
            ->groupBy('hari');

        return $query->pluck('jumlah', 'hari');
    }
}
