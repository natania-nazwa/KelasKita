<?php

namespace Tests\Feature;

use App\Models\AktivitasHarian as BarisAktivitas;
use App\Models\User;
use App\Support\AktivitasHarian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Jejak aktivitas harian dan streak yang dihitung darinya.
 *
 * Yang diuji di sini:
 *   1. Satu jenis kegiatan satu hari hanya jadi satu baris, berapa kali pun
 *      dipanggil, dan updated_at-nya tetap mengikuti kegiatan terakhir.
 *   2. Dua jenis kegiatan di hari yang sama dihitung sebagai satu hari streak,
 *      bukan dua.
 *   3. Aturan 24 jam: belajar pukul 23.00 lalu dibaca pukul 09.00 keesokan
 *      hari masih menyala, sedangkan lewat 24 jam penuh langsung 0 dan
 *      dihitung ulang dari 1 saat belajar lagi.
 *   4. Baris yang sudah lewat batas dipangkas supaya tabel tidak tumbuh.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi bentuk nilai
 * tanggal yang dipakai di sini sama persis dengan yang dipakai test lain —
 * termasuk yang akan menangkap perbedaan antara "2026-10-06" dan
 * "2026-10-06 00:00:00" pada kolom tanggal.
 */
class AktivitasHarianTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $email = 'belajar@example.com'): User
    {
        return User::create([
            'nama' => 'Natania Nazwa Gisella',
            'email' => $email,
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

    private function barisPengguna(User $pengguna): int
    {
        return BarisAktivitas::query()->milik($pengguna->getKey())->count();
    }

    public function test_kegiatan_kosong_tidak_mencatat_apa_pun(): void
    {
        $pengguna = $this->buatPengguna();

        // Halaman yang bisa dibuka tanpa login harus tetap aman dipanggil.
        AktivitasHarian::bacaMateri(null);
        AktivitasHarian::kerjakanQuiz(null);

        $this->assertSame(0, $this->barisPengguna($pengguna));
    }

    public function test_kegiatan_yang_sama_dipanggil_berulang_kali_tetap_satu_baris(): void
    {
        $pengguna = $this->buatPengguna();
        $malam = Carbon::parse('2026-03-10 20:00:00');

        AktivitasHarian::bacaMateri($pengguna, $malam);
        AktivitasHarian::bacaMateri($pengguna, $malam->copy()->addMinutes(20));
        AktivitasHarian::bacaMateri($pengguna, $malam->copy()->addMinutes(45));

        // Membuka materi yang sama berulang kali dalam sehari tidak boleh
        // menambah baris: unique constraint di database juga akan menolaknya.
        $this->assertSame(1, $this->barisPengguna($pengguna));
    }

    public function test_kegiatan_berulang_memperbarui_waktu_kegiatan_terakhir(): void
    {
        $pengguna = $this->buatPengguna();
        $malam = Carbon::parse('2026-03-10 20:00:00');

        AktivitasHarian::bacaMateri($pengguna, $malam);

        $awal = BarisAktivitas::query()
            ->milik($pengguna->getKey())
            ->firstOrFail();

        $pagi = $malam->copy()->addMinutes(45);

        AktivitasHarian::bacaMateri($pengguna, $pagi);

        $setelah = BarisAktivitas::query()
            ->milik($pengguna->getKey())
            ->firstOrFail();

        $this->assertTrue($setelah->is($awal), 'Barisnya harus sama, bukan baris baru.');
        $this->assertTrue($setelah->updated_at->equalTo($pagi));
    }

    public function test_dua_jenis_kegiatan_di_hari_yang_sama_menghitung_satu_hari(): void
    {
        $pengguna = $this->buatPengguna();
        $hariIni = Carbon::parse('2026-03-10 08:00:00');

        AktivitasHarian::bacaMateri($pengguna, $hariIni);
        AktivitasHarian::kerjakanQuiz($pengguna, $hariIni->copy()->addHours(4));

        // Dua baris, tapi satu hari: dua kegiatan di tanggal sama bukan dua hari belajar.
        $this->assertSame(2, $this->barisPengguna($pengguna));

        $streak = AktivitasHarian::streak($pengguna, $hariIni->copy()->addHours(5));

        $this->assertSame(1, $streak['jumlah']);
        $this->assertTrue($streak['aktif']);
    }

    public function test_streak_berjumlah_hari_berturut_turut(): void
    {
        $pengguna = $this->buatPengguna();
        $hariIni = Carbon::parse('2026-03-10 08:00:00');

        foreach (range(0, 3) as $mundur) {
            AktivitasHarian::bacaMateri(
                $pengguna,
                $hariIni->copy()->subDays($mundur),
            );
        }

        $streak = AktivitasHarian::streak($pengguna, $hariIni->copy()->addHours(1));

        $this->assertSame(4, $streak['jumlah']);
        $this->assertTrue($streak['aktif']);
    }

    public function test_jarak_satu_hari_mematahkan_rantai(): void
    {
        $pengguna = $this->buatPengguna();
        $hariIni = Carbon::parse('2026-03-10 08:00:00');

        // Hari ini, lalu lompat ke dua hari lalu: kemarin kosong.
        AktivitasHarian::bacaMateri($pengguna, $hariIni);
        AktivitasHarian::bacaMateri($pengguna, $hariIni->copy()->subDays(2));

        $streak = AktivitasHarian::streak($pengguna, $hariIni->copy()->addHours(1));

        $this->assertSame(1, $streak['jumlah']);
        $this->assertTrue($streak['aktif']);
    }

    public function test_belajar_tengah_malam_lalu_dashboard_pagi_hari_ini_tetap_menyala(): void
    {
        $pengguna = $this->buatPengguna();

        // Belajar pukul 23.00, lalu dashboard dibuka pukul 09.00 keesokan
        // hari: baru 10 jam berlalu, jadi rantainya belum padam walau
        // sudah beda hari kalender.
        $semalamMalam = Carbon::parse('2026-03-09 23:00:00');
        $pagiIni = Carbon::parse('2026-03-10 09:00:00');

        AktivitasHarian::bacaMateri($pengguna, $semalamMalam);

        $streak = AktivitasHarian::streak($pengguna, $pagiIni);

        $this->assertSame(1, $streak['jumlah']);
        $this->assertTrue($streak['aktif']);
    }

    public function test_tepat_24_jam_masih_menghitung_sebagai_menyala(): void
    {
        $pengguna = $this->buatPengguna();

        $semalam = Carbon::parse('2026-03-09 08:00:00');
        AktivitasHarian::bacaMateri($pengguna, $semalam);

        // Batasnya "lebih dari 24 jam", jadi tepat 24 jam belum terputus:
        // yang mematikan rantai adalah 24 jam penuh tanpa kegiatan baru.
        $tepat24Jam = $semalam->copy()->addHours(AktivitasHarian::BATAS_JAM);
        $streak = AktivitasHarian::streak($pengguna, $tepat24Jam);

        $this->assertSame(1, $streak['jumlah']);
        $this->assertTrue($streak['aktif']);
    }

    public function test_belajar_lagi_setelah_rantai_mati_menambah_hari_baru(): void
    {
        $pengguna = $this->buatPengguna();

        $senin = Carbon::parse('2026-03-09 08:00:00');
        AktivitasHarian::bacaMateri($pengguna, $senin);

        // Gap 25 jam: Senin 08.00 lalu Selasa 09.01, jadi rantai sempat mati.
        $selasa = $senin->copy()->addHours(25)->addMinutes(1);
        $mati = AktivitasHarian::streak($pengguna, $selasa->copy()->subHour());

        $this->assertSame(0, $mati['jumlah']);
        $this->assertFalse($mati['aktif']);

        // Belajar lagi. Hitungan berjalan mundur per hari kalender, jadi Senin
        // dan Selasa masih dua hari berturut-turut meskipun jaraknya 25 jam.
        AktivitasHarian::bacaMateri($pengguna, $selasa);

        $baru = AktivitasHarian::streak($pengguna, $selasa->copy()->addMinutes(30));

        $this->assertSame(2, $baru['jumlah']);
        $this->assertTrue($baru['aktif']);
    }

    public function test_belajar_lagi_setelah_sehari_lompat_mulai_dari_satu(): void
    {
        $pengguna = $this->buatPengguna();

        $rabu = Carbon::parse('2026-03-11 08:00:00');
        AktivitasHarian::bacaMateri($pengguna, $rabu);

        // Lompat satu hari penuh: Kamis kosong.
        $jumat = Carbon::parse('2026-03-13 09:00:00');
        AktivitasHarian::bacaMateri($pengguna, $jumat);

        $streak = AktivitasHarian::streak($pengguna, $jumat->copy()->addMinutes(30));

        // Hari yang kosong memutus rantai, jadi angka dimulai dari 1 lagi
        // dan bukan dilanjutkan dari angka sebelumnya.
        $this->assertSame(1, $streak['jumlah']);
        $this->assertTrue($streak['aktif']);
    }

    public function test_aktivitas_lama_dipangkas_dan_tidak_mengganggu_streak(): void
    {
        $pengguna = $this->buatPengguna();
        $hariIni = Carbon::parse('2026-03-10 08:00:00');

        // Tiga baris jauh di masa lalu yang tidak mungkin dihitung lagi.
        foreach ([40, 60, 90] as $mundur) {
            AktivitasHarian::bacaMateri($pengguna, $hariIni->copy()->subDays($mundur));
        }

        AktivitasHarian::bacaMateri($pengguna, $hariIni);

        $this->assertSame(4, $this->barisPengguna($pengguna));

        $streak = AktivitasHarian::streak($pengguna, $hariIni->copy()->addHours(1));

        $this->assertSame(1, $streak['jumlah']);

        // Baris lama dibuang sekalian supaya tabel tidak tumbuh tanpa batas.
        $this->assertSame(1, $this->barisPengguna($pengguna));
    }

    public function test_streak_pengguna_lain_tidak_ikut_terhitung(): void
    {
        $saya = $this->buatPengguna('saya@example.com');
        $orangLain = $this->buatPengguna('lain@example.com');
        $hariIni = Carbon::parse('2026-03-10 08:00:00');

        AktivitasHarian::bacaMateri($saya, $hariIni);
        AktivitasHarian::bacaMateri($saya, $hariIni->copy()->subDay());
        AktivitasHarian::bacaMateri($orangLain, $hariIni);

        $streak = AktivitasHarian::streak($saya, $hariIni->copy()->addHours(1));

        $this->assertSame(2, $streak['jumlah']);
        $this->assertSame(1, $this->barisPengguna($orangLain));
    }

    public function test_tanpa_aktivitas_streak_nol_dan_tidak_aktif(): void
    {
        $pengguna = $this->buatPengguna();

        $streak = AktivitasHarian::streak($pengguna, Carbon::parse('2026-03-10 08:00:00'));

        $this->assertSame(['jumlah' => 0, 'aktif' => false, 'terakhir' => null], $streak);
    }

    public function test_streak_pengguna_tanpa_login_aman(): void
    {
        $streak = AktivitasHarian::streak(null);

        $this->assertSame(0, $streak['jumlah']);
        $this->assertFalse($streak['aktif']);
        $this->assertNull($streak['terakhir']);
        $this->assertSame(0, AktivitasHarian::jumlahHari(null));
    }

    public function test_jumlah_hari_menghitung_tanggal_unik_bukan_baris(): void
    {
        $pengguna = $this->buatPengguna();
        $hariIni = Carbon::parse('2026-03-10 08:00:00');

        AktivitasHarian::bacaMateri($pengguna, $hariIni);
        AktivitasHarian::kerjakanQuiz($pengguna, $hariIni->copy()->addHours(3));
        AktivitasHarian::bacaMateri($pengguna, $hariIni->copy()->subDay());

        $this->assertSame(3, $this->barisPengguna($pengguna));
        $this->assertSame(2, AktivitasHarian::jumlahHari($pengguna));
    }
}
