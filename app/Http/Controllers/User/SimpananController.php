<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\SimpananMateri;
use App\Models\SimpananQuiz;
use App\Support\DaftarMateri;
use App\Support\DaftarQuiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Simpan": seluruh materi dan quiz yang disimpan pengguna
 * yang sedang login lewat tombol "Simpan" di pojok kanan atas kartu.
 *
 * Dua tab (Materi / Quiz) memakai query string ?tab=, sama seperti
 * halaman "Karya Saya", jadi perpindahan tab tetap jalan walau
 * JavaScript dimatikan dan alamatnya bisa disalin.
 *
 * Daftar hanya berisi simpanan milik sendiri: filter kepemilikan ada di
 * query lewat subselect tb_simpanan_materi / tb_simpanan_quiz, bukan di
 * Blade. Materi maupun quiz yang sudah tidak tayang ikut tersaring, jadi
 * simpanan yang isinya ditarik admin tidak menampilkan kartu mati.
 */
class SimpananController extends Controller
{
    /** Tab "Materi". */
    private const TAB_MATERI = 'materi';

    /** Tab "Quiz". */
    private const TAB_QUIZ = 'quiz';

    public function __invoke(Request $request): View
    {
        $idPengguna = $request->user()?->getKey();
        $tab = $this->tab($request);
        $kataKunci = $this->kataKunci($request);

        $jumlahPerTab = [
            self::TAB_MATERI => $this->jumlahMateri($idPengguna),
            self::TAB_QUIZ => $this->jumlahQuiz($idPengguna),
        ];

        $daftar = $this->daftar($tab, $idPengguna, $kataKunci);

        return view('user.simpanan', [
            'tab' => $tab,
            'daftar' => $daftar['kartu'],
            'paginasi' => $daftar['hal'],
            'jumlahPerTab' => $jumlahPerTab,
            'kataKunci' => $kataKunci,
            // Alasan kosong dipakai empty state: belum pernah menyimpan
            // apa pun atau sedang mencari yang tidak ada, bedanya jelas
            // dari pesannya.
            'alasanKosong' => $kataKunci !== '' ? 'cari' : 'kosong',
        ]);
    }

    /**
     * Halaman berikutnya untuk tombol "Muat lagi".
     *
     * Yang dikirim cuma kartu dan tautan halaman berikutnya, bukan
     * halaman utuh: pengguna tidak boleh kehilangan isi yang sudah
     * dibaca gara-gara menambah isi.
     *
     * Tautannya tetap tautan biasa, jadi tanpa JavaScript tombol "Muat
     * lagi" tetap bekerja seperti pagination biasa: parameter ?page=
     * dibaca oleh __invoke() di atas.
     */
    public function muat(Request $request): JsonResponse
    {
        $idPengguna = $request->user()?->getKey();
        $tab = $this->tab($request);
        $kataKunci = $this->kataKunci($request);

        $daftar = $this->daftar($tab, $idPengguna, $kataKunci);
        $hal = $daftar['hal'];

        return response()->json([
            'kartu' => view('user.partials.simpanan-kartu', [
                'tab' => $tab,
                'daftar' => $daftar['kartu'],
                'kataKunci' => $kataKunci,
            ])->render(),
            // null berarti sudah tidak ada halaman berikutnya, jadi
            // initMuatLebih() menyembunyikan tombolnya.
            'berikutnya' => $hal->hasMorePages() ? $hal->nextPageUrl() : null,
        ]);
    }

    /**
     * Tab yang diminta. Nilai selain "quiz" dianggap "materi", jadi
     * URL yang rusak tidak pernah membuat halaman kosong.
     */
    private function tab(Request $request): string
    {
        return $request->query('tab') === self::TAB_QUIZ
            ? self::TAB_QUIZ
            : self::TAB_MATERI;
    }

    /**
     * Kata kunci pencarian, sudah dipangkas spasinya.
     */
    private function kataKunci(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    /**
     * Daftar sesuai tab: kartu siap tampil plus paginator-nya.
     *
     * @return array{kartu: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftar(string $tab, ?int $idPengguna, string $kataKunci): array
    {
        return $tab === self::TAB_QUIZ
            ? $this->daftarQuiz($idPengguna, $kataKunci)
            : $this->daftarMateri($idPengguna, $kataKunci);
    }

    /**
     * Jumlah simpanan per halaman.
     *
     * Dua puluh lima, mengikuti grid lima kolom di halaman ini (lihat
     * components/simpanan/daftar): 25 = 5 baris × 5 kolom, jadi gridnya
     * selalu penuh di layar besar tanpa kartu yatim di baris terakhir.
     *
     * Angka ini sengaja terpisah dari DaftarMateri::perHalaman() dan
     * DaftarQuiz::perHalaman(): kedua angka itu milik halaman Materi dan
     * Quiz yang gridnya sudah empat kolom, jadi ikut mengubahnya akan
     * menggeser pembagian halaman di kedua halaman itu.
     */
    private function perHalaman(): int
    {
        return 25;
    }

    /**
     * Materi terbit yang disimpan pengguna, siap jadi kartu.
     *
     * Urutannya created_at lalu id, bukan created_at saja. created_at bisa
     * bernilai sama untuk banyak materi — misalnya saat pengimpor berjalan,
     * atau pada data yang dibuat dalam satu transaksi — dan tanpa pemutus
     * tie-breaker database bebas mengembalikan baris dengan created_at sama
     * dalam urutan apa pun. Akibatnya satu materi bisa melompat antara
     * halaman 1 dan 2 lalu balik lagi, jadi "Muat lagi" ikut mengulang atau
     * melewatkan kartu.
     *
     * @return array{kartu: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarMateri(?int $idPengguna, string $kataKunci): array
    {
        $hal = Materi::query()
            ->terbit()
            ->whereIn('id', $this->simpananMateri($idPengguna))
            ->with(['pelajaran', 'pembuat'])
            ->cari($kataKunci)
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perHalaman())
            ->withQueryString();

        return [
            'kartu' => DaftarMateri::petakan($hal->items()),
            'hal' => $hal,
        ];
    }

    /**
     * Quiz terbit yang disimpan pengguna, siap jadi kartu.
     *
     * Pemutus tie-breaker id-nya sama seperti daftarMateri() di atas, dengan
     * alasan yang sama.
     *
     * @return array{kartu: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarQuiz(?int $idPengguna, string $kataKunci): array
    {
        $hal = Quiz::query()
            ->terbit()
            ->whereIn('id', $this->simpananQuiz($idPengguna))
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->where('aktif', true)])
            ->cari($kataKunci)
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perHalaman())
            ->withQueryString();

        return [
            'kartu' => DaftarQuiz::petakan($hal->items(), $idPengguna),
            'hal' => $hal,
        ];
    }

    /**
     * Angka tab Materi: jumlah simpanan materi yang masih tayang.
     */
    private function jumlahMateri(?int $idPengguna): int
    {
        return Materi::query()
            ->terbit()
            ->whereIn('id', $this->simpananMateri($idPengguna))
            ->count();
    }

    /**
     * Angka tab Quiz: jumlah simpanan quiz yang masih tayang.
     */
    private function jumlahQuiz(?int $idPengguna): int
    {
        return Quiz::query()
            ->terbit()
            ->whereIn('id', $this->simpananQuiz($idPengguna))
            ->count();
    }

    /**
     * Subselect id materi yang disimpan pengguna. Dipakai sebagai subquery
     * supaya daftar tetap satu query dan tidak menarik id ke memori.
     */
    private function simpananMateri(?int $idPengguna): Builder
    {
        return SimpananMateri::query()
            ->where('pengguna_id', $idPengguna)
            ->select('materi_id');
    }

    /**
     * Subselect id quiz yang disimpan pengguna.
     */
    private function simpananQuiz(?int $idPengguna): Builder
    {
        return SimpananQuiz::query()
            ->where('pengguna_id', $idPengguna)
            ->select('quiz_id');
    }
}
