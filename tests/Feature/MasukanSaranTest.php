<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman "Masukan & Saran": mengirim masukan atau saran ke pengelola.
 *
 * Yang diuji di sini:
 *   1. Halaman hanya bisa dibuka setelah login, dan formnya benar-benar
 *      tampil.
 *   2. Kiriman tersimpan dengan pemilik dari sesi login, bukan dari field
 *      form, supaya tidak ada jalan mengirim atas nama orang lain.
 *   3. Judul dan detail wajib serta punya batas panjang.
 *   4. Lampiran opsional, boleh gambar atau PDF, dan benar-benar tersimpan
 *      di disk publik; format salah dan berkas kelewat besar ditolak.
 *   5. Baris ikut terhapus bersama akun pemiliknya.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml) dan disk publik
 * palsu, jadi tidak menyentuh database maupun berkas sungguhan.
 */
class MasukanSaranTest extends TestCase
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

    public function test_halaman_dan_kiriman_membutuhkan_login(): void
    {
        $this->get('/user/masukan-saran')->assertRedirect('/login');
        $this->post('/user/masukan-saran', [])->assertRedirect('/login');
    }

    public function test_halaman_menampilkan_form_masukan(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get(route('user.masukan-saran'))
            ->assertOk()
            ->assertSee('Masukan & Saran')
            ->assertSee('name="judul"', false)
            ->assertSee('name="detail"', false)
            ->assertSee('name="lampiran"', false)
            ->assertSee('Kirim Masukan');
    }

    public function test_pesan_sukses_masukan_memakai_kartu_toast_yang_bisa_ditutup(): void
    {
        $user = $this->buatPengguna();

        // Halaman Masukan & Saran dulu punya kartu sukses inline tanpa
        // tombol tutup dan tanpa batas waktu. Sekarang pesannya memakai
        // kartu toast yang sama dengan halaman lain: ada kait data-toast
        // dan tombol tutup yang dipakai resources/js/app.js untuk memasang
        // timer lima detik.
        $respons = $this->actingAs($user)
            ->withSession(['sukses' => 'Terima kasih! Masukan dan saranmu sudah kami terima.'])
            ->get(route('user.masukan-saran'))
            ->assertOk();

        $respons->assertSee('Terima kasih! Masukan dan saranmu sudah kami terima.')
            ->assertSee('data-toast', false)
            ->assertSee('data-toast-tutup', false);
    }

    public function test_masukan_tersimpan_dengan_pemilik_dari_sesi_login(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Tombol simpan sulit ditekan',
                'detail' => 'Di layar kecil tombolnya ketutup navigasi bawah.',
            ])
            ->assertRedirect(route('user.masukan-saran'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', 'Terima kasih! Masukan dan saranmu sudah kami terima.');

        $this->assertDatabaseHas('tb_masukan_saran', [
            'pengguna_id' => $user->getKey(),
            'judul' => 'Tombol simpan sulit ditekan',
            'detail' => 'Di layar kecil tombolnya ketutup navigasi bawah.',
            'lampiran' => null,
        ]);
    }

    public function test_pemilik_tidak_bisa_dipalsukan_lewat_field_form(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Budi', 'email' => 'budi@example.com']);

        $this->actingAs($user)
            ->post(route('user.masukan-saran.store'), [
                'pengguna_id' => $orangLain->getKey(),
                'judul' => 'Judul',
                'detail' => 'Detail.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_masukan_saran', [
            'pengguna_id' => $user->getKey(),
            'judul' => 'Judul',
        ]);

        $this->assertDatabaseMissing('tb_masukan_saran', [
            'pengguna_id' => $orangLain->getKey(),
        ]);
    }

    public function test_judul_dan_detail_wajib_diisi(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.masukan-saran'))
            ->post(route('user.masukan-saran.store'), ['judul' => '', 'detail' => ''])
            ->assertSessionHasErrors(['judul', 'detail']);

        $this->assertDatabaseCount('tb_masukan_saran', 0);
    }

    public function test_judul_dan_detail_punya_batas_panjang(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.masukan-saran'))
            ->post(route('user.masukan-saran.store'), [
                'judul' => str_repeat('a', 151),
                'detail' => str_repeat('b', 501),
            ])
            ->assertSessionHasErrors(['judul', 'detail']);

        $this->assertDatabaseCount('tb_masukan_saran', 0);
    }

    public function test_lampiran_opsional(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Tanpa lampiran',
                'detail' => 'Tidak ada berkas yang dilampirkan.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_masukan_saran', [
            'judul' => 'Tanpa lampiran',
            'lampiran' => null,
        ]);
    }

    public function test_lampiran_gambar_bisa_diunggah(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Ada tangkapan layar',
                'detail' => 'Lihat lampirannya ya.',
                'lampiran' => UploadedFile::fake()->image('bukti.png', 800, 600),
            ])
            ->assertSessionHasNoErrors();

        $tersimpan = $user->fresh();

        $baris = $tersimpan->masukanSaran()->firstOrFail();

        $this->assertStringStartsWith('masukan-saran/', $baris->lampiran);
        Storage::disk('public')->assertExists($baris->lampiran);
    }

    public function test_lampiran_pdf_bisa_diunggah(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Ada dokumen',
                'detail' => 'Lampirannya berupa PDF.',
                'lampiran' => UploadedFile::fake()->create('laporan.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $baris = $user->fresh()->masukanSaran()->firstOrFail();

        $this->assertStringStartsWith('masukan-saran/', $baris->lampiran);
        Storage::disk('public')->assertExists($baris->lampiran);
    }

    public function test_lampiran_berformat_salah_ditolak(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.masukan-saran'))
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Lampiran aneh',
                'detail' => 'Berkas yang tidak diizinkan.',
                'lampiran' => UploadedFile::fake()->create('virus.zip', 100, 'application/zip'),
            ])
            ->assertSessionHasErrors('lampiran');

        $this->assertDatabaseCount('tb_masukan_saran', 0);
    }

    public function test_lampiran_terlalu_besar_ditolak(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.masukan-saran'))
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Lampiran besar',
                'detail' => 'Ukurannya melebihi batas.',
                // 6000 KB = 6MB, di atas batas 5MB.
                'lampiran' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('lampiran');

        $this->assertDatabaseCount('tb_masukan_saran', 0);
    }

    public function test_masukan_ikut_terhapus_bersama_akun(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post(route('user.masukan-saran.store'), [
                'judul' => 'Masukan sementara',
                'detail' => 'Ikut hilang bersama akun.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tb_masukan_saran', 1);

        $user->delete();

        $this->assertDatabaseCount('tb_masukan_saran', 0);
    }
}
