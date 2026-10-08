<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman "Pengguna" di area admin.
 *
 * Halaman ini paling mudah berbohong dari daftar admin lain: setiap baris
 * menampilkan hitungan karya dan aktivitas, dan hitungan itu bisa salah tanpa
 * halaman terlihat rusak sama sekali. Jadi yang diuji di sini bukan hanya
 * "halamannya terbuka", tapi angkanya benar, filternya benar, dan angka yang
 * belum punya data tidak dikarang jadi nol.
 *
 * Test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini tidak
 * menyentuh database sungguhan.
 */
class PenggunaHalamanTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatAdmin(): User
    {
        return $this->buatPengguna([
            'nama' => 'Admin KelasKita',
            'email' => 'admin@example.com',
            'peran' => User::PERAN_ADMIN,
        ]);
    }

    /**
     * Pengguna yang sudah lewat batas 24 jam sejak terakhir membuka aplikasi.
     *
     * Status aktif dihitung dari kolom "terakhir_aktivitas", bukan lagi kolom
     * boolean yang bisa diisi saat membuat model. Jadi untuk membuat akun
     * nonaktif, waktunya yang digeser ke belakang, bukan "aktif" yang di-set.
     */
    private function buatNonaktif(array $atribut = []): User
    {
        $pengguna = $this->buatPengguna($atribut);

        $pengguna->forceFill([
            'terakhir_aktivitas' => now()->subHours(User::BATAS_AKTIF_JAM + 1),
        ])->save();

        return $pengguna;
    }

    private function buatPelajaran(string $nama = 'Matematika'): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => str($nama)->slug()->value()], [
            'nama' => $nama,
            'deskripsi' => "Deskripsi $nama",
            'aktif' => true,
        ]);
    }

    private function buatMateri(User $pembuat, string $nama): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.Materi::query()->count(),
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
        ]);
    }

    private function buatQuiz(User $pembuat, string $judul): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value().'-'.Quiz::query()->count(),
            'deskripsi' => 'Ringkasan quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }

    /**
     * Pengerjaan yang sudah ditutup, jadi ikut terhitung sebagai selesai.
     */
    private function buatPengerjaanSelesai(User $pengguna, Quiz $quiz, int $nilai): PengerjaanQuiz
    {
        return PengerjaanQuiz::create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => 10,
            'jumlah_dijawab' => 10,
            'jumlah_benar' => (int) round($nilai / 10),
            'jumlah_salah' => 10 - (int) round($nilai / 10),
            'nilai' => $nilai,
            'dimulai_pada' => now()->subMinutes(30),
            'selesai_pada' => now(),
        ]);
    }

    /**
     * Pengerjaan yang masih berjalan: belum ada selesai_pada, jadi nilai yang
     * tersimpan di sini adalah nilai sementara dan tidak boleh ikut dihitung.
     */
    private function buatPengerjaanProses(User $pengguna, Quiz $quiz, int $nilai): PengerjaanQuiz
    {
        return PengerjaanQuiz::create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => 10,
            'jumlah_dijawab' => 5,
            'jumlah_benar' => 5,
            'jumlah_salah' => 0,
            'nilai' => $nilai,
            'dimulai_pada' => now()->subMinutes(5),
            'selesai_pada' => null,
        ]);
    }

    /*
     * =================================================================
     * Akses
     * =================================================================
     */

    public function test_admin_melihat_halaman_pengguna(): void
    {
        $this->buatPengguna();

        $this->actingAs($this->buatAdmin())
            ->get(route('admin.pengguna'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertSee('Budi Santoso')
            ->assertSee('budi@example.com');
    }

    public function test_non_admin_tidak_bisa_membuka_halaman_pengguna(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get(route('admin.pengguna'))
            ->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get(route('admin.pengguna'))->assertRedirect(route('login'));
    }

    /*
     * =================================================================
     * Hitungan karya dan aktivitas
     * =================================================================
     */

    public function test_halaman_tidak_gagal_saat_menghitung_karya_setiap_pengguna(): void
    {
        /*
         * Halaman ini pernah memakai withCount() untuk relasi yang tidak pernah
         * didefinisikan di model User, jadi seluruh halaman gagal dibuka dengan
         * RelationNotFoundException. Test ini yang menahan agar relasi materi,
         * quiz, dan pengerjaanQuiz tidak hilang lagi dari model.
         */
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->buatMateri($siswa, 'Materi Satu');
        $this->buatQuiz($siswa, 'Quiz Satu');

        $detail = $this->actingAs($admin)->get(route('admin.pengguna'))
            ->assertOk()
            ->assertSee('Sari')
            ->viewData('detail');

        $baris = $detail[$siswa->getKey()];

        $this->assertSame(1, $baris['materi']);
        $this->assertSame(1, $baris['quiz']);
    }

    public function test_tabel_tidak_lagi_menampilkan_kolom_karya_dan_peran(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->buatMateri($siswa, 'Materi Satu');
        $this->buatQuiz($siswa, 'Quiz Satu');

        $html = $this->actingAs($admin)->get(route('admin.pengguna'))
            ->assertOk()
            ->getContent();

        // Lima kolom data saja: No, Nama, Email, Status, Bergabung. Peran dan
        // angka karya pindah ke dialog detail, jadi tidak lagi jadi kolom.
        $this->assertSame(
            6,
            substr_count($html, '<th scope="col"'),
            'Harus ada lima kolom data dan satu kolom aksi.',
        );

        $this->assertStringNotContainsString('Karya &amp; Aktivitas', $html);
        $this->assertStringNotContainsString('materi · 1 quiz', $html);
    }

    public function test_detail_dialog_mengambil_angka_karya_dan_aktivitas_yang_benar(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->buatMateri($siswa, 'Materi Satu');
        $this->buatMateri($siswa, 'Materi Dua');
        $quiz = $this->buatQuiz($siswa, 'Quiz Satu');

        $this->buatPengerjaanSelesai($siswa, $quiz, 80);
        $this->buatPengerjaanSelesai($siswa, $quiz, 60);
        $this->buatPengerjaanProses($siswa, $quiz, 100);

        $detail = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('detail');

        $baris = $detail[$siswa->getKey()];

        $this->assertSame(2, $baris['materi']);
        $this->assertSame(1, $baris['quiz']);

        // Tiga pengerjaan, tapi hanya dua yang sudah ditutup.
        $this->assertSame(3, $baris['pengerjaan']);
        $this->assertSame(2, $baris['selesai']);

        // Rata-rata hanya yang selesai: (80 + 60) / 2 = 70. Pengerjaan yang
        // masih berjalan dengan nilai sementara 100 tidak boleh ikut, kalau
        // ikut hasilnya jadi 80 dan Meanya terlihat naik tanpa sebab.
        $this->assertSame('70', $baris['nilai_rata_label']);
        $this->assertSame('Dari 2 quiz yang selesai', $baris['nilai_keterangan']);
    }

    public function test_rata_rata_nilai_tidak_dikarang_jadi_nol_saat_belum_ada_yang_selesai(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->buatPengerjaanProses($siswa, $this->buatQuiz($siswa, 'Quiz Satu'), 100);

        $detail = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('detail');

        $baris = $detail[$siswa->getKey()];

        // Bukan "0": belum ada yang dinilai, jadi belum ada rata-ratanya.
        $this->assertSame('—', $baris['nilai_rata_label']);
        $this->assertSame('Belum ada quiz yang selesai', $baris['nilai_keterangan']);
    }

    public function test_detail_menandai_peran_dan_status_dari_data_bukan_dari_tebakan(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatNonaktif([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);

        $detail = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('detail');

        // Hanya ada dua peran, jadi labelnya persis nilai kolomnya.
        $this->assertSame('User', $detail[$siswa->getKey()]['peran_label']);
        $this->assertFalse($detail[$siswa->getKey()]['aktif']);
        $this->assertSame('Nonaktif', $detail[$siswa->getKey()]['status_label']);

        $this->assertSame('Admin', $detail[$admin->getKey()]['peran_label']);
        $this->assertTrue($detail[$admin->getKey()]['aktif']);
        $this->assertSame('Aktif', $detail[$admin->getKey()]['status_label']);
    }

    public function test_detail_status_menyertakan_keterangan_berapa_lama(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatNonaktif([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);

        $detail = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('detail');

        // "Berapa lama" disusun di server dan ikut dikirim ke dialog detail,
        // supaya teks bahasa Indonesianya punya satu sumber saja.
        $this->assertSame(
            'Terakhir membuka 1 hari lalu',
            $detail[$siswa->getKey()]['aktivitas'],
        );
    }

    public function test_detail_cuma_berisi_pengguna_di_halaman_itu_saja(): void
    {
        $admin = $this->buatAdmin();
        $sari = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        // Satu halaman menampung 15 baris, jadi sebagian pengguna masuk ke
        // halaman dua dan tidak ikut dirender. Kalau peta detail ikut memuatnya,
        // isinya jadi grown tanpa batas dan setiap baris menarik data yang tidak
        // tampil.
        foreach (range(1, 20) as $urutan) {
            $this->buatPengguna([
                'nama' => "Siswa $urutan",
                'email' => "siswa{$urutan}@example.com",
            ]);
        }

        $halaman = $this->actingAs($admin)->get(route('admin.pengguna'));

        $daftar = $halaman->viewData('daftar');
        $detail = $halaman->viewData('detail');

        $idDiHalaman = $daftar->getCollection()->pluck('id')->all();

        $this->assertCount(10, $idDiHalaman);
        $this->assertCount(10, $detail);

        // Peta detail harus persis sama dengan isi halamannya: tidak ada
        // pengguna luar halaman ini yang ikut terambil, dan tidak ada baris di
        // halaman ini yang hilang.
        $this->assertSame([], array_diff(array_keys($detail), $idDiHalaman));
        $this->assertSame([], array_diff($idDiHalaman, array_keys($detail)));

        // Daftar diurutkan terbaru lebih dulu, jadi Sari yang dibuat paling
        // awal ada di halaman dua dan tidak boleh muncul di peta ini.
        $this->assertNotContains($sari->getKey(), array_keys($detail));
    }

    /*
     * =================================================================
     * Pencarian dan filter
     * =================================================================
     */

    public function test_pencarian_menyaring_nama_dan_email(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $this->buatPengguna(['nama' => 'Rina', 'email' => 'rina@example.com']);

        $cariNama = $this->actingAs($admin)->get(route('admin.pengguna', ['q' => 'Sari']));
        $cariNama->assertOk()->assertSee('sari@example.com')->assertDontSee('rina@example.com');

        $cariEmail = $this->actingAs($admin)->get(route('admin.pengguna', ['q' => 'rina@']));
        $cariEmail->assertOk()->assertSee('rina@example.com')->assertDontSee('sari@example.com');
    }

    public function test_filter_status_hanya_menampilkan_akun_yang_cocok(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);
        $this->buatNonaktif([
            'nama' => 'Rina',
            'email' => 'rina@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pengguna', ['status' => 'nonaktif']))
            ->assertOk()
            ->assertSee('rina@example.com')
            ->assertDontSee('sari@example.com');
    }

    public function test_filter_peran_hanya_menampilkan_peran_yang_cocok(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $halaman = $this->actingAs($admin)->get(route('admin.pengguna', ['peran' => 'admin']));

        $halaman->assertOk()
            ->assertSee('admin@example.com')
            ->assertDontSee('sari@example.com');
    }

    public function test_filter_peran_dan_status_bisa_dipakai_bersamaan(): void
    {
        $admin = $this->buatAdmin();

        $this->buatNonaktif([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);

        // Admin nonaktif tidak boleh muncul di sini, sedangkan admin aktif
        // justru boleh: yang dihitung status, bukan perannya.
        $this->actingAs($admin)
            ->get(route('admin.pengguna', ['peran' => 'admin', 'status' => 'nonaktif']))
            ->assertOk()
            ->assertSee('Tidak ada pengguna yang cocok');

        $this->actingAs($admin)
            ->get(route('admin.pengguna', ['peran' => 'user', 'status' => 'nonaktif']))
            ->assertOk()
            ->assertSee('sari@example.com');
    }

    public function test_nilai_filter_asing_kembali_ke_semua_bukan_membuat_halaman_kosong(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $halaman = $this->actingAs($admin)->get(route('admin.pengguna', [
            'peran' => 'ngawur',
            'status' => 'ngawur',
        ]));

        $halaman->assertOk()->assertSee('sari@example.com');

        $this->assertSame('semua', $halaman->viewData('peranAktif'));
        $this->assertSame('semua', $halaman->viewData('statusAktif'));
    }

    public function test_daftar_kosong_menampilkan_empty_state_yang_membedakan_bukan_ada_dari_tidak_cocok(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna();

        $this->actingAs($admin)
            ->get(route('admin.pengguna', ['q' => 'tidak-ada-akun-ini']))
            ->assertOk()
            ->assertSee('Tidak ada pengguna yang cocok');
    }

    /*
     * =================================================================
     * Jumlah di tab dan kartu statistik
     * =================================================================
     */

    public function test_jumlah_di_tab_peran_mengikuti_filter_status(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);
        $this->buatNonaktif([
            'nama' => 'Rina',
            'email' => 'rina@example.com',
        ]);

        // Tab dihitung setelah filter status: dari dua siswa, yang nonaktif
        // cuma satu. Kalau tab ikut menghitung seluruh tabel, angkanya tetap
        // dua padahal yang bisa diklik cuma satu.
        $tab = $this->actingAs($admin)
            ->get(route('admin.pengguna', ['status' => 'nonaktif']))
            ->viewData('tabPeran');

        $this->assertSame(1, $tab['semua']['jumlah']);
        $this->assertSame(0, $tab['admin']['jumlah']);
        $this->assertSame(1, $tab['user']['jumlah']);
    }

    public function test_jumlah_di_tab_status_mengikuti_filter_peran(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);
        $this->buatNonaktif([
            'nama' => 'Rina',
            'email' => 'rina@example.com',
        ]);

        $tab = $this->actingAs($admin)
            ->get(route('admin.pengguna', ['peran' => 'user']))
            ->viewData('tabStatus');

        $this->assertSame(2, $tab['semua']['jumlah']);
        $this->assertSame(1, $tab['aktif']['jumlah']);
        $this->assertSame(1, $tab['nonaktif']['jumlah']);
    }

    public function test_kartu_statistik_membagi_pengguna_aktif_dan_nonaktif_tanpa_tumpang_tindih(): void
    {
        $admin = $this->buatAdmin();

        $this->buatPengguna([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);
        $this->buatNonaktif([
            'nama' => 'Rina',
            'email' => 'rina@example.com',
        ]);

        $statistik = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('statistik');

        $this->assertSame(3, $statistik['total']);
        $this->assertSame(2, $statistik['aktif']);
        $this->assertSame(1, $statistik['nonaktif']);

        // Aktif + nonaktif harus selalu sama dengan totalnya.
        $this->assertSame($statistik['total'], $statistik['aktif'] + $statistik['nonaktif']);
    }

    public function test_kartu_bergabung_hari_ini_hanya_menghitung_pengguna_dari_hari_ini(): void
    {
        $admin = $this->buatAdmin();

        $kemarin = $this->buatPengguna([
            'nama' => 'Kemarin',
            'email' => 'kemarin@example.com',
        ]);
        $kemarin->forceFill(['created_at' => now()->subDay()])->save();

        $this->buatPengguna([
            'nama' => 'HariIni',
            'email' => 'hariini@example.com',
        ]);

        $statistik = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('statistik');

        // Admin dan pengguna hari ini ikut, pengguna kemarin tidak.
        $this->assertSame(2, $statistik['bergabung']);
        $this->assertSame(3, $statistik['total']);
    }

    /*
     * =================================================================
     * Pagination
     * =================================================================
     */

    public function test_daftar_dipotong_lima_belas_baris_per_halaman(): void
    {
        $admin = $this->buatAdmin();

        foreach (range(1, 17) as $urutan) {
            $this->buatPengguna([
                'nama' => "Siswa $urutan",
                'email' => "siswa{$urutan}@example.com",
            ]);
        }

        $daftar = $this->actingAs($admin)->get(route('admin.pengguna'))->viewData('daftar');

        $this->assertSame(10, $daftar->perPage());
        $this->assertSame(18, $daftar->total());
        $this->assertCount(10, $daftar->items());
    }

    public function test_filter_ikut_terbawa_saat_pindah_halaman(): void
    {
        $admin = $this->buatAdmin();

        foreach (range(1, 17) as $urutan) {
            $this->buatPengguna([
                'nama' => "Siswa $urutan",
                'email' => "siswa{$urutan}@example.com",
            ]);
        }

        $halaman = $this->actingAs($admin)->get(route('admin.pengguna', [
            'q' => 'Siswa',
            'peran' => 'user',
            'status' => 'aktif',
            'page' => 2,
        ]));

        $halaman->assertOk();

        // Kalau filter hilang di halaman dua, admin akan melihat semua akun
        // lagi dan mengira filternya tidak bekerja.
        $daftar = $halaman->viewData('daftar');
        $this->assertSame(17, $daftar->total());

        $html = $halaman->getContent();
        $this->assertStringContainsString('q=Siswa', $html);
        $this->assertStringContainsString('peran=user', $html);
    }

    /*
     * =================================================================
     * Aksi: hapus saja, tanpa ubah peran dan tanpa tombol status
     * =================================================================
     */

    public function test_halaman_menawarkan_aksi_hapus_tanpa_ubah_peran_atau_status(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $html = $this->actingAs($admin)->get(route('admin.pengguna'))->assertOk()->getContent();

        // Akun orang lain bisa dihapus.
        $this->assertStringContainsString('data-hapus-buka', $html);
        $this->assertStringContainsString(route('admin.pengguna.destroy', $siswa), $html);

        // Status aktif tidak lagi bisa diubah manual: tidak ada tombolnya.
        $this->assertStringNotContainsString('Nonaktifkan', $html);
        $this->assertStringNotContainsString('Aktifkan', $html);

        // Ubah peran tetap sengaja tidak ada.
        $this->assertStringNotContainsString('Ubah Peran', $html);
    }

    public function test_akun_sendiri_tidak_ditawarkan_tombol_hapus(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $html = $this->actingAs($admin)->get(route('admin.pengguna'))->assertOk()->getContent();

        // Akun admin sendiri tidak boleh punya jalur hapus.
        $this->assertStringNotContainsString(
            route('admin.pengguna.destroy', $admin),
            $html,
        );

        // Alasannya ditulis supaya tidak terlihat seperti tombol yang hilang.
        $this->assertStringContainsString('Akun Anda sendiri tidak bisa dihapus.', $html);
    }
}
