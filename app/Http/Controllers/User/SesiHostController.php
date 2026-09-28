<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Support\KodeSesi;
use App\Support\PenjagaSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aksi host: membuka sesi baru, memulai quiz, dan mengakhiri quiz.
 *
 * Hanya pemilik quiz yang boleh membuka sesi, dan hanya host yang boleh
 * memulai atau mengakhiri sesi. Peserta tidak punya akses ke controller ini
 * sama sekali; syaratnya dijaga PenjagaSesi.
 */
class SesiHostController extends Controller
{
    /**
     * Buka sesi baru untuk sebuah quiz, lalu langsung masuk ke lobby.
     *
     * Sesi yang dibuat selalu berstatus "waiting": host baru bisa melihat
     * daftar peserta, dan belum ada soal yang boleh dibuka siapa pun sampai
     * tombol "Mulai Quiz" ditekan.
     */
    public function buka(Request $request, Quiz $quiz): RedirectResponse
    {
        $pengguna = $request->user();

        abort_unless($quiz->dimilikiOleh($pengguna?->getKey()), 403);

        // Quiz tanpa soal tidak bisa dijalankan, jadi sesi tidak dibuat.
        if ($quiz->soal()->aktif()->count() === 0) {
            return back()->withErrors([
                'sesi' => 'Quiz ini belum punya soal, jadi belum bisa dimulai.',
            ]);
        }

        // Jangan buat sesi kedua untuk quiz yang sedang berjalan.
        $sesiAktif = SesiQuiz::query()
            ->where('quiz_id', $quiz->getKey())
            ->milik($pengguna->getKey())
            ->belumSelesai()
            ->latest('id')
            ->first();

        if ($sesiAktif !== null) {
            return redirect()->route('user.sesi.lobby', $sesiAktif);
        }

        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $pengguna->getKey(),
            'kode' => KodeSesi::baru(),
            'status' => SesiQuiz::STATUS_MENUNGGU,
        ]);

        return redirect()
            ->route('user.sesi.lobby', $sesi)
            ->with('sukses', 'Sesi dibuat. Bagikan kode ini kepada peserta.');
    }

    /**
     * Mulai quiz: status sesi waiting -> started.
     *
     * Setelah baris ini berubah, semua peserta yang sedang di lobby
     * terdeteksi oleh polling di resources/js/quiz-lobby.js dan langsung
     * diarahkan ke soal pertama tanpa perlu me-refresh halaman.
     *
     * Tombol ini sengaja dibuat idempoten: sesi yang sudah started atau
     * sudah selesai tidak diubah, jadi menekan dua kali atau membuka URL
     * ini lewat refresh tidak ikut menutup quiz.
     */
    public function mulai(Request $request, SesiQuiz $sesi): RedirectResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanHost($sesi, $pengguna);

        if ($sesi->sudahSelesai()) {
            return redirect()
                ->route('user.sesi.hasil', $sesi)
                ->with('info', 'Quiz ini sudah selesai.');
        }

        if (! $sesi->mulai()) {
            return redirect()
                ->route('user.sesi.lobby', $sesi)
                ->with('info', 'Quiz ini sedang berjalan.');
        }

        return redirect()->route('user.sesi.lobby', $sesi);
    }

    /**
     * Akhiri quiz: sesi yang sedang berjalan -> finished.
     *
     * Semua peserta yang masih mengerjakan soal langsung diarahkan ke
     * halaman hasil.
     */
    public function akhiri(Request $request, SesiQuiz $sesi): RedirectResponse
    {
        PenjagaSesi::pastikanHost($sesi, $request->user());

        $sesi->tutup();

        return redirect()
            ->route('user.sesi.hasil', $sesi)
            ->with('sukses', 'Quiz ditutup. Peserta diarahkan ke halaman hasil.');
    }
}
