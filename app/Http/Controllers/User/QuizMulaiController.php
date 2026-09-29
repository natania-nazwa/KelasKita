<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Support\KodeSesi;
use App\Support\SesiAktif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tombol "Mulai Quiz" di halaman detail quiz.
 *
 * Alurnya sengaja memakai sesi yang sudah ada, bukan membuat halaman
 * mengerjakan quiz yang baru: sesi dibuat dengan host = pengguna yang sedang
 * login lalu langsung berstatus "started", jadi pengguna langsung masuk ke soal
 * pertama tanpa lewat lobby. Semua aturan mengerjakan soal (harus host atau
 * peserta, sesi harus started) tetap ditegakkan SesiKerjakanController, bukan
 * di sini.
 *
 * Quiz yang belum terbit hanya boleh dibuka pembuatnya, sama seperti halaman
 * detail. Quiz tanpa soal aktif tidak bisa dijalankan sama sekali, jadi
 * pengguna dikembalikan ke halaman detail dengan pesan yang jelas.
 */
class QuizMulaiController extends Controller
{
    public function __invoke(Request $request, Quiz $quiz): RedirectResponse
    {
        $pengguna = $request->user();

        abort_unless(
            $quiz->status === Quiz::STATUS_PUBLISHED || $quiz->dimilikiOleh($pengguna?->getKey()),
            404
        );

        if ($quiz->soal()->aktif()->count() === 0) {
            return back()->withErrors(['quiz' => 'Quiz ini belum memiliki soal.']);
        }

        $sesi = $this->sesiTerbuka($quiz, $pengguna?->getKey());

        /*
         * Halaman mengerjakan soal tidak menyebut id sesi di URL-nya
         * (/user/quiz/{quiz}/soal/{nomor}), jadi sesi ini dicatat di session
         * pengguna sebelum dialihkan. Tanpa itu, permintaan pertama tidak
         * akan tahu sesi mana yang harus dibuka.
         */
        if ($sesi !== null) {
            SesiAktif::pakai($sesi);

            // Sesi yang belum dimulai milik host yang sedang mengumpulkan
            // peserta, jadi jangan langsung masuk ke soal.
            return $sesi->sudahDimulai()
                ? redirect()->route('user.judulsoal.soal', [$quiz->slug, 1])
                : redirect()->route('user.sesi.lobby', $sesi);
        }

        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $pengguna->getKey(),
            'kode' => KodeSesi::baru(),
            'status' => SesiQuiz::STATUS_MENUNGGU,
        ]);

        $sesi->mulai();

        SesiAktif::pakai($sesi);

        return redirect()->route('user.judulsoal.soal', [$quiz->slug, 1]);
    }

    /**
     * Sesi milik pengguna ini untuk quiz ini yang belum ditutup, kalau ada.
     *
     * Sesi yang pengerjaannya sudah tuntas ikut ditutup dulu di sini, supaya
     * menekan "Mulai Quiz" lagi benar-benar membuka latihan baru, bukan
     * mengulang soal-soal yang sudah dijawab.
     *
     * Percobaan yang WAKTUNYA sudah habis juga ikut dianggap tuntas, walau
     * peserta tidak pernah menekan tombolnya di dialog "Waktu Anda Habis".
     * Kalau tidak, sesi lamanya tetap berstatus started lalu dipakai ulang:
     * pengguna mendarat di soal pertama dengan sisa waktu 0, jadi dialognya
     * muncul lagi di halaman yang seharusnya baru sama sekali.
     *
     * Sesi mode kode tidak lewat sini sama sekali, jadi peserta yang masuk
     * lewat kode tetap melihat dialog di sesi yang memang sedang berjalan
     * untuk semua orang.
     */
    private function sesiTerbuka(Quiz $quiz, ?int $idPengguna): ?SesiQuiz
    {
        if ($idPengguna === null) {
            return null;
        }

        $sesi = SesiQuiz::query()
            ->where('quiz_id', $quiz->getKey())
            ->milik($idPengguna)
            ->belumSelesai()
            ->latest('id')
            ->first();

        if ($sesi === null) {
            return null;
        }

        $pengerjaan = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->where('pengguna_id', $idPengguna)
            ->latest('id')
            ->first();

        // Sesi sudah jalan tapi belum ada jawaban sama sekali. Kalau
        // waktunya masih ada, ini lanjutan yang sah; kalau sudah lewat,
        // sesi ini tidak bisa dilanjutkan dan harus ditinggalkan.
        if ($pengerjaan === null) {
            if ($this->waktuSesiHabis($quiz, $sesi)) {
                $sesi->tutup();

                return null;
            }

            return $sesi;
        }

        if ($pengerjaan->sudahSelesai()) {
            $sesi->tutup();

            return null;
        }

        if ($pengerjaan->waktuSudahHabis($quiz)) {
            /*
             * Pengerjaannya ikut ditutup, bukan cuma sesinya. Kalau tidak,
             * baris "Belum Selesai" di menu Hasil akan tetap menggantung
             * walaupun sesinya sudah ditinggalkan.
             */
            $pengerjaan->hitungUlang();
            $pengerjaan->forceFill(['selesai_pada' => now()])->save();

            $sesi->tutup();

            return null;
        }

        return $sesi;
    }

    /**
     * Sesi yang belum punya satu pun pengerjaan tapi sudah berjalan melewati
     * durasi quiz, sudah dianggap lewat waktunya.
     *
     * Hitungannya memakai kolom dimulai_pada sesi, bukan started_pada
     * pengerjaan, karena di keadaan ini belum ada pengerjaan yang bisa dibaca.
     */
    private function waktuSesiHabis(Quiz $quiz, SesiQuiz $sesi): bool
    {
        $durasi = (int) $quiz->durasi * 60;

        if ($durasi <= 0 || $sesi->dimulai_pada === null) {
            return false;
        }

        return $durasi <= time() - $sesi->dimulai_pada->getTimestamp();
    }
}
