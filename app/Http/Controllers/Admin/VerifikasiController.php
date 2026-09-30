<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Support\StatistikAdmin;
use Illuminate\Http\Request;
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

        $daftar = $this->gabungkan($request, $jenis, $status, $kataKunci);

        return view('admin.verifikasi', [
            'daftar' => $daftar['baris'],
            'paginasi' => $daftar['hal'],
            'jenisAktif' => $jenis,
            'statusAktif' => $status,
            'kataKunci' => $kataKunci,
            'pilihanJenis' => self::JENIS,
            'pilihanStatus' => self::STATUS,
            'jumlahJenis' => $this->jumlahJenis($status),
            'jumlahStatus' => $this->jumlahStatus($jenis),
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
    private function gabungkan(Request $request, string $jenis, string $status, string $kataKunci): array
    {
        $materi = $jenis === 'quiz'
            ? collect()
            : $this->materi($status, $kataKunci)->map(
                fn (Materi $item): array => StatistikAdmin::barisTinjauMateri($item)
            );

        $quiz = $jenis === 'materi'
            ? collect()
            : $this->quiz($status, $kataKunci)->map(
                fn (Quiz $item): array => StatistikAdmin::barisTinjauQuiz($item)
            );

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
     * Materi sesuai status (dari nilai status di model) dan pencarian.
     *
     * @return Collection<int, Materi>
     */
    private function materi(string $status, string $kataKunci): Collection
    {
        return Materi::query()
            ->where('status', $this->statusMateri($status))
            ->with(['pelajaran', 'pembuat'])
            ->cari($kataKunci)
            ->latest('created_at')
            ->get();
    }

    /**
     * Quiz sesuai status dan pencarian.
     *
     * @return Collection<int, Quiz>
     */
    private function quiz(string $status, string $kataKunci): Collection
    {
        return Quiz::query()
            ->where('status', $this->statusQuiz($status))
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->aktif()])
            ->cari($kataKunci)
            ->latest('created_at')
            ->get();
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
     * @return array<string, int>
     */
    private function jumlahJenis(string $status): array
    {
        $materi = (int) Materi::query()->where('status', $this->statusMateri($status))->count();
        $quiz = (int) Quiz::query()->where('status', $this->statusQuiz($status))->count();

        return [
            'semua' => $materi + $quiz,
            'materi' => $materi,
            'quiz' => $quiz,
        ];
    }

    /**
     * Berapa konten tiap status, untuk angka di tab status.
     *
     * Satu query per tabel untuk semua status, jadi tab tidak butuh N
     * query. Materi dan quiz dijumlahkan karena dua tab status dipakai
     * bersama oleh dua jenis konten.
     *
     * @return array<string, int>
     */
    private function jumlahStatus(string $jenis): array
    {
        $materi = $jenis === 'quiz'
            ? []
            : StatistikAdmin::jumlahPerStatus(
                Materi::query(),
                [
                    Materi::STATUS_PENDING => 'menunggu',
                    Materi::STATUS_PUBLISHED => 'disetujui',
                    Materi::STATUS_REJECTED => 'ditolak',
                ]
            );

        $quiz = $jenis === 'materi'
            ? []
            : StatistikAdmin::jumlahPerStatus(
                Quiz::query(),
                [
                    Quiz::STATUS_PENDING => 'menunggu',
                    Quiz::STATUS_PUBLISHED => 'disetujui',
                    Quiz::STATUS_REJECTED => 'ditolak',
                ]
            );

        $hasil = [];

        foreach (self::STATUS as $nilai => $label) {
            $hasil[$nilai] = ($materi[$nilai] ?? 0) + ($quiz[$nilai] ?? 0);
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
