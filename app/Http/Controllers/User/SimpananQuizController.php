<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\SimpananQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Penyimpanan quiz milik pengguna yang sedang login.
 *
 * Dua pintu dipakai kartu quiz lewat fetch:
 *   POST /user/quiz/detail-quiz/{quiz}/simpan  menambah / melepas simpanan
 *   GET  /user/simpanan/quiz                   daftar id quiz yang disimpan
 *
 * Keduanya kembaran dari SimpananMateriController. Daftar itulah yang
 * dipakai resources/js/app.js untuk menampilkan status tombol "Simpan"
 * di semua kartu quiz dalam satu pembacaan, dan halaman Simpan untuk
 * menampilkan kartu quiz yang disimpan.
 *
 * Berbeda dari materi yang memakai slug, kunci tombol quiz adalah id
 * karena route detail quiz memang memakai id.
 */
class SimpananQuizController extends Controller
{
    /**
     * Balik status simpan satu quiz untuk pengguna yang login.
     */
    public function toggle(Request $request, string $quiz): JsonResponse
    {
        $item = Quiz::query()
            ->terbit()
            ->findOrFail($quiz);

        $pasangan = [
            'pengguna_id' => $request->user()->getKey(),
            'quiz_id' => $item->getKey(),
        ];

        // delete() menghasilkan jumlah baris yang hilang: 0 berarti memang
        // belum tersimpan, jadi barisnya dibuat. Satu langkah sekaligus
        // menangani klik ganda tanpa mengandung race yang menggantung.
        $tersimpan = SimpananQuiz::query()->where($pasangan)->delete() === 0;

        if ($tersimpan) {
            SimpananQuiz::query()->create($pasangan);
        }

        return response()->json(['tersimpan' => $tersimpan]);
    }

    /**
     * Id seluruh quiz yang disimpan pengguna yang login.
     *
     * Angka dikirim sebagai string supaya cocok dengan nilai atribut
     * data-bookmark di kartu quiz (selalu string) tanpa perlu konversi
     * di sisi klien.
     */
    public function data(Request $request): JsonResponse
    {
        $quizId = SimpananQuiz::query()
            ->where('pengguna_id', $request->user()->getKey())
            ->pluck('quiz_id');

        $id = Quiz::query()
            ->terbit()
            ->whereIn('id', $quizId)
            ->pluck('id')
            ->map(fn ($nilai) => (string) $nilai)
            ->values();

        return response()->json(['id' => $id->all()]);
    }
}
