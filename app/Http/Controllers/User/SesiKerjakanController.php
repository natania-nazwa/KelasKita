<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JawabanQuiz;
use App\Models\PengerjaanQuiz;
use App\Models\PesertaQuiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\User;
use App\Support\PenjagaSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Mengerjakan soal di dalam sesi quiz yang sudah dimulai.
 *
 * Semua aturan "soal tidak boleh dibuka sebelum host memulai" ditegakkan di
 * sini, di server:
 *   - hanya host dan peserta sesi ini yang boleh menjawab, selain itu 403;
 *   - status sesi harus "started"; kalau belum, dikembalikan ke lobby;
 *   - kalau sesi sudah "finished", dikembalikan ke halaman hasil.
 *
 * Karena dicek di server, membuka URL soal secara langsung tidak bisa
 * melewati host.
 *
 * Jawaban disimpan di tb_jawaban_quiz dan rekapnya dihitung ulang di
 * PengerjaanQuiz, jadi mengubah jawaban tidak membuat nilai dobel.
 */
class SesiKerjakanController extends Controller
{
    /**
     * Halaman satu soal.
     */
    public function show(Request $request, SesiQuiz $sesi, int $nomor): View|RedirectResponse
    {
        $pengguna = $request->user();

        if ($larangan = $this->larangan($sesi, $pengguna)) {
            return $larangan;
        }

        $sesi->loadMissing('quiz');

        $daftar = $sesi->quiz->soal()->aktif()->terurut()->get();

        $posisi = $this->posisi($daftar, $nomor);

        abort_if($posisi === null, 404);

        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        $this->tandaiMengerjakan($sesi, $pengguna);

        return view('user.quiz-kerjakan', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'soal' => $daftar[$posisi],
            'nomor' => $posisi + 1,
            'jumlahSoal' => $daftar->count(),
            'jawaban' => JawabanQuiz::query()
                ->where('pengerjaan_quiz_id', $pengerjaan->getKey())
                ->where('soal_id', $daftar[$posisi]->getKey())
                ->first(),
            'jumlahDijawab' => $pengerjaan->jumlah_dijawab,
            'adalahHost' => $sesi->adalahHost($pengguna),
        ]);
    }

    /**
     * Simpan jawaban satu soal, lalu pindah ke soal berikutnya.
     *
     * Menjawab soal terakhir otomatis berarti selesai, jadi tidak ada tombol
     * "Selesai" yang terpisah di ujung.
     */
    public function simpan(Request $request, SesiQuiz $sesi, int $nomor): RedirectResponse
    {
        $pengguna = $request->user();

        if ($larangan = $this->larangan($sesi, $pengguna)) {
            return $larangan;
        }

        $sesi->loadMissing('quiz');

        $data = $request->validate([
            'jawaban' => ['required', 'in:A,B,C,D'],
        ], [
            'jawaban.required' => 'Pilih salah satu jawaban sebelum melanjutkan.',
            'jawaban.in' => 'Jawaban harus berupa A, B, C, atau D.',
        ]);

        $daftar = $sesi->quiz->soal()->aktif()->terurut()->get();

        $posisi = $this->posisi($daftar, $nomor);

        abort_if($posisi === null, 404);

        $soal = $daftar[$posisi];
        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        JawabanQuiz::query()->updateOrCreate(
            [
                'pengerjaan_quiz_id' => $pengerjaan->getKey(),
                'soal_id' => $soal->getKey(),
            ],
            [
                'jawaban_dipilih' => $data['jawaban'],
                'jawaban_benar' => $soal->jawaban_benar,
                'benar' => $data['jawaban'] === $soal->jawaban_benar,
                'dijawab_pada' => now(),
            ],
        );

        $pengerjaan->hitungUlang();

        // Menyimpan jawaban berarti peserta sedang mengerjakan, apa pun jalan
        // masuknya. Penandaan ini tidak harus bergantung pada halaman soal
        // yang sempat dibuka lebih dulu.
        $this->tandaiMengerjakan($sesi, $pengguna);

        /*
         * Menjawab soal terakhir berarti selesai. Form dikirim lewat POST,
         * sedangkan halaman hasil dibaca lewat GET, jadi di sini pengerjaan
         * ditutup langsung dan pengguna dikirim ke halaman hasil. Kalau
         * objek Laravel ikut ikut diarahkan ke user.sesi.selesai yang
         * hanya menerima POST, hasilnya 405.
         */
        if ($posisi + 1 < $daftar->count()) {
            return redirect()->route('user.sesi.soal', [$sesi, $posisi + 2]);
        }

        $this->tutupPengerjaan($sesi, $pengguna);

        return redirect()->route('user.sesi.hasil', $sesi);
    }

    /**
     * Tutup pengerjaan dan tampilkan hasil.
     *
     * Tombol "Selesai" ada di semua soal supaya peserta bisa berhenti di
     * tengah, bukan hanya setelah menjawab yang terakhir.
     */
    public function selesai(Request $request, SesiQuiz $sesi): RedirectResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $this->tutupPengerjaan($sesi, $pengguna);

        return redirect()->route('user.sesi.hasil', $sesi);
    }

    /**
     * Tandai pengerjaan pengguna sebagai selesai dan perbarui status
     * pesertanya di daftar lobby.
     *
     * Dipakai oleh dua pemanggil: tombol "Selesai" di tiap soal, dan
     * jawaban soal terakhir yang otomatis berarti selesai. Kalau sesi
     * sudah ditutup host, atau pengguna belum pernah membuka soal, maka
     * pengerjaannya tidak dibuat di sini.
     */
    private function tutupPengerjaan(SesiQuiz $sesi, User $pengguna): void
    {
        if (! $sesi->sudahDimulai() || $sesi->sudahSelesai()) {
            return;
        }

        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        if (! $pengerjaan->sudahSelesai()) {
            $pengerjaan->hitungUlang();
            $pengerjaan->selesai_pada = now();
            $pengerjaan->save();
        }

        $sesi->peserta()
            ->where('pengguna_id', $pengguna->getKey())
            ->update(['status' => PesertaQuiz::STATUS_SELESAI]);
    }

    /**
     * Tujuan redirect kalau pengguna belum boleh mengerjakan soal, atau null
     * kalau boleh.
     */
    private function larangan(SesiQuiz $sesi, User $pengguna): ?RedirectResponse
    {
        // Bukan host dan bukan peserta sesi ini: 403.
        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        // Quiz sudah ditutup: semua orang pindah ke halaman hasil.
        if ($sesi->sudahSelesai()) {
            return redirect()->route('user.sesi.hasil', $sesi);
        }

        // Aturan utama: soal hanya boleh dibuka setelah host memulai.
        if (! $sesi->sudahDimulai()) {
            return redirect()->route('user.sesi.lobby', $sesi);
        }

        return null;
    }

    /**
     * Posisi soal pada daftar (basis nol).
     *
     * Nomor di luar rentang dijepit ke soal terdekat, jadi /soal/0 dan
     * /soal/999 masih menampilkan soal yang paling mendekati, bukan error.
     *
     * @param  Collection<int, Soal>  $daftar
     */
    private function posisi(Collection $daftar, int $nomor): ?int
    {
        if ($daftar->isEmpty()) {
            return null;
        }

        return min(max($nomor, 1), $daftar->count()) - 1;
    }

    /**
     * Pengerjaan milik pengguna di sesi ini. Dibuat otomatis saat pertama
     * kali soal dibuka, lalu dipakai ulang setiap kali menjawab.
     */
    private function pengerjaan(SesiQuiz $sesi, User $pengguna): PengerjaanQuiz
    {
        $pengerjaan = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->where('pengguna_id', $pengguna->getKey())
            ->first();

        if ($pengerjaan !== null) {
            return $pengerjaan;
        }

        return PengerjaanQuiz::create([
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $sesi->quiz_id,
            'jumlah_soal' => $sesi->quiz->soal()->aktif()->count(),
            'dimulai_pada' => now(),
        ]);
    }

    /**
     * Tandai peserta sedang mengerjakan, supaya status di lobby tidak lagi
     * menampilkan "Siap".
     */
    private function tandaiMengerjakan(SesiQuiz $sesi, User $pengguna): void
    {
        $sesi->peserta()
            ->where('pengguna_id', $pengguna->getKey())
            ->where('status', PesertaQuiz::STATUS_LOBBY)
            ->update(['status' => PesertaQuiz::STATUS_MENGERJAKAN]);
    }
}
