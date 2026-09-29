<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Support\DaftarQuiz;
use App\Support\DaftarSoal;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Detail satu quiz: kartu besar berisi identitas quiz, kategorinya, dan
 * soal-soalnya.
 *
 * Quiz yang belum tayang hanya boleh dibuka oleh pembuatnya sendiri, jadi
 * satu pemeriksaan di bawah: statusnya sudah terbit, atau quiz tersebut
 * milik pengguna yang sedang login. Aturan yang sama dipakai lagi di
 * QuizMulaiController, jadi tombol "Mulai Quiz" tidak membuka quiz yang
 * halamannya saja tertutup.
 */
class QuizDetailController extends Controller
{
    public function __invoke(Request $request, Quiz $quiz): View
    {
        $pengguna = $request->user();

        abort_unless(
            $quiz->status === Quiz::STATUS_PUBLISHED || $quiz->dibuat_oleh === $pengguna?->getKey(),
            404
        );

        $quiz->loadMissing(['pelajaran', 'pembuat']);

        $daftarSoal = $quiz->soal()->aktif()->terurut()->get();

        /*
         * Sesi yang masih hidup untuk quiz ini, kalau ada.
         *
         * Dicari tanpa filter host: sesi kode bisa dibuat lebih dulu oleh
         * peserta yang lebih dulu mengetik kodenya, tapi host-nya tetap
         * pemilik quiz. Jadi whoever yang sedang menjadi host, sesi yang
         * gefunden di sini tetap milik pengguna yang sedang login.
         */
        $sesiAktif = SesiQuiz::query()
            ->where('quiz_id', $quiz->getKey())
            ->belumSelesai()
            ->latest('id')
            ->first();

        return view('user.quiz-detail', [
            'quiz' => $quiz,
            'kartu' => DaftarQuiz::petakan([$quiz], $pengguna?->getKey())[0],
            'soal' => DaftarSoal::petakan($daftarSoal),
            'jumlahSoal' => $daftarSoal->count(),
            'sesiAktif' => $sesiAktif,
            // Komponen tampilan tidak terikat Eloquent, jadi sesi yang
            // diteruskan ke view sudah dipangkas jadi tautan lobby saja.
            // Kodenya tidak ikut karena yang dibaca peserta adalah kode akses
            // quiz, yang sudah ada di kartu.
            'sesiHost' => $sesiAktif === null ? null : [
                'tautan' => route('user.sesi.lobby', $sesiAktif),
            ],
            'rekomendasi' => $this->rekomendasi($quiz, $pengguna?->getKey()),
        ]);
    }

    /**
     * Tiga quiz lain dari kategori yang sama sebagai saran tempat belajar
     * berikutnya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rekomendasi(Quiz $quiz, ?int $idPengguna): array
    {
        $lain = Quiz::query()
            ->terbit()
            ->whereKeyNot($quiz->getKey())
            ->where('pelajaran_id', $quiz->pelajaran_id)
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->where('aktif', true)])
            ->latest()
            ->limit(3)
            ->get();

        return DaftarQuiz::petakan($lain, $idPengguna);
    }
}
