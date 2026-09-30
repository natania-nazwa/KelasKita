<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\SimpananMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman "Materi" di area admin.
 *
 * Batas yang dijaga test di sini: halaman ini hanya mengelola materi yang
 * sudah terbit, dan detail yang dibuka dari sana memakai komponen tampilan
 * yang sama dengan halaman detail milik pengguna. Dua hal itu yang paling
 * mudah rusak diam-diam kalau ada yang mengubah salah satu sisinya saja.
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
            'status' => $status,
            'jumlah_dilihat' => 0,
            'dipublish_pada' => $terbitPada,
        ]);
    }

    private function buatTerbit(
        ?User $pemilik,
        string $nama = 'Materi Terbit',
        ?Pelajaran $pelajaran = null,
        string $isi = 'Isi materi untuk pengujian halaman kelola.',
        ?string $terbitPada = null,
    ): Materi {
        return $this->buatMateri(
            $pemilik,
            Materi::STATUS_PUBLISHED,
            $nama,
            $pelajaran,
            $isi,
            $terbitPada ?? now()->toDateTimeString(),
        );
    }

    /*
     * =============================================================
     * BATAS HALAMAN: HANYA MATERI YANG SUDAH TERBIT
     * =============================================================
     */

    public function test_halaman_materi_hanya_menampilkan_materi_yang_sudah_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Terbit');
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Menunggu');
        $this->buatMateri($pemilik, Materi::STATUS_REJECTED, 'Materi Ditolak');
        $this->buatMateri($pemilik, Materi::STATUS_DRAFT, 'Materi Draft');

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Materi Terbit')
            ->assertDontSee('Materi Menunggu')
            ->assertDontSee('Materi Ditolak')
            ->assertDontSee('Materi Draft');
    }

    public function test_halaman_materi_tidak_menampilkan_keputusan_verifikasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        /*
         * Setujui/Tolak dan angka "menunggu" milik halaman Verifikasi.
         * Halaman Materi tidak pernah punya salah satunya, jadi tidak boleh
         * muncul di sini meski ada materi yang statusnya bukan published.
         */
        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertDontSee('Menunggu Verifikasi')
            ->assertDontSee('Menunggu Persetujuan')
            ->assertDontSee('Tinjau');
    }

    public function test_hero_menjelaskan_bahwa_halaman_ini_untuk_materi_terbit(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Kelola materi pembelajaran yang telah dipublikasikan')
            ->assertSee('Ilmu hari ini');
    }

    /*
     * =============================================================
     * PENCARIAN DAN FILTER
     * =============================================================
     */

    public function test_pencarian_menemukan_materi_lewat_judul_dan_isi(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Materi Katakana', null, 'Isi ini memuat kata khusus xilofon.');
        $this->buatTerbit($pemilik, 'Materi Lain');

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
        $this->buatTerbit($budi, 'Materi Budi');
        $this->buatTerbit($siti, 'Materi Siti');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'Santoso']))
            ->assertOk()
            ->assertSee('Materi Budi')
            ->assertDontSee('Materi Siti');
    }

    public function test_pencarian_menemukan_materi_lewat_kategori(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $matematika = $this->buatPelajaran('Matematika', 'matematika');
        $biologi = $this->buatPelajaran('Biologi', 'biologi');
        $this->buatTerbit($pemilik, 'Materi Matematika', $matematika);
        $this->buatTerbit($pemilik, 'Materi Biologi', $biologi);

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'Matematika']))
            ->assertOk()
            ->assertSee('Materi Matematika')
            ->assertDontSee('Materi Biologi');
    }

    public function test_filter_kategori_mengerucutkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $matematika = $this->buatPelajaran('Matematika', 'matematika');
        $biologi = $this->buatPelajaran('Biologi', 'biologi');
        $this->buatTerbit($pemilik, 'Materi Matematika', $matematika);
        $this->buatTerbit($pemilik, 'Materi Biologi', $biologi);

        $this->actingAs($admin)
            ->get(route('admin.materi', ['kategori' => 'matematika']))
            ->assertOk()
            ->assertSee('Materi Matematika')
            ->assertDontSee('Materi Biologi');
    }

    public function test_filter_pembuat_mengerucutkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso']);
        $siti = $this->buatPengguna(['nama' => 'Siti Aminah', 'email' => 'siti@example.com']);
        $this->buatTerbit($budi, 'Materi Budi');
        $this->buatTerbit($siti, 'Materi Siti');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['pembuat' => (string) $siti->getKey()]))
            ->assertOk()
            ->assertSee('Materi Siti')
            ->assertDontSee('Materi Budi');
    }

    public function test_urutan_paling_lama_menampilkan_materi_terlama_dulu(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Materi Lama', null, 'Isi materi lama.', '2025-01-01 08:00:00');
        $this->buatTerbit($pemilik, 'Materi Baru', null, 'Isi materi baru.', '2025-06-01 08:00:00');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['urut' => 'terlama']))
            ->assertOk()
            // "Materi Lama" harus tampil sebelum "Materi Baru" di HTML.
            ->assertSeeInOrder(['Materi Lama', 'Materi Baru']);
    }

    public function test_urutan_tak_dikenal_tidak_membuat_halaman_kosong(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Materi Satu');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['urut' => 'ngawur']))
            ->assertOk()
            ->assertSee('Materi Satu');
    }

    /*
     * =============================================================
     * EMPTY STATE
     * =============================================================
     */

    public function test_empty_state_muncul_saat_belum_ada_materi_terbit(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Belum ada materi')
            ->assertSee('Materi yang telah disetujui akan muncul di sini.');
    }

    public function test_empty_state_muncul_saat_pencarian_tidak_mencocokkan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Materi tidak ditemukan')
            ->assertSee('Coba gunakan kata kunci yang berbeda.');
    }

    public function test_materi_yang_belum_terbit_tidak_mengubah_empty_state(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Menunggu');

        // Materi pending ada, tapi tidak pernah dihitung sebagai materi tayang.
        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Belum ada materi')
            ->assertDontSee('Materi Menunggu');
    }

    /*
     * =============================================================
     * PANEL DETAIL DI SEBELAH KANAN
     * =============================================================
     */

    public function test_panel_kanan_kosong_sampai_materi_dipilih(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Detail Materi')
            ->assertSee('Pilih materi untuk melihat detail dan preview')
            ->assertSee('Pilih salah satu materi dari daftar untuk melihat detailnya.');
    }

    public function test_panel_kanan_menampilkan_ringkasan_materi_yang_dipilih(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna(['nama' => 'Natania', 'email' => 'natania@example.com']);
        $materi = $this->buatTerbit(
            $pemilik,
            'Dasar HTML',
            null,
            "# Bab Satu\n\nIsi bab satu.\n\n# Bab Dua\n\nIsi bab dua.",
        );

        $this->actingAs($admin)
            ->get(route('admin.materi', ['materi' => $materi->slug]))
            ->assertOk()
            ->assertSee('Dasar HTML')
            ->assertSee('Natania')
            ->assertSee('Bab Satu')
            ->assertSee('Bab Dua')
            ->assertSee('Lihat Materi');
    }

    public function test_materi_yang_belum_terbit_tidak_bisa_dipreview(): void
    {
        $admin = $this->buatAdmin();
        $menunggu = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Menunggu');

        // Slug-nya diketik manual, jadi harus tetap jatuh ke panel kosong.
        $this->actingAs($admin)
            ->get(route('admin.materi', ['materi' => $menunggu->slug]))
            ->assertOk()
            ->assertSee('Pilih salah satu materi dari daftar untuk melihat detailnya.')
            ->assertDontSee('Materi Menunggu');
    }

    /*
     * =============================================================
     * PAGINASI
     * =============================================================
     */

    public function test_daftar_dipaginasi_delapan_per_halaman(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        for ($i = 1; $i <= 9; $i++) {
            $this->buatTerbit($pemilik, 'Materi Paginasi '.$i);
        }

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Materi Paginasi 1')
            ->assertSee('Materi Paginasi 8')
            ->assertDontSee('Materi Paginasi 9');

        $this->actingAs($admin)
            ->get(route('admin.materi', ['page' => 2]))
            ->assertOk()
            ->assertSee('Materi Paginasi 9')
            ->assertDontSee('Materi Paginasi 8');
    }

    /*
     * =============================================================
     * DETAIL: PAKAI KOMPONEN TAMPILAN MILIK PENGGUNA
     * =============================================================
     */

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

    public function test_detail_memakai_komponen_yang_sama_dengan_halaman_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit(
            $this->buatPengguna(),
            'Materi Uji Detail',
            null,
            "# Bab Satu\n\nParagraf pembuka untuk bab ini.\n\n```php\n\$kode = 'contoh';\n```\n\n# Bab Dua\n\nIsi bab kedua.",
        );

        $halaman = $this->actingAs($admin)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertOk()
            ->assertSee('Materi Uji Detail')
            ->assertSee('Bab Satu')
            ->assertSee('Paragraf pembuka untuk bab ini.')
            ->assertSee('$kode = &#039;contoh&#039;;', false);

        /*
         * Penanda yang hanya ada kalau halaman ini memakai komponen
         * x-materi.detail-* yang sama dengan halaman detail pengguna:
         * kepala (kartu-kepala), Daftar Isi (daftar-isi), pemilih bab
         * (data-bab), dan blok kode dengan tombol salin.
         *
         * Tombol Simpan tidak boleh ada: bookmark adalah urusan pembaca, dan
         * materi ini milik pengguna lain.
         */
        $html = $halaman->getContent();

        $this->assertStringContainsString('kartu-kepala', $html);
        $this->assertStringContainsString('daftar-isi', $html);
        $this->assertStringContainsString('data-bab="bab-satu"', $html);
        $this->assertStringContainsString('data-bab="bab-dua"', $html);
        $this->assertStringContainsString('data-bab-nav', $html);
        $this->assertStringContainsString('data-salin-kode', $html);
        $this->assertStringNotContainsString('data-bookmark', $html);
    }

    public function test_daftar_bab_di_detail_admin_sama_persis_dengan_halaman_pengguna(): void
    {
        $isi = "# Bab Satu\n\nIsi satu.\n\n# Bab Dua\n\nIsi dua.\n\n# Bab Tiga\n\nIsi tiga.";

        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna(['nama' => 'Natania', 'email' => 'natania@example.com']);
        $materi = $this->buatTerbit($pemilik, 'Dasar HTML', null, $isi);

        $adminHtml = $this->actingAs($admin)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertOk()
            ->getContent();

        $penggunaHtml = $this->actingAs($pemilik)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->getContent();

        /*
         * Daftar bab adalah satu-satunya pembeda struktural antara dua
         * tampilan ini, jadi membandingkannya langsung adalah cara paling
         * jujur membuktikan "tampilan yang sama": kalau nanti ada komponen
         * materi yang berubah hanya di satu sisi, test ini langsung gagal.
         */
        $this->assertSame($this->slugBab($penggunaHtml), $this->slugBab($adminHtml));
        $this->assertSame(
            $this->judulBab($penggunaHtml),
            $this->judulBab($adminHtml),
            'Daftar bab di detail admin harus sama dengan yang dilihat pengguna.',
        );
    }

    public function test_detail_admin_menampilkan_kembali_ke_daftar_materi(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna());

        $this->actingAs($admin)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertOk()
            ->assertSee('Kembali ke Materi')
            ->assertSee(route('admin.materi'), false)
            ->assertSee(route('admin.materi.edit', $materi->slug), false);
    }

    /**
     * Slug bab yang muncul di markup, sesuai urutan.
     *
     * @return array<int, string>
     */
    private function slugBab(string $html): array
    {
        preg_match_all('/data-bab="([^"]+)"/', $html, $cocok);

        return $cocok[1];
    }

    /**
     * Teks "1. Judul" pada kartu bab, sesuai urutan.
     *
     * @return array<int, string>
     */
    private function judulBab(string $html): array
    {
        preg_match_all('/<h2 class="materi-seksi__judul">\s*([^<]+?)\s*<\/h2>/', $html, $cocok);

        return array_map('trim', $cocok[1] ?? []);
    }

    /*
     * =============================================================
     * EDIT DAN HAPUS
     * =============================================================
     */

    public function test_admin_bisa_membuka_form_edit_materi_milik_pengguna_lain(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $materi = $this->buatTerbit($pemilik, 'Materi Milik Orang Lain');

        $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('Edit Materi')
            ->assertSee('Materi Milik Orang Lain')
            // Form milik pemilik menyebut "Ajukan Persetujuan"; di sini tidak.
            ->assertDontSee('Ajukan Persetujuan');
    }

    public function test_admin_bisa_memperbarui_materi_dan_statusnya_tetap_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatTerbit($pemilik, 'Judul Lama');
        $terbitPada = $materi->dipublish_pada;

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Judul Baru',
                'deskripsi' => 'Deskripsi baru.',
                'isi' => 'Isi materi yang sudah diperbarui oleh admin.',
                'tingkat_kesulitan' => 'Sedang',
            ])
            ->assertRedirect(route('admin.materi.show', $materi->slug));

        $materi->refresh();

        $this->assertSame('Judul Baru', $materi->nama);
        $this->assertSame('Sedang', $materi->tingkat_kesulitan);

        /*
         * Berbeda dari revisi pemilik, suntingan admin tidak menarik materi
         * dari halaman publik dan tidak menggeser tanggal terbitnya.
         */
        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->status);
        $this->assertTrue($terbitPada->equalTo($materi->dipublish_pada));

        $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->assertSee('Judul Baru');
    }

    public function test_update_menolak_isian_tidak_valid(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Judul Lama');

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), [
                'pelajaran_id' => $this->buatPelajaran()->id,
                'nama' => '',
                'isi' => 'pendek',
                'tingkat_kesulitan' => 'Mudah',
            ])
            ->assertSessionHasErrors(['nama', 'isi']);

        $this->assertSame('Judul Lama', $materi->refresh()->nama);
    }

    public function test_admin_bisa_menghapus_materi(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Materi Dihapus');

        $this->actingAs($admin)
            ->delete(route('admin.materi.destroy', $materi->slug))
            ->assertRedirect(route('admin.materi'));

        $this->assertDatabaseMissing('tb_materi', ['id' => $materi->getKey()]);
    }

    public function test_menghapus_materi_ikut_membersihkan_simpanan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $pembaca = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $materi = $this->buatTerbit($this->buatPengguna());

        SimpananMateri::create([
            'pengguna_id' => $pembaca->getKey(),
            'materi_id' => $materi->getKey(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.materi.destroy', $materi->slug))
            ->assertRedirect(route('admin.materi'));

        // Foreign key-nya cascadeOnDelete, jadi tidak ada baris yatim.
        $this->assertDatabaseMissing('tb_simpanan_materi', ['materi_id' => $materi->getKey()]);
    }

    public function test_aksi_kelola_menolak_materi_yang_tidak_ada(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->get(route('admin.materi.edit', 'tidak-ada'))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.materi.destroy', 'tidak-ada'))->assertNotFound();
    }

    /*
     * =============================================================
     * AKSES
     * =============================================================
     */

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_kelola_dan_aksinya(): void
    {
        $user = $this->buatPengguna();
        $pemilik = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $materi = $this->buatTerbit($pemilik);

        $this->actingAs($user)->get('/admin/materi')->assertForbidden();
        $this->actingAs($user)->get(route('admin.materi.show', $materi->slug))->assertForbidden();
        $this->actingAs($user)->get(route('admin.materi.edit', $materi->slug))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.materi.destroy', $materi->slug))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login_pada_halaman_kelola_dan_aksinya(): void
    {
        $materi = $this->buatTerbit(null);

        $this->get('/admin/materi')->assertRedirect(route('login'));
        $this->get(route('admin.materi.show', $materi->slug))->assertRedirect(route('login'));
        $this->get(route('admin.materi.edit', $materi->slug))->assertRedirect(route('login'));
        $this->delete(route('admin.materi.destroy', $materi->slug))->assertRedirect(route('login'));
    }
}
