<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pelajaran;
use App\Models\SesiQuiz;
use App\Support\DaftarPeserta;
use App\Support\PenjagaSesi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman lobby sesi quiz: ruang tunggu sebelum quiz dimulai.
 *
 * Isinya berbeda tipis antara host dan peserta:
 *   - Host melihat kode, jumlah peserta, daftar nama, dan tombol
 *     "Mulai Quiz". Host tidak ikut masuk sebagai peserta.
 *   - Peserta melihat kode, jumlah peserta, daftar nama, lalu status
 *     "Menunggu host memulai". Peserta tidak pernah melihat tombol mulai.
 *
 * Peserta yang sesinya sudah "started" langsung dialihkan ke soal pertama dari
 * controller ini, bukan dari JavaScript, jadi membuka URL soal pun tidak
 * bisa melewati host.
 */
class SesiLobbyController extends Controller
{
    /**
     * Halaman lobby.
     */
    public function __invoke(Request $request, SesiQuiz $sesi): View|RedirectResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $sesi->loadMissing(['quiz.pelajaran', 'host']);

        // Quiz sudah ditutup: semua orang, termasuk host, pindah ke hasil.
        if ($sesi->sudahSelesai()) {
            return redirect()->route('user.sesi.hasil', $sesi);
        }

        /*
         * Aturan utama fitur ini: selama status masih "waiting" peserta
         * hanya boleh melihat lobby. Begitu host memulai, peserta langsung
         * masuk ke soal pertama tanpa harus menekan apa pun.
         */
        if ($sesi->sudahDimulai() && ! $sesi->adalahHost($pengguna)) {
            return redirect()->route('user.sesi.soal', [$sesi, 1]);
        }

        $jumlahSoal = $sesi->quiz->soal()->aktif()->count();

        return view('user.quiz-lobby', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'host' => $sesi->host,
            'kategori' => Pelajaran::warna(
                $sesi->quiz->pelajaran?->slug ?? '',
                $sesi->quiz->pelajaran?->nama ?? 'Umum',
            ),
            'jumlahSoal' => $jumlahSoal,
            'jumlahPeserta' => $sesi->peserta()->count(),
            'peserta' => $this->daftarPeserta($sesi),
            'adalahHost' => $sesi->adalahHost($pengguna),
        ]);
    }

    /**
     * Data lobby untuk polling JavaScript.
     *
     * Dipanggil setiap beberapa detik oleh resources/js/quiz-lobby.js.
     * Datanya sengaja dibuat kecil (hanya status, jumlah, dan nama peserta)
     * supaya yang lewat jaringan tetap ringan, dan perubahannya hanya
     * terjadi di layar yang sedang dibuka.
     */
    public function data(Request $request, SesiQuiz $sesi): JsonResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $adalahHost = $sesi->adalahHost($pengguna);

        return response()->json([
            'status' => $sesi->status,
            'status_label' => $sesi->labelStatus(),
            'adalah_host' => $adalahHost,
            'jumlah_peserta' => $sesi->peserta()->count(),
            'peserta' => $this->daftarPeserta($sesi),
            'tautan_soal' => route('user.sesi.soal', [$sesi, 1]),
            'tautan_hasil' => route('user.sesi.hasil', $sesi),
        ]);
    }

    /**
     * Daftar peserta siap pakai untuk komponen, diurutkan dari yang paling
     * dulu bergabung.
     *
     * @return array<int, array<string, mixed>>
     */
    private function daftarPeserta(SesiQuiz $sesi): array
    {
        return DaftarPeserta::petakan(
            $sesi->peserta()->with('pengguna')->orderBy('bergabung_pada')->orderBy('id')->get()
        );
    }
}
