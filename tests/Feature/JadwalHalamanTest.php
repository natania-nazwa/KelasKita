<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\User;
use App\Support\DaftarJadwal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Jadwal (/user/jadwal) dan tombol "Lihat Semua" di panel
 * "Jadwal Hari Ini" milik dashboard.
 *
 * Yang diuji di sini adalah perilaku yang harus tetap benar: rute, pemilihan
 * tanggal, filter, pencarian, kalender, top bar yang disembunyikan, dan
 * bahwa kartu dashboard menunjuk ke halaman yang sama.
 *
 * Aplikasi tidak punya jadwal contoh lagi, jadi test yang butuh daftar
 * pelajaran membuat barisnya sendiri lewat buatJadwalMinggu(). Pengguna
 * tanpa baris sama sekali — seperti akun baru — diuji terpisah lewat
 * empty state-nya.
 *
 * Baris jadwal yang diuji lewat atribut data (data-jadwal-id pada tiap
 * baris, data-jadwal-hari pada strip tujuh hari), bukan lewat teks judul.
 * Judul pelajaran juga muncul di dropdown filter, jadi teksnya tidak bisa
 * dipakai untuk membuktikan sebuah baris tidak ikut terfilter.
 *
 * Test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini tidak
 * menyentuh database sungguhan.
 */
class JadwalHalamanTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $nama = 'Natania'): User
    {
        return User::create([
            'nama' => $nama,
            'email' => str($nama)->slug()->value().'@example.com',
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

    /**
     * Isi jadwal milik satu pengguna untuk seluruh minggu.
     *
     * Enam baris per hari, judulnya unik per hari, supaya dua hal bisa
     * diuji sekaligus: kartu dashboard yang hanya memuat empat baris
     * pertama, dan baris kelima-enam yang tidak boleh ikut muncul di sana.
     *
     * Ruangnya sengaja berselang-seling karena satu test mencari jadwal
     * lewat nama ruangnya.
     */
    private function buatJadwalMinggu(User $user): void
    {
        $jam = [
            ['07:00:00', '08:30:00'],
            ['08:30:00', '10:00:00'],
            ['10:15:00', '11:45:00'],
            ['12:00:00', '13:30:00'],
            ['13:30:00', '15:00:00'],
            ['15:15:00', '16:45:00'],
        ];

        $mapel = [
            ['matematika', 'Matematika'],
            ['ipa', 'IPA'],
            ['bahasa-inggris', 'Bahasa Inggris'],
            ['pemrograman', 'Pemrograman Web'],
            ['ips', 'IPS'],
            ['ppkn', 'PPKN'],
        ];

        foreach (range(0, 6) as $hari) {
            foreach ($mapel as $urut => [$slug, $judul]) {
                Jadwal::create([
                    'dibuat_oleh' => $user->getKey(),
                    'hari' => $hari,
                    'mulai' => $jam[$urut][0],
                    'selesai' => $jam[$urut][1],
                    'pelajaran' => $slug,
                    'judul' => $judul,
                    'kelas' => 'Kelas 11 RPL 2',
                    'ruang' => $urut % 2 === 0 ? 'Lab Komputer 1' : 'Ruang Kelas 3B',
                ]);
            }
        }
    }

    /**
     * Tanggal terdekat yang jatuh pada hari sekolah (bukan Minggu), supaya
     * test tidak bergantung pada hari apa yang sedang berjalan waktu test
     * dieksekusi. Kalau hari ini sudah sekolah, hari ini yang dipakai.
     */
    private function tanggalSekolah(): Carbon
    {
        $tanggal = now()->startOfDay();

        for ($i = 0; $i < 7; $i++) {
            if ((int) $tanggal->dayOfWeek !== Carbon::SUNDAY) {
                return $tanggal;
            }

            $tanggal = $tanggal->copy()->addDay();
        }

        return now()->startOfDay();
    }

    /**
     * Jadwal pada satu tanggal untuk satu pengguna, dibaca lewat kelas yang
     * sama dengan yang dipakai halaman.
     *
     * Dipakai sebagai sumber kebenaran pembanding di test: test memeriksa
     * apa yang dirender halaman, bukan mengulang perhitungan sendiri.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function jadwalHari(User $user, Carbon $tanggal): array
    {
        return DaftarJadwal::hari(DaftarJadwal::jadwalMinggu($user->getKey()), $tanggal);
    }

    /**
     * Tanggal Minggu terdekat, dipakai untuk menguji hari libur.
     */
    private function tanggalLibur(): Carbon
    {
        $tanggal = now()->startOfDay();

        while ((int) $tanggal->dayOfWeek !== Carbon::SUNDAY) {
            $tanggal = $tanggal->copy()->addDay();
        }

        return $tanggal;
    }

    /**
     * Atribut penanda tiap baris jadwal yang ada di respons.
     *
     * @return array<int, int>
     */
    private function barisDirender(string $html): array
    {
        preg_match_all('/data-jadwal-id="(\d+)"/', $html, $cocok);

        return array_map('intval', $cocok[1]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/user/jadwal')->assertRedirect(route('login'));
    }

    public function test_halaman_jadwal_membuka_dan_menampilkan_kepala_halaman(): void
    {
        $tanggal = $this->tanggalSekolah();

        $respons = $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString())
            ->assertOk()
            ->assertSee('Jadwal Pelajaran')
            ->assertSee(DaftarJadwal::namaHariPenuh($tanggal));

        // Strip tujuh hari harus memuat tujuh tanggal, dari Senin sampai
        // Minggu, masing-masing dengan tautan ke jadwal hari itu.
        $senin = $tanggal->copy()->startOfWeek(Carbon::MONDAY);

        for ($i = 0; $i < 7; $i++) {
            $hari = $senin->copy()->addDays($i);

            $respons->assertSee('data-jadwal-hari="'.$hari->toDateString().'"', false);
        }
    }

    public function test_kartu_dashboard_menunjuk_ke_halaman_jadwal(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/user/dashboard')
            ->assertOk()
            ->assertSee('Jadwal Hari Ini')
            ->assertSee(route('user.jadwal'), false);
    }

    public function test_akun_baru_melihat_empty_state_di_halaman_jadwal(): void
    {
        $tanggal = $this->tanggalSekolah();

        $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString())
            ->assertOk()
            ->assertSee('Belum Ada Jadwal')
            ->assertSee('supaya kartu di dashboard ikut terisi')
            ->assertSee(route('user.jadwal.tambah', ['tanggal' => $tanggal->toDateString()]), false);
    }

    public function test_kartu_dashboard_akun_baru_menampilkan_empty_state_mini(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/user/dashboard')
            ->assertOk()
            ->assertSee('Belum ada jadwal hari ini')
            ->assertSee('lalu kartu ini akan langsung terisi')
            ->assertDontSee('Tidak ada jadwal hari ini');
    }

    public function test_kartu_dashboard_menampilkan_paling_empat_jadwal(): void
    {
        $user = $this->buatPengguna();
        $tanggal = $this->tanggalSekolah();
        $this->buatJadwalMinggu($user);

        $dashboard = $this->actingAs($user)->get('/user/dashboard')->assertOk();
        $halaman = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString())
            ->assertOk();

        $semua = self::jadwalHari($user, $tanggal);
        $this->assertGreaterThan(4, count($semua), 'Jadwal harus punya lebih dari empat baris.');

        $tampil = array_slice($semua, 0, 4);
        $dipotong = array_slice($semua, 4);

        // Empat baris pertama tampil di kartu dan di halaman jadwal.
        foreach ($tampil as $baris) {
            $dashboard->assertSee($baris['mulai']);
            $dashboard->assertSee($baris['judul']);
            $halaman->assertSee($baris['mulai']);
            $halaman->assertSee($baris['judul']);
        }

        // Sisanya tidak dipotong di halaman jadwal, hanya di kartu dashboard.
        foreach ($dipotong as $baris) {
            $dashboard->assertDontSee($baris['judul']);
            $halaman->assertSee($baris['judul']);
        }

        // Sisanya disebut, supaya tidak hilang begitu saja.
        $dashboard->assertSee('+'.count($dipotong).' pelajaran lainnya');
    }

    public function test_memilih_tanggal_menampilkan_jadwal_hari_itu(): void
    {
        $user = $this->buatPengguna();
        $tanggal = $this->tanggalSekolah()->copy()->addWeek();
        $this->buatJadwalMinggu($user);

        $hari = self::jadwalHari($user, $tanggal);

        $respons = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString())
            ->assertOk()
            ->assertSee($hari[0]['judul'])
            /*
             * Tiap baris menampilkan kelas, ruang, dan durasinya. Nilainya
             * diambil dari $hari (sumber kebenaran yang sama dengan
             * halaman), bukan ditulis mati, karena ruang tiap baris boleh
             * berbeda dan teks hardcoded hanya lulus pada baris tertentu.
             */
            ->assertSee($hari[0]['ruang'])
            ->assertSee($hari[0]['durasi_label']);

        $this->assertSame(
            array_column($hari, 'id'),
            $this->barisDirender($respons->getContent())
        );
    }

    public function test_tanggal_tidak_valid_dijatuhkan_ke_hari_ini(): void
    {
        $user = $this->buatPengguna();
        $hariIni = now()->startOfDay();

        foreach (['2026-02-31', 'bukan-tanggal', ''] as $nilai) {
            $this->actingAs($user)
                ->get('/user/jadwal'.($nilai === '' ? '' : '?tanggal='.$nilai))
                ->assertOk()
                ->assertSee(DaftarJadwal::namaHariPenuh($hariIni));
        }
    }

    public function test_filter_pelajaran_menyisakan_pelajaran_yang_dipilih(): void
    {
        $user = $this->buatPengguna();
        $tanggal = $this->tanggalSekolah();
        $this->buatJadwalMinggu($user);

        $semua = self::jadwalHari($user, $tanggal);
        $satu = $semua[0];

        $respons = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString().'&kategori='.$satu['kategori']['slug'])
            ->assertOk()
            ->assertSee($satu['judul']);

        $harusAda = array_values(array_filter(
            $semua,
            fn (array $baris) => $baris['kategori']['slug'] === $satu['kategori']['slug']
        ));

        $this->assertSame(
            array_column($harusAda, 'id'),
            $this->barisDirender($respons->getContent())
        );
    }

    public function test_filter_pelajaran_asing_diabaikan(): void
    {
        $user = $this->buatPengguna();
        $tanggal = $this->tanggalSekolah();
        $this->buatJadwalMinggu($user);

        $semua = self::jadwalHari($user, $tanggal);

        $respons = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString().'&kategori=pelajaran-hantu')
            ->assertOk()
            ->assertSee('Semua pelajaran');

        // Nilai yang tidak dikenal diperlakukan sebagai "semua", jadi
        // daftar penuh tetap muncul.
        $this->assertSame(
            array_column($semua, 'id'),
            $this->barisDirender($respons->getContent())
        );
    }

    public function test_pencarian_mencocokkan_judul_dan_ruang(): void
    {
        $user = $this->buatPengguna();
        $tanggal = $this->tanggalSekolah();
        $this->buatJadwalMinggu($user);

        $semua = self::jadwalHari($user, $tanggal);

        // Dicari berdasarkan potongan judul baris pertama.
        $kata = str($semua[0]['judul'])->before(' ')->lower()->value();

        $respons = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString().'&q='.$kata)
            ->assertOk();

        $harusAda = array_values(array_filter(
            $semua,
            fn (array $baris) => str_contains(strtolower($baris['judul']), $kata)
        ));

        $this->assertSame(
            array_column($harusAda, 'id'),
            $this->barisDirender($respons->getContent())
        );

        // Mencari nama ruang harusnya menemukan jadwal yang memakai ruang itu.
        $responsRuang = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal->toDateString().'&q='.urlencode($semua[0]['ruang']))
            ->assertOk();

        $this->assertContains(
            $semua[0]['id'],
            $this->barisDirender($responsRuang->getContent())
        );
    }

    public function test_pencarian_tidak_ada_hasil_menampilkan_reset_filter(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal?q=pelajaran-yang-tidak-ada')
            ->assertOk()
            ->assertSee('Jadwal Tidak Ditemukan')
            ->assertSee('Reset Filter');
    }

    public function test_hari_libur_menampilkan_empty_state_libur(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal?tanggal='.$this->tanggalLibur()->toDateString())
            ->assertOk()
            ->assertSee('Hari Libur')
            ->assertSee('Kembali ke Hari Ini')
            ->assertDontSee('Reset Filter');
    }

    public function test_kalender_mini_punya_navigasi_bulan(): void
    {
        $bulan = now()->startOfMonth();

        $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal?bulan='.$bulan->format('Y-m'))
            ->assertOk()
            ->assertSee(DaftarJadwal::namaBulan($bulan))
            ->assertSee('bulan='.$bulan->copy()->subMonth()->format('Y-m'), false)
            ->assertSee('bulan='.$bulan->copy()->addMonth()->format('Y-m'), false);
    }

    public function test_bulan_tidak_valid_diabaikan(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal?bulan=2026-13')
            ->assertOk()
            ->assertSee(DaftarJadwal::namaBulan(now()));
    }

    public function test_halaman_jadwal_tanpa_top_bar(): void
    {
        $respons = $this->actingAs($this->buatPengguna())->get('/user/jadwal')->assertOk();

        // Top bar dihapus di halaman ini, jadi pencarian global, tombol
        // notifikasi, dan chip akun tidak boleh ikut terender.
        $respons->assertDontSee('data-app-topbar', false)
            ->assertDontSee('Notifikasi', false)
            ->assertDontSee('Cari pelajaran...', false);

        // Pencarian jadwal tetap ada, tapi yang di dalam panel daftar.
        $respons->assertSee('data-cari-form', false)
            ->assertSee('Cari pelajaran, kelas, atau ruang...', false);
    }

    public function test_menu_sidebar_menunjuk_ke_halaman_jadwal(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal')
            ->assertOk()
            ->assertSee(route('user.jadwal'), false);
    }
}
