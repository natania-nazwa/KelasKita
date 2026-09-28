<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JawabanQuiz;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Soal;
use App\Support\DaftarHasil;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Detail satu pengerjaan quiz: nilai, rincian jawaban, dan daftar soal
 * beserta jawaban yang dipilih pengguna.
 *
 * Halaman ini hanya boleh membuka pengerjaan milik pengguna yang sedang
 * login. Pengecekan dilakukan di server terhadap pengguna_id, jadi
 * menebak URL pengerjaan orang lain tetap ditolak 403.
 *
 * Sistem pengerjaan quiz tidak dibuat ulang di sini: halaman ini hanya
 * membaca apa yang sudah tersimpan di tb_pengerjaan_quiz dan
 * tb_jawaban_quiz.
 */
class HasilDetailController extends Controller
{
    public function __invoke(Request $request, PengerjaanQuiz $pengerjaan): View
    {
        $pengguna = $request->user();

        abort_unless(
            $pengerjaan->pengguna_id === $pengguna?->getKey(),
            403,
            'Hasil ini bukan milikmu.'
        );

        $pengerjaan->loadMissing(['quiz.pelajaran', 'sesi']);

        $durasi = DaftarHasil::durasiLabel($pengerjaan->durasiDetik());
        $kategori = $this->kategori($pengerjaan);

        return view('user.hasil-detail', [
            'pengerjaan' => $pengerjaan,
            'quiz' => $pengerjaan->quiz,
            'kategori' => $kategori,
            'status' => $pengerjaan->status(),
            'statusLabel' => $pengerjaan->labelStatus(),
            'benar' => (int) $pengerjaan->jumlah_benar,
            'salah' => (int) $pengerjaan->jumlah_salah,
            'dijawab' => (int) $pengerjaan->jumlah_dijawab,
            'belumDijawab' => $pengerjaan->belumDijawab(),
            'jumlahSoal' => (int) $pengerjaan->jumlah_soal,
            'durasi' => $durasi,
            'daftarSoal' => $this->daftarSoal($pengerjaan),
            'adaRiwayatSesi' => $pengerjaan->sesi_id !== null && $pengerjaan->sesi !== null,
            'tautanSesi' => $pengerjaan->sesi !== null
                ? route('user.sesi.hasil', $pengerjaan->sesi)
                : null,
        ]);
    }

    /**
     * Daftar soal quiz beserta jawaban pengguna.
     *
     * Semua soal aktif ditampilkan, bukan hanya yang dijawab, supaya soal
     * yang dilewati terlihat jelas sebagai "Tidak dijawab".
     *
     * @return array<int, array<string, mixed>>
     */
    private function daftarSoal(PengerjaanQuiz $pengerjaan): array
    {
        $soal = Soal::query()
            ->where('quiz_id', $pengerjaan->quiz_id)
            ->aktif()
            ->terurut()
            ->get();

        if ($soal->isEmpty()) {
            return [];
        }

        $jawaban = JawabanQuiz::query()
            ->where('pengerjaan_quiz_id', $pengerjaan->getKey())
            ->get()
            ->keyBy(fn (JawabanQuiz $item) => $item->soal_id);

        $hasil = [];

        foreach ($soal as $nomor => $item) {
            $jawab = $jawaban->get($item->getKey());
            $pilihan = $item->pilihan();

            $hasil[] = [
                'nomor' => $nomor + 1,
                'pertanyaan' => $item->pertanyaan,
                'tingkat' => $item->tingkat_kesulitan,
                'pembahasan' => (string) $item->pembahasan,
                'pilihan' => $pilihan,
                'terpilih' => $jawab?->jawaban_dipilih,
                'benar' => $jawab?->jawaban_benar ?? $item->jawaban_benar,
                'status' => match (true) {
                    $jawab === null => 'kosong',
                    (bool) $jawab->benar => 'benar',
                    default => 'salah',
                },
                'teks_terpilih' => $jawab !== null
                    ? ($pilihan[$jawab->jawaban_dipilih] ?? null)
                    : null,
                'teks_benar' => $pilihan[$jawab?->jawaban_benar ?? $item->jawaban_benar] ?? null,
            ];
        }

        return $hasil;
    }

    /**
     * @return array{nama: string, ikon: string, warna: string, warna_gelap: string}
     */
    private function kategori(PengerjaanQuiz $pengerjaan): array
    {
        $pelajaran = $pengerjaan->quiz?->pelajaran;

        return Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
    }
}
