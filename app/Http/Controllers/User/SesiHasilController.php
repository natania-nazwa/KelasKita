<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PengerjaanQuiz;
use App\Models\SesiQuiz;
use App\Support\DaftarPeringkat;
use App\Support\PenjagaSesi;
use App\Support\SesiAktif;
use App\Support\TujuanHasil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman hasil sesi quiz.
 *
 * Satu halaman, dua hal yang bisa sama-sama muncul karena "peran" di sini
 * bukan cuma satu:
 *
 *   - NILAI SENDIRI. Muncul kalau pengguna punya pengerjaan di sesi ini,
 *     apa pun perannya. Ini yang menggantikan blok kosong di alur
 *     "publikan": setelah selesai, yang dibuka adalah angkanya sendiri, bukan
 *     tabel nilai orang lain.
 *   - REKAP PESERTA. Muncul hanya untuk host sesi mode kode, karena itu satu-
 *    -satunya tempat yang perlu melihat nilai semua orang. Sesi mode kode
 *     dibuat untuk peserta yang masuk lewat kode, jadi host tidak pernah ada
 *     di daftar itu (dan memang tidak boleh mengerjakan soal, lihat
 *     SesiKerjakanController::larangan()).
 *
 * Kenapa dulu tidak begini: blok mana yang tampil ditentukan satu flag
 * "adalah host", sehingga pengguna yang mengerjakan quiznya sendiri sebagai
 * host sesi solo tidak pernah sampai ke blok nilainya sendiri. Angkanya ada
 * di database tapi halamannya menampilkan "0 peserta".
 *
 * Halaman ini tetap bisa dibuka saat quiz masih berjalan, supaya host bisa
 * memantau nilai yang sudah masuk.
 *
 * Punya nilai sendiri = dialihkan ke kartu hasil, bukan dirender di sini.
 * Kartu itulah tampilan resmi "hasil quiz" sekarang, dan menyajikannya di
 * dua tempat sekaligus berarti perbaikan desainnya hanya berlaku di salah
 * satu. Aturan/Mainnya ada di App\Support\TujuanHasil, sama dengan yang
 * dipakai redirect setelah menjawab, jadi keduanya tidak bisa berbeda.
 * Yang tetap dirender di sini hanya kasus yang benar-benar tidak punya
 * kartu: rekap peserta milik host, dan "kamu belum mengerjakan soal".
 *
 * Daftar nilainya sendiri tidak dihitung di controller ini, tapi di
 * App\Support\DaftarPeringkat, kelas yang sama dengan halaman peringkat
 * /user/sesi/{sesi}/peringkat. Dua halaman itu menampilkan urutan yang sama,
 * jadi tidak mungkin host melihat urutan berbeda dari yang dilihat peserta.
 */
class SesiHasilController extends Controller
{
    public function __invoke(Request $request, SesiQuiz $sesi): View|RedirectResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $sesi->loadMissing(['quiz', 'host']);

        $tujuan = TujuanHasil::untuk($sesi, $pengguna);

        if ($tujuan !== route('user.sesi.hasil', $sesi)) {
            return redirect()->to($tujuan);
        }

        $pengerjaanSaya = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->where('pengguna_id', $pengguna->getKey())
            ->first();

        $tampilNilai = $pengerjaanSaya !== null;

        /*
         * Rekap hanya untuk host sesi mode kode. Host sesi solo juga punya
         * host_id yang sama, jadi tanpa syarat "quiz mode kode" ia akan
         * melihat tabel kosong: sesi solo tidak pernah punya baris peserta.
         */
        $tampilRekap = $sesi->adalahHost($pengguna) && $sesi->quiz->pakaiKode();

        /*
         * Tombol "Kembali Mengerjakan" menuju halaman soal yang tidak
         * menyebut id sesi di URL-nya, jadi sesi ikut dicatat di session.
         * Tautan di halaman hasil tetap membawa id-nya sebagai pengaman,
         * sehingga baris ini hanya menutup jalan ketika tautan itu dibuka
         * tanpa parameter.
         */
        if (! $pengerjaanSaya?->sudahSelesai() && ! $sesi->sudahSelesai()) {
            SesiAktif::pakai($sesi);
        }

        return view('user.quiz-hasil', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'tampilNilai' => $tampilNilai,
            'tampilRekap' => $tampilRekap,
            'pengerjaan' => $pengerjaanSaya,
            'jumlahPeserta' => $sesi->peserta()->count(),
            'daftarNilai' => $tampilRekap ? DaftarPeringkat::untukSesi($sesi) : [],
        ]);
    }
}
