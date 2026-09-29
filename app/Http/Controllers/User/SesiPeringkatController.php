<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SesiQuiz;
use App\Support\DaftarPeringkat;
use App\Support\PenjagaSesi;
use App\Support\TujuanHasil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman peringkat sesi: /user/sesi/{sesi}/peringkat.
 *
 * Satu-satunya halaman yang menunjukkan siapa saja yang masuk lewat kode,
 * urut dari nilai tertinggi. Yang dibuka lewat tombol "Lihat Peringkat" di
 * kartu hasil quiz mode kode, dan juga bisa dibuka langsung oleh host dari
 * halaman hasil sesinya.
 *
 * Bedanya dengan /user/sesi/{sesi}/hasil: halaman itu milik host (rekap nilai
 * beserta tombol "Akhiri Quiz"), sedangkan yang ini adalah tampilan peringkat
 * yang dilihat peserta. Keduanya membaca App\Support\DaftarPeringkat, jadi
 * urutan yang dilihat keduanya tidak mungkin berbeda.
 *
 * Aturannya:
 *   - Hanya host dan peserta sesi ini yang boleh masuk (PenjagaSesi), sama
 *     seperti lobby dan halaman hasil. Orang lain yang menebak URL tetap 403.
 *   - Hanya sesi mode KODE yang punya daftar peserta. Sesi solo dibuat dari
 *     tombol "Mulai Quiz" dan tidak pernah punya baris peserta, jadi
 *     halamannya tidak akan pernah dibuka lewat tautan mana pun.
 *   - Halaman ini boleh dibuka saat quiz masih berjalan. Nilainya dibaca dari
 *     tb_pengerjaan_quiz apa adanya, jadi yang belum menjawab tampil dengan
 *     nilai 0 dan ditandai "Belum menjawab", bukan disembunyikan.
 */
class SesiPeringkatController extends Controller
{
    public function __invoke(Request $request, SesiQuiz $sesi): View|RedirectResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $sesi->loadMissing(['quiz.pelajaran', 'host']);

        /*
         * Sesi solo tidak punya peserta, jadi halaman ini tidak punya isi apa
         * pun untuknya. Diarahkan ke tujuan hasil yang biasa supaya tidak
         * ada halaman kosong yang muncul dari URL yang diketik manual.
         */
        if (! $sesi->quiz->pakaiKode()) {
            return redirect()->to(TujuanHasil::untuk($sesi, $pengguna));
        }

        $daftar = DaftarPeringkat::untukSesi($sesi);

        /*
         * Podium butuh minimal tiga orang. Dengan dua orang atau satu orang,
         * podiumnya cuma jadi satu petak bertulis "1" yang tidak menambah
         * informasi apa pun, dan daftar polos di bawahnya sudah menampilkan
         * semuanya dengan lebih rapi. Jadi di bawah tiga orang, seluruh
         * daftar langsung jadi daftar biasa.
         */
        $adaPodium = count($daftar) >= 3;

        return view('user.quiz-peringkat', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'daftar' => $daftar,
            'jumlahPeserta' => count($daftar),
            'podium' => $adaPodium ? DaftarPeringkat::podium($daftar) : [],
            'sisanya' => $adaPodium ? array_slice($daftar, 3) : $daftar,
            'saya' => DaftarPeringkat::barisSaya($daftar, $pengguna->getKey()),
            'adalahHost' => $sesi->adalahHost($pengguna),
        ]);
    }
}
