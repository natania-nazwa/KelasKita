<?php

namespace App\Support;

use App\Models\PengerjaanQuiz;
use App\Models\SesiQuiz;

/**
 * Satu-satunya tempat yang menghitung peringkat nilai peserta satu sesi.
 *
 * Dipakai oleh dua halaman yang sebelumnya masing-masing punya aturan sendiri:
 *   - /user/sesi/{sesi}/hasil, rekap yang dilihat host mode kode;
 *   - /user/sesi/{sesi}/peringkat, halaman peringkat yang dibuka peserta lewat
 *     tombol "Lihat Peringkat" di kartu hasil.
 *
 * Karena keduanya membaca cara yang sama, peringkat yang dilihat host di
 * halaman rekap dan peringkat yang dilihat peserta di halaman ini tidak
 * mungkin berbeda urutan, bahkan saat nilainya masih bergerak.
 *
 * Tiga aturan yang dijaga di sini:
 *   - Yang dihitung hanya PESERTA sesi, bukan host. Host sesi mode kode tidak
 *     pernah punya baris peserta (lihat App\Support\SesiKode), jadi namanya
 *     tidak akan muncul di daftar ini.
 *   - Peserta yang belum menjawab tetap ikut tampil dengan nilai 0 supaya tidak
 *     terlihat hilang. Keadaannya ditulis terpisah lewat "sudah_selesai",
 *     jadi angka 0 di sebelah namanya tidak pernah dibaca sebagai nilai akhir.
 *   - Peringkat diberikan setelah semua nilai terkumpul, jadi dua orang dengan
 *     nilai sama mendapat peringkat sama dan peringkat berikutnya meloncat
 *     (1, 1, 3) — bukan 1, 1, 2.
 */
final class DaftarPeringkat
{
    /**
     * Daftar peserta sesi ini, urut dari nilai tertinggi, lengkap dengan
     * peringkatnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function untukSesi(SesiQuiz $sesi): array
    {
        $sesi->loadMissing('quiz');

        $pengerjaan = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->get()
            ->keyBy('pengguna_id');

        $jumlahSoal = $sesi->quiz?->soal()->aktif()->count() ?? 0;

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
                'jumlah_soal' => (int) ($nilai?->jumlah_soal ?? $jumlahSoal),
                'sudah_selesai' => $nilai?->sudahSelesai() ?? false,
            ];
        }

        // Nilai sama diurutkan dari nama supaya urutannya selalu sama walau
        // halaman dibuka berkali-kali dengan data yang tidak berubah.
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

    /**
     * Tiga baris teratas, dipakai untuk podium.
     *
     * Urutannya dibiarkan apa adanya (peringkat 1, 2, 3), bukan diacak jadi
     * urutan tampil 2-1-3. Yang dipindah ke tengah hanya posisi tampilnya,
     * lewat CSS "order", supaya urutan di DOM — yang dipakai pembaca layar
     * dan urutan ketik Tab — tetap sama dengan urutan peringkatnya.
     *
     * Kalau pesertanya kurang dari tiga, pemanggil tidak memakai hasilnya:
     * podium dengan satu petak tidak menambah informasi apa pun.
     *
     * @param  array<int, array<string, mixed>>  $daftar
     * @return array<int, array<string, mixed>>
     */
    public static function podium(array $daftar): array
    {
        return array_slice($daftar, 0, 3);
    }

    /**
     * Baris milik satu pengguna saja, untuk menyorot barisnya sendiri.
     *
     * Host tidak pernah punya baris di daftar ini, jadi hasilnya null dan
     * view tidak boleh memakainya tanpa mengecek lebih dulu.
     *
     * @param  array<int, array<string, mixed>>  $daftar
     * @return array<string, mixed>|null
     */
    public static function barisSaya(array $daftar, ?int $idPengguna): ?array
    {
        if ($idPengguna === null) {
            return null;
        }

        foreach ($daftar as $baris) {
            // Kedua sisi dipaksa jadi int: kolom pengguna_id dibaca dari
            // database dan driver tidak selalu mengembalikan tipe yang
            // sama, jadi perbandingan ketat bisa gagal padahal angkanya sama.
            if ((int) $baris['pengguna_id'] === $idPengguna) {
                return $baris;
            }
        }

        return null;
    }
}
