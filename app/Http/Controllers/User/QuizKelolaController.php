<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizIsianRequest;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\BerkasQuiz;
use App\Support\KodeQuiz;
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
            // Quiz tanpa kode (mis. quiz publik yang belum pernah punya
            // kode) tetap membuka form dengan kode acak, supaya peserta
            // bisa memakai tombol "Generate Kode" tanpa mengetik dari nol.
            'kodeAwal' => $quiz->kode_akses ?? KodeQuiz::unik(),
        ]);
    }

    public function update(QuizIsianRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $data = $request->isian();
        $thumbnailBaru = BerkasQuiz::simpan($request)['thumbnail'] ?? null;

        /*
         * Status tidak pernah diubah diam-diam oleh pemilik. Satu-satunya
         * jalan masuk ke daftar tunggu admin adalah saklar "Ajukan
         * Persetujuan", dan saklar itu hanya berarti sesuatu kalau quiznya
         * masih boleh diajukan (draft, ditolak di bawah batas pengajuan, atau
         * sudah terbit lalu direvisi).
         *
         * Quiz yang sudah terbit pun ikut diturunkan ke daftar tunggu, bukan
         * tetap tayang. Kalau tidak, perubahannya bisa langsung menjangkau
         * pengguna tanpa pernah ditinjau admin.
         */
        $pernahTerbit = $quiz->pernahTerbit();
        $dikirim = $request->diajukanKeAdmin() && $quiz->bolehDiajukan();

        /*
         * Quiz yang tayang lalu diganti jadi mode kode ditarik dari halaman
         * Quiz. Mode kode hanya bisa dibuka lewat kodenya, jadi membiarkannya
         * tetap tayang akan menayangkan quiz privat ke semua orang.
         */
        $menjadiKode = $data['visibilitas'] === Quiz::VISIBILITAS_PRIVAT;

        $quiz->fill([
            'pelajaran_id' => $data['pelajaran_id'],
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => $data['visibilitas'],
            'tampilkan_jawaban' => $data['tampilkan_jawaban'],
            'kode_akses' => $data['kode_akses'] ?? null,
            'catatan_pengajuan' => $data['catatan_pengajuan'] ?? null,
            ...($thumbnailBaru !== null ? ['thumbnail' => $thumbnailBaru] : []),
        ])->save();

        if ($thumbnailBaru !== null) {
            BerkasQuiz::hapus($quiz->getOriginal('thumbnail'));
        }

        if ($menjadiKode) {
            $quiz->tarikDariDaftar();
        }

        if ($dikirim) {
            $quiz->ajukanPersetujuan();
        }

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        return redirect()
            ->route('user.karya-saya', ['tab' => 'quiz'])
            ->with('sukses', $this->pesanSimpan($quiz, $dikirim, $pernahTerbit, $menjadiKode));
    }

    /**
     * Pesan setelah quiz edit tersimpan.
     *
     * Diberi kasus tersendiri karena akibatnya berbeda: quiz yang tayang lalu
     * ditarik kembali ke daftar tunggu hilang dari halaman Quiz, jadi
     * pemiliknya perlu tahu supaya tidak mengira quiznya masih dibaca.
     */
    private function pesanSimpan(Quiz $quiz, bool $dikirim, bool $pernahTerbit, bool $menjadiKode): string
    {
        if ($menjadiKode) {
            return 'Quiz "'.$quiz->judul.'" diperbarui dan sekarang memakai kode '.$quiz->kodeGabung().'. Quiz tidak lagi tayang di halaman Quiz.';
        }

        if (! $dikirim) {
            return 'Quiz "'.$quiz->judul.'" berhasil diperbarui.';
        }

        return $pernahTerbit
            ? 'Quiz "'.$quiz->judul.'" diperbarui dan dikirim ulang ke admin. Quiz ini berhenti tayang sampai disetujui lagi.'
            : 'Quiz "'.$quiz->judul.'" diperbarui dan dikirim ke admin untuk ditinjau.';
    }

    public function destroy(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $judul = $quiz->judul;
        $thumbnail = $quiz->thumbnail;

        // tb_soal tidak punya relasi database ke tb_quiz, jadi soalnya
        // dihapus eksplisit supaya tidak tertinggal sebagai baris yatim.
        $quiz->soal()->delete();
        $quiz->delete();

        // Berkas thumbnail dihapus setelah kovery dihapus supaya tidak
        // tertinggal sebagai gambar yatim di disk publik.
        BerkasQuiz::hapus($thumbnail);

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
