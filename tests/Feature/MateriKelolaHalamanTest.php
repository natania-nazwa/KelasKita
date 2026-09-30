<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\SimpananMateri;
use App\Models\User;
use App\Support\DaftarMateri;
use App\Support\DaftarMateriAdmin;
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
            ->assertSee('Kelola materi pembelajaran yang telah dipublikasikan');
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
     * FILTER TAMPIL LANGSUNG
     * =============================================================
     */

    public function test_pencarian_dan_ketiga_filter_tampil_tanpa_hanya_dukungan_javascript(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        // Tiga select, ketiganya di dalam satu form GET tanpa popover.
        $this->assertStringContainsString('name="kategori"', $html);
        $this->assertStringContainsString('name="urut"', $html);
        $this->assertStringContainsString('name="pembuat"', $html);
        $this->assertStringContainsString('Semua kategori', $html);
        $this->assertStringContainsString('Semua pembuat', $html);

        // Dua tombol aksi.
        $this->assertStringContainsString('>Terapkan<', $html);
        $this->assertStringContainsString('Hapus filter', $html);

        // Tidak ada lagi tombol/popover yang menyembunyikan filter.
        $this->assertStringNotContainsString('ad-saring', $html);
    }

    /*
     * =============================================================
     * KOLOM CARI DI HALAMAN INI SUDAH DIHAPUS
     * =============================================================
     */

    public function test_halaman_tidak_lagi_menampilkan_kolom_cari(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Dasar HTML');

        $html = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('ad-alat-baris__cari', $html);
        $this->assertStringNotContainsString('placeholder="Cari materi..."', $html);
    }

    public function test_pencarian_tetap_bisa_dipakai_lewat_kotak_di_topbar(): void
    {
        /*
         * Kolom cari dihapus dari halaman, jadi kotak pencarian di topbar
         * sekarang satu-satunya jalannya. Form-nya harus tetap mengarah ke
         * halaman ini dan tetap mengirim "q" — kalau salah satu berubah,
         * pencarian mati total tanpa ada satu pun tombol yang gagal terlihat.
         */
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Materi Katakana');
        $this->buatTerbit($pemilik, 'Materi Lain');

        $html = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('action="'.route('admin.materi').'"', $html);
        $this->assertStringContainsString('id="cari-ad"', $html);
        $this->assertStringContainsString('name="q"', $html);

        // Dan form GET di halaman ini tetap Apply kata kunci yang masuk dari
        // topbar, walau tidak ada lagi kolomnya.
        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'Katakana']))
            ->assertOk()
            ->assertSee('Materi Katakana')
            ->assertDontSee('Materi Lain');
    }

    public function test_kata_kunci_aktif_tetap_ikut_dibawa_saat_menyaring(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatPelajaran('Matematika', 'matematika');
        $this->buatTerbit($pemilik, 'Materi Katakana', $this->buatPelajaran('Teknologi', 'teknologi'));

        // Menyaring kategori tidak boleh menghapus kata kunci yang sedang aktif.
        $this->actingAs($admin)
            ->get(route('admin.materi', ['q' => 'Katakana', 'kategori' => 'teknologi']))
            ->assertOk()
            ->assertSee('Materi Katakana');
    }

    /*
     * =============================================================
     * HERO: TEKS DI KIRI, GAMBAR BUKU DI KANAN
     * =============================================================
     */

    public function test_hero_memakai_teks_dan_gambar_buku_saja(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        // Teks di kolom kiri.
        $this->assertStringContainsString('ad-hero-konten__teks', $html);
        $this->assertStringContainsString('ad-hero-konten__judul', $html);
        $this->assertStringContainsString('Kelola materi pembelajaran yang telah dipublikasikan', $html);

        // Gambar buku di kolom kanan.
        $this->assertStringContainsString('ad-hero-konten__gambar', $html);
        $this->assertStringContainsString(asset('images/buku.png'), $html);
        $this->assertLessThan(
            strpos($html, 'ad-hero-konten__gambar'),
            strpos($html, 'Kelola materi pembelajaran'),
            'Gambar harus di sebelah kanan teks, bukan di atas atau di kiri.',
        );

        // Murni dekoratif: alt kosong, disembunyikan dari pembaca layar.
        $this->assertSame(
            1,
            preg_match('/<img[^>]*ad-hero-konten__gambar[^>]*>/', $html, $tag),
        );
        $this->assertStringContainsString('alt=""', $tag[0]);
        $this->assertStringContainsString('aria-hidden="true"', $tag[0]);

        // Yang tetap dihapus: kutipan, ikon, dan ilustrasi SVG lama.
        $this->assertStringNotContainsString('Ilmu hari ini', $html);
        $this->assertStringNotContainsString('ad-hero-konten__kutip', $html);
        $this->assertStringNotContainsString('ad-hero-konten__ilustrasi', $html);
        $this->assertStringNotContainsString('ad-hero-konten__kiri', $html);
        $this->assertStringNotContainsString('ad-hero-konten__kanan', $html);
        $this->assertStringNotContainsString('ad-hero-konten__ikon', $html);
    }

    public function test_gambar_hero_terkunci_dari_dua_sisi_agar_tidak_mendorong_judul(): void
    {
        // Berkasnya bujur sangkar. Tanpa width + height + object-fit yang
        // dikunci, gambar ikut meninggi dan mendorong judul keluar container
        // di layar sempit.
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-hero-konten__gambar\s*\{([^}]*)\}/', $css, $cocok));

        $aturan = $this->tanpaKomentar($cocok[1]);

        $this->assertMatchesRegularExpression('/width:\s*[\d.]+rem/', $aturan);
        $this->assertMatchesRegularExpression('/height:\s*[\d.]+rem/', $aturan);
        $this->assertStringContainsString('object-fit: contain', $aturan);
        $this->assertStringContainsString('flex-shrink: 0', $aturan);
    }

    public function test_teks_hero_dan_gambarnya_berdampingan(): void
    {
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-hero-konten__susun\s*\{([^}]*)\}/', $css, $cocok));

        $aturan = $this->tanpaKomentar($cocok[1]);

        $this->assertStringContainsString('display: flex', $aturan);
        $this->assertStringContainsString('justify-content: space-between', $aturan);
    }

    public function test_hapus_filter_aktif_khwa_saring_tidak_ada(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Materi Satu');

        $dimatikan = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();
        $menyala = $this->actingAs($admin)
            ->get(route('admin.materi', ['kategori' => 'matematika']))
            ->assertOk()
            ->getContent();

        // Tanpa saringan, tombolnya tampil redup dan tidak bisa diklik.
        $this->assertStringContainsString('aria-disabled="true"', $dimatikan);

        // Ada saringan, tautan hapus filter-nya hidup lagi.
        $this->assertStringNotContainsString('aria-disabled="true"', $menyala);
    }

    public function test_urutan_hanya_ada_terbaru_dan_terlama(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();

        $this->assertStringContainsString('value="terbaru"', $html);
        $this->assertStringContainsString('value="terlama"', $html);
        $this->assertStringNotContainsString('value="dilihat"', $html);
    }

    /*
     * =============================================================
     * PANAH DROPDOWN TEPAT DI PILNYA
     * =============================================================
     *
     * Bug ini sudah pernah dua kali lolos dari test markup: markup ketiga
     * select memang identik, jadi tidak ada yang bisa diperiksa dari HTML.
     * Yang rusak murni CSS, jadi penjaganya juga di CSS.
     *
     * Akar masalahnya: .ad-pilih__bungkus tidak display: flex, sehingga
     * <select> di dalamnya memakai lebar teks opsi terpanjangnya, bukan
     * lebar kolom — sementara panahnya diposisikan ke tepi wrapper. Diukur
     * di Chrome, pil "Terbaru" hanya 101px di dalam kolom 202px dan panahnya
     * melayang 88px di kanan pil itu.
     */
    public function test_pembungkus_select_adalah_flex_container(): void
    {
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(
            1,
            preg_match('/\.ad-pilih__bungkus\s*\{([^}]*)\}/', $css, $cocok),
            'Aturan .ad-pilih__bungkus tidak ditemukan di admin.css.'
        );

        $this->assertMatchesRegularExpression(
            '/(?:^|;|\s)display\s*:\s*flex\s*(?:;|$)/m',
            $cocok[1],
            '.ad-pilih__bungkus wajib display: flex, kalau tidak <select> tidak '
            .'mengisi kolomnya dan panah dropdown terlihat lepas dari pilnya.'
        );
    }

    public function test_kaki_kartu_boleh_membungkus_jangan_meluber(): void
    {
        /*
         * Pada grid empat kolom satu kartu cuma ~218px, sedangkan kaki
         * kartunya perlu ~222px (lencana "Dipublikasikan" 105px + tombol
         * Lihat + tombol tiga titik 117px). Selagi kartu memakai
         * overflow: hidden, kelebihan itu terpotong — dan itulah yang
         * membuat tombol tiga titik terlihat "ketimpa card". Setelah
         * overflow-nya dihapus (supaya menu bisa keluar), kelebihan yang
         * sama akan meluber keluar halaman.
         *
         * Jadi flex-wrap di kaki kartu wajib: saat kolomnya sempit, tombolnya
         * turun ke baris sendiri di bawah lencana.
         */
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-kartu-daftar__kaki\s*\{([^}]*)\}/', $css, $cocok));

        $aturan = $this->tanpaKomentar($cocok[1]);

        $this->assertStringContainsString('flex-wrap: wrap', $aturan);
        $this->assertStringContainsString('min-width: 0', $aturan);

        // Tombolnya boleh turun baris tapi tidak boleh diremas.
        $this->assertSame(1, preg_match('/\.ad-kartu-daftar__aksi\s*\{([^}]*)\}/', $css, $aksi));
        $this->assertStringContainsString('flex-shrink: 0', $this->tanpaKomentar($aksi[1]));
    }

    public function test_kartu_tidak_memotong_menu_tiga_titik(): void
    {
        /*
         * Menu tiga titik diposisikan absolute DI DALAM kartu, jadi
         * overflow: hidden pada .ad-kartu-daftar akan memotongnya persis di
         * tepi kartu — menunya tidak pernah terlihat dan tombolnya ikut
         * terlihat terpotong. Pembulatan sudut ada di blok gambar, bukan di
         * kartu, jadi kartu tidak butuh overflow sama sekali.
         *
         * Kartu juga harus melayang di atas kartu berikutnya saat di-hover,
         * supaya menunya tidak ketimpa kartu yang tidak sedang di-hover.
         */
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-kartu-daftar\s*\{([^}]*)\}/', $css, $kartu));
        $this->assertStringNotContainsString('overflow', $this->tanpaKomentar($kartu[1]));

        // Blok gambar yang memotong, dan hanya sudut atasnya yang dibulatkan.
        $this->assertSame(1, preg_match('/\.ad-kartu-daftar__gambar\s*\{([^}]*)\}/', $css, $gambar));
        $this->assertStringContainsString('overflow: hidden', $this->tanpaKomentar($gambar[1]));
        $this->assertMatchesRegularExpression('/border-radius:\s*[\d.]+rem\s+[\d.]+rem\s+0\s+0/', $gambar[1]);

        // Kartu yang di-hover dapat z-index supaya menunya tidak ketimpa.
        $this->assertSame(
            1,
            preg_match('/\.ad-kartu-daftar:hover,\s*\.ad-kartu-daftar:focus-within\s*\{([^}]*)\}/', $css, $hover),
        );
        $this->assertStringContainsString('z-index', $hover[1]);
    }

    /**
     * Buang komentar CSS dari satu blok aturan.
     *
     * Diperlukan karena penjelasan DI DALAM aturan ikut menyebut nama
     * properti yang sedang diperiksa — misalnya komentar "sengaja TIDAK
     * overflow: hidden" di .ad-kartu-daftar. Tanpa ini test-nya akan salah
     * gagal justru karena penjelasannya sudah benar.
     */
    private function tanpaKomentar(string $aturan): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $aturan);
    }

    public function test_pembungkus_select_memakai_kelas_yang_sama(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();

        // Ketiganya harus dibungkus .ad-pilih__bungkus dan memakai .ad-pilih,
        // tidak ada select yang dilepas dari pembungkusnya.
        $this->assertSame(3, substr_count($html, 'class="ad-pilih__bungkus"'));
        $this->assertSame(3, substr_count($html, 'class="ad-pilih"'));

        // Tiap pembungkus harus punya tepat satu select dan satu svg panah.
        preg_match_all('#<div class="ad-pilih__bungkus">(.*?)</div>#s', $html, $bungkus);

        $this->assertCount(3, $bungkus[1]);

        foreach ($bungkus[1] as $isi) {
            $this->assertSame(1, substr_count($isi, '<select'));
            $this->assertSame(1, substr_count($isi, '<svg'));
        }
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
     * PANEL DETAIL: SUDAH DIHAPUS
     * =============================================================
     * Panel "Detail Materi" di sebelah kanan dicabut, jadi daftar jadi satu
     * kolom selebar penuh. Test di bawah hanya menjaga sisa jejak panel itu
     * tidak ikut tinggal: tidak ada lagi parameter "?materi=" yang memilih
     * materi, dan panel kosong tidak muncul sebagai elemen tersendiri.
     */

    public function test_halaman_tidak_lagi_memakai_parameter_materi(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Dasar HTML');

        $halaman = $this->actingAs($admin)
            ->get(route('admin.materi', ['materi' => $materi->slug]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('materi='.$materi->slug, $halaman);
        $this->assertStringNotContainsString('ad-panel', $halaman);
    }

    public function test_daftar_menampilkan_kartu_dengan_aksi_lihat(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Dasar HTML');

        $halaman = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        // Kartu materi, tombol Lihat, dan menu tiga titik.
        $this->assertStringContainsString('ad-kartu-daftar', $halaman);
        $this->assertStringContainsString(route('admin.materi.show', $materi->slug), $halaman);
        $this->assertStringContainsString('ad-titik__menu', $halaman);

        // Lencana "Materi" di depan judul sudah dicabut: halaman ini hanya
        // berisi materi, jadi lencana itu mengulang isi halaman.
        $this->assertStringNotContainsString('ad-kartu-daftar__tipe', $halaman);
    }

    /*
     * =============================================================
     * BENTUK KARTU: VERTIKAL, SEPERTI KARTU MILIK PENGGUNA
     * =============================================================
     */

    public function test_kartu_vertikal_dengan_blok_gambar_di_atas(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Dasar HTML');

        $html = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();

        // Susunan vertikal: gambar dulu, baru badan. Kalau urutannya
        // terbalik, kartu ini kembali jadi horizontal.
        $gambar = strpos($html, 'ad-kartu-daftar__gambar');
        $badan = strpos($html, 'ad-kartu-daftar__badan');

        $this->assertIsInt($gambar);
        $this->assertIsInt($badan);
        $this->assertLessThan($badan, $gambar, 'Blok gambar harus di atas badan kartu.');

        // Bagian yang tidak boleh ikut: kolom horizontal yang dulu dipakai,
        // dan baris kosong yang menggantung di sebelah.
        $this->assertStringNotContainsString('ad-kartu-daftar__kanan', $html);
    }

    public function test_kartu_menampilkan_kategori_dan_tidak_menampilkan_deskripsi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit(
            $this->buatPengguna(['nama' => 'Natania', 'email' => 'natania@example.com']),
            'Dasar HTML',
            $this->buatPelajaran('Teknologi', 'teknologi'),
        );

        $html = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();

        // Baris yang di kartu user dipakai deskripsi, di sini kategori.
        $this->assertStringContainsString('Teknologi', $html);
        $this->assertStringContainsString('Dibuat oleh:', $html);
        $this->assertStringContainsString('Natania', $html);
    }

    public function test_paginasi_tanpa_kartu_putih_dan_tanpa_jumlah_data(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        for ($i = 1; $i <= 21; $i++) {
            $this->buatTerbit($pemilik, 'Materi Paginasi '.$i);
        }

        $html = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();

        // Baris rekap jumlah data dihapus.
        $this->assertStringNotContainsString('Menampilkan', $html);

        // Paginasi tetap ada, tapi tidak lagi memakai kartu putih.
        $this->assertStringContainsString('ad-paginasi', $html);
        $this->assertStringNotContainsString('ad-paginasi__info', $html);
    }

    /*
     * =============================================================
     * TOMBOL EDIT: HANYA UNTUK MATERI BUATAN ADMIN SENDIRI
     * =============================================================
     */

    public function test_menu_edit_muncul_untuk_materi_yang_dibuat_admin(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($admin, 'Materi Buatan Admin');

        $halaman = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.materi.edit', $materi->slug), $halaman);
    }

    public function test_menu_edit_tidak_muncul_untuk_materi_buatan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Materi Buatan Pengguna');

        $halaman = $this->actingAs($admin)
            ->get('/admin/materi')
            ->assertOk()
            ->getContent();

        // Menu masih ada, tapi isinya hanya Lihat dan Hapus.
        $this->assertStringNotContainsString(route('admin.materi.edit', $materi->slug), $halaman);
        $this->assertStringContainsString(route('admin.materi.show', $materi->slug), $halaman);
        $this->assertStringContainsString(route('admin.materi.destroy', $materi->slug), $halaman);
    }

    /*
     * =============================================================
     * PAGINASI
     * =============================================================
     */

    public function test_daftar_dipaginasi_dua_puluh_per_halaman(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        /*
         * Tanggal terbit diberi berbeda-beda, satu menit per materi. Kalau
         * semuanya now() dalam detik yang sama, urutannya seri dan database
         * bebas menaruh kartunya di halaman mana saja — test ini lalu gagal
         * tanpa ada yang benar-benar berubah.
         */
        for ($i = 1; $i <= 21; $i++) {
            $this->buatTerbit(
                $pemilik,
                'Materi Paginasi '.$i,
                terbitPada: now()->subMinutes(22 - $i)->toDateTimeString(),
            );
        }

        /*
         * Jumlah kartu dihitung, bukan judulnya: "Materi Paginasi 2" adalah
         * awalan dari "Materi Paginasi 21", jadi assertsSee pada nomor akan
         * selalu cocok begitu saja. Yang benar-benar diuji di sini adalah
         * batas 20 per halaman.
         */
        $halamanSatu = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();
        $halamanDua = $this->actingAs($admin)
            ->get(route('admin.materi', ['page' => 2]))
            ->assertOk()
            ->getContent();

        $this->assertSame(20, substr_count($halamanSatu, 'ad-kartu-daftar__judul'));
        $this->assertSame(1, substr_count($halamanDua, 'ad-kartu-daftar__judul'));

        // Yang terbaru di halaman pertama, yang terlama di halaman kedua.
        $this->assertStringContainsString('Materi Paginasi 21', $halamanSatu);
        $this->assertStringNotContainsString('Materi Paginasi 1"', $halamanSatu);
        $this->assertStringContainsString('Materi Paginasi 1"', $halamanDua);
        $this->assertStringNotContainsString('Materi Paginasi 2"', $halamanDua);
    }

    public function test_jumlah_kartu_per_halaman_sama_dengan_halaman_pengguna(): void
    {
        // 20 = 5 baris penuh pada grid empat kolom, sama seperti
        // DaftarMateri::perHalaman() di halaman Materi pengguna.
        $this->assertSame(20, DaftarMateriAdmin::perHalaman());
        $this->assertSame(DaftarMateri::perHalaman(), DaftarMateriAdmin::perHalaman());
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

    public function test_admin_bisa_membuka_form_edit_materi_yang_dibuatnya_sendiri(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($admin, 'Materi Buatan Admin');

        $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('Edit Materi')
            ->assertSee('Materi Buatan Admin')
            // Form milik pemilik menyebut "Ajukan Persetujuan"; di sini tidak.
            ->assertDontSee('Ajukan Persetujuan');
    }

    public function test_admin_tidak_bisa_mengedit_materi_buatan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatTerbit($this->buatPengguna(), 'Materi Buatan Pengguna');

        // Menu-nya tidak menampilkan Edit, dan URL yang diketik manual
        // ditolak dengan aturan yang sama.
        $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), [
                'pelajaran_id' => $this->buatPelajaran()->id,
                'nama' => 'Berhasil Diubah',
                'isi' => 'Isi materi yang tidak seharusnya bisa diubah admin.',
                'tingkat_kesulitan' => 'Mudah',
            ])
            ->assertForbidden();

        $this->assertSame('Materi Buatan Pengguna', $materi->refresh()->nama);
    }

    public function test_admin_bisa_memperbarui_materi_dan_statusnya_tetap_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatTerbit($admin, 'Judul Lama');
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
        $materi = $this->buatTerbit($admin, 'Judul Lama');

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

    public function test_admin_bisa_menghapus_materi_buatan_pengguna(): void
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
