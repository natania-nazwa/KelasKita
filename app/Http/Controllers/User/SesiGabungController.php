<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\GabungSesiRequest;
use App\Models\PesertaQuiz;
use App\Models\SesiQuiz;
use App\Models\User;
use App\Support\PenjagaSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu "Masukkan Kode" di dashboard: form untuk masuk ke lobby sesi quiz
 * yang sedang dibuka orang lain.
 *
 * Aturan alurnya:
 *   - Kode dicari pada SESI yang sedang dibuka, bukan pada quiz. Satu quiz
 *     bisa punya beberapa sesi, dan tiap sesi punya kodenya sendiri.
 *   - Peserta yang belum bergabung DITARUH ke lobby, bukan langsung ke soal.
 *     Baru setelah host menekan "Mulai Quiz" mereka boleh membuka soal.
 *   - Kalau sudah pernah bergabung, barisnya tidak dibuat lagi; pengguna
 *     diberi tahu lalu langsung dikembalikan ke lobby yang sama.
 */
class SesiGabungController extends Controller
{
    /**
     * Halaman form "Gabung Quiz".
     *
     * Kalau pengguna sedang jadi host dari sesi yang masih terbuka, halaman
     * ini tidak menampilkan form, melainkan langsung menawarinya masuk kembali
     * ke lobby miliknya sendiri supaya tidak perlu mengetik kode.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $sesiSendiri = $this->sesiTerbukaMilik($request->user());

        if ($sesiSendiri !== null) {
            return redirect()
                ->route('user.sesi.lobby', $sesiSendiri)
                ->with('info', 'Kamu sedang menjadi host dari sesi ini. Bagikan kodenya ke teman.');
        }

        return view('user.quiz-gabung', [
            'kode' => SesiQuiz::normalisasiKode($request->string('kode')->toString()),
        ]);
    }

    /**
     * Memproses kode yang diketik peserta.
     */
    public function store(GabungSesiRequest $request): RedirectResponse
    {
        $kode = $request->kode();

        $sesi = SesiQuiz::query()->kode($kode)->first();

        if ($sesi === null) {
            return back()
                ->withInput(['kode' => $kode])
                ->withErrors(['kode' => 'Kode quiz tidak ditemukan.']);
        }

        if ($sesi->sudahSelesai()) {
            return back()
                ->withInput(['kode' => $kode])
                ->withErrors(['kode' => 'Quiz ini sudah selesai.']);
        }

        $pengguna = $request->user();

        // Host tidak perlu jadi peserta sesinya sendiri.
        if ($sesi->adalahHost($pengguna)) {
            return redirect()
                ->route('user.sesi.lobby', $sesi)
                ->with('info', 'Kamu adalah host dari quiz ini.');
        }

        if (PenjagaSesi::sudahIkut($sesi, $pengguna)) {
            return redirect()
                ->route('user.sesi.lobby', $sesi)
                ->with('info', 'Kamu sudah bergabung ke quiz ini.');
        }

        $sesi->peserta()->create([
            'pengguna_id' => $pengguna->getKey(),
            'status' => PesertaQuiz::STATUS_LOBBY,
            'bergabung_pada' => now(),
        ]);

        return redirect()
            ->route('user.sesi.lobby', $sesi)
            ->with('sukses', 'Berhasil bergabung. Tunggu host memulai quiz.');
    }

    /**
     * Sesi milik pengguna yang sedang login yang belum ditutup, kalau ada.
     */
    private function sesiTerbukaMilik(?User $pengguna): ?SesiQuiz
    {
        if ($pengguna === null) {
            return null;
        }

        return SesiQuiz::query()
            ->milik($pengguna->getKey())
            ->belumSelesai()
            ->latest('id')
            ->first();
    }
}
