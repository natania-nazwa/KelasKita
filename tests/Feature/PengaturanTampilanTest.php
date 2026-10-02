<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Pelajaran;
use App\Models\Preferensi;
use App\Models\Quiz;
use App\Models\User;
use App\Support\NotifikasiAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Yang benar-benar tertulis di HTML halaman Pengaturan admin.
 *
 * Test PengaturanAdminTest memeriksa perilakunya: route terbuka untuk siapa,
 * isian apa yang tersimpan, dan apa yang tidak boleh terjadi. Test ini
 * memeriksa hal yang lain: markup-nya benar-benar ada di halaman yang
 * dirender.
 *
 * Dipisah karena kerusakannya berbeda. Route yang salah akan menggagalkan
 * PengaturanAdminTest; elemen yang hilang dari halaman hanya kelihatan di
 * sini, dan it'll lolos semua test lain tanpa pernah ketahuan.
 */
class PengaturanTampilanTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatAdmin(array $atribut = []): User
    {
        return $this->buatPengguna(array_merge([
            'nama' => 'Admin KelasKita',
            'email' => 'admin@example.com',
            'peran' => User::PERAN_ADMIN,
        ], $atribut));
    }

    private function buatPelajaran(string $nama = 'Pemrograman', string $slug = 'pemrograman'): Pelajaran
    {
        return Pelajaran::create(['nama' => $nama, 'slug' => $slug, 'aktif' => true]);
    }

    /* ================================================================
     * KERANGKA HALAMAN
     * ================================================================ */

    public function test_dua_kolom_dan_enam_kartu_seksi_ada(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        // Dua kolom yang berubah jadi satu kolom di bawah 1024px.
        $this->assertStringContainsString('ad-atur-kolom', $html);
        $this->assertStringContainsString('ad-atur-tumpukan', $html);

        // Lima seksi Berjudul: Akun, Preferensi, Sistem di kiri; Pengaturan
        // Pembelajaran dan Keamanan Lanjutan di kanan. Kartu Keluar dari Akun
        // sengaja tidak punya judul, jadi tidak dihitung di sini.
        foreach ([
            'Akun',
            'Preferensi',
            'Sistem',
            'Pengaturan Pembelajaran',
            'Keamanan Lanjutan',
        ] as $seksi) {
            $this->assertStringContainsString(
                '>'.$seksi.'</h2>',
                $html,
                "Seksi \"{$seksi}\" tidak ada di halaman."
            );
        }
    }

    public function test_kartu_keluar_dari_akun_tanpa_judul_tapi_tombolnya_berfungsi(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        // Kepala kartunya dihapus: tidak ada judul "Keluar dari Akun" sebagai
        // h2 dan tidak ada ikon pintu keluar yang jadi penanda judul.
        $this->assertStringNotContainsString('>Keluar dari Akun</h2>', $html);
        $this->assertStringNotContainsString('Akhiri sesi login admin di perangkat ini.', $html);

        // Tapi baris logout-nya tetap utuh, lengkap dengan ikon dan dialognya.
        $this->assertStringContainsString('ad-atur-seksi--bahaya', $html);
        $this->assertStringContainsString('ad-atur-baris--bahaya', $html);
        $this->assertStringContainsString('data-atur-dialog="atur-keluar"', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
    }

    public function test_kepala_halaman_memuat_judul_dan_subjudul(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertSee('Pengaturan')
            ->assertSee('Kelola preferensi akun, tampilan, notifikasi, dan aplikasi.');
    }

    public function test_semua_baris_pengaturan_memang_ada(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        foreach ([
            'Profil Admin',
            'Kelola foto profil, nama, dan email.',
            'Keamanan',
            'Ubah password dan kelola keamanan akun.',
            'Tampilan',
            'Pilih mode tampilan aplikasi.',
            'Notifikasi',
            'Atur notifikasi yang ingin diterima.',
            'Kelola Mata Pelajaran',
            'Atur mata pelajaran yang digunakan.',
            'Pengaturan Publikasi',
            'Atur default konten baru.',
            'Sesi Login',
            'Lihat perangkat yang sedang login.',
            'Logout dari Semua Perangkat',
            'Keluar dari semua perangkat yang terhubung.',
            'Informasi Sistem',
            'Tentang Kelas Kita',
            'Keluar dari Akun',
            'Anda akan keluar dari aplikasi.',
        ] as $teks) {
            $this->assertStringContainsString($teks, $html, "Teks \"{$teks}\" hilang.");
        }
    }

    public function test_jumlah_mata_pelajaran_tertulis_di_barisnya(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatPelajaran('Basis Data', 'basis-data');
        Pelajaran::create(['nama' => 'UI UX', 'slug' => 'ui-ux', 'aktif' => false]);

        // Yang dihitung hanya yang aktif, karena itu yang jadi pilihan konten.
        $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertSee('2 mata pelajaran');
    }

    public function test_baris_yang_membuka_halaman_menyasar_url_yang_benar(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        foreach ([
            'admin.pengaturan.profil',
            'admin.pengaturan.pelajaran',
            'admin.pengaturan.sesi',
            'admin.pengaturan.sistem',
            'admin.pengaturan.tentang',
        ] as $nama) {
            $this->assertStringContainsString(route($nama), $html, "URL {$nama} tidak ada.");
        }
    }

    /* ================================================================
     * AKSESIBILITAS
     * ================================================================ */

    public function test_kendali_punya_label_yang_bisa_dibaca(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.profil'))->assertOk()->getContent();

        /*
         * Pola label project adalah label yang membungkus input-nya, bukan
         * pasangan for/id. Hubungan itu tetap terbaca pembaca layar, jadi
         * yang diperiksa di sini justru bentuknya: setiap kolom isian berada
         * di dalam label-nya sendiri.
         */
        foreach (['Nama lengkap', 'Email'] as $label) {
            $this->assertMatchesRegularExpression(
                '/<label[^>]*>\s*<span[^>]*>'.preg_quote($label, '/').'<\/span>.*?<input/s',
                $html,
                "Kolom {$label} tidak dibungkus label."
            );
        }

        // Tombol "Ganti Foto" punya teks yang terlihat, jadi tidak butuh
        // aria-label. Tombol tutup dialog hanya ikon, jadi wajib punya.
        $this->assertStringContainsString('Ganti Foto', $html);
        $this->assertStringContainsString('aria-label="Tutup"', $html);
    }

    public function test_saklar_notifikasi_memakai_role_switch(): void
    {
        $admin = $this->buatAdmin();

        // Satu saklar dimatikan supaya kedua keadaan ada di halaman yang sama.
        Preferensi::ambil($admin)->fill(['notifikasi_konten_terbit' => false])->save();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        /*
         * Lima saklar di halaman ini: empat untuk notifikasi dan satu untuk
         * konfirmasi publish. Semuanya harus pakai role="switch" supaya
         * pembaca tahu itu nyala/mati, bukan centang biasa.
         */
        $this->assertSame(5, substr_count($html, 'role="switch"'));

        foreach ([
            'Konten berhasil dipublikasikan',
            'Konten berhasil disimpan sebagai draft',
            'Ada hasil kuis baru',
            'Konten menunggu ditinjau',
            'Konfirmasi sebelum Publish',
        ] as $nama) {
            $this->assertStringContainsString('aria-label="'.$nama.'"', $html, "Saklar {$nama} tidak ada.");
        }

        $this->assertStringContainsString('aria-checked="true"', $html);
        $this->assertStringContainsString('aria-checked="false"', $html);
    }

    public function test_mematikan_saklar_tetap_mengirim_nol(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        /*
         * Field tersembunyi dengan nilai 0 adalah satu-satunya cara checkbox
         * yang tidak dicentang bisa sampai ke server. Kalau hilang, saklarnya
         * tidak akan pernah benar-benar bisa dimatikan.
         */
        foreach ([
            'notifikasi_konten_terbit',
            'notifikasi_konten_draft',
            'notifikasi_aktivitas_kuis',
            'notifikasi_aktivitas_konten',
            'konfirmasi_publikasi',
        ] as $kolom) {
            $this->assertStringContainsString(
                'name="'.$kolom.'" value="0"',
                $html,
                "Field tersembunyi {$kolom} tidak ada."
            );
        }
    }

    public function test_segmen_tema_menandai_mode_yang_aktif(): void
    {
        $admin = $this->buatAdmin();
        Preferensi::ambil($admin)->simpanTema('gelap');

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        /*
         * Dua atribut ini dicek terpisah: Blade boleh memecah baris di
         * antara keduanya, jadi assertion harus tahan terhadap itu.
         */
        $gelap = strpos($html, 'data-atur-tema-pilih="gelap"');
        $terang = strpos($html, 'data-atur-tema-pilih="terang"');

        $this->assertNotFalse($gelap, 'Tombol Gelap tidak ada.');
        $this->assertNotFalse($terang, 'Tombol Terang tidak ada.');

        $this->assertStringContainsString(
            'aria-pressed="true"',
            substr($html, $gelap, 260),
            'Mode gelap tidak ditandai sebagai yang aktif.'
        );

        $this->assertStringContainsString(
            'aria-pressed="false"',
            substr($html, $terang, 260),
            'Mode terang tidak ditandai sebagai yang tidak aktif.'
        );

        $this->assertStringContainsString('role="group" aria-label="Mode tampilan"', $html);
    }

    /* ================================================================
     * THEME
     * ================================================================ */

    public function test_script_anti_kedip_dipasang_di_kepala_halaman(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        // Script-nya harus mendahului stylesheet, kalau tidak tidak berguna.
        $posisiScript = strpos($html, 'var dariServer');
        $posisiCss = strpos($html, 'rel="stylesheet"');

        $this->assertNotFalse($posisiScript, 'Script tema tidak ada di halaman.');
        $this->assertNotFalse($posisiCss, 'Stylesheet tidak dimuat.');
        $this->assertLessThan($posisiCss, $posisiScript, 'Script tema memuat setelah CSS.');
    }

    public function test_mode_gelap_didukung_di_setiap_halaman_admin(): void
    {
        /*
         * Mode gelap dipasang lewat satu blok [data-theme='gelap'] di
         * admin.css, jadi setiap halaman admin yang memakai layout itu ikut
         * berubah tanpa perlu aturan tambahan per halaman.
         */
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertStringContainsString("[data-theme='gelap']", $css);

        // Layout admin benar-benar memasang atribut itu di <html>.
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        $this->assertStringContainsString('document.documentElement.dataset.theme', $html);
        $this->assertStringContainsString("localStorage.getItem('kk-tema')", $html);
    }

    public function test_setiap_halaman_admin_membawa_skrip_tema(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();
        Materi::create([
            'pelajaran_id' => Pelajaran::query()->first()->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi',
            'slug' => 'materi',
        ]);

        // Layout-nya sama untuk semua halaman admin, jadi skripnya harus ada
        // di semuanya.
        foreach ([
            '/admin/dashboard',
            '/admin/konten',
            '/admin/konten/materi/tambah',
            '/admin/verifikasi',
            '/admin/materi',
            '/admin/quiz',
            '/admin/pengguna',
        ] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'var dariServer',
                $html,
                "Skrip tema tidak ada di {$url}."
            );
        }
    }

    /* ================================================================
     * KELOLA MATA PELAJARAN
     * ================================================================ */

    public function test_daftar_mata_pelajaran_menampilkan_nama_status_dan_jumlah(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        Pelajaran::create(['nama' => 'Nonaktif', 'slug' => 'nonaktif', 'aktif' => false]);

        Materi::create([
            'pelajaran_id' => $pelajaran->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi Satu',
            'slug' => 'materi-satu',
        ]);

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.pelajaran'))->assertOk()->getContent();

        $this->assertStringContainsString('Pemrograman', $html);
        $this->assertStringContainsString('Nonaktif', $html);
        $this->assertStringContainsString('1 aktif', $html);
        $this->assertStringContainsString('1 nonaktif', $html);
        $this->assertStringContainsString('1 materi', $html);
    }

    public function test_tombol_hapus_hanya_muncul_untuk_yang_belum_dipakai(): void
    {
        $admin = $this->buatAdmin();
        $dipakai = $this->buatPelajaran();

        Materi::create([
            'pelajaran_id' => $dipakai->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi',
            'slug' => 'materi',
        ]);

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.pelajaran'))->assertOk()->getContent();

        $this->assertStringContainsString('Sudah dipakai konten', $html);
    }

    public function test_daftar_kosong_menampilkan_pesan_yang_jelas(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan.pelajaran'))
            ->assertOk()
            ->assertSee('Belum ada mata pelajaran')
            ->assertSee('Tambahkan mata pelajaran untuk mulai mengatur konten pembelajaran.');
    }

    public function test_dialog_mata_pelajaran_memuat_kolom_wajib(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.pelajaran'))->assertOk()->getContent();

        $this->assertStringContainsString('data-atur-dialog="atur-pelajaran"', $html);
        $this->assertStringContainsString('name="nama"', $html);
        $this->assertStringContainsString('name="slug"', $html);
        $this->assertStringContainsString('name="deskripsi"', $html);
        $this->assertStringContainsString('name="aktif"', $html);
    }

    /* ================================================================
     * SESI LOGIN
     * ================================================================ */

    public function test_daftar_perangkat_menampilkan_nama_dan_keterangan_waktu(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->buatAdmin();

        DB::table('sessions')->insert([
            'id' => 'sesi-lain',
            'user_id' => $admin->getKey(),
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0 Safari/537.36',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.sesi'))->assertOk()->getContent();

        $this->assertStringContainsString('Perangkat Aktif', $html);
        $this->assertStringContainsString('Chrome', $html);
        $this->assertStringContainsString('Windows', $html);
        $this->assertStringContainsString('Perangkat ini', $html);
    }

    /* ================================================================
     * KELUAR DARI AKUN
     * ================================================================ */

    public function test_dialog_keluar_mengirim_form_ke_route_logout(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        $this->assertStringContainsString('data-atur-dialog="atur-keluar"', $html);
        $this->assertStringContainsString('Keluar dari akun?', $html);
        $this->assertStringContainsString('form="form-atur-keluar"', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
    }

    public function test_dialog_logout_semua_mengirim_form_ke_route_sesi(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        $this->assertStringContainsString('data-atur-dialog="atur-logout-semua"', $html);
        $this->assertStringContainsString('Logout dari semua perangkat?', $html);
        $this->assertStringContainsString('action="'.route('admin.pengaturan.sesi.destroy').'"', $html);
    }

    /* ================================================================
     * DIALOG PUBLIKASI
     * ================================================================ */

    public function test_dialog_publikasi_menampilkan_dua_pilihan_status(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk()->getContent();

        $this->assertStringContainsString('Status Default Konten Baru', $html);
        $this->assertStringContainsString('value="draft"', $html);
        $this->assertStringContainsString('value="published"', $html);
        $this->assertStringContainsString('Konten baru akan disimpan sebagai <strong>Draft</strong>', $html);
        $this->assertStringContainsString('Tidak ada persetujuan di aplikasi ini', $html);
    }

    /* ================================================================
     * TOPBAR ADMIN
     * ================================================================ */

    /**
     * Halaman Pengaturan memakai topbar ringkas: tombol buka sidebar saja.
     *
     * Semua sub-halaman diuji, karena topbar ini ikut berubah kalau ada
     * /admin/pengaturan/* yang lupa ikut tertangani.
     */
    public function test_topbar_pengaturan_cuma_punya_tombol_buka_sidebar(): void
    {
        $admin = $this->buatAdmin();

        $halaman = [
            '/admin/pengaturan',
            '/admin/pengaturan/profil',
            '/admin/pengaturan/keamanan',
            '/admin/pengaturan/pelajaran',
            '/admin/pengaturan/sesi',
            '/admin/pengaturan/sistem',
            '/admin/pengaturan/tentang',
        ];

        foreach ($halaman as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-sisi-buka', $html);

            foreach ([
                'ad-atas__cari',
                'ad-atas__notif',
                'ad-atas__akun',
                'data-admin-notif',
                'data-akun-tombol',
            ] as $harusTidakAda) {
                $this->assertStringNotContainsString(
                    $harusTidakAda,
                    $html,
                    "Topbar {$url} masih memuat {$harusTidakAda} padahal harusnya ringkas."
                );
            }
        }
    }

    /**
     * Tidak ada halaman admin yang lagi memakai topbar penuh.
     *
     * Kotak pencarian, lonceng notifikasi, dan menu akun tidak dirender di
     * satu pun halaman area admin — termasuk Dashboard. Yang tersisa hanya
     * tombol buka sidebar.
     *
     * Daftar URL-nya sengaja ditulis satu per satu: satu per menu, dengan
     * sub-halaman yang paling mudah terlupa (detail dan form edit), supaya
     * halaman baru yang nanti ditambahkan ikut tertangkap di sini.
     */
    public function test_tidak_ada_halaman_admin_yang_menampilkan_topbar_penuh(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $materi = Materi::create([
            'pelajaran_id' => Pelajaran::query()->value('id'),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi Topbar',
            'slug' => 'materi-topbar',
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
        ]);

        $quiz = Quiz::create([
            'pelajaran_id' => Pelajaran::query()->value('id'),
            'dibuat_oleh' => $admin->getKey(),
            'judul' => 'Quiz Topbar',
            'slug' => 'quiz-topbar',
            'deskripsi' => 'Ringkasan quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $halaman = [
            // Dashboard.
            route('admin.dashboard'),

            // Konten Pembelajaran: daftar, tab quiz, dan kedua form.
            route('admin.konten'),
            route('admin.konten', ['tab' => 'quiz']),
            route('admin.konten.materi.tambah'),
            route('admin.konten.quiz.tambah'),
            route('admin.konten.materi.edit', $materi->slug),
            route('admin.konten.quiz.edit', $quiz),

            // Verifikasi dan Pengguna.
            route('admin.verifikasi'),
            route('admin.pengguna'),

            // Materi: daftar, detail, form edit.
            route('admin.materi'),
            route('admin.materi.show', $materi->slug),
            route('admin.materi.edit', $materi->slug),

            // Quiz: daftar, detail, form edit.
            route('admin.quiz'),
            route('admin.quiz.show', $quiz),
            route('admin.quiz.edit', $quiz),
        ];

        foreach ($halaman as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-sisi-buka', $html);

            foreach ([
                'ad-atas__cari',
                'ad-atas__notif',
                'ad-atas__akun',
                'data-admin-notif',
                'data-akun-tombol',
            ] as $harusTidakAda) {
                $this->assertStringNotContainsString(
                    $harusTidakAda,
                    $html,
                    "Topbar {$url} masih memuat {$harusTidakAda} padahal topbar penuh sudah tidak dipakai."
                );
            }
        }
    }

    public function test_halaman_pengaturan_tidak_menjalankan_query_lonceng_notifikasi(): void
    {
        $admin = $this->buatAdmin();

        /*
         * Lonceng sengaja tidak dirender di mana pun di area admin, jadi query
         * notifikasi tidak boleh jalan di halaman mana pun. Kalau topbar penuh
         * tanpa sengaja ikut dirender lagi, test ini gagal.
         */
        DB::enableQueryLog();

        $this->actingAs($admin)->get(route('admin.pengaturan'))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $query = DB::getQueryLog();

        DB::disableQueryLog();

        $iniNotifikasi = array_filter(
            $query,
            fn (array $baris): bool => str_contains($baris['query'], 'tb_notifikasi')
        );

        $this->assertSame(
            [],
            array_values($iniNotifikasi),
            'Tidak ada halaman admin yang perlu notifikasi, jadi tidak boleh querying tb_notifikasi.'
        );
    }

    /**
     * Notifikasi admin tetap dibuat dan tetap bisa dibaca, meski loncengnya
     * sudah tidak dirender di halaman mana pun.
     *
     * Yang diuji sejak ini adalah datanya (NotifikasiAdmin::daftar), bukan
     * markup lonceng: isi notifikasi, tautannya, dan waktunya masih jadi
     * data yang dipakai bagian lain, dan tidak boleh hilang hanya karena
     * loncengnya tidak lagi ditampilkan.
     */
    public function test_notifikasi_admin_menampilkan_daftar_konten_menunggu(): void
    {
        $admin = $this->buatAdmin();
        $materi = Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi',
            'slug' => 'materi',
        ]);

        NotifikasiAdmin::kontenMenunggu($materi);

        $notifikasi = NotifikasiAdmin::daftar($admin)->first();

        $this->assertNotNull($notifikasi, 'Notifikasi konten menunggu harus terbaca untuk admin.');
        $this->assertSame('Konten menunggu ditinjau', $notifikasi['judul']);
        $this->assertSame(route('admin.verifikasi'), $notifikasi['tautan']);
    }

    public function test_notifikasi_admin_menautkan_karyanya_sendiri_ke_form_edit(): void
    {
        $admin = $this->buatAdmin();
        $materi = Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi',
            'slug' => 'materi',
        ]);

        NotifikasiAdmin::kontenDiterbitkan($materi, $admin);

        $notifikasi = NotifikasiAdmin::daftar($admin)->first();

        $this->assertNotNull($notifikasi);
        $this->assertSame(route('admin.konten.materi.edit', $materi->slug), $notifikasi['tautan']);
    }

    public function test_notifikasi_admin_menampilkan_waktu_notifikasi(): void
    {
        $admin = $this->buatAdmin();
        $materi = Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi',
            'slug' => 'materi',
        ]);

        NotifikasiAdmin::kontenDiterbitkan($materi, $admin);

        $notifikasi = Notifikasi::query()->latest('id')->firstOrFail();

        $this->assertNotNull($notifikasi->created_at, 'Notifikasi yang ditulis harus punya waktu dibuat.');

        $baris = NotifikasiAdmin::daftar($admin)->first();

        $this->assertNotNull($baris);
        $this->assertNotSame('', $baris['waktu_label'], 'Waktu notifikasi tidak boleh kosong.');
    }

    public function test_lonceng_admin_tidak_menampilkan_judul_notifikasi_di_halaman_pengaturan(): void
    {
        $admin = $this->buatAdmin();
        $materi = Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi',
            'slug' => 'materi',
        ]);

        NotifikasiAdmin::kontenMenunggu($materi);

        $notifikasi = Notifikasi::query()->latest('id')->firstOrFail();

        // Barisnya tetap dibuat di database, hanya tempat membacanya yang tidak
        // ada di halaman ini.
        $this->assertDatabaseHas('tb_notifikasi', [
            'pengguna_id' => $admin->getKey(),
            'jenis' => Notifikasi::JENIS_KONTEN_MENUNGGU,
        ]);

        /*
         * Yang diuji adalah URL tandai-terbaca, bukan judul notifikasi.
         * Judul "Konten menunggu ditinjau" juga dipakai sebagai label saklar
         * di halaman ini, jadi kalau yang dicari judul, test ini akan selalu
         * gagal walau loncengnya benar-benar tidak dirender.
         */
        $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertOk()
            ->assertDontSee(route('admin.pengaturan.notifikasi.baca', $notifikasi), false);
    }

    public function test_halaman_tidak_boleh_punya_tautan_ke_halaman_yang_tidak_ada(): void
    {
        $admin = $this->buatAdmin();

        foreach ([
            '/admin/pengaturan',
            '/admin/pengaturan/tentang',
        ] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            foreach (['Privacy Policy', 'Terms of Service', 'Ketentuan'] as $tidakAda) {
                $this->assertStringNotContainsString(
                    $tidakAda,
                    $html,
                    "Halaman {$url} menautkan ke {$tidakAda} yang tidak ada."
                );
            }
        }
    }

    /* ================================================================
     * WIDGET QUIZ TIDAK RUSAK
     * ================================================================ */

    public function test_form_tambah_quiz_masih_memakai_komponen_yang_sama(): void
    {
        /*
         * Pengaturan memakai form publikasi yang sama dengan menu Konten
         * Pembelajaran. Kalau komponen yang dipakai berubah, halaman ini akan
         * diam-diam ikut berubah tanpa ada yang disengaja.
         */
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $html = $this->actingAs($admin)
            ->get(route('admin.konten.quiz.tambah'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-konten-aksi', $html);
        $this->assertStringContainsString('data-konten-publish-dialog', $html);
    }

    public function test_form_tambah_materi_masih_punya_tombol_publish_dengan_konfirmasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $html = $this->actingAs($admin)
            ->get(route('admin.konten.materi.tambah'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-konten-publish="publish"', $html);
        $this->assertStringContainsString('name="aksi" value="publish"', $html);
    }
}
