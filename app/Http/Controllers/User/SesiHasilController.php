<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PengerjaanQuiz;
use App\Models\SesiQuiz;
use App\Support\DaftarPeserta;
use App\Support\PenjagaSesi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman hasil sesi quiz.
 *
 * Dua tampilan dari satu halaman:
 *   - Peserta melihat nilainya sendiri: berapa soal yang dijawab, berapa
 *     yang benar, dan sisa waktunya.
 *   - Host melihat seluruh peserta sesi ini beserta nilai masing-masing, dan
 *     bisa mengurutkan dari yang tertinggi.
 *
 * Halaman ini tetap bisa dibuka saat quiz masih berjalan, supaya host bisa
 * memantau nilai yang sudah masuk.
 */
class SesiHasilController extends Controller
{
    public function __invoke(Request $request, SesiQuiz $sesi): View
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $sesi->loadMissing(['quiz', 'host']);

        $adalahHost = $sesi->adalahHost($pengguna);

        $pengerjaanSaya = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->where('pengguna_id', $pengguna->getKey())
            ->first();

        return view('user.quiz-hasil', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'adalahHost' => $adalahHost,
            'pengerjaan' => $pengerjaanSaya,
            'jumlahPeserta' => $sesi->peserta()->count(),
            'daftarNilai' => $adalahHost ? $this->daftarNilai($sesi) : [],
            'daftarPeserta' => $adalahHost ? [] : DaftarPeserta::petakan(
                $sesi->peserta()->with('pengguna')->orderBy('bergabung_pada')->orderBy('id')->get()
            ),
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
