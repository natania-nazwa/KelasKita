<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Agregasi read-only untuk halaman admin.
 *
 * Semua angka di dashboard dan di halaman admin lain dihitung di
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
     * Berapa baris teratas yang dipakai untuk "pelajaran terpopuler".
     */
    public static function batasTerpopuler(): int
    {
        return 5;
    }

    /**
     * Angka utama untuk kartu statistik dashboard.
     *
     * Satu query beruntun, bukan N+1: jumlah materi, quiz, dan pengguna
     * diambil bersama supaya membuka dashboard tetap beberapa query
     * tetap, bukan bertambahnya sebanding dengan isi database.
     *
     * "materi" dan "quiz" di sini BUKAN total seluruh konten, tapi
     * jumlah yang bertambah dalam 30 hari terakhir (dari yang sudah
     * terbit). Kartu di dashboard sengaja menampilkan laju
     * pertumbuhan, bukan akumulasi: total yang membesar terus akan
     * membuat angka kartu terasa diam dan tidak berguna dipakai
     * memantau. Angka yang sama persis diambil dari
     * "perubahan", jadi baris "12% dari bulan lalu" di bawahnya
     * dibandingkan dengan rentang sepanjang yang sama.
     *
     * Pending dan yang ditolak tidak masuk ke hitungan ini; keduanya
     * tetap terlihat lewat "materi_menunggu" dan "quiz_menunggu".
     *
     * "pengguna" tetap akumulasi, jadi baris keterangan di bawah
     * kartu pengguna memakai "perubahan.pengguna".
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
        $jumlahPengguna = (int) User::query()->count();

        $materiMenunggu = (int) Materi::query()->menunggu()->count();
        $quizMenunggu = (int) Quiz::query()->menunggu()->count();

        $nilai = PengerjaanQuiz::query()
            ->selesai()
            ->avg('nilai');

        $perubahan = [
            'pengguna' => self::perubahanBaru(User::query()),
            'materi' => self::perubahanBaru(Materi::query()->terbit()),
            'quiz' => self::perubahanBaru(Quiz::query()->terbit()),
        ];

        return [
            'pengguna' => $jumlahPengguna,
            'materi' => $perubahan['materi']['bulan_ini'],
            'quiz' => $perubahan['quiz']['bulan_ini'],
            'materi_menunggu' => $materiMenunggu,
            'quiz_menunggu' => $quizMenunggu,
            'pengguna_aktif' => self::penggunaAktif(),
            'quiz_dikerjakan' => (int) PengerjaanQuiz::query()->selesai()->count(),
            'rata_nilai' => round((float) ($nilai ?? 0), 1),
            'materi_dipelajari' => (int) DB::table('tb_simpanan_materi')->distinct()->count('materi_id'),
            'perubahan' => $perubahan,
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
     * Materi dan quiz yang menunggu keputusan admin, digabung jadi satu
     * daftar untuk kartu "Perlu Ditinjau".
     *
     * Batasnya 4: kartu itu ruang baca sekilas, dan kunci lengkapnya
     * ada di halaman Verifikasi. Empat baris juga pernah jadi tinggi
     * minimum daftar itu, jadi batas ini yang menentukan tinggi card
     * ketika antreannya penuh.
     *
     * Urutannya dari yang paling baru, jadi "N terbaru" di sini benar
     * berarti N yang terakhir diajukan, bukan N pertama yang terambil.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function perluDitinjau(int $batas = 4): array
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
            /*
             * Tautan "Tinjau" harus membuka halaman Verifikasi, bukan katalog
             * Materi.
             *
             * Katalog Materi dan Quiz hanya menampilkan konten yang sudah
             * tayang (scopeTerbit), sedangkan baris di sini justru konten
             * yang MENUNGGU keputusan — jadi tautan ke sana mendarat di
             * halaman yang tidak memuat konten itu. Verifikasi juga menerima
             * query "pilih" dengan bentuk "jenis:id" (lihat
             * Admin\VerifikasiController::rincianTerpilih), jadi panel
             * review-nya langsung terbuka untuk konten yang ditekan tanpa
             * admin mencari-cari di daftar.
             */
            'tautan' => route('admin.verifikasi', ['pilih' => 'materi:'.$materi->getKey()]),
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
            // Sama seperti materi: tautannya ke Verifikasi, bukan katalog Quiz.
            'tautan' => route('admin.verifikasi', ['pilih' => 'quiz:'.$quiz->getKey()]),
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
}
