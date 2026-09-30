<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman "Kelola Materi" di area admin.
 *
 * Halaman ini adalah papan untuk semua materi dari semua status: tidak ada
 * keputusan setujui/tolak, tapi ada ringkasan jumlah, filter status dan
 * pelajaran, pencarian yang menjangkau pembuat, serta halaman detail untuk
 * membaca isi materi apa pun.
 */
class MateriKelolaHalamanTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatAdmin(): User
    {
        return $this->buatPengguna([
            'nama' => 'Admin',
            'email' => 'admin@example.com',
            'peran' => User::PERAN_ADMIN,
        ]);
    }

    private function buatPelajaran(string $nama = 'Pemrograman', string $slug = 'pemrograman'): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => $slug], [
            'nama' => $nama,
            'deskripsi' => "Deskripsi $nama",
            'aktif' => true,
        ]);
    }

    private function buatMateri(
        ?User $pemilik,
        string $status,
        string $nama = 'Materi Uji',
        ?Pelajaran $pelajaran = null,
        string $isi = 'Isi materi untuk pengujian halaman kelola.',
    ): Materi {
        $this->urutan++;

        return Materi::create([
            'pelajaran_id' => ($pelajaran ?? $this->buatPelajaran())->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.$this->urutan,
            'deskripsi' => 'Ringkasan materi.',
            'isi' => $isi,
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    public function test_halaman_kelola_menampilkan_ringkasan_dan_semua_status(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Menunggu');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Terbit');
        $this->buatMateri($pemilik, Materi::STATUS_REJECTED, 'Materi Ditolak');
        $this->buatMateri($pemilik, Materi::STATUS_DRAFT, 'Materi Draft');

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Kelola Semua Materi')
            ->assertSee('Total Materi')
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Dipublikasikan')
            ->assertSee('Ditolak')
            ->assertSee('Materi Menunggu')
            ->assertSee('Materi Terbit')
            ->assertSee('Materi Ditolak')
            ->assertSee('Materi Draft')
            ->assertSee('Menunggu Persetujuan');
    }

    public function test_filter_status_mengerucutkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Menunggu');
        $this->buatMateri($pemilik, Materi::STATUS_REJECTED, 'Materi Ditolak');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['status' => Materi::STATUS_REJECTED]))
            ->assertOk()
            ->assertSee('Materi Ditolak')
            ->assertDontSee('Materi Menunggu');
    }

    public function test_filter_pelajaran_mengerucutkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $matematika = $this->buatPelajaran('Matematika', 'matematika');
        $biologi = $this->buatPelajaran('Biologi', 'biologi');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Matematika', $matematika);
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Biologi', $biologi);

        $this->actingAs($admin)
            ->get(route('admin.materi', ['pelajaran' => 'matematika']))
            ->assertOk()
            ->assertSee('Materi Matematika')
            ->assertDontSee('Materi Biologi');
    }

    public function test_pencarian_menemukan_materi_lewat_judul_dan_isi(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Katakana', null, 'Isi ini memuat kata khusus xilofon.');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Lain');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'xilofon']))
            ->assertOk()
            ->assertSee('Materi Katakana')
            ->assertDontSee('Materi Lain');
    }

    public function test_pencarian_menemukan_materi_lewat_nama_pembuat(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso']);
        $siti = $this->buatPengguna(['nama' => 'Siti Aminah', 'email' => 'siti@example.com']);
        $this->buatMateri($budi, Materi::STATUS_PUBLISHED, 'Materi Budi');
        $this->buatMateri($siti, Materi::STATUS_PUBLISHED, 'Materi Siti');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'Santoso']))
            ->assertOk()
            ->assertSee('Materi Budi')
            ->assertDontSee('Materi Siti');
    }

    public function test_empty_state_muncul_saat_belum_ada_materi(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Belum ada materi');
    }

    public function test_empty_state_muncul_saat_filter_tidak_mencocokkan(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED);

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada materi yang cocok');
    }

    public function test_daftar_dipaginasi_dua_belas_per_halaman(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        for ($i = 1; $i <= 13; $i++) {
            $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Paginasi '.$i);
        }

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Materi Paginasi 1')
            ->assertSee('Materi Paginasi 12')
            ->assertDontSee('Materi Paginasi 13');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['page' => 2]))
            ->assertOk()
            ->assertSee('Materi Paginasi 13')
            ->assertDontSee('Materi Paginasi 12');
    }

    public function test_detail_dapat_dibuka_untuk_materi_dari_semua_status(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        foreach ([
            Materi::STATUS_PENDING,
            Materi::STATUS_PUBLISHED,
            Materi::STATUS_REJECTED,
            Materi::STATUS_DRAFT,
        ] as $status) {
            $materi = $this->buatMateri($pemilik, $status, 'Materi-'.$status);

            $this->actingAs($admin)
                ->get(route('admin.materi.show', $materi->slug))
                ->assertOk()
                ->assertSee('Materi-'.$status);
        }
    }

    public function test_detail_menampilkan_isi_materi_dan_metadata(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $materi = $this->buatMateri(
            $pemilik,
            Materi::STATUS_PENDING,
            'Materi Uji Detail',
            null,
            "# Bab Satu\n\nParagraf pembuka untuk bab ini.\n\n```php\n\$kode = 'contoh';\n```",
        );

        $this->actingAs($admin)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertOk()
            ->assertSee('Materi Uji Detail')
            ->assertSee('Menunggu Persetujuan')
            ->assertSee('Bab Satu')
            ->assertSee('Paragraf pembuka untuk bab ini.')
            ->assertSee('$kode = &#039;contoh&#039;;', false);
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_kelola_dan_detail(): void
    {
        $user = $this->buatPengguna();
        $pemilik = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $materi = $this->buatMateri($pemilik, Materi::STATUS_PENDING);

        $this->actingAs($user)
            ->get('/admin/materi')
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login_pada_halaman_kelola_dan_detail(): void
    {
        $materi = $this->buatMateri(null, Materi::STATUS_DRAFT);

        $this->get('/admin/materi')->assertRedirect(route('login'));

        $this->get(route('admin.materi.show', $materi->slug))->assertRedirect(route('login'));
    }
}
