<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QA halaman "Materi" di area admin.
 *
 * MateriKelolaHalamanTest sudah menjaga batas halaman, pencarian, filter,
 * kartu, paginasi, edit, dan hapus dari sisi aturan. Test di sini memeriksa
 * sisa fitur yang dijalankan lewat jalur lain dan paling mudah rusak diam-
 * diam tanpa ada yang gagal: pesan sukses setelah simpan, saringan yang
 * tidak dikenal, query string yang dibawa paginasi, unggah/hapus thumbnail
 * lewat form edit, dan kerangka dialog hapus.
 */
class MateriAdminQaTest extends TestCase
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

    private function buatTerbit(
        ?User $pemilik,
        string $nama,
        ?Pelajaran $pelajaran = null,
        string $isi = 'Isi materi untuk pengujian halaman kelola.',
        ?string $terbitPada = null,
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
            'status' => Materi::STATUS_PUBLISHED,
            'jumlah_dilihat' => 0,
            'dipublish_pada' => $terbitPada ?? now()->toDateTimeString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Judul Baru',
            'deskripsi' => 'Deskripsi baru.',
            'isi' => 'Isi materi yang sudah diperbarui oleh admin lewat form edit.',
            'tingkat_kesulitan' => 'Sedang',
        ], $tambahan);
    }

    /*
     * =============================================================
     * PESAN SUKSES
     * =============================================================
     * Aksi simpan mengirim flash "sukses", jadi halaman tujuannya harus
     * benar-benar merender flash itu. Kalau tidak, admin menekan Simpan,
     * halaman berpindah, dan tidak ada satu pun tanda bahwa perubahan
     * tersimpan.
     */

    public function test_hapus_materi_menampilkan_pesan_sukses_di_daftar(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Materi Dihapus');

        $this->actingAs($admin)
            ->delete(route('admin.materi.destroy', $materi->slug))
            ->assertRedirect(route('admin.materi'))
            ->assertSessionHas('sukses', 'Materi "Materi Dihapus" berhasil dihapus.');

        $this->actingAs($admin)
            ->get(route('admin.materi'))
            ->assertOk()
            ->assertSee('berhasil dihapus');
    }

    public function test_update_materi_menampilkan_pesan_sukses(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatTerbit($admin, 'Judul Lama');

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran))
            ->assertRedirect(route('admin.materi.show', $materi->slug))
            ->assertSessionHas('sukses');

        // Halaman tuju detail; di sanalah pesan itu harus terbaca.
        $this->actingAs($admin)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertOk()
            ->assertSee('berhasil diperbarui');
    }

    /*
     * =============================================================
     * SARINGAN YANG TIDAK DIKENAL
     * =============================================================
     */

    public function test_filter_kategori_tak_dikenal_menuju_keadaan_kosong(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Materi Satu');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['kategori' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Materi tidak ditemukan')
            ->assertDontSee('ad-kartu-daftar__judul');
    }

    public function test_kombinasi_kata_kunci_kategori_dan_urut(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $matematika = $this->buatPelajaran('Matematika', 'matematika');
        $biologi = $this->buatPelajaran('Biologi', 'biologi');

        $this->buatTerbit($pemilik, 'Aljabar Dasar', $matematika, terbitPada: '2025-01-01 08:00:00');
        $this->buatTerbit($pemilik, 'Aljabar Lanjut', $matematika, terbitPada: '2025-06-01 08:00:00');
        $this->buatTerbit($pemilik, 'Aljabar Biologi', $biologi);

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'Aljabar', 'kategori' => 'matematika', 'urut' => 'terlama']))
            ->assertOk()
            ->assertSeeInOrder(['Aljabar Dasar', 'Aljabar Lanjut'])
            ->assertDontSee('Aljabar Biologi');
    }

    public function test_pencarian_melalui_email_pembuat(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna([
            'nama' => 'Natania',
            'email' => 'natania@example.com',
        ]);
        $this->buatTerbit($pemilik, 'Materi Natania');
        $this->buatTerbit($this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']), 'Materi Siti');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'natania@example.com']))
            ->assertOk()
            ->assertSee('Materi Natania')
            ->assertDontSee('Materi Siti');
    }

    /*
     * =============================================================
     * PAGINASI
     * =============================================================
     */

    public function test_paginasi_membawa_kata_kunci_dan_saringan_ke_halaman_berikutnya(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Matematika', 'matematika');

        for ($i = 1; $i <= 21; $i++) {
            $this->buatTerbit(
                $pemilik,
                'Materi QA '.$i,
                $pelajaran,
                terbitPada: now()->subMinutes(22 - $i)->toDateTimeString(),
            );
        }

        $html = $this->actingAs($admin)
            ->get(route('admin.materi', [
                'q' => 'QA',
                'kategori' => 'matematika',
                'urut' => 'terlama',
                'page' => 2,
            ]))
            ->assertOk()
            ->getContent();

        // Tautan halaman tetap membawa saringan yang sedang aktif, jadi
        // menekan "1" tidak mengembalikan daftar yang belum tersaring.
        $this->assertStringContainsString('page=1', $html);
        $this->assertStringContainsString('q=QA', $html);
        $this->assertStringContainsString('kategori=matematika', $html);
        $this->assertStringContainsString('urut=terlama', $html);
    }

    public function test_daftar_kosong_tidak_menghasilkan_paginasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Materi Satu');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'tidak-ada']))
            ->assertOk()
            ->assertDontSee('ad-paginasi');
    }

    /*
     * =============================================================
     * PILIHAN KATEGORI DAN KARTU
     * =============================================================
     */

    public function test_pilihan_kategori_menampilkan_jumlah_materi_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $matematika = $this->buatPelajaran('Matematika', 'matematika');
        $biologi = $this->buatPelajaran('Biologi', 'biologi');

        $this->buatTerbit($pemilik, 'Materi Matematika Satu', $matematika);
        $this->buatTerbit($pemilik, 'Materi Matematika Dua', $matematika);
        $this->buatTerbit($pemilik, 'Materi Biologi', $biologi);

        $this->actingAs($admin)
            ->get(route('admin.materi'))
            ->assertOk()
            ->assertSee('Matematika (2)', false)
            ->assertSee('Biologi (1)', false);
    }

    public function test_kartu_memakai_thumbnail_atau_ikon_kategori(): void
    {
        Storage::fake('public');

        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $denganFoto = $this->buatTerbit($pemilik, 'Materi Berfoto');
        $tanpaFoto = $this->buatTerbit($pemilik, 'Materi Tanpa Foto');

        Storage::disk('public')->put('thumbnails/uji.jpg', 'isi-gambar');
        $denganFoto->update(['thumbnail' => 'thumbnails/uji.jpg']);

        $html = $this->actingAs($admin)->get(route('admin.materi'))->assertOk()->getContent();

        $this->assertStringContainsString(
            'src="'.asset('storage/thumbnails/uji.jpg').'"',
            $html,
        );

        // Hanya satu kartu yang berfoto; kartu lainnya memakai ikon kategori.
        $this->assertSame(1, substr_count($html, 'ad-kartu-daftar__foto'));
        $this->assertGreaterThanOrEqual(1, substr_count($html, 'ad-kartu-daftar__ikon'));
        $this->assertNotNull($tanpaFoto->getKey());
    }

    public function test_lencana_status_menampilkan_label_terbit(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Materi Satu');

        $this->actingAs($admin)
            ->get(route('admin.materi'))
            ->assertOk()
            ->assertSee('Dipublikasikan');
    }

    /*
     * =============================================================
     * DIALOG HAPUS
     * =============================================================
     */

    public function test_dialog_hapus_membentang_tujuan_form_dari_tombol_kartu(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Materi Dihapus');

        $html = $this->actingAs($admin)->get(route('admin.materi'))->assertOk()->getContent();

        // Tombol kartu membawa tujuan form-nya sendiri...
        $this->assertStringContainsString('data-hapus-aksi="'.route('admin.materi.destroy', $materi->slug).'"', $html);

        // ...sementara form-nya belum menunjuk ke mana pun sampai tombol itu
        // ditekan, supaya tidak ada yang bisa terhapus tanpa konfirmasi.
        $this->assertStringContainsString(
            '<form method="POST" action="" id="form-hapus-materi"',
            $html,
        );
    }

    /*
     * =============================================================
     * FORM EDIT: KERANGKA DAN PRATINJAU
     * =============================================================
     */

    public function test_form_edit_menuju_ke_admin_update_dan_memasang_url_pratinjau(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($admin, 'Judul Lama');

        $html = $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertOk()
            ->getContent();

        // Simpan tertuju ke route admin, bukan ke route milik pemilik.
        $this->assertStringContainsString(
            'action="'.route('admin.materi.update', $materi->slug).'"',
            $html,
        );
        $this->assertStringContainsString('name="_method" value="PUT"', $html);

        // Tab Preview memakai endpoint pratinjau; tanpa URL ini panelnya
        // diam tanpa pesan apa pun.
        $this->assertStringContainsString(
            'data-pratinjau-url="'.route('admin.konten.materi.pratinjau').'"',
            $html,
        );
    }

    public function test_pratinjau_dari_form_edit_mengembalikan_isi_materi(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.pratinjau'), [
                'nama' => 'Materi Pratinjau Admin',
                'isi' => "# Bab Satu\n\nParagraf pembuka untuk bab ini.",
            ])
            ->assertOk()
            ->assertSee('Paragraf pembuka untuk bab ini.');
    }

    /*
     * =============================================================
     * THUMBNAIL LEWAT FORM EDIT
     * =============================================================
     */

    public function test_admin_bisa_mengganti_thumbnail_lewat_form_edit(): void
    {
        Storage::fake('public');

        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatTerbit($admin, 'Judul Lama');

        Storage::disk('public')->put('thumbnails/lama.jpg', 'isi-lama');

        $materi->update(['thumbnail' => 'thumbnails/lama.jpg']);

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('baru.png', 320, 180),
            ]))
            ->assertRedirect(route('admin.materi.show', $materi->slug))
            ->assertSessionHasNoErrors();

        $materi->refresh();

        $this->assertStringStartsWith('thumbnails/', (string) $materi->thumbnail);
        Storage::disk('public')->assertExists($materi->thumbnail);

        // Berkas lama ikut dibuang supaya tidak menumpuk di disk.
        Storage::disk('public')->assertMissing('thumbnails/lama.jpg');
    }

    public function test_admin_bisa_menghapus_thumbnail_lewat_form_edit(): void
    {
        Storage::fake('public');

        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatTerbit($admin, 'Judul Lama');

        Storage::disk('public')->put('thumbnails/lama.jpg', 'isi-lama');
        $materi->update(['thumbnail' => 'thumbnails/lama.jpg']);

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'thumbnail_hapus' => '1',
            ]))
            ->assertRedirect(route('admin.materi.show', $materi->slug))
            ->assertSessionHasNoErrors();

        $this->assertNull($materi->refresh()->thumbnail);
        Storage::disk('public')->assertMissing('thumbnails/lama.jpg');
    }

    public function test_menghapus_materi_membuang_berkas_thumbnail(): void
    {
        Storage::fake('public');

        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Materi Berfoto');

        Storage::disk('public')->put('thumbnails/foto.jpg', 'isi-gambar');
        $materi->update(['thumbnail' => 'thumbnails/foto.jpg']);

        $this->actingAs($admin)
            ->delete(route('admin.materi.destroy', $materi->slug))
            ->assertRedirect(route('admin.materi'));

        Storage::disk('public')->assertMissing('thumbnails/foto.jpg');
        $this->assertDatabaseMissing('tb_materi', ['id' => $materi->getKey()]);
    }
}
