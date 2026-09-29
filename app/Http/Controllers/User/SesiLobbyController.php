<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pelajaran;
use App\Models\SesiQuiz;
use App\Support\DaftarPeserta;
use App\Support\PenjagaSesi;
use App\Support\SesiAktif;
use App\Support\TujuanHasil;
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
        // Tujuan diambil dari satu fungsi yang sama dengan redirect setelah
        // menjawab, jadi lobby, soal, dan kartu hasil tidak pernah membuka
        // halaman yang berbeda untuk keadaan yang sama. Host sesi mode kode
        // tetap diarahkan ke halaman hasil sesi karena rekap peserta hanya
        // ada di sana.
        if ($sesi->sudahSelesai()) {
            return redirect()->to(TujuanHasil::untuk($sesi, $pengguna));
        }

        /*
         * Aturan utama fitur ini: selama status masih "waiting" peserta
         * hanya boleh melihat lobby. Begitu host memulai, peserta langsung
         * masuk ke soal pertama tanpa harus menekan apa pun.
         *
         * Sesi ikut dicatat di session karena URL halaman soal tidak
         * menyebut id sesi, dan polling di bawah melakukan hal yang sama
         * saat JavaScript yang memindahkan peserta ke sana.
         */
        if ($sesi->sudahDimulai() && ! $sesi->adalahHost($pengguna)) {
            SesiAktif::pakai($sesi);

            return redirect()->route('user.judulsoal.soal', [$sesi->quiz->slug, 1]);
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

        /*
         * Halaman soal tidak menyebut id sesi di URL-nya, jadi sesi ikut
         * dicatat di session. Polling inilah yang memindahkan peserta ke
         * sana, jadi tanpa baris ini permintaan berikutnya tidak tahu sesi
         * mana yang harus dibuka.
         */
        SesiAktif::pakai($sesi);

        return response()->json([
            'status' => $sesi->status,
            'status_label' => $sesi->labelStatus(),
            'adalah_host' => $adalahHost,
            'jumlah_peserta' => $sesi->peserta()->count(),
            'peserta' => $this->daftarPeserta($sesi),
            'tautan_soal' => route('user.judulsoal.soal', [$sesi->quiz->slug, 1]),
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
