<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Soal;
use App\Support\DaftarQuiz;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Detail satu quiz: kartu besar berisi identitas quiz, kategorinya, dan
 * soal-soalnya.
 *
 * Quiz yang belum tayang hanya boleh dibuka oleh pembuatnya sendiri, jadi
 * satu pemeriksaan di bawah: statusnya sudah terbit, atau quiz tersebut
 * milik pengguna yang sedang login.
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

        return view('user.quiz-detail', [
            'quiz' => $quiz,
            'kartu' => DaftarQuiz::petakan([$quiz], $pengguna?->getKey())[0],
            'soal' => $daftarSoal,
            'jumlahSoal' => $daftarSoal->count(),
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
