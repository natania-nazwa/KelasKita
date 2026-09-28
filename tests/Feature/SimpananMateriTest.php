<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\SimpananMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pintu simpan materi: tombol "Simpan" di kepala Materi Detail dan status
 * simpan di kartu materi.
 *
 *   POST /user/materi-detail/{materi}/simpan  balik status simpan
 *   GET  /user/simpanan/materi                daftar slug yang disimpan
 *
 * Keduanya dipanggil fetch dari resources/js/app.js.
 */
class SimpananMateriTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Natania',
            'email' => 'natania@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatMateri(string $status = Materi::STATUS_PUBLISHED, ?User $pembuat = null): Materi
    {
        $pelajaran = Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'aktif' => true,
        ]);

        return Materi::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => ($pembuat ?? $this->buatPengguna())->getKey(),
            'nama' => 'HTML Dasar',
            'slug' => 'html-dasar',
            'deskripsi' => 'Materi dasar HTML untuk membuat struktur halaman web.',
            'isi' => 'Isi materi.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    public function test_toggle_menyimpan_materi_lalu_melepaskannya(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri(pembuat: $pengguna);

        $this->actingAs($pengguna)
            ->postJson(route('user.materi.simpan', $materi->slug))
            ->assertOk()
            ->assertJson(['tersimpan' => true]);

        $this->assertDatabaseHas('tb_simpanan_materi', [
            'pengguna_id' => $pengguna->getKey(),
            'materi_id' => $materi->getKey(),
        ]);

        $this->actingAs($pengguna)
            ->postJson(route('user.materi.simpan', $materi->slug))
            ->assertOk()
            ->assertJson(['tersimpan' => false]);

        $this->assertDatabaseMissing('tb_simpanan_materi', [
            'pengguna_id' => $pengguna->getKey(),
            'materi_id' => $materi->getKey(),
        ]);
    }

    public function test_toggle_membutuhkan_login(): void
    {
        $materi = $this->buatMateri();

        $this->post(route('user.materi.simpan', $materi->slug))->assertRedirect('/login');
    }

    public function test_toggle_mengembalikan_404_untuk_materi_yang_belum_terbit(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri(Materi::STATUS_DRAFT, $pengguna);

        $this->actingAs($pengguna)
            ->postJson(route('user.materi.simpan', $materi->slug))
            ->assertNotFound();
    }

    public function test_daftar_simpanan_hanya_mengembalikan_slug_pengguna_itu(): void
    {
        $natania = $this->buatPengguna();
        $materi = $this->buatMateri(pembuat: $natania);

        $lain = $this->buatPengguna([
            'nama' => 'Sein',
            'email' => 'sein@example.com',
        ]);

        SimpananMateri::query()->create([
            'pengguna_id' => $natania->getKey(),
            'materi_id' => $materi->getKey(),
        ]);

        $this->actingAs($lain)
            ->get(route('user.simpanan.materi'))
            ->assertOk()
            ->assertJson(['slug' => []]);

        $this->actingAs($natania)
            ->get(route('user.simpanan.materi'))
            ->assertOk()
            ->assertJson(['slug' => ['html-dasar']]);
    }

    public function test_daftar_simpanan_mengabaikan_materi_yang_sudah_tidak_terbit(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri(pembuat: $pengguna);

        SimpananMateri::query()->create([
            'pengguna_id' => $pengguna->getKey(),
            'materi_id' => $materi->getKey(),
        ]);

        $materi->update(['status' => Materi::STATUS_DRAFT]);

        $this->actingAs($pengguna)
            ->get(route('user.simpanan.materi'))
            ->assertOk()
            ->assertJson(['slug' => []]);
    }

    public function test_daftar_simpanan_membutuhkan_login(): void
    {
        $this->get(route('user.simpanan.materi'))->assertRedirect('/login');
    }

    public function test_halaman_detail_menampilkan_status_simpan_pemilik(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri(pembuat: $pengguna);

        SimpananMateri::query()->create([
            'pengguna_id' => $pengguna->getKey(),
            'materi_id' => $materi->getKey(),
        ]);

        $this->actingAs($pengguna)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('Tersimpan');
    }
}
