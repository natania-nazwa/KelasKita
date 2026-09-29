<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\GabungSesiRequest;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Support\SesiKode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu "Masukkan Kode": form untuk masuk ke lobby quiz mode kode milik
 * orang lain.
 *
 * Alurnya:
 *   - Kode yang diketik dicari pada QUIZ, bukan pada sesi. Satu quiz mode
 *     kode punya satu kode akses, dan semua yang mengetik kode itu masuk ke
 *     sesi yang sama.
 *   - Peserta yang belum bergabung DITARUH ke lobby, bukan langsung ke soal.
 *     Baru setelah host menekan "Mulai Quiz" mereka boleh membuka soal.
 *   - Kalau belum ada sesi yang hidup, sesi dibuat lebih dulu dengan host
 *     pemilik quiz, jadi peserta tidak pernah terjebak di lobby yang tidak
 *     akan pernah dimulai dan host tetap orang yang membuat quiz.
 *   - Kalau sudah pernah bergabung, barisnya tidak dibuat lagi; pengguna
 *     diberi tahu lalu langsung dikembalikan ke lobby yang sama.
 *
 * Quiz mode publik tidak punya kode, jadi tidak akan pernah ditemukan di sini:
 * quiz publik dibuka lewat halaman detailnya, bukan lewat kode.
 */
class SesiGabungController extends Controller
{
    /**
     * Halaman form "Gabung Quiz".
     *
     * Kalau kode yang sudah diketik milik quiz yang pengguna ini buat, dan
     * quiz itu punya sesi, halaman ini langsung membawa ke lobby miliknya
     * sendiri supaya tidak perlu mengetik kode lagi.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $kode = $request->string('kode')->toString();

        if ($kode !== '') {
            $sesiMilik = $this->sesiMilikPengguna($request, $kode);

            if ($sesiMilik !== null) {
                return redirect()
                    ->route('user.sesi.lobby', $sesiMilik)
                    ->with('info', 'Kode ini milikmu. Bagikan kodenya ke teman.');
            }
        }

        return view('user.quiz-gabung', [
            'kode' => Quiz::kodeBaku($kode),
        ]);
    }

    /**
     * Memproses kode yang diketik peserta.
     */
    public function store(GabungSesiRequest $request): RedirectResponse
    {
        $kode = $request->kode();

        $quiz = Quiz::query()->kodeAkses($kode)->first();

        if ($quiz === null) {
            return $this->gagal($kode, 'Kode quiz tidak ditemukan.');
        }

        /*
         * Kode tanpa soal tidak bisa apa-apa. Quiz seperti ini tetap boleh
         * ada di Karya Saya pemiliknya, jadi pesertanya diberi tahu di sini
         * alih-alih terjebak di lobby yang selamanya kosong.
         */
        if ($quiz->soal()->aktif()->count() === 0) {
            return $this->gagal($kode, 'Quiz ini belum punya soal, jadi belum bisa dimulai.');
        }

        $sesi = SesiKode::bukaAtauBuat($quiz);
        $pengguna = $request->user();

        if ($sesi->sesi->adalahHost($pengguna)) {
            return redirect()
                ->route('user.sesi.lobby', $sesi->sesi)
                ->with('info', 'Kamu adalah pemilik quiz ini. Bagikan kodenya ke peserta.');
        }

        $baru = SesiKode::gabung($sesi->sesi, $pengguna)->baruDibuat;

        return redirect()
            ->route('user.sesi.lobby', $sesi->sesi)
            ->with('sukses', $baru
                ? 'Berhasil bergabung. Tunggu pemilik quiz memulai.'
                : 'Kamu sudah bergabung ke quiz ini.');
    }

    /**
     * Kembali ke form dengan pesan kesalahan. Kode yang diketik tetap terisi
     * supaya tidak perlu diketik ulang.
     */
    private function gagal(string $kode, string $pesan): RedirectResponse
    {
        return back()
            ->withInput(['kode' => $kode])
            ->withErrors(['kode' => $pesan]);
    }

    /**
     * Sesi milik pengguna yang sedang login untuk quiz dengan kode tersebut,
     * kalau ada.
     *
     * Dipakai supaya pemilik quiz yang mengetik kodenya sendiri langsung
     * sampai ke lobby, bukan ke pesan "kode tidak ditemukan".
     */
    private function sesiMilikPengguna(Request $request, string $kode): ?SesiQuiz
    {
        $pengguna = $request->user();

        if ($pengguna === null) {
            return null;
        }

        $quiz = Quiz::query()->kodeAkses($kode)->first();

        if ($quiz === null || ! $quiz->dimilikiOleh($pengguna->getKey())) {
            return null;
        }

        return SesiKode::bukaAtauBuat($quiz)->sesi;
    }
}
