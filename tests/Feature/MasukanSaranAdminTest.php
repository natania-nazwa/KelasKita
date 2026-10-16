<?php

namespace Tests\Feature;

use App\Models\MasukanSaran;
use App\Models\User;
use App\Support\Ikon;
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

    public function test_badge_sidebar_menghitung_masukan_yang_belum_dibaca(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $this->buatMasukan($budi, 'Kiriman baru pertama');
        $this->buatMasukan($budi, 'Kiriman baru kedua');
        $this->buatMasukan($budi, 'Kiriman selesai', MasukanSaran::STATUS_SELESAI);

        $html = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        // Angka badge menempel pada menu "Masukan & Saran", jadi diukur dari
        // potongan tepat setelah tautannya, bukan di seluruh halaman.
        $potongan = $this->potonganMenuMasukan($html);

        $this->assertStringContainsString('ad-sisi__jumlah', $potongan);
        $this->assertStringContainsString('>2</span>', $potongan);
    }

    public function test_badge_sidebar_hilang_kalau_tidak_ada_masukan_baru(): void
    {
        $admin = $this->buatAdmin();
        $budi = $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $this->buatMasukan($budi, 'Kiriman selesai', MasukanSaran::STATUS_SELESAI);

        $html = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('ad-sisi__jumlah', $this->potonganMenuMasukan($html));
    }

    /**
     * Potongan HTML mulai dari tautan menu "Masukan & Saran" beberapa ratus
     * karakter ke depan, cukup untuk memuat ikon, label, dan angkanya.
     */
    private function potonganMenuMasukan(string $html): string
    {
        $posisi = strpos($html, 'href="'.route('admin.masukan-saran').'"');

        $this->assertNotFalse($posisi, 'Tautan menu Masukan & Saran tidak ditemukan di sidebar.');

        return substr($html, $posisi, 1500);
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
        $this->assertSame(1, $statistik['diproses']);
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

    /**
     * Tombol kembali ke Dashboard berdiri di isi konten, hanya di layar kecil.
     *
     * Di bawah 768px sidebar disembunyikan, jadi halaman ini butuh jalan pulang
     * sendiri. Tombolnya duduk di dalam kepala halaman (isi konten), sama
     * seperti Pengaturan, dan disembunyikan di >= 768px lewat md:hidden supaya
     * tidak tampil berdua dengan menu Dashboard di sidebar.
     *
     * Lima sisi yang dijaga, masing-masing bisa hilang sendiri:
     *
     *   - tautannya benar-benar ke Dashboard, bukan ke halaman sebelumnya;
     *   - ikonnya panah-kiri. Dicek lewat jalur SVG-nya (Ikon::path), bukan
     *     lewat atribut nama="...", karena <x-admin.ikon> tidak pernah
     *     merender nama atributnya ke HTML. Memakai jalur SVG sekaligus
     *     membuat test gagal kalau ikonnya diganti, bukan lolos diam-diam;
     *   - letaknya di dalam <main class="ad-isi">, bukan di header ponsel;
     *   - kelas md:hidden ada, supaya tombolnya justru hilang di atas 768px;
     *   - admin.css punya aturan pasangan .ad-tombol.md:hidden, karena
     *     .ad-tombol menulis display tanpa @layer sehingga md:hidden dari
     *     Tailwind (di @layer utilities) kalah cascade dan tombolnya tetap
     *     tampil di desktop tanpa aturan ini. Kedua sisi terakhir tidak boleh
     *     saling menggantikan: kelas tanpa aturan = terlihat di desktop,
     *     aturan tanpa kelas = tidak menempel pada tombolnya.
     */
    public function test_tombol_kembali_ke_dashboard_hanya_di_layar_kecil(): void
    {
        $admin = $this->buatAdmin();

        $halaman = $this->actingAs($admin)->get(route('admin.masukan-saran'))->assertOk();
        $isi = $halaman->baseResponse->getContent();

        $halaman->assertSee('Kembali ke Dashboard');
        $halaman->assertSee(Ikon::path('panah-kiri'), false);

        $this->assertSame(
            1,
            preg_match(
                '/<a[^>]*href="'.preg_quote(route('admin.dashboard'), '/').'"[^>]*class="[^"]*\bmd:hidden\b[^"]*"[^>]*>/',
                $isi
            ),
            'Tombol kembali harus menunjuk Dashboard dan memakai md:hidden supaya hilang di atas 768px.'
        );

        $this->assertStringNotContainsString(
            'hidden md:inline-flex',
            $isi,
            'Tombol kembali terbalik: disembunyikan justru di layar kecil.'
        );

        /*
         * Kelas md:hidden saja tidak cukup: utilitasnya berada di
         * @layer utilities sehingga kalah dari .ad-tombol yang menulis
         * display tanpa lapisan. Yang menutup desktop adalah aturan
         * pasangan di admin.css; komennya ditarik dulu supaya pencarian
         * tidak cocok dengan penjelasan di dalam komentar.
         */
        $css = (string) preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/admin.css')));

        $this->assertSame(
            1,
            preg_match(
                '/@media \(min-width: 768px\)\s*\{\s*\.ad-tombol\.md\\\\:hidden\s*\{\s*display:\s*none;\s*\}\s*\}/',
                $css
            ),
            'admin.css harus punya aturan .ad-tombol.md:hidden di media 768px supaya tombol kembali benar-benar hilang di desktop.'
        );

        $posisiKonten = strpos($isi, '<main class="ad-isi"');
        $posisiKembali = strpos($isi, 'Kembali ke Dashboard');

        $this->assertNotFalse($posisiKonten);
        $this->assertNotFalse($posisiKembali);
        $this->assertLessThan(
            $posisiKembali,
            $posisiKonten,
            'Tombol kembali harus berada di dalam isi konten, bukan di header ponsel.'
        );
    }

    /**
     * Header ponsel punya jalan pintas ke halaman ini.
     *
     * Di bawah 768px sidebar disembunyikan, dan menu "Masukan & Saran" sengaja
     * tidak ikut navigasi bawah (barisnya dipatok enam tautan). Ikon di header
     * ponsel mengisi jalur yang hilang itu: diletakkan di sebelah kiri ikon
     * Pengaturan dan memakai lambang yang sama dengan menu di sidebar desktop
     * ("pesan"), supaya satu halaman dikenali dengan lambang yang sama.
     */
    public function test_header_ponsel_punya_jalan_pintas_ke_masukan_dan_saran(): void
    {
        $admin = $this->buatAdmin();

        $isi = $this->actingAs($admin)
            ->get(route('admin.masukan-saran'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertSame(
            1,
            preg_match(
                '/<a[^>]*href="'.preg_quote(route('admin.masukan-saran'), '/').'"[^>]*aria-label="Masukan &amp; Saran"[^>]*>/',
                $isi
            ),
            'Ikon jalan pintas di header ponsel harus menunjuk halaman Masukan & Saran.'
        );

        /*
         * Lambangnya harus ikon "pesan" yang sama dengan menu sidebar desktop.
         * Dicari tepat setelah tautannya (jarak dibatasi) supaya yang cocok
         * benar-benar SVG di dalam tautan itu, bukan ikon lain di halaman.
         */
        $this->assertSame(
            1,
            preg_match(
                '/aria-label="Masukan &amp; Saran"[^>]*>.{0,300}?'.preg_quote(Ikon::path('pesan'), '/').'/s',
                $isi
            ),
            'Jalan pintas harus memakai ikon "pesan", sama seperti menu Masukan & Saran di sidebar desktop.'
        );

        $posisiPintas = strpos($isi, 'aria-label="Masukan &amp; Saran"');
        $posisiPengaturan = strpos($isi, 'aria-label="Pengaturan"');

        $this->assertNotFalse($posisiPintas);
        $this->assertNotFalse($posisiPengaturan);
        $this->assertLessThan(
            $posisiPengaturan,
            $posisiPintas,
            'Jalan pintas harus berada sebelum ikon Pengaturan (di sebelah kirinya).'
        );

        /* Jalan pintas ini global: halaman admin lain pun harus punya, supaya
           halaman Masukan & Saran bisa dijangkau dari mana saja di layar kecil. */
        $dashboard = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertStringContainsString('aria-label="Masukan &amp; Saran"', $dashboard);
    }
}
