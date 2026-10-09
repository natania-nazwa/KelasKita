<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isian Deskripsi Materi di panel Informasi Materi.
 *
 * Kolom deskripsi sudah lama ada di tb_materi dan sudah divalidasi, tapi form
 * tidak pernah mengirimnya — panel Informasi Materi tidak punya isian itu.
 * Jadi isinya hanya bisa diisi lewat database, dan kartu materi pun memakai
 * Materi::ringkasan() yang jatuh ke isi materi kalau deskripsi kosong, jadi
 * yang tampil di bawah judul kartu adalah kepala babnya ("Bab 1: …").
 *
 * Test di sini menjaga isi deskripsi bisa ditulis dari keempat form materi,
 * kembali tampil saat form dibuka lagi, boleh dikosongkan, ditolak kalau
 * melewati batas yang ditulis form, dan tidak pernah dikotomi isi bab.
 */
class MateriDeskripsiTest extends TestCase
{
    use RefreshDatabase;

    private const ISI = 'Isi materi yang cukup panjang untuk memenuhi batas minimal validasi.';

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

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => 'pemrograman'], [
            'nama' => 'Pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'aktif' => true,
        ]);
    }

    private function buatMateri(?User $pemilik, string $nama, array $tambahan = []): Materi
    {
        $this->urutan++;

        return Materi::create(array_merge([
            'pelajaran_id' => $this->buatPelajaran()->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.$this->urutan,
            'deskripsi' => 'Ringkasan materi.',
            'isi' => self::ISI,
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
            'dipublish_pada' => now()->toDateTimeString(),
        ], $tambahan));
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $this->buatPelajaran()->id,
            'nama' => 'Judul Materi',
            'isi' => self::ISI,
            'tingkat_kesulitan' => 'Mudah',
        ], $tambahan);
    }

    /*
     * =============================================================
     * ISIANNYA ADA DI KEEMPAT FORM MATERI
     * =============================================================
     * Keempat form memakai komponen x-materi.informasi yang sama, jadi satu
     * test untuk keempat halaman sekaligus: kalau suatu form nanti diganti ke
     * komponen sendiri, halaman mana yang kehilangan isiannya langsung
     * kelihatan dari pesan gagalnya.
     */

    public function test_keempat_form_materi_memiliki_isian_deskripsi_di_bawah_judul(): void
    {
        $pengguna = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $milikPengguna = $this->buatMateri($pengguna, 'Materi Pengguna');
        $milikAdmin = $this->buatMateri($admin, 'Materi Admin');

        $halaman = [
            'tambah milik pengguna' => [$pengguna, route('user.materi.tambah')],
            'edit milik pengguna' => [$pengguna, route('user.materi.edit', $milikPengguna->slug)],
            'edit admin di Konten Pembelajaran' => [$admin, route('admin.konten.materi.edit', $milikAdmin->slug)],
            'edit admin di katalog Materi' => [$admin, route('admin.materi.edit', $milikAdmin->slug)],
        ];

        foreach ($halaman as $nama => [$aktor, $alamat]) {
            $html = $this->actingAs($aktor)->get($alamat)->assertOk()->getContent();

            $this->assertStringContainsString('name="deskripsi"', $html, $nama.' tidak punya isian deskripsi.');
            $this->assertStringContainsString('maxlength="220"', $html, $nama.' menulis batas yang berbeda dari server.');

            // Isiannya langsung di bawah judul, bukan di tempat lain di form.
            $judul = strpos($html, 'name="nama"');
            $deskripsi = strpos($html, 'name="deskripsi"');

            $this->assertIsInt($judul);
            $this->assertIsInt($deskripsi);
            $this->assertGreaterThan($judul, $deskripsi, $nama.' menaruh deskripsi bukan di bawah judul.');
        }
    }

    public function test_edit_menampilkan_kembali_deskripsi_yang_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, 'Materi Berdeskripsi', [
            'deskripsi' => 'Ringkasan yang ditulis pengarangnya.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('Ringkasan yang ditulis pengarangnya.', false);
    }

    /*
     * =============================================================
     * TERSIMPAN DAN KEMBALI TAMPIL
     * =============================================================
     */

    public function test_tambah_materi_menyimpan_deskripsi(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('user.materi.tambah.store'), $this->dataForm([
                'nama' => 'Materi Baru',
                'deskripsi' => 'Pengenalan sintaks PHP beserta variabel dan fungsinya.',
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']));

        $materi = Materi::query()->where('nama', 'Materi Baru')->firstOrFail();

        $this->assertSame(
            'Pengenalan sintaks PHP beserta variabel dan fungsinya.',
            $materi->deskripsi,
        );
    }

    public function test_edit_pemilik_menyimpan_deskripsi_baru(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($pengguna, 'Materi Milik Sendiri');

        $this->actingAs($pengguna)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm([
                'nama' => 'Materi Milik Sendiri',
                'deskripsi' => 'Deskripsi baru dari pemiliknya.',
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']));

        $this->assertSame('Deskripsi baru dari pemiliknya.', $materi->refresh()->deskripsi);

        $this->actingAs($pengguna)
            ->get(route('user.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('Deskripsi baru dari pemiliknya.', false);
    }

    public function test_edit_admin_menyimpan_deskripsi_baru(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, 'Materi Admin');

        $this->actingAs($admin)
            ->put(route('admin.konten.materi.update', $materi->slug), $this->dataForm([
                'nama' => 'Materi Admin',
                'deskripsi' => 'Deskripsi yang diperbaiki oleh admin.',
            ]))
            ->assertRedirect(route('admin.konten', ['tab' => 'materi']));

        $this->assertSame('Deskripsi yang diperbaiki oleh admin.', $materi->refresh()->deskripsi);
    }

    /*
     * =============================================================
     * OPSIONAL DAN BATASNYA
     * =============================================================
     * Deskripsi boleh dikosongkan supaya materi lama tetap bisa disimpan tanpa
     * harus diisi dulu, tapi batasnya dipegang server: form menulis
     * maxlength 220.
     */

    public function test_deskripsi_boleh_dikosongkan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, 'Materi Tanpa Deskripsi');

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm([
                'nama' => 'Materi Tanpa Deskripsi',
                'deskripsi' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($materi->refresh()->deskripsi);
    }

    public function test_deskripsi_yang_kepanjangan_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, 'Materi Batas Deskripsi');

        $this->actingAs($admin)
            ->from(route('admin.materi.edit', $materi->slug))
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm([
                'nama' => 'Materi Batas Deskripsi',
                'deskripsi' => str_repeat('a', 221),
            ]))
            ->assertSessionHasErrors('deskripsi');

        $this->assertSame('Ringkasan materi.', $materi->refresh()->deskripsi);
    }

    /*
     * =============================================================
     * YANG TAMPIL DI BAWAH JUDUL KARTU
     * =============================================================
     * Isi materi disusun dari beberapa bab, jadi 120 karakter pertamanya adalah
     * kepala bab ("Bab 1: Pendahuluan …"). Kalau itu yang muncul di bawah judul
     * kartu, pembaca mengira kartu itu rusak — bukan sedang membaca materi.
     */

    public function test_kartu_materi_menampilkan_deskripsi_bukan_isi_bab(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($pengguna, 'Belajar Blade', [
            'deskripsi' => 'Membaca dan menulis markup Blade dari nol.',
            'isi' => "Bab 1: Pendahuluan\n\nBlade adalah mesin templating.\n\nBab 2: Sintaks\n\nInterpolasi dan directive.",
        ]);

        $html = $this->actingAs($pengguna)->get(route('user.materi'))->assertOk()->getContent();

        $this->assertStringContainsString('Membaca dan menulis markup Blade dari nol.', $html);
        $this->assertStringNotContainsString('Bab 1: Pendahuluan', $html);

        // Materi ini tetap tampil utuh sebagai kartu.
        $this->assertStringContainsString($materi->nama, $html);
    }

    public function test_kartu_materi_tanpa_deskripsi_tidak_menampilkan_isi_bab(): void
    {
        $pengguna = $this->buatPengguna();
        $this->buatMateri($pengguna, 'Materi Tanpa Deskripsi', [
            'deskripsi' => null,
            'isi' => "Bab 1: Pendahuluan\n\nIsi bab pertama.\n\nBab 2: Lanjutan\n\nIsi bab kedua.",
        ]);

        $html = $this->actingAs($pengguna)->get(route('user.materi'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Bab 1: Pendahuluan', $html);
        $this->assertStringContainsString('Materi Tanpa Deskripsi', $html);
        $this->assertStringContainsString('2 Bab', $html);
    }
}
