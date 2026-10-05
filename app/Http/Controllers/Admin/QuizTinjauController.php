<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TolakQuizRequest;
use App\Models\Quiz;
use App\Support\TinjauanQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keputusan admin atas satu quiz: menyetujui atau menolak.
 *
 * Dua action ini hanya boleh dijalankan untuk quiz yang benar-benar sedang
 * menunggu. Quiz draft tidak bisa disetujui (belum pernah diajukan), dan quiz
 * yang sudah terbit atau ditolak tidak bisa diputuskan lagi dari halaman ini:
 * kalau tetap dipaksakan, jawabannya 404 supaya angka status di halaman
 * review tidak pernah berbeda dengan isi tabelnya.
 *
 * Keduanya mengembalikan admin ke tab status yang sedang dibuka, supaya
 * menolak satu quiz tidak membuat admin kehilangan tempatnya.
 */
class QuizTinjauController extends Controller
{
    public function setujui(Request $request, Quiz $quiz): RedirectResponse
    {
        $item = $this->quizMenunggu($quiz);

        $item->setujui();

        return $this->kembaliKeTinjauan(
            $request,
            'Quiz "'.$item->judul.'" disetujui dan sekarang tayang untuk semua pengguna.'
        );
    }

    public function tolak(TolakQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $item = $this->quizMenunggu($quiz);

        $item->tolak($request->string('alasan')->trim()->value());

        return $this->kembaliKeTinjauan(
            $request,
            'Quiz "'.$item->judul.'" ditolak. Alasannya sudah dikirim ke pemiliknya.'
        );
    }

    /**
     * Pastikan quiz ini benar-benar sedang menunggu keputusan.
     *
     * Status lain 404, bukan 403: bukan soal hak akses, tapi soal quiz ini
     * memang tidak sedang diputuskan.
     */
    private function quizMenunggu(Quiz $quiz): Quiz
    {
        abort_unless($quiz->status === Quiz::STATUS_PENDING, 404);

        return $quiz;
    }

    /**
     * Kembali ke tab status yang tadi dibuka, atau ke daftar tunggu kalau
     * form ditolak dari tab lain.
     *
     * Field "kembali" opsional: dipakai halaman "Verifikasi" yang meminjam
     * route ini untuk tombol keputusannya. Kalau diisi dan berupa daftar
     * putih yang dikenal, admin dikembalikan ke halaman itu. Tanpa field ini
     * perilakunya sama persis seperti sebelumnya.
     *
     * Halaman Verifikasi mengirim tambahan "kembali_url" berisi URL
     * halamannya sendiri lengkap dengan tab, pencarian, kategori, dan
     * halaman yang sedang dibuka, supaya memutuskan satu konten tidak
     * membuat admin kehilangan tempatnya. URL itu hanya dipakai kalau
     * benar-benar menunjuk ke route admin.verifikasi dengan skema, host,
     * port, dan path yang sama -- field ini tidak boleh bisa mengalihkan
     * admin ke halaman lain.
     */
    private function kembaliKeTinjauan(Request $request, string $pesan): RedirectResponse
    {
        $status = (string) $request->input('status', Quiz::STATUS_PENDING);

        $params = array_key_exists($status, TinjauanQuiz::pilihanStatus())
            ? ['status' => $status]
            : [];

        $kembali = (string) $request->input('kembali', '');

        if (in_array($kembali, ['admin.verifikasi'], true)) {
            return redirect()->to($this->tujuanVerifikasi($request))->with('sukses', $pesan);
        }

        return redirect()
            ->route('admin.quiz', $params)
            ->with('sukses', $pesan);
    }

    /**
     * URL tujuan halaman Verifikasi, lengkap dengan filter yang tadi dibuka.
     */
    private function tujuanVerifikasi(Request $request): string
    {
        $tujuan = route('admin.verifikasi');
        $url = (string) $request->input('kembali_url', '');

        if ($url === '') {
            return $tujuan;
        }

        $terurai = parse_url($url);
        $acuan = parse_url($tujuan);

        $sama = is_array($terurai)
            && ($terurai['scheme'] ?? null) === ($acuan['scheme'] ?? null)
            && ($terurai['host'] ?? null) === ($acuan['host'] ?? null)
            && ($terurai['port'] ?? null) === ($acuan['port'] ?? null)
            && ($terurai['path'] ?? null) === ($acuan['path'] ?? null);

        return $sama ? $url : $tujuan;
    }
}
