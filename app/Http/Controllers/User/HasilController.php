<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Support\DaftarHasil;
use App\Support\StatistikHasil;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Hasil": rekap seluruh pengerjaan quiz milik pengguna yang sedang
 * login.
 *
 * Berbeda dengan halaman hasil sesi (/user/sesi/{sesi}/hasil) yang cuma
 * menampilkan satu sesi, halaman ini menggabungkan semua quiz yang pernah
 * dikerjakan, lengkap dengan statistik, filter, pencarian, dan pengurutan.
 *
 * Kepemilikan ditegakkan di query (PengerjaanQuiz::scopeMilik), jadi data
 * pengguna lain tidak pernah masuk ke halaman ini, bukan sekadar
 * disembunyikan.
 */
class HasilController extends Controller
{
    /**
     * Halaman daftar hasil.
     *
     * Dipakai dua route: /user/hasil (seluruh riwayat) dan
     * /user/hasil/quiz/{quiz} (riwayat satu quiz saja, dibuka dari kartu
     * "Quiz Terpopuler"). Keduanya menampilkan data yang sama; parameter
     * quiz hanya mempersempit daftar, bukan mengurangi ownership.
     */
    public function __invoke(Request $request, ?Quiz $quiz = null): View
    {
        $pengguna = $request->user();
        $idPengguna = $pengguna?->getKey();
        $idQuiz = $quiz?->getKey();

        $status = $this->statusAktif($request->query('status'));
        $urut = $this->urutAktif($request->query('urut'));
        $kataKunci = trim((string) $request->query('q', ''));

        $ringkasan = StatistikHasil::ringkas($idPengguna);
        $riwayat = DaftarHasil::riwayat($idPengguna, $status, $kataKunci, $urut, $idQuiz);

        return view('user.hasil', [
            'ringkasan' => $ringkasan,
            'riwayat' => $riwayat['baris'],
            'paginasi' => $riwayat['hal'],
            'statusAktif' => $status,
            'urutAktif' => $urut,
            'kataKunci' => $kataKunci,
            'jumlahStatus' => DaftarHasil::jumlahStatus($idPengguna, $kataKunci, $idQuiz),
            // Quiz yang sedang disorot, supaya judul dan tombol kembali
            // menyesuaikan.
            'quiz' => $quiz,
            'daftarUrutan' => DaftarHasil::urutan(),
            // Belum pernah mengerjakan sama sekali. Kartu statistik tetap
            // dirender dengan angka 0, dan empty state muncul di dalam
            // area Riwayat Hasil Quiz.
            'pernahMengerjakan' => ($ringkasan['total_selesai'] + $ringkasan['total_proses']) > 0,
        ]);
    }

    /**
     * Status dari query string, hanya kalau memang salah satu status yang
     * sah. Nilai lain dianggap "semua" supaya URL yang diketik manual tidak
     * membuat halaman kosong tanpa penjelasan.
     */
    private function statusAktif(mixed $nilai): ?string
    {
        $nilai = is_string($nilai) ? $nilai : '';

        return in_array($nilai, DaftarHasil::status(), true) ? $nilai : null;
    }

    /**
     * Urutan dari query string, hanya kalau memang salah satu pilihan yang
     * sah. Nilai lain jatuh ke "terbaru".
     */
    private function urutAktif(mixed $nilai): string
    {
        $nilai = is_string($nilai) ? $nilai : '';

        foreach (DaftarHasil::urutan() as $pilihan) {
            if ($pilihan['nilai'] === $nilai) {
                return $nilai;
            }
        }

        return 'terbaru';
    }
}
