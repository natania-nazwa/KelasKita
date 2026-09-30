<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur persetujuan materi oleh admin.
 *
 * Yang diuji di sini adalah inti dari fitur ini: materi buatan pengguna tidak
 * pernah tayang sebelum disetujui, admin memutuskan lewat satu tempat saja,
 * dan materi yang sudah ditolak dua kali tidak bisa masuk daftar tunggu lagi.
 *
 * Test ini sengaja tidak mengulang aturan validasi field yang sudah diuji
 * di MateriHalamanTest; yang di sini hanya percabangan status.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml).
 */
class MateriPersetujuanTest extends TestCase
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

    private function buatMateri(?User $pemilik, string $status, string $nama = 'Materi Uji'): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran('Matematika', 'matematika')->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.Materi::query()->count(),
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Materi Diajukan',
            'isi' => 'Isi materi yang sudah lebih dari dua puluh karakter.',
            'tingkat_kesulitan' => 'Mudah',
        ], $tambahan);
    }

    public function test_materi_baru_tidak_langsung_tayang_walau_tombol_persetujuan_ditekan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)->post('/user/materi/tambah', $this->dataForm($pelajaran, [
            'publikasikan' => 1,
        ]))->assertSessionHasNoErrors();

        $materi = Materi::query()->sole();

        $this->assertSame(Materi::STATUS_PENDING, $materi->status);

        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertSee('Materi belum ditemukan');
    }

    public function test_admin_menyetujui_materi_lalu_materi_tayang(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING);

        $this->actingAs($admin)
            ->from(route('admin.materi', ['status' => Materi::STATUS_PENDING]))
            ->post(route('admin.materi.setujui', $materi->slug))
            ->assertRedirect(route('admin.materi', ['status' => Materi::STATUS_PENDING]))
            ->assertSessionHas('sukses');

        $materi->refresh();

        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->status);
        $this->assertNotNull($materi->dipublish_pada);
    }

    public function test_materi_ditolak_menyimpan_alasan_dan_menghitung_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING);

        $this->actingAs($admin)
            ->post(route('admin.materi.tolak', $materi->slug), [
                'alasan' => 'Bab 3 belum ada contoh kode.',
            ])
            ->assertRedirect();

        $materi->refresh();

        $this->assertSame(Materi::STATUS_REJECTED, $materi->status);
        $this->assertSame('Bab 3 belum ada contoh kode.', $materi->catatan_admin);
        $this->assertSame(1, $materi->jumlah_ditolak);
    }

    public function test_penolakan_tanpa_alasan_ditolak_dengan_pesan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING);

        $this->actingAs($admin)
            ->post(route('admin.materi.tolak', $materi->slug), ['alasan' => ''])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(Materi::STATUS_PENDING, $materi->refresh()->status);
    }

    public function test_pengajuan_ulang_wajib_disertai_catatan_pendukung(): void
    {
        $user = $this->buatPengguna();
        $materi = $this->buatMateri($user, Materi::STATUS_REJECTED);
        $materi->tolak('Bab 3 belum lengkap.');
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'publikasikan' => 1,
            ]))
            ->assertSessionHasErrors('catatan_pengajuan');

        $this->assertSame(Materi::STATUS_REJECTED, $materi->refresh()->status);
    }

    public function test_pengajuan_ulang_dengan_catatan_masuk_ke_daftar_tunggu(): void
    {
        $user = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($user, Materi::STATUS_REJECTED);
        $materi->tolak('Bab 3 belum lengkap.');
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'publikasikan' => 1,
                'catatan_pengajuan' => 'Bab 3 sudah saya lengkapi dengan contoh kode.',
            ]))
            ->assertSessionHasNoErrors();

        $materi->refresh();

        $this->assertSame(Materi::STATUS_PENDING, $materi->status);
        $this->assertNull($materi->catatan_admin, 'Catatan penolakan lama harus dibersihkan saat mengajukan ulang.');
        $this->assertSame(
            'Bab 3 sudah saya lengkapi dengan contoh kode.',
            $materi->catatan_pengajuan,
        );

        $this->actingAs($admin)->get('/admin/materi')->assertOk()->assertSee($materi->nama);
    }

    public function test_materi_yang_sudah_ditolak_dua_kali_tidak_bisa_diajukan_lagi(): void
    {
        $user = $this->buatPengguna();
        $materi = $this->buatMateri($user, Materi::STATUS_REJECTED);
        $materi->tolak('Bab 3 belum lengkap.');
        $materi->tolak('Bab 4 masih kosong.');
        $pelajaran = $this->buatPelajaran();

        $this->assertFalse($materi->bolehDiajukan());
        $this->assertSame(0, $materi->sisaPengajuan());

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'publikasikan' => 1,
                'catatan_pengajuan' => 'Semua bab sudah diperbaiki.',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Materi::STATUS_REJECTED, $materi->refresh()->status);
    }

    public function test_revisi_materi_terbit_kembali_menunggu_dan_berhenti_tayang(): void
    {
        $user = $this->buatPengguna();
        $materi = $this->buatMateri($user, Materi::STATUS_PUBLISHED);
        $materi->setujui();
        $pelajaran = $this->buatPelajaran();

        $this->assertTrue($materi->bolehDiajukan());

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'publikasikan' => 1,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']));

        $materi->refresh();

        $this->assertSame(Materi::STATUS_PENDING, $materi->status);
        $this->assertNull($materi->dipublish_pada, 'Materi yang menunggu keputusan tidak boleh punya tanggal terbit.');

        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertSee('Materi belum ditemukan');
    }

    public function test_catatan_pendukung_hanya_tampil_pada_materi_yang_ditolak(): void
    {
        $user = $this->buatPengguna();

        $draft = $this->buatMateri($user, Materi::STATUS_DRAFT, 'Materi Draft');
        $terbit = $this->buatMateri($user, Materi::STATUS_PUBLISHED, 'Materi Terbit');
        $ditolak = $this->buatMateri($user, Materi::STATUS_REJECTED, 'Materi Ditolak');
        $ditolak->tolak('Bab 3 belum lengkap.');

        // Materi yang belum pernah dinilai admin tidak dimintai catatan,
        // jadi isian yang bisa menyesatkan itu tidak ikut dirender.
        foreach ([$draft, $terbit] as $materi) {
            $this->actingAs($user)
                ->get(route('user.materi.edit', $materi->slug))
                ->assertOk()
                ->assertDontSee('name="catatan_pengajuan"', false)
                ->assertDontSee('data-catatan-isian', false);
        }

        $halaman = $this->actingAs($user)
            ->get(route('user.materi.edit', $ditolak->slug))
            ->assertOk()
            ->assertSee('Alasan ditolak admin')
            ->assertSee('name="catatan_pengajuan"', false)
            ->assertSee('data-catatan-isian', false)
            ->baseResponse->getContent();

        // Tertutup sampai tombol "Ajukan Persetujuan" ditekan.
        $this->assertMatchesRegularExpression('/data-catatan-wadah\s+hidden/', $halaman);
    }

    public function test_materi_terbit_yang_belum_dikirim_kembali_tetap_tayang(): void
    {
        $user = $this->buatPengguna();
        $materi = $this->buatMateri($user, Materi::STATUS_PUBLISHED);
        $materi->setujui();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm($pelajaran))
            ->assertSessionHasNoErrors();

        $materi->refresh();

        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->status);
        $this->assertNotNull($materi->dipublish_pada);
    }

    public function test_materi_belum_terbit_tidak_punya_tautan_lihat_yang_akan_gagal(): void
    {
        $user = $this->buatPengguna();
        $this->buatMateri($user, Materi::STATUS_PENDING, 'Materi Menunggu Tinjauan');

        $this->actingAs($user)
            ->get(route('user.karya-saya', ['tab' => 'materi']))
            ->assertOk()
            ->assertSee('Materi Menunggu Tinjauan')
            ->assertDontSee('Buka halaman materi ini');
    }

    public function test_admin_tidak_bisa_memutuskan_materi_yang_belum_menunggu(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        foreach ([Materi::STATUS_DRAFT, Materi::STATUS_PUBLISHED, Materi::STATUS_REJECTED] as $status) {
            $materi = $this->buatMateri($pemilik, $status, 'Materi '.$status);

            $this->actingAs($admin)
                ->post(route('admin.materi.setujui', $materi->slug))
                ->assertNotFound();

            $this->assertSame($status, $materi->refresh()->status);
        }
    }

    public function test_halaman_kelola_menampilkan_semua_status(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Menunggu');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Terbit');

        // Halaman kelola membuka ke semua status, bukan ke satu tab tunggu.
        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Materi Menunggu')
            ->assertSee('Materi Terbit');

        // Filter status tetap mengerucutkan daftar.
        $this->actingAs($admin)
            ->get(route('admin.materi', ['status' => Materi::STATUS_PUBLISHED]))
            ->assertOk()
            ->assertSee('Materi Terbit')
            ->assertDontSee('Materi Menunggu');
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_tinjau(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);

        $this->actingAs($user)
            ->get('/admin/materi')
            ->assertForbidden();

        $materi = $this->buatMateri($orangLain, Materi::STATUS_PENDING);

        $this->actingAs($user)
            ->post(route('admin.materi.setujui', $materi->slug))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/materi')->assertRedirect(route('login'));
    }
}
