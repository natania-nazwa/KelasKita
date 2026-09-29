<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PengerjaanQuiz;
use App\Support\DaftarHasil;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Hasil Quiz" versi satu kartu besar: nilai akhir, rincian jawaban,
 * waktu pengerjaan, dan detail quiz dalam satu layar.
 *
 * Berdiri sendiri dan tidak menyentuh halaman yang sudah ada. Halaman hasil
 * sesi (/user/sesi/{sesi}/hasil) dan halaman hasil riwayat
 * (/user/hasil/{pengerjaan}) tetap seperti sebelumnya; halaman ini hanya
 * membaca ulang data yang sudah tersimpan di tb_pengerjaan_quiz.
 *
 * Pengerjaan yang ditampilkan selalu milik pengguna yang sedang login
 * (PengerjaanQuiz::scopeMilik ditegakkan di query), jadi mengarang id lewat
 * query string hanya menghasilkan halaman kosong, bukan nilai orang lain.
 */
class UiuxHasilController extends Controller
{
    public function __invoke(Request $request): View
    {
        $pengguna = $request->user();
        $idPengguna = $pengguna?->getKey();

        /*
         * Pengerjaan yang tampil bisa dipilih lewat "?pengerjaan=<id>", dan
         * tanpa parameter itu yang dibuka adalah pengerjaan terbaru. Urutannya
         * sama dengan urutan "terbaru" di halaman Hasil: closed dulu, lalu id
         * sebagai pemutus-seri, supaya dua pengerjaan yang dibuat berdekatan
         * tidak menghasilkan halaman yang berbeda tergantung urutan query.
         */
        $pengerjaan = PengerjaanQuiz::query()
            ->milik($idPengguna)
            ->with(['quiz.pelajaran', 'sesi'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->when(
                $request->filled('pengerjaan'),
                fn ($query) => $query->whereKey($request->query('pengerjaan'))
            )
            ->first();

        /*
         * Pengerjaan yang belum ditutup berarti nilai di database masih bisa
         * bergerak, jadi kartu hasil yang berat tidak boleh ditampilkan
         * sebagai hasil final. Halaman ini tetap dirender, tapi dengan nilai
         * sementara, mengikuti aturan yang sudah dipakai PengerjaanQuiz.
         */
        $ringkasan = $pengerjaan !== null
            ? DaftarHasil::ringkasanPengerjaan($pengerjaan)
            : null;

        /*
         * Tautan ke "halaman hasil sesi" hanya ditampilkan kalau benar-benar
         * ada yang tidak ada di kartu ini: rekap nilai peserta milik host sesi
         * mode kode. Untuk peserta biasa, tautan itu hanya akan memantulkan
         * mereka kembali ke kartu yang sedang mereka lihat.
         */
        $sesi = $pengerjaan?->sesi;
        $tampilRekap = $sesi !== null
            && $sesi->adalahHost($pengguna)
            && $sesi->quiz?->pakaiKode();

        return view('user.uiux-hasil', [
            'hasil' => $ringkasan,
            'adaHasil' => $ringkasan !== null,
            'tautanSesi' => $tampilRekap ? route('user.sesi.hasil', $sesi) : null,
        ]);
    }
}
