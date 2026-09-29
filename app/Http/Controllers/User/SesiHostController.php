<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Support\PenjagaSesi;
use App\Support\SesiKode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aksi host: membuka sesi baru, memulai quiz, dan mengakhiri quiz.
 *
 * Hanya pemilik quiz yang boleh membuka sesi, dan hanya host yang boleh
 * memulai atau mengakhiri sesi. Peserta tidak punya akses ke controller ini
 * sama sekali; syaratnya dijaga PenjagaSesi.
 *
 * Host sebuah sesi kode selalu pemilik quiz-nya (lihat App\Support\SesiKode),
 * jadi siapa pun boleh mengetik kode lebih dulu tanpa jadi host: sesi yang
 * dibuka tetap milik orang yang membuat quiz.
 */
class SesiHostController extends Controller
{
    /**
     * Buka sesi lobby untuk quiz mode kode milik pengguna ini.
     *
     * Sesi yang dibuat selalu berstatus "waiting": host baru bisa melihat
     * daftar peserta, dan belum ada soal yang boleh dibuka siapa pun sampai
     * tombol "Mulai Quiz" ditekan. Begitu host menekan tombol itu, semua
     * peserta yang sedang di lobby otomatis masuk ke soal pertama.
     *
     * Sesi dibuat dengan kode yang sama dengan kode akses quiz, jadi angka
     * yang dilihat host di lobby persis sama dengan yang diketik peserta.
     * Kalau sesi untuk quiz ini masih hidup, host diarahkan ke sana, bukan
     * diberi sesi kedua: dua lobby untuk satu quiz akan memecah peserta.
     */
    public function buka(Request $request, Quiz $quiz): RedirectResponse
    {
        $pengguna = $request->user();

        abort_unless($quiz->dimilikiOleh($pengguna?->getKey()), 403);

        /*
         * Hanya quiz mode kode yang punya lobby. Quiz mode publik tidak
         * punya kode untuk diketik peserta, jadi sesi yang dibuat tidak akan
         * pernah diisi siapa pun; QuizMulaiController yang menangani mode itu.
         */
        if (! $quiz->pakaiKode() || blank($quiz->kode_akses)) {
            return back()->withErrors([
                'sesi' => 'Quiz ini dipublikasikan untuk semua pengguna, jadi tidak ada kode untuk diikuti. Ubah cara publikasinya jadi "Gunakan Kode" dulu kalau mau menguji bersama-sama.',
            ]);
        }

        // Quiz tanpa soal tidak bisa dijalankan, jadi sesi tidak dibuat.
        if ($quiz->soal()->aktif()->count() === 0) {
            return back()->withErrors([
                'sesi' => 'Quiz ini belum punya soal, jadi belum bisa dimulai.',
            ]);
        }

        $sesi = SesiKode::bukaAtauBuat($quiz);

        if (! $sesi->baruDibuat) {
            return redirect()->route('user.sesi.lobby', $sesi->sesi);
        }

        return redirect()
            ->route('user.sesi.lobby', $sesi->sesi)
            ->with('sukses', 'Sesi dibuat. Bagikan kode '.$sesi->sesi->kode.' kepada peserta.');
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
