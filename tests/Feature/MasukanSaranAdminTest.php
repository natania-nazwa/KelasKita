<?php

namespace Tests\Feature;

use App\Models\MasukanSaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman "Masukan & Saran" di area admin.
 *
 * Yang diuji di sini adalah jalur bacanya: daftar, pencarian, filter status,
 * isi dialog detail, perubahan status tindak lanjut, dan penyajian lampiran.
 * Penjagaan akses ikut diuji karena halaman ini memuat isi kiriman pengguna
 * beserta email pengirimnya, jadi tidak boleh terbuka untuk selain admin.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml) dan disk publik
 * palsu, jadi tidak menyentuh database maupun berkas sungguhan.
 */
class MasukanSaranAdminTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Natania Nazwa Gisella',
            'email' => 'natania@gmail.com',
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

    private function buatMasukan(
        User $pengguna,
        string $judul,
        string $status = MasukanSaran::STATUS_BARU,
        ?string $lampiran = null,
    ): MasukanSaran {
        return MasukanSaran::create([
            'pengguna_id' => $pengguna->getKey(),
            'judul' => $judul,
            'detail' => 'Detail untuk '.$judul.'.',
            'status' => $status,
            'lampiran' => $lampiran,
        ]);
    }

    public function test_halaman_butuh_login_dan_hanya_admin(): void
    {
        $this->get(route('admin.masukan-saran'))->assertRedirect(route('login'));

        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)->get(route('admin.masukan-saran'))->assertForbidden();
        $this->actingAs($this->buatAdmin())->get(route('admin.masukan-saran'))->assertOk();
    }

    public function test_menu_sidebar_admin_menampilkan_masukan_dan_saran(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Masukan & Saran')
            ->assertSee(route('admin.masukan-saran'), false);

        // Menu ini bukan milik sidebar pengguna, jadi tautannya tidak pernah
        // muncul di halaman pengguna biasa.
        $this->actingAs($this->buatPengguna())
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.masukan-saran'), false);
    }

    public function test_halaman_menampilkan_kiriman_dan_statistik(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $this->buatMasukan($budi, 'Tombol simpan sulit ditekan');
        $this->buatMasukan($budi, 'Warna teks kurang kontras', MasukanSaran::STATUS_DIPROSES);
        $this->buatMasukan($budi, 'Tambah fitur pencarian', MasukanSaran::STATUS_SELESAI);

        $halaman = $this->actingAs($admin)
            ->get(route('admin.masukan-saran'))
            ->assertOk()
            ->assertSee('Tombol simpan sulit ditekan')
            ->assertSee('Budi Santoso')
            ->assertSee('Sudah Ditangani');

        $statistik = $halaman->viewData('statistik');

        $this->assertSame(3, $statistik['total']);
        $this->assertSame(1, $statistik['baru']);
        $this->assertSame(1, $statistik['selesai']);
    }

    public function test_pencarian_menyaring_per_judul_dan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);
        $rina = $this->buatPengguna(['nama' => 'Rina Wati', 'email' => 'rina@example.com']);

        $this->buatMasukan($budi, 'Tombol simpan sulit ditekan');
        $this->buatMasukan($rina, 'Warna teks kurang kontras');

        $this->actingAs($admin)
            ->get(route('admin.masukan-saran', ['q' => 'tombol']))
            ->assertOk()
            ->assertSee('Tombol simpan sulit ditekan')
            ->assertDontSee('Warna teks kurang kontras');

        $this->actingAs($admin)
            ->get(route('admin.masukan-saran', ['q' => 'Rina']))
            ->assertOk()
            ->assertSee('Warna teks kurang kontras')
            ->assertDontSee('Tombol simpan sulit ditekan');
    }

    public function test_filter_status_menyaring_dan_menghitung_tab(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $this->buatMasukan($budi, 'Kiriman baru');
        $this->buatMasukan($budi, 'Kiriman diproses', MasukanSaran::STATUS_DIPROSES);
        $this->buatMasukan($budi, 'Kiriman selesai', MasukanSaran::STATUS_SELESAI);

        $halaman = $this->actingAs($admin)
            ->get(route('admin.masukan-saran', ['status' => 'baru']))
            ->assertOk()
            ->assertSee('Kiriman baru')
            ->assertDontSee('Kiriman diproses')
            ->assertDontSee('Kiriman selesai');

        $tab = $halaman->viewData('tabStatus');

        $this->assertSame(3, $tab['semua']['jumlah']);
        $this->assertSame(1, $tab['baru']['jumlah']);
        $this->assertSame(1, $tab['diproses']['jumlah']);
        $this->assertSame(1, $tab['selesai']['jumlah']);

        // Nilai status asing jatuh ke bawaan "semua", tidak membuat halaman
        // kosong tanpa penjelasan.
        $this->actingAs($admin)
            ->get(route('admin.masukan-saran', ['status' => 'ngawur']))
            ->assertOk()
            ->assertSee('Kiriman baru');
    }

    public function test_detail_map_berisi_isi_dan_tujuan_status(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $masukan = $this->buatMasukan($budi, 'Tombol simpan sulit ditekan');

        $detail = $this->actingAs($admin)
            ->get(route('admin.masukan-saran'))
            ->assertOk()
            ->viewData('detail');

        $baris = $detail[$masukan->getKey()];

        $this->assertSame('Tombol simpan sulit ditekan', $baris['judul']);
        $this->assertSame('Detail untuk Tombol simpan sulit ditekan.', $baris['detail']);
        $this->assertSame('Budi Santoso', $baris['nama']);
        $this->assertSame('budi@example.com', $baris['email']);
        $this->assertSame(MasukanSaran::STATUS_BARU, $baris['status']);
        $this->assertSame('Baru', $baris['status_label']);
        $this->assertSame('abu', $baris['status_nada']);
        $this->assertNull($baris['lampiran']);
        $this->assertNull($baris['lampiran_url']);
        $this->assertSame(route('admin.masukan-saran.status', $masukan), $baris['status_url']);
    }

    public function test_status_bisa_diperbarui_dan_tercatat(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $masukan = $this->buatMasukan($budi, 'Tombol simpan sulit ditekan');

        $this->actingAs($admin)
            ->from(route('admin.masukan-saran'))
            ->put(route('admin.masukan-saran.status', $masukan), ['status' => MasukanSaran::STATUS_SELESAI])
            ->assertRedirect(route('admin.masukan-saran'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', 'Status diperbarui');

        $this->assertDatabaseHas('tb_masukan_saran', [
            'id' => $masukan->getKey(),
            'status' => MasukanSaran::STATUS_SELESAI,
        ]);
    }

    public function test_status_tidak_sah_ditolak_dan_tidak_mengubah_baris(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $masukan = $this->buatMasukan($budi, 'Tombol simpan sulit ditekan');

        $this->actingAs($admin)
            ->from(route('admin.masukan-saran'))
            ->put(route('admin.masukan-saran.status', $masukan), ['status' => 'ngawur'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('tb_masukan_saran', [
            'id' => $masukan->getKey(),
            'status' => MasukanSaran::STATUS_BARU,
        ]);
    }

    public function test_hanya_admin_yang_bisa_mengubah_status(): void
    {
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);
        $masukan = $this->buatMasukan($budi, 'Tombol simpan sulit ditekan');

        $this->actingAs($budi)
            ->put(route('admin.masukan-saran.status', $masukan), ['status' => MasukanSaran::STATUS_SELESAI])
            ->assertForbidden();

        $this->assertDatabaseHas('tb_masukan_saran', [
            'id' => $masukan->getKey(),
            'status' => MasukanSaran::STATUS_BARU,
        ]);
    }

    public function test_lampiran_disajikan_untuk_admin_dan_404_kalau_tidak_ada(): void
    {
        Storage::fake('public');

        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        Storage::disk('public')->put('masukan-saran/bukti.png', 'isi-gambar');

        $berlampiran = $this->buatMasukan($budi, 'Ada tangkapan layar', MasukanSaran::STATUS_BARU, 'masukan-saran/bukti.png');
        $tanpaLampiran = $this->buatMasukan($budi, 'Tanpa lampiran');

        $respons = $this->actingAs($admin)
            ->get(route('admin.masukan-saran.lampiran', $berlampiran))
            ->assertOk();

        $this->assertStringContainsString('inline', (string) $respons->headers->get('content-disposition'));

        $this->actingAs($admin)
            ->get(route('admin.masukan-saran.lampiran', $tanpaLampiran))
            ->assertNotFound();
    }

    public function test_lampiran_tidak_terbuka_untuk_selain_admin(): void
    {
        Storage::fake('public');

        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        Storage::disk('public')->put('masukan-saran/bukti.png', 'isi-gambar');

        $masukan = $this->buatMasukan($budi, 'Ada tangkapan layar', MasukanSaran::STATUS_BARU, 'masukan-saran/bukti.png');

        $this->actingAs($budi)
            ->get(route('admin.masukan-saran.lampiran', $masukan))
            ->assertForbidden();
    }
}
