<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Materi + form tambah materi.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data
 * di sini tidak menyentuh database Supabase sungguhan.
 */
class MateriHalamanTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatPelajaran(string $nama, string $slug): Pelajaran
    {
        return Pelajaran::create([
            'nama' => $nama,
            'slug' => $slug,
            'deskripsi' => "Deskripsi $nama",
            'aktif' => true,
        ]);
    }

    private function buatMateri(Pelajaran $pelajaran, ?User $pembuat, string $nama, string $isi = 'Isi materi yang cukup panjang untuk sebuah pengujian.'): Materi
    {
        return Materi::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $pembuat?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value(),
            'deskripsi' => "Ringkasan $nama",
            'isi' => $isi,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);
    }

    public function test_halaman_materi_menampilkan_kartu_dari_database(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertSee('Belajar Blade')
            ->assertSee('Pemrograman');
    }

    public function test_materi_tidak_aktif_tidak_muncul(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $disembunyikan = $this->buatMateri($pelajaran, $user, 'Materi Rahasia');
        $disembunyikan->update(['aktif' => false]);

        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertDontSee('Materi Rahasia');
    }

    public function test_pencarian_menyaring_berdasarkan_judul(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatMateri($pelajaran, $user, 'Belajar Blade');
        $this->buatMateri($pelajaran, $user, 'Belajar Tailwind');

        $this->actingAs($user)
            ->get('/user/materi?q=Tailwind')
            ->assertOk()
            ->assertSee('Belajar Tailwind')
            ->assertDontSee('Belajar Blade');
    }

    public function test_filter_kategori_menyaring_materi(): void
    {
        $user = $this->buatPengguna();
        $kode = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $desain = $this->buatPelajaran('Desain Web', 'desain-web');
        $this->buatMateri($kode, $user, 'Belajar Blade');
        $this->buatMateri($desain, $user, 'Tipografi Modern');

        $this->actingAs($user)
            ->get('/user/materi?kategori=desain-web')
            ->assertOk()
            ->assertSee('Tipografi Modern')
            ->assertDontSee('Belajar Blade');
    }

    public function test_halaman_materi_juga_menampilkan_materi_milik_pengguna_lain(): void
    {
        $budi = $this->buatPengguna();
        $siti = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatMateri($pelajaran, $budi, 'Materi Budi');
        $this->buatMateri($pelajaran, $siti, 'Materi Siti');

        // Halaman Materi menampilkan seluruh materi, bukan hanya milik sendiri.
        $this->actingAs($budi)
            ->get('/user/materi')
            ->assertOk()
            ->assertSee('Materi Budi')
            ->assertSee('Materi Siti');
    }

    public function test_halaman_materi_tidak_perlunya_tombol_tambah(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        // Membuat materi hanya lewat menu "Karya Saya", jadi halaman Materi
        // tidak lagi punya tombol tambah.
        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertDontSee(route('user.materi.tambah'), false);
    }

    public function test_halaman_materi_membuka_form_tambah(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->get('/user/materi/tambah')
            ->assertOk()
            ->assertSee('Tambah Materi');
    }

    public function test_materi_baru_tersimpan_dan_muncul_di_karya_saya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->post('/user/materi/tambah', [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Materi Baru Saya',
                'deskripsi' => 'Deskripsi singkat.',
                'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
                'tingkat_kesulitan' => 'Mudah',
            ])
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tb_materi', [
            'nama' => 'Materi Baru Saya',
            'slug' => 'materi-baru-saya',
            'dibuat_oleh' => $user->getKey(),
            'pelajaran_id' => $pelajaran->id,
            'aktif' => true,
        ]);

        // Materi milik sendiri tetap tampil di halaman Materi umum.
        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertSee('Materi Baru Saya');

        $this->actingAs($user)
            ->get('/user/karya-saya')
            ->assertOk()
            ->assertSee('Materi Baru Saya');
    }

    public function test_slug_duplikat_diberi_akhiran_otomatis(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatMateri($pelajaran, $user, 'Judul Kembar');

        $this->actingAs($user)->post('/user/materi/tambah', [
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Judul Kembar',
            'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
            'tingkat_kesulitan' => 'Mudah',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_materi', ['slug' => 'judul-kembar-2']);
    }

    public function test_form_menolak_input_tidak_valid(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->post('/user/materi/tambah', [
                'pelajaran_id' => null,
                'nama' => '',
                'isi' => 'pendek',
                'tingkat_kesulitan' => 'Tidak Dikenal',
            ])
            ->assertSessionHasErrors(['pelajaran_id', 'nama', 'isi', 'tingkat_kesulitan']);

        $this->assertDatabaseCount('tb_materi', 0);
    }

    public function test_dibuat_oleh_selalu_mengikuti_pengguna_yang_login(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');

        // Materi yang dibuat orang lain tidak boleh bisa "diambil" lewat
        // request: dibuat_oleh selalu mengikuti pengguna yang login.
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);

        $this->actingAs($user)->post('/user/materi/tambah', [
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'PTakingan Materi',
            'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
            'tingkat_kesulitan' => 'Mudah',
            'dibuat_oleh' => $orangLain->getKey(),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_materi', [
            'nama' => 'PTakingan Materi',
            'dibuat_oleh' => $user->getKey(),
        ]);
        $this->assertDatabaseMissing('tb_materi', [
            'nama' => 'PTakingan Materi',
            'dibuat_oleh' => $orangLain->getKey(),
        ]);
    }

    public function test_halaman_detail_materi_membuka_dan_menampilkan_rekomendasi(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $ini = $this->buatMateri($pelajaran, $user, 'Materi Utama');
        $rekomendasi = $this->buatMateri($pelajaran, $user, 'Materi Rekomendasi');

        $this->actingAs($user)
            ->get(route('user.materi.detail', $ini->slug))
            ->assertOk()
            ->assertSee('Materi Utama')
            ->assertSee('Materi Rekomendasi');
    }

    public function test_detail_materi_tidak_aktif_menghasilkan_404(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $materi = $this->buatMateri($pelajaran, $user, 'Materi Disembunyikan');
        $materi->update(['aktif' => false]);

        $this->actingAs($user)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertNotFound();
    }

    public function test_form_tambah_membutuhkan_login(): void
    {
        $this->get('/user/materi/tambah')->assertRedirect('/login');
        $this->post('/user/materi/tambah')->assertRedirect('/login');
    }
}
