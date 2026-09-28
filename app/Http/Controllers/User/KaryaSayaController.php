<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Support\DaftarMateri;
use App\Support\DaftarQuiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Karya Saya": pusat pengelolaan konten milik pengguna yang
 * sedang login.
 *
 * Dua tab di halaman ini (Materi Saya / Quiz Saya) satu-satunya tempat
 * pengguna membuat, mengubah, dan menghapus karyanya. Menu Materi dan Quiz
 * tetap menampilkan seluruh isi aplikasi tanpa filter kepemilikan.
 *
 * Berbeda dengan halaman Materi, materi milik sendiri tidak dibatasi
 * scopeAktif(): materi yang sedang disembunyikan tetap harus bisa
 * dikelola pemiliknya.
 */
class KaryaSayaController extends Controller
{
    /** Tab "Materi Saya". */
    private const TAB_MATERI = 'materi';

    /** Tab "Quiz Saya". */
    private const TAB_QUIZ = 'quiz';

    public function __invoke(Request $request): View
    {
        $idPembuat = $request->user()?->getKey();
        $tab = $request->query('tab') === self::TAB_QUIZ ? self::TAB_QUIZ : self::TAB_MATERI;
        $kataKunci = trim((string) $request->query('q', ''));

        $jumlahPerTab = [
            self::TAB_MATERI => Materi::query()->milik($idPembuat)->count(),
            self::TAB_QUIZ => Quiz::query()->milik($idPembuat)->count(),
        ];

        $daftar = $tab === self::TAB_QUIZ
            ? $this->daftarQuiz($idPembuat, $kataKunci)
            : $this->daftarMateri($idPembuat, $kataKunci);

        return view('user.karya-saya', [
            'tab' => $tab,
            'daftar' => $daftar['kartu'],
            'paginasi' => $daftar['hal'],
            'jumlahPerTab' => $jumlahPerTab,
            'kataKunci' => $kataKunci,
            // Alasan kosong dipakai empty state: masih punya karya atau
            // sedang mencari yang tidak ada bedanya.
            'alasanKosong' => $kataKunci !== '' ? 'cari' : 'saya',
        ]);
    }

    /**
     * Materi milik pengguna yang sedang login, siap jadi kartu.
     *
     * @return array{kartu: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarMateri(?int $idPembuat, string $kataKunci): array
    {
        $hal = Materi::query()
            ->milik($idPembuat)
            ->with(['pelajaran', 'pembuat'])
            ->cari($kataKunci)
            ->latest()
            ->paginate(DaftarMateri::perHalaman())
            ->withQueryString();

        return [
            'kartu' => DaftarMateri::petakan($hal->items()),
            'hal' => $hal,
        ];
    }

    /**
     * Quiz milik pengguna yang sedang login, siap jadi kartu. Semua status
     * ikut ditampilkan supaya quiz yang masih draft atau ditolak tidak
     * hilang begitu saja.
     *
     * @return array{kartu: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarQuiz(?int $idPembuat, string $kataKunci): array
    {
        $hal = Quiz::query()
            ->milik($idPembuat)
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->where('aktif', true)])
            ->cari($kataKunci)
            ->latest()
            ->paginate(DaftarQuiz::perHalaman())
            ->withQueryString();

        return [
            'kartu' => DaftarQuiz::petakan($hal->items(), $idPembuat),
            'hal' => $hal,
        ];
    }
}
