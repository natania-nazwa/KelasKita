<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Support\IsiMateri;
use App\Support\StatistikAdmin;
use App\Support\TinjauanMateri;
use App\Support\TinjauanQuiz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman "Verifikasi Konten": satu tempat admin melihat seluruh
 * materi dan quiz yang butuh keputusan, apa pun jenis dan statusnya.
 *
 * Halaman ini tidak pernah memutuskan apa pun. Tombol Setujui dan
 * Tolak di dalam dialog mengirim POST ke route yang sudah ada
 * (admin.materi.setujui / admin.materi.tolak, dan padanannya untuk
 * quiz), jadi seluruh logika persetujuan tetap milik
 * MateriTinjauController dan QuizTinjauController.
 *
 * Quiz mode kode sengaja tidak pernah muncul di sini, sama seperti di
 * halaman Tinjau Quiz: quiz berbasis kode tidak tayang untuk semua
 * pengguna, jadi tidak ada yang perlu disetujui admin.
 */
class VerifikasiController extends Controller
{
    /**
     * Konten per halaman.
     */
    private const PER_HALAMAN = 8;

    /**
     * Tab jenis konten: semua | materi | quiz
     */
    private const JENIS = [
        'semua' => 'Semua',
        'materi' => 'Materi',
        'quiz' => 'Quiz',
    ];

    /**
     * Tab status: menunggu | disetujui | ditolak
     *
     * Berbeda dari halaman Tinjau Materi dan Tinjau Quiz yang punya
     * empat status, di sini admin hanya butuh tiga: pekerjaan yang
     * belum selesai, yang sudah disetujui, dan yang ditolak. Status
     * "draft" tidak masuk karena isinya milik pembuatnya dan belum
     * pernah ditawarkan ke admin.
     *
     * @var array<string, string>
     */
    private const STATUS = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
    ];

    public function __invoke(Request $request): View
    {
        $jenis = $this->terpilih($request->query('jenis'), array_keys(self::JENIS), 'semua');
        $status = $this->terpilih($request->query('status'), array_keys(self::STATUS), 'menunggu');
        $kataKunci = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $pilih = trim((string) $request->query('pilih', ''));

        $daftar = $this->gabungkan($request, $jenis, $status, $kataKunci, $kategori);

        return view('admin.verifikasi', [
            'daftar' => $daftar['baris'],
            'paginasi' => $daftar['hal'],
            'rincian' => $this->rincianTerpilih($pilih, $daftar['baris']),
            'pilih' => $pilih,
            'jenisAktif' => $jenis,
            'statusAktif' => $status,
            'kataKunci' => $kataKunci,
            'kategoriAktif' => $kategori,
            'daftarPelajaran' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
            'pilihanJenis' => self::JENIS,
            'pilihanStatus' => self::STATUS,
            'jumlahJenis' => $this->jumlahJenis($status, $kataKunci, $kategori),
            'jumlahStatus' => $this->jumlahStatus($jenis, $kataKunci, $kategori),
        ]);
    }

    /**
     * Panel review untuk satu konten, sebagai fragment HTML.
     *
     * Dipanggil JavaScript saat admin memilih baris lain di daftar. Hanya
     * mengembalikan isi panel, bukan seluruh halaman, supaya memilih baris
     * tidak memuat ulang hero, filter, dan daftar yang sudah terbaca.
     *
     * Id yang tidak dikenal tidak dijawab 404: panel memang harus selalu
     * punya isi, dan isi "belum ada yang dipilih" adalah jawaban yang benar
     * untuk id yang tidak ada.
     */
    public function panel(Request $request): Response
    {
        $jenis = $this->terpilih($request->query('jenis'), ['materi', 'quiz'], 'materi');

        return response()->view('admin.verifikasi-panel', [
            'rincian' => $this->rincian($jenis, (int) $request->query('id', 0)),
        ]);
    }

    /**
     * Materi dan quiz yang cocok, digabung jadi satu daftar urut dari
     * yang paling baru.
     *
     * Paginasi dihitung sendiri di sini, bukan lewat paginate(),
     * karena dua tabel harus digabung lebih dulu supaya admin bisa
     * memindai keduanya dalam satu daftar. Tautan halaman tetap
     * bekerja, dan nomor halamannya benar walau jumlah baris tidak
     * kelipatan ukuran halaman.
     *
     * @return array{baris: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function gabungkan(Request $request, string $jenis, string $status, string $kataKunci, string $kategori): array
    {
        $materi = $jenis === 'quiz'
            ? collect()
            : collect(
                TinjauanMateri::petakan($this->materi($status, $kataKunci, $kategori))
            )->map(fn (array $baris): array => $this->lengkapiBaris($baris, 'materi', 'Materi'));

        $quiz = $jenis === 'materi'
            ? collect()
            : collect(
                TinjauanQuiz::petakan($this->quiz($status, $kataKunci, $kategori))
            )->map(fn (array $baris): array => $this->lengkapiBaris($baris, 'quiz', 'Quiz'));

        $semua = $materi
            ->concat($quiz)
            ->sortByDesc('dibuat_pada')
            ->values();

        $halamanSekarang = max(1, (int) $request->query('page', 1));

        $hal = new LengthAwarePaginator(
            $semua->forPage($halamanSekarang, self::PER_HALAMAN),
            $semua->count(),
            self::PER_HALAMAN,
            $halamanSekarang,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return [
            'baris' => $hal->items(),
            'hal' => $hal,
        ];
    }

    /**
     * Materi sesuai status (dari nilai status di model), pencarian, dan
     * kategori.
     *
     * @return Collection<int, Materi>
     */
    private function materi(string $status, string $kataKunci, string $kategori): Collection
    {
        return $this->cariMateri(Materi::query(), $kataKunci)
            ->where('status', $this->statusMateri($status))
            ->with(['pelajaran', 'pembuat'])
            ->kategori($kategori)
            ->latest('created_at')
            ->get();
    }

    /**
     * Quiz sesuai status, pencarian, dan kategori.
     *
     * @return Collection<int, Quiz>
     */
    private function quiz(string $status, string $kataKunci, string $kategori): Collection
    {
        $quiz = $this->cariQuiz(Quiz::query(), $kataKunci)
            ->where('status', $this->statusQuiz($status))
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->aktif()])
            ->kategori($kategori)
            ->latest('created_at')
            ->get();

        /*
         * Jumlah soal dipakai TinjauanQuiz lewat jumlahSoal(), yang membaca
         * atribut "jumlah_soal_termuat". Tanpa pengisian ini tiap baris akan
         * menghitung ulang soalnya sendiri, jadi delapan baris berarti
         * delapan query tambahan yang isinya sama dengan soal_count ini.
         */
        return $quiz->each(
            fn (Quiz $item): Quiz => $item->setRawAttributes(
                $item->getAttributes() + ['jumlah_soal_termuat' => (int) $item->soal_count]
            )
        );
    }

    /**
     * Pencarian materi di halaman Verifikasi.
     *
     * Materi::scopeCari() sengaja tidak dipakai: ia tidak mencakup nama
     * pembuat, sementara label filter halaman ini menuliskan "Cari judul
     * atau pembuat" dan placeholder-nya menjanjikan hal yang sama. Menambah
     * pembuat ke scope itu akan diam-diam mengubah pencarian di halaman
     * Materi, Karya Saya, dan halaman lain yang memakainya, jadi sisipannya
     * dilakukan di sini saja, tempat janjinya ditulis.
     *
     * @return Builder<Materi>
     */
    private function cariMateri(Builder $query, string $kataKunci): Builder
    {
        $kataKunci = trim($kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        [$operator, $pola] = $this->polaCari($query, $kataKunci);

        return $query->where(function (Builder $query) use ($operator, $pola) {
            $query->where('nama', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhere('isi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola))
                ->orWhereHas('pembuat', fn (Builder $pembuat) => $pembuat->where('nama', $operator, $pola));
        });
    }

    /**
     * Pencarian quiz di halaman Verifikasi, pasangan cariMateri().
     *
     * @return Builder<Quiz>
     */
    private function cariQuiz(Builder $query, string $kataKunci): Builder
    {
        $kataKunci = trim($kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        [$operator, $pola] = $this->polaCari($query, $kataKunci);

        return $query->where(function (Builder $query) use ($operator, $pola) {
            $query->where('judul', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola))
                ->orWhereHas('pembuat', fn (Builder $pembuat) => $pembuat->where('nama', $operator, $pola));
        });
    }

    /**
     * Operator LIKE sesuai driver dan pola yang sudah meng-escape karakter
     * wildcard milik pengguna. Aturannya sama dengan scopeCari() di model,
     * supaya pencarian di sini dan di halaman lain berperilaku seragam.
     *
     * @return array{0: string, 1: string}
     */
    private function polaCari(Builder $query, string $kataKunci): array
    {
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return [$operator, '%'.addcslashes($kataKunci, '%_\\').'%'];
    }

    /**
     * Melengkapi satu baris hasil petakan dengan apa yang hanya dipakai
     * halaman Verifikasi: jenis kontennya dan ringkasan satu baris.
     *
     * Isinya dibuang karena daftar hanya butuh judul dan metadata, sedangkan
     * isi lengkapnya sudah dirender di panel review.
     *
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    private function lengkapiBaris(array $baris, string $jenis, string $label): array
    {
        unset($baris['isi']);

        $baris['jenis'] = $jenis;
        $baris['label_jenis'] = $label;
        $baris['rincian'] = $jenis === 'materi'
            ? $baris['jumlah_bab'].' Bab · '.$baris['waktu_baca'].' Menit Baca'
            : $baris['jumlah_soal'].' Soal · Mode '.($baris['pakai_kode'] ? 'Kode' : 'Publik');

        return $baris;
    }

    /**
     * Konten yang sedang dibuka di panel review.
     *
     * Urutannya: pilihan eksplisit dari query "pilih" lebih dulu, supaya
     * tautan baris yang dibuka tanpa JavaScript tetap menunjuk konten yang
     * sama. Kalau id itu tidak dikenal (mis. sudah dipindah ke halaman
     * lain), panel jatuh ke baris pertama halaman yang sedang dilihat.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<string, mixed>|null
     */
    private function rincianTerpilih(string $pilih, array $baris): ?array
    {
        if (preg_match('/^(materi|quiz):(\d+)$/', $pilih, $cocok) === 1) {
            $hasil = $this->rincian($cocok[1], (int) $cocok[2]);

            if ($hasil !== null) {
                return $hasil;
            }
        }

        $pertama = $baris[0] ?? null;

        return $pertama === null
            ? null
            : $this->rincian((string) $pertama['jenis'], (int) $pertama['id']);
    }

    /**
     * Satu konten lengkap untuk panel review, atau null kalau id-nya tidak
     * ada.
     *
     * Materi dibawa beserta isi yang sudah dipecah jadi seksi + blok,
     * quiz dibawa beserta daftar soalnya (termasuk kunci jawaban, karena
     * panel ini memang hanya untuk mata admin).
     *
     * @return array<string, mixed>|null
     */
    private function rincian(string $jenis, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        if ($jenis === 'materi') {
            $materi = Materi::query()->with(['pelajaran', 'pembuat'])->find($id);

            if (! $materi instanceof Materi) {
                return null;
            }

            $baris = TinjauanMateri::petakan([$materi])[0];

            return $this->lengkapiBaris($baris, 'materi', 'Materi') + [
                'seksi' => IsiMateri::seksi($materi->isi, $materi->nama),
            ];
        }

        $quiz = $this->quizLengkap($id);

        if (! $quiz instanceof Quiz) {
            return null;
        }

        $baris = TinjauanQuiz::petakan([$quiz])[0];

        return $this->lengkapiBaris($baris, 'quiz', 'Quiz') + [
            'soal' => $this->daftarSoal($quiz),
        ];
    }

    /**
     * Satu quiz lengkap relasinya, untuk panel review.
     */
    private function quizLengkap(int $id): ?Quiz
    {
        $quiz = Quiz::query()
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->aktif()])
            ->find($id);

        return $quiz instanceof Quiz
            ? $quiz->setRawAttributes(
                $quiz->getAttributes() + ['jumlah_soal_termuat' => (int) $quiz->soal_count]
            )
            : null;
    }

    /**
     * Daftar soal aktif sebuah quiz, siap dirender jadi pratinjau.
     *
     * Kuncinya ikut dibawa: panel review adalah ruang admin, jadi yang
     * diperiksa adalah apakah jawaban benarnya sudah tepat, bukan
     * menyembunyikannya dari pemeriksa.
     *
     * @return array<int, array{teks: string, tipe: string, tipe_label: string, pilihan: array<string, string>, benar: array<int, string>, kunci: string}>
     */
    private function daftarSoal(Quiz $quiz): array
    {
        return $quiz->soal()
            ->aktif()
            ->terurut()
            ->with('pilihanSoal')
            ->get()
            ->map(fn (Soal $soal): array => [
                'teks' => (string) $soal->pertanyaan,
                'tipe' => $soal->tipe(),
                'tipe_label' => Soal::labelTipe($soal->tipe()),
                'pilihan' => $soal->pilihan(),
                'benar' => $soal->hurufBenar(),
                'kunci' => $soal->tipeTeks() ? $soal->kunciTeks() : '',
            ])
            ->all();
    }

    /**
     * Nilai status materi di model untuk tab status di halaman ini.
     */
    private function statusMateri(string $status): string
    {
        return match ($status) {
            'disetujui' => Materi::STATUS_PUBLISHED,
            'ditolak' => Materi::STATUS_REJECTED,
            default => Materi::STATUS_PENDING,
        };
    }

    /**
     * Nilai status quiz di model untuk tab status di halaman ini.
     */
    private function statusQuiz(string $status): string
    {
        return match ($status) {
            'disetujui' => Quiz::STATUS_PUBLISHED,
            'ditolak' => Quiz::STATUS_REJECTED,
            default => Quiz::STATUS_PENDING,
        };
    }

    /**
     * Berapa konten tiap jenis, untuk angka di tab jenis.
     *
     * Pencarian ikut dihitung supaya angka tab selalu sejalan dengan isi
     * daftar: kalau pencarian menyaring daftar tapi tidak menyaring angka,
     * admin melihat tab bercap "12" di atas daftar yang hanya berisi satu.
     *
     * @return array<string, int>
     */
    private function jumlahJenis(string $status, string $kataKunci, string $kategori): array
    {
        $materi = (int) $this->cariMateri(Materi::query(), $kataKunci)
            ->where('status', $this->statusMateri($status))
            ->kategori($kategori)
            ->count();

        $quiz = (int) $this->cariQuiz(Quiz::query(), $kataKunci)
            ->where('status', $this->statusQuiz($status))
            ->kategori($kategori)
            ->count();

        return [
            'semua' => $materi + $quiz,
            'materi' => $materi,
            'quiz' => $quiz,
        ];
    }

    /**
     * Berapa konten tiap status, untuk angka di pil status.
     *
     * Satu query per tabel untuk semua status, jadi pil tidak butuh N
     * query. Materi dan quiz dijumlahkan karena dua pil status dipakai
     * bersama oleh dua jenis konten. Pencarian ikut dihitung, sama seperti
     * jumlahJenis(), supaya angka dan daftar tidak pernah berbeda.
     *
     * StatistikAdmin::jumlahPerStatus() mengembalikan hasil yang dikunci
     * dengan nilai status di tabel ("pending", "published", "rejected"),
     * bukan dengan kunci tab ("menunggu", "disetujui", "ditolak"). Karena
     * itu hasilnya dibaca lewat statusMateri() / statusQuiz() -- tanpa
     * penerjemahan ini semua angka pil akan selalu nol.
     *
     * @return array<string, int>
     */
    private function jumlahStatus(string $jenis, string $kataKunci, string $kategori): array
    {
        $materi = $jenis === 'quiz'
            ? []
            : StatistikAdmin::jumlahPerStatus(
                $this->cariMateri(Materi::query(), $kataKunci)->kategori($kategori),
                [
                    Materi::STATUS_PENDING => 'menunggu',
                    Materi::STATUS_PUBLISHED => 'disetujui',
                    Materi::STATUS_REJECTED => 'ditolak',
                ]
            );

        $quiz = $jenis === 'materi'
            ? []
            : StatistikAdmin::jumlahPerStatus(
                $this->cariQuiz(Quiz::query(), $kataKunci)->kategori($kategori),
                [
                    Quiz::STATUS_PENDING => 'menunggu',
                    Quiz::STATUS_PUBLISHED => 'disetujui',
                    Quiz::STATUS_REJECTED => 'ditolak',
                ]
            );

        $hasil = [];

        foreach (self::STATUS as $nilai => $label) {
            $hasil[$nilai] = ($materi[$this->statusMateri($nilai)] ?? 0)
                + ($quiz[$this->statusQuiz($nilai)] ?? 0);
        }

        return $hasil;
    }

    /**
     * Nilai dari query string, dipaksa ke salah satu yang diizinkan.
     *
     * @param  array<int, string>  $pilihan
     */
    private function terpilih(mixed $nilai, array $pilihan, string $bawaan): string
    {
        return in_array((string) $nilai, $pilihan, true) ? (string) $nilai : $bawaan;
    }
}
