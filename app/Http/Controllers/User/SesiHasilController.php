<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PengerjaanQuiz;
use App\Models\SesiQuiz;
use App\Support\DaftarPeserta;
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
            'daftarNilai' => $tampilRekap ? $this->daftarNilai($sesi) : [],
        ]);
    }

    /**
     * Rekap nilai seluruh peserta sesi ini, dari yang tertinggi.
     *
     * Peserta yang belum menjawab sama sekali tetap ikut tampil dengan nilai
     * nol, supaya host tidak mengira mereka hilang.
     *
     * @return array<int, array<string, mixed>>
     */
    private function daftarNilai(SesiQuiz $sesi): array
    {
        $pengerjaan = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->get()
            ->keyBy('pengguna_id');

        $hasil = [];

        foreach ($sesi->peserta()->with('pengguna')->orderBy('bergabung_pada')->orderBy('id')->get() as $baris) {
            $daftarPeserta = DaftarPeserta::petakan([$baris])[0];
            $nilai = $pengerjaan[$baris->pengguna_id] ?? null;

            $hasil[] = [
                ...$daftarPeserta,
                'peringkat' => 0,
                'nilai' => (int) ($nilai?->nilai ?? 0),
                'benar' => (int) ($nilai?->jumlah_benar ?? 0),
                'salah' => (int) ($nilai?->jumlah_salah ?? 0),
                'dijawab' => (int) ($nilai?->jumlah_dijawab ?? 0),
                'jumlah_soal' => (int) ($nilai?->jumlah_soal ?? $sesi->quiz->soal()->aktif()->count()),
                'sudah_selesai' => $nilai?->sudahSelesai() ?? false,
            ];
        }

        // Peringkat diberikan setelah semua nilai terkumpul, jadi dua orang
        // dengan nilai sama sama-sama mendapat peringkat yang sama.
        usort($hasil, fn (array $a, array $b) => [$b['nilai'], $a['nama']] <=> [$a['nilai'], $b['nama']]);

        $peringkat = 0;
        $sebelumnya = null;

        foreach ($hasil as $index => $baris) {
            if ($baris['nilai'] !== $sebelumnya) {
                $peringkat = $index + 1;
                $sebelumnya = $baris['nilai'];
            }

            $hasil[$index]['peringkat'] = $peringkat;
        }

        return $hasil;
    }
}
