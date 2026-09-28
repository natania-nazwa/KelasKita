<?php

namespace App\Support;

use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use Carbon\CarbonInterface;

/**
 * Menghitung seluruh angka untuk halaman "Hasil" dari data milik satu
 * pengguna saja.
 *
 * Semua angka berasal dari query ke tb_pengerjaan_quiz, tidak ada angka
 * yang ditulis manual di view. Kalau pengguna belum pernah mengerjakan
 * quiz, hasilnya nol semua dan kartu statistik tetap dirender dengan
 * angka nol, karena nol di sini memang jawaban database, bukan data
 * dummy.
 *
 * Bentuk array yang dikembalikan:
 *   total_selesai, total_proses, rata_rata, tertinggi,
 *   total_benar, total_salah, total_soal, total_dijawab, durasi_menit,
 *   waktu_belajar => ['bulat', 'satuan', 'label'],
 *   kartu          => empat kartu statistik, sudah termasuk catatan
 *                     pembanding minggu lalu,
 *   terpopuler     => array<int, array<string, mixed>>
 */
final class StatistikHasil
{
    /**
     * Jumlah hari dalam satu periode pembanding.
     */
    private const JEMBLAHAN = 7;

    /**
     * Ambang durasi (menit) untuk menulis waktu sebagai jam.
     */
    private const BATAS_JAM = 60;

    /**
     * Jumlah quiz di kartu "Quiz Terpopuler".
     */
    public const JUMLAH_TERPOPULER = 5;

    /**
     * Ringkasan seluruh pengerjaan milik satu pengguna.
     *
     * @param  int|null  $idPengguna  pengguna yang sedang login
     */
    public static function ringkas(?int $idPengguna): array
    {
        $dasar = PengerjaanQuiz::query()->milik($idPengguna);
        $selesai = (clone $dasar)->selesai();

        $jumlahSelesai = (clone $selesai)->count();
        $jumlahProses = (clone $dasar)->proses()->count();

        $agregat = $selesai
            ->selectRaw('COALESCE(SUM(nilai), 0) AS jumlah_nilai')
            ->selectRaw('COALESCE(MAX(nilai), 0) AS nilai_tertinggi')
            ->selectRaw('COALESCE(SUM(jumlah_benar), 0) AS jumlah_benar')
            ->selectRaw('COALESCE(SUM(jumlah_salah), 0) AS jumlah_salah')
            ->selectRaw('COALESCE(SUM(jumlah_soal), 0) AS jumlah_soal')
            ->selectRaw('COALESCE(SUM(jumlah_dijawab), 0) AS jumlah_dijawab')
            ->first();

        $rataRata = $jumlahSelesai > 0
            ? round((float) $agregat->jumlah_nilai / $jumlahSelesai, 1)
            : 0.0;

        $durasiMenit = self::durasiMenit($idPengguna);

        return [
            'total_selesai' => $jumlahSelesai,
            'total_proses' => $jumlahProses,
            'rata_rata' => $rataRata,
            'tertinggi' => (int) $agregat->nilai_tertinggi,
            'total_benar' => (int) $agregat->jumlah_benar,
            'total_salah' => (int) $agregat->jumlah_salah,
            'total_soal' => (int) $agregat->jumlah_soal,
            'total_dijawab' => (int) $agregat->jumlah_dijawab,
            'durasi_menit' => $durasiMenit,
            'waktu_belajar' => self::waktuBelajar($durasiMenit),
            'kartu' => self::kartu(
                $jumlahSelesai,
                $rataRata,
                (int) $agregat->nilai_tertinggi,
                $durasiMenit,
                $idPengguna,
            ),
            'terpopuler' => self::terpopuler($idPengguna),
        ];
    }

    /**
     * Empat kartu statistik, lengkap dengan catatan pembanding tujuh hari.
     *
     * Nilai kartu boleh nol: itu memang isi database. Yang tidak boleh
     * ada adalah angka pembanding yang dikarang, jadi kalau periode
     * minggu lalu masih kosong, catatannya terus terang-terangan
     * mengatakannya dan tidak menampilkan panah naik/turun.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function kartu(
        int $jumlahSelesai,
        float $rataRata,
        int $tertinggi,
        int $durasiMenit,
        ?int $idPengguna,
    ): array {
        $belumAda = ['arah' => 'netral', 'teks' => 'Belum ada quiz selesai'];

        // Tanpa satu pun quiz selesai, tidak ada yang bisa dibandingkan,
        // jadi keempat kartu memakai catatan yang sama. Bentuknya tetap
        // keempat key supaya pemanggil tidak perlu tahu kondisi ini.
        $catatan = $jumlahSelesai === 0
            ? array_fill_keys(['jumlah', 'rata', 'tertinggi', 'durasi'], $belumAda)
            : self::catatanMingguan($idPengguna);

        return [
            [
                'label' => 'Total Quiz Dikerjakan',
                'nilai' => (string) $jumlahSelesai,
                'satuan' => null,
                'ikon' => 'clipboard',
                'warna' => '#6c4de6',
                'catatan' => $catatan['jumlah'],
            ],
            [
                'label' => 'Rata-rata Nilai',
                'nilai' => Angka::teks($rataRata),
                'satuan' => '%',
                'ikon' => 'target',
                'warna' => '#2f7fe0',
                'catatan' => $catatan['rata'],
            ],
            [
                'label' => 'Nilai Tertinggi',
                'nilai' => (string) $tertinggi,
                'satuan' => null,
                'ikon' => 'bintang',
                'warna' => '#c98a06',
                'catatan' => $catatan['tertinggi'],
            ],
            [
                'label' => 'Waktu Belajar',
                'nilai' => Angka::durasi((float) $durasiMenit),
                'satuan' => null,
                'ikon' => 'jam',
                'warna' => '#0f9d76',
                'catatan' => $catatan['durasi'],
            ],
        ];
    }

    /**
     * Catatan pembanding untuk keempat kartu: tujuh hari terakhir
     * dibanding tujuh hari sebelumnya.
     *
     * Kalau periode pembanding tidak punya satu pun quiz selesai, delta
     * tidak dihitung sama sekali dan catatannya menyebutkan bahwa
     * pembandingnya belum ada, bukan menampilkan "0" yang terlihat
     * seperti data.
     *
     * @return array{jumlah: array, rata: array, tertinggi: array, durasi: array}
     */
    private static function catatanMingguan(?int $idPengguna): array
    {
        $sekarang = now();
        $batasAtas = $sekarang->copy();
        $awalMingguIni = $sekarang->copy()->subDays(self::JEMBLAHAN - 1)->startOfDay();
        $awalMingguLalu = $awalMingguIni->copy()->subDays(self::JEMBLAHAN);

        $kini = self::periode($idPengguna, $awalMingguIni, $batasAtas);
        $lalu = self::periode($idPengguna, $awalMingguLalu, $awalMingguIni->copy()->subSecond());

        $tanpaPembanding = static fn (): array => [
            'arah' => 'netral',
            'teks' => 'Belum ada pembanding minggu lalu',
        ];

        return [
            'jumlah' => $lalu['jumlah'] === 0
                ? $tanpaPembanding()
                : self::banding((float) $kini['jumlah'], (float) $lalu['jumlah'], Angka::teks(...)),

            'rata' => $lalu['jumlah'] === 0
                ? $tanpaPembanding()
                : self::banding($kini['rata'], $lalu['rata'], fn (float $beda): string => Angka::teks($beda).'%'),

            'tertinggi' => $lalu['jumlah'] === 0
                ? $tanpaPembanding()
                : self::banding((float) $kini['tertinggi'], (float) $lalu['tertinggi'], Angka::teks(...)),

            'durasi' => $lalu['jumlah'] === 0
                ? $tanpaPembanding()
                : self::banding(
                    (float) $kini['durasi_menit'],
                    (float) $lalu['durasi_menit'],
                    fn (float $menit): string => Angka::durasi($menit),
                ),
        ];
    }

    /**
     * Bentuk teks pembanding dari selisih dua angka.
     *
     * @param  callable(float): string  $format  menulis nilai selisih jadi teks
     * @return array{arah: string, teks: string}
     */
    private static function banding(float $kini, float $dulu, callable $format): array
    {
        $beda = round($kini - $dulu, 1);

        return match (true) {
            $beda > 0 => ['arah' => 'naik', 'teks' => 'Naik '.$format($beda).' dari minggu lalu'],
            $beda < 0 => ['arah' => 'turun', 'teks' => 'Turun '.$format(abs($beda)).' dari minggu lalu'],
            default => ['arah' => 'datar', 'teks' => 'Sama seperti minggu lalu'],
        };
    }

    /**
     * Agregat satu periode waktu untuk satu pengguna.
     *
     * Hanya pengerjaan yang sudah ditutup yang dihitung, jadi quiz yang
     * masih berjalan tidak ikut menambah jumlah maupun waktu belajar.
     *
     * @return array{jumlah: int, rata: float, tertinggi: int, durasi_menit: int}
     */
    private static function periode(?int $idPengguna, CarbonInterface $dari, CarbonInterface $sampai): array
    {
        $query = PengerjaanQuiz::query()
            ->milik($idPengguna)
            ->selesai()
            ->whereNotNull('dimulai_pada')
            ->whereBetween('selesai_pada', [$dari, $sampai]);

        $agregat = (clone $query)
            ->selectRaw('COUNT(*) AS jumlah')
            ->selectRaw('COALESCE(AVG(nilai), 0) AS rata')
            ->selectRaw('COALESCE(MAX(nilai), 0) AS tertinggi')
            ->first();

        return [
            'jumlah' => (int) $agregat->jumlah,
            'rata' => round((float) $agregat->rata, 1),
            'tertinggi' => (int) $agregat->tertinggi,
            'durasi_menit' => (int) ceil(self::durasiDari($query->get(['dimulai_pada', 'selesai_pada'])) / 60),
        ];
    }

    /**
     * Total durasi pengerjaan milik satu pengguna, dalam menit.
     *
     * Hanya pengerjaan yang sudah ditutup yang dihitung, jadi quiz yang
     * masih berjalan tidak ikut menambah waktu belajar.
     */
    private static function durasiMenit(?int $idPengguna): int
    {
        $baris = PengerjaanQuiz::query()
            ->milik($idPengguna)
            ->selesai()
            ->whereNotNull('dimulai_pada')
            ->get(['dimulai_pada', 'selesai_pada']);

        return (int) ceil(self::durasiDari($baris) / 60);
    }

    /**
     * Jumlah detik dari selisih dua timestamp.
     *
     * Sengaja mengembalikan detik, bukan menit: pemanggil yang tahu
     * periodenya yang membulatkan ke menit, supaya kartu total dan
     * selisih antar periode memakai aturan pembulatan yang sama.
     *
     * Dihitung di PHP, bukan lewat fungsi bawaan database, supaya angkanya
     * sama persis di pgsql (dipakai aplikasi) dan di sqlite (dipakai test).
     *
     * @param  iterable<int, PengerjaanQuiz>  $baris
     */
    private static function durasiDari(iterable $baris): int
    {
        $detik = 0;

        foreach ($baris as $item) {
            $mulai = $item->dimulai_pada;
            $selesai = $item->selesai_pada;

            if (! $mulai instanceof CarbonInterface || ! $selesai instanceof CarbonInterface) {
                continue;
            }

            $detik += max(0, (int) round(abs($selesai->getTimestamp() - $mulai->getTimestamp())));
        }

        return $detik;
    }

    /**
     * Bentuk label waktu belajar: jam kalau sudah lewat satu jam, menit
     * kalau belum. Nol tetap ditulis "0 menit" karena itu jawaban database.
     *
     * @return array{bulat: float, satuan: string, label: string}
     */
    private static function waktuBelajar(int $menit): array
    {
        return [
            'bulat' => $menit >= self::BATAS_JAM
                ? round($menit / self::BATAS_JAM, 1)
                : (float) $menit,
            'satuan' => $menit >= self::BATAS_JAM ? 'jam' : 'menit',
            'label' => Angka::durasi((float) $menit),
        ];
    }

    /**
     * Quiz yang paling banyak dikerjakan pengguna yang sedang login.
     *
     * Dihitung dari pengerjaan miliknya sendiri, jadi angkanya jujur
     * meskipun belum ada ribuan peserta.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function terpopuler(?int $idPengguna): array
    {
        $baris = PengerjaanQuiz::query()
            ->milik($idPengguna)
            ->selesai()
            ->select('quiz_id')
            ->selectRaw('COUNT(*) AS jumlah_pengerjaan')
            ->selectRaw('COALESCE(AVG(nilai), 0) AS rata_nilai')
            ->groupBy('quiz_id')
            ->orderByDesc('jumlah_pengerjaan')
            ->orderByDesc('rata_nilai')
            ->limit(self::JUMLAH_TERPOPULER)
            ->get();

        if ($baris->isEmpty()) {
            return [];
        }

        $quiz = Quiz::query()
            ->with('pelajaran')
            ->whereIn('id', $baris->pluck('quiz_id'))
            ->get()
            ->keyBy(fn (Quiz $item) => $item->getKey());

        $hasil = [];

        foreach ($baris as $item) {
            $satu = $quiz->get($item->quiz_id);

            if (! $satu instanceof Quiz) {
                continue;
            }

            $pelajaran = $satu->pelajaran;
            $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');

            $hasil[] = [
                'peringkat' => count($hasil) + 1,
                'judul' => $satu->judul,
                'jumlah_pengerjaan' => (int) $item->jumlah_pengerjaan,
                'rata_nilai' => (int) round((float) $item->rata_nilai),
                'kategori' => [
                    'nama' => $kategori['nama'],
                    'ikon' => $kategori['ikon'],
                    'warna' => $kategori['warna'],
                    'warna_gelap' => $kategori['warna_gelap'],
                ],
                'tautan' => route('user.hasil.daftar', $satu->getKey()),
            ];
        }

        return $hasil;
    }
}
