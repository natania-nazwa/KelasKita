<?php

namespace App\Support;

use App\Models\PengerjaanQuiz;
use App\Models\User;

/**
 * Leaderboard lintas sesi untuk panel dashboard.
 *
 * Berbeda dengan App\Support\DaftarPeringkat, yang membandingkan peserta di
 * dalam satu sesi, kelas ini membandingkan semua pengguna yang pernah
 * mengerjakan quiz. Dua aturan yang jadi kunci di sini:
 *
 *   - Yang dihitung hanya pengguna peran "user" yang masih aktif. Akun admin
 *     tidak punya pengerjaan sendiri, dan kalau nanti ada data seperti itu,
 *     admin tidak boleh menduduki peringkat pertama.
 *   - Yang dihitung adalah jumlah soal yang sudah dijawab, bukan jumlah quiz
 *     yang dikerjakan. Orang yang mengerjakan dua kuis pendek tidak boleh
 *     mendahului orang yang satu kuis panjang tapi benar-benar menjawab
 *     semuanya.
 *
 * Karena "soal dijawab" tidak selalu bisa membedakan dua orang dengan aktivitas
 * yang sama, peringkat berikutnya memakai nilai tertinggi sebagai pemutus, dan
 * nama sebagai pemutus terakhir. Tiga pemutus itu membuat urutannya stabil:
 * membuka halaman berkali-kali dengan data yang sama selalu menghasilkan urutan
 * yang sama.
 */
final class PeringkatGlobal
{
    /**
     * Berapa baris yang ditampilkan di panel leaderboard.
     *
     * Lima: tiga baris pertama memakai medali, dan dua baris sisanya supaya
     * peringkatmu tidak langsung hilang begitu ada satu orang yang naik.
     */
    public const JUMLAH_DIATAS = 5;

    /**
     * Baris teratas leaderboard, siap dipakai komponen dashboard.
     *
     * Baris milik pengguna yang sedang login diberi flag "saya" supaya panel
     * bisa menyorotnya. Peringkat memakai nomor urut baris (1, 2, 3, ...),
     * bukan peringkat bertingkat: yang ditampilkan sudah dipotong jadi lima
     * baris teratas, jadi tidak ada lagi yang perlu seri.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftar(?int $idPengguna, int $jumlah = self::JUMLAH_DIATAS): array
    {
        if ($idPengguna === null || $jumlah < 1) {
            return [];
        }

        /*
         * Agregat per pengguna diambil lebih dulu, baru namanya.
         *
         * Dipisah supaya nama tidak ikut masuk ke dalam SELECT aggregate: di
         * PostgreSQL, kolom yang tidak ada di GROUP BY akan menggagalkan query
         * kalau ikut dipilih, dan nama tidak perlu ikut teragregasi.
         *
         * Ambil lebih banyak baris daripada yang ditampilkan supaya ada
         * cadangan kalau ternyata sebagian baris dikeluarkan di bawah karena
         * akunnya admin.
         */
        $agregat = PengerjaanQuiz::query()
            ->selectRaw('pengguna_id, COALESCE(SUM(jumlah_dijawab), 0) AS dijawab, COALESCE(MAX(nilai), 0) AS nilai')
            ->groupBy('pengguna_id')
            ->orderByDesc('dijawab')
            ->orderByDesc('nilai')
            ->limit($jumlah * 4)
            ->get();

        if ($agregat->isEmpty()) {
            return [];
        }

        /*
         * Akun yang lama tidak membuka aplikasi tetap ikut: peringkat ini
         * menjumlahkan capaian sepanjang waktu, dan nilai yang sudah
         * dikerjakan tidak hilang hanya karena pengguna sejenak tidak masuk.
         */
        $pengguna = User::query()
            ->where('peran', User::PERAN_USER)
            ->whereIn('id', $agregat->pluck('pengguna_id')->all())
            ->get()
            ->keyBy('id');

        $hasil = [];

        foreach ($agregat as $baris) {
            $orang = $pengguna[$baris->pengguna_id] ?? null;

            if ($orang === null) {
                continue;
            }

            $warna = $orang->warnaAvatar();

            $hasil[] = [
                'pengguna_id' => (int) $orang->getKey(),
                'nama' => $orang->nama,
                'inisial' => $orang->inisial(),
                'warna' => $warna['warna'],
                'warna_gelap' => $warna['warna_gelap'],
                'skor' => (int) $baris->dijawab,
                'nilai' => (int) $baris->nilai,
                'saya' => (int) $orang->getKey() === $idPengguna,
            ];
        }

        usort(
            $hasil,
            fn (array $a, array $b) => [$b['skor'], $b['nilai'], $a['nama']] <=> [$a['skor'], $a['nilai'], $b['nama']]
        );

        $teratas = array_slice($hasil, 0, $jumlah);

        return array_map(function (array $baris, int $urut) {
            return [
                ...$baris,
                'peringkat' => $urut + 1,
                'medali' => match ($urut + 1) {
                    1 => 'emas',
                    2 => 'perak',
                    3 => 'perunggu',
                    default => null,
                },
            ];
        }, $teratas, array_keys($teratas));
    }
}
