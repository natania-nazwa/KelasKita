<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizIsianRequest;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\SimpanSoalQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mengelola satu quiz milik sendiri: membuka form edit, menyimpan
 * perubahan, dan menghapus.
 *
 * Form edit memakai halaman "Buat Quiz" yang sama (view user.quiz-tambah),
 * hanya isian dan tujuan simpan yang berbeda, jadi tidak ada dua form untuk
 * hal yang sama.
 *
 * Semua tiga action dijaga hal yang sama: quiz-nya harus benar-benar milik
 * pengguna yang sedang login, kalau tidak jawabannya 403.
 */
class QuizKelolaController extends Controller
{
    public function edit(Request $request, Quiz $quiz): View
    {
        $this->pastikanMilik($request, $quiz);

        return view('user.quiz-tambah', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
            'quiz' => $quiz,
            'soal' => $quiz->soal()->terurut()->get(),
        ]);
    }

    public function update(QuizIsianRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $data = $request->isian();

        /*
         * Status tidak ikut diubah: quiz yang sudah tayang tetap tayang
         * setelah diperbaiki, dan quiz yang masih menunggu persetujuan
         * admin tidak diam-diam naik status.
         */
        $quiz->fill([
            'pelajaran_id' => $data['pelajaran_id'],
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => $data['visibilitas'],
            'kode_akses' => $data['kode_akses'] ?? null,
        ])->save();

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        return redirect()
            ->route('user.karya-saya', ['tab' => 'quiz'])
            ->with('sukses', 'Quiz "'.$quiz->judul.'" berhasil diperbarui.');
    }

    public function destroy(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $judul = $quiz->judul;

        // tb_soal tidak punya relasi database ke tb_quiz, jadi soalnya
        // dihapus eksplisit supaya tidak tertinggal sebagai baris yatim.
        $quiz->soal()->delete();
        $quiz->delete();

        return redirect()
            ->route('user.karya-saya', ['tab' => 'quiz'])
            ->with('sukses', 'Quiz "'.$judul.'" berhasil dihapus.');
    }

    /**
     * Quiz yang bukan karya pengguna yang sedang login tidak boleh dibuka
     * untuk diedit atau dihapus.
     */
    private function pastikanMilik(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->dimilikiOleh($request->user()?->getKey()), 403);
    }
}
