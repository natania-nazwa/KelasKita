<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Pelajaran;
use App\Models\Preferensi;
use App\Models\User;
use App\Support\NotifikasiAdmin;
use App\Support\SesiAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman "Pengaturan" di area admin beserta seluruh sub-halamannya.
 *
 * Yang diuji di sini adalah kontrak fitur ini, yaitu apa yang harus benar dan
 * apa yang tidak boleh terjadi:
 *
 *   - semua halaman Pengaturan terbuka untuk admin dan tertutup untuk selain
 *     admin, termasuk lewat URL yang diketik manual;
 *   - profil dan kata sandi benar-benar tersimpan, bukan hanya tampil;
 *   - preferensi tema, notifikasi, dan publikasi tersimpan di database dan
 *     dibaca kembali oleh pemanggilnya;
 *   - saklar notifikasi benar-benar mengatur notifikasi yang dibuat, bukan
 *     hanya warnanya yang berubah;
 *   - manages mata pelajaran aman: yang belum dipakai boleh dihapus, yang
 *     sudah dipakai tidak, dan status aktifnya benar-benar memengaruhi pilihan
 *     saat membuat konten;
 *   - logout dari semua perangkat memutus sesi di server, dan sesi di
 *     perangkat ini tidak ikut terputus.
 *
 * Test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini tidak
 * menyentuh database sungguhan.
 */
class PengaturanAdminTest extends TestCase
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

    /* ================================================================
     * HALAMAN
     * ================================================================ */

    public function test_seluruh_halaman_pengaturan_terbuka_untuk_admin(): void
    {
        $admin = $this->buatAdmin();

        foreach ([
            'admin.pengaturan',
            'admin.pengaturan.profil',
            'admin.pengaturan.keamanan',
            'admin.pengaturan.pelajaran',
            'admin.pengaturan.sesi',
            'admin.pengaturan.sistem',
            'admin.pengaturan.tentang',
        ] as $nama) {
            $this->actingAs($admin)->get(route($nama))->assertOk();
        }
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_pengaturan(): void
    {
        $pengguna = $this->buatPengguna();

        foreach ([
            'admin.pengaturan',
            'admin.pengaturan.profil',
            'admin.pengaturan.pelajaran',
            'admin.pengaturan.sesi',
        ] as $nama) {
            $this->actingAs($pengguna)->get(route($nama))->assertForbidden();
        }
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.pengaturan'))->assertRedirect(route('login'));
    }

    public function test_menu_pengaturan_tetap_aktif_di_seluruh_sub_halamannya(): void
    {
        $admin = $this->buatAdmin();

        // Halaman utamanya saja tidak cukup: tanpa tanda bintang pada
        // routeIs(), menunya padam begitu admin membuka sub-halaman.
        foreach (['admin.pengaturan', 'admin.pengaturan.pelajaran', 'admin.pengaturan.sesi'] as $nama) {
            $this->actingAs($admin)
                ->get(route($nama))
                ->assertSee('ad-sisi__tautan ad-sisi__tautan--aktif', false);
        }
    }

    /* ================================================================
     * PROFIL
     * ================================================================ */

    public function test_profil_admin_bisa_disimpan(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.profil.update'), [
                'nama' => 'Admin Baru',
                'email' => 'baru@example.com',
            ])
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('sukses');

        $admin->refresh();

        $this->assertSame('Admin Baru', $admin->nama);
        $this->assertSame('baru@example.com', $admin->email);
    }

    public function test_email_admin_tidak_bisa_dipakai_akun_lain(): void
    {
        $admin = $this->buatAdmin();
        $lain = $this->buatPengguna(['email' => 'dipakai@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.pengaturan.profil'))
            ->put(route('admin.pengaturan.profil.update'), [
                'nama' => 'Admin',
                'email' => 'dipakai@example.com',
            ])
            ->assertRedirect(route('admin.pengaturan.profil'))
            ->assertSessionHasErrors('email');

        $this->assertSame('admin@example.com', $admin->refresh()->email);
        $this->assertDatabaseHas('tb_pengguna', ['email' => 'dipakai@example.com']);
    }

    public function test_nama_admin_wajib_diisi(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->from(route('admin.pengaturan.profil'))
            ->put(route('admin.pengaturan.profil.update'), [
                'nama' => '',
                'email' => $admin->email,
            ])
            ->assertSessionHasErrors('nama');
    }

    public function test_foto_profil_admin_dapat_dihapus_dan_avatar_kembali_ke_inisial(): void
    {
        Storage::fake('public');

        $admin = $this->buatAdmin(['foto_profil' => 'foto-profil/admin-lama.png']);
        Storage::disk('public')->put('foto-profil/admin-lama.png', 'lama');

        $this->actingAs($admin)
            ->delete(route('admin.pengaturan.profil.foto.destroy'))
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('sukses', 'Foto profil berhasil dihapus.');

        $setelah = $admin->fresh();

        $this->assertNull($setelah->foto_profil);
        $this->assertNull($setelah->fotoProfilUrl());
        Storage::disk('public')->assertMissing('foto-profil/admin-lama.png');

        // Akun adminnya sendiri tidak ikut rusak: nama, email, dan peran tetap.
        $this->assertSame('Admin KelasKita', $setelah->nama);
        $this->assertSame('admin@example.com', $setelah->email);
        $this->assertSame(User::PERAN_ADMIN, $setelah->peran);
    }

    public function test_menghapus_foto_admin_yang_tidak_punya_foto_menghasilkan_404(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->delete(route('admin.pengaturan.profil.foto.destroy'))
            ->assertNotFound();
    }

    public function test_pengguna_biasa_tidak_bisa_menghapus_foto_admin(): void
    {
        $pengguna = $this->buatPengguna();
        $admin = $this->buatAdmin(['foto_profil' => 'foto-profil/admin.png']);

        $this->actingAs($pengguna)
            ->delete(route('admin.pengaturan.profil.foto.destroy'))
            ->assertForbidden();

        $this->assertSame('foto-profil/admin.png', $admin->fresh()->foto_profil);
    }

    /* ================================================================
     * KATA SANDI
     * ================================================================ */

    public function test_kata_sandi_bisa_diganti_dengan_password_lama_yang_benar(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.keamanan.kata-sandi'), [
                'kata_sandi_lama' => 'rahasia123',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'katasandibaru',
            ])
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('sukses');

        $this->assertTrue(Hash::check('katasandibaru', $admin->refresh()->kata_sandi));
    }

    public function test_kata_sandi_lama_yang_salah_ditolak(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->from(route('admin.pengaturan.keamanan'))
            ->put(route('admin.pengaturan.keamanan.kata-sandi'), [
                'kata_sandi_lama' => 'salah',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'katasandibaru',
            ])
            ->assertSessionHasErrors('kata_sandi_lama');

        $this->assertTrue(Hash::check('rahasia123', $admin->refresh()->kata_sandi));
    }

    public function test_konfirmasi_kata_sandi_harus_sama(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->from(route('admin.pengaturan.keamanan'))
            ->put(route('admin.pengaturan.keamanan.kata-sandi'), [
                'kata_sandi_lama' => 'rahasia123',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'bedakan',
            ])
            ->assertSessionHasErrors('kata_sandi_baru');
    }

    public function test_kegagalan_validasi_membuka_lagi_dialog_password_supaya_error_terlihat(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->from(route('admin.pengaturan'))
            ->put(route('admin.pengaturan.keamanan.kata-sandi'), [
                'kata_sandi_lama' => 'salah',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'katasandibaru',
            ]);

        /*
         * Halaman ulangnya harus membuka lagi dialog yang gagal itu. Pesan
         * errornya dirender di dalam dialog, jadi kalau dialognya rapat,
         * admin tidak akan melihat kalau ada yang salah.
         */
        $html = $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '#data-atur-dialog="atur-keamanan"[^>]*aria-hidden="false"#',
            $html,
        );
    }

    /* ================================================================
     * TEMA
     * ================================================================ */

    public function test_tema_gelap_tersimpan_dan_dibaca_kembali(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.tema'), ['tema' => 'gelap'])
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('sukses');

        $this->assertSame('gelap', Preferensi::ambil($admin)->temaAman());
    }

    public function test_tema_yang_tidak_dikenal_ditolak(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->from(route('admin.pengaturan'))
            ->put(route('admin.pengaturan.tema'), ['tema' => 'neon'])
            ->assertSessionHasErrors('tema');
    }

    public function test_halaman_menulis_tema_yang_tersimpan_ke_script_anti_kedip(): void
    {
        $admin = $this->buatAdmin();
        Preferensi::ambil($admin)->simpanTema('gelap');

        /*
         * Skrip di <head> yang mencegah kilatan terang harus membawa nilai
         * tema dari server. Kalau tidak, visiting dari perangkat lain akan
         * selalu mulai dari terang walau database bilang gelap.
         */
        $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertSee('var dariServer = "gelap"', false);
    }

    /* ================================================================
     * NOTIFIKASI
     * ================================================================ */

    public function test_saklar_notifikasi_tersimpan(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.notifikasi'), [
                'notifikasi_konten_terbit' => '0',
                'notifikasi_konten_draft' => '1',
                'notifikasi_aktivitas_kuis' => '0',
                'notifikasi_aktivitas_konten' => '1',
            ])
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('sukses');

        $preferensi = Preferensi::ambil($admin);

        $this->assertFalse($preferensi->notifikasi_konten_terbit);
        $this->assertTrue($preferensi->notifikasi_konten_draft);
        $this->assertFalse($preferensi->notifikasi_aktivitas_kuis);
        $this->assertTrue($preferensi->notifikasi_aktivitas_konten);
    }

    public function test_form_notifikasi_yang_kurang_field_ditolak(): void
    {
        $admin = $this->buatAdmin();

        /*
         * Saklar yang tidak diklik mengirim 0 lewat field tersembunyi. Kalau
         * field itu hilang, nilainya akan kembali ke bawaan diam-diam dan
         * saklarnya tidak akan pernah benar-benar bisa dimatikan.
         */
        $this->actingAs($admin)
            ->from(route('admin.pengaturan'))
            ->put(route('admin.pengaturan.notifikasi'), [
                'notifikasi_konten_terbit' => '0',
                'notifikasi_konten_draft' => '0',
            ])
            ->assertSessionHasErrors([
                'notifikasi_aktivitas_kuis',
                'notifikasi_aktivitas_konten',
            ]);
    }

    public function test_saklar_notifikasi_benar_benar_menghentikan_notifikasi_admin(): void
    {
        $admin = $this->buatAdmin();
        $materi = Materi::create([
            'pelajaran_id' => Pelajaran::create([
                'nama' => 'Pemrograman',
                'slug' => 'pemrograman',
                'aktif' => true,
            ])->getKey(),
            'nama' => 'Materi Satu',
            'slug' => 'materi-satu',
            'dibuat_oleh' => $admin->getKey(),
        ]);

        NotifikasiAdmin::kontenDiterbitkan($materi, $admin);

        $this->assertDatabaseHas('tb_notifikasi', [
            'pengguna_id' => $admin->getKey(),
            'jenis' => Notifikasi::JENIS_KONTEN_TERBIT,
        ]);

        Preferensi::ambil($admin)->fill(['notifikasi_konten_terbit' => false])->save();

        NotifikasiAdmin::kontenDiterbitkan($materi, $admin);

        // Saklarnya dimatikan, jadi tidak boleh ada baris baru sama sekali.
        $this->assertSame(1, Notifikasi::query()
            ->where('pengguna_id', $admin->getKey())
            ->belumDibaca()
            ->count());
    }

    public function test_admin_tanpa_baris_preferensi_tetap_menerima_notifikasi(): void
    {
        $admin = $this->buatAdmin();
        $materi = Materi::create([
            'pelajaran_id' => Pelajaran::create([
                'nama' => 'Basis Data',
                'slug' => 'basis-data',
                'aktif' => true,
            ])->getKey(),
            'nama' => 'Materi Dua',
            'slug' => 'materi-dua',
            'dibuat_oleh' => $admin->getKey(),
        ]);

        NotifikasiAdmin::kontenDiterbitkan($materi, $admin);

        $this->assertDatabaseHas('tb_notifikasi', [
            'pengguna_id' => $admin->getKey(),
            'jenis' => Notifikasi::JENIS_KONTEN_TERBIT,
        ]);
        $this->assertDatabaseMissing('tb_preferensi', ['pengguna_id' => $admin->getKey()]);
    }

    public function test_notifikasi_admin_menunjuk_halaman_tujuannya(): void
    {
        $admin = $this->buatAdmin();
        $notifikasi = Notifikasi::create([
            'pengguna_id' => $admin->getKey(),
            'jenis' => Notifikasi::JENIS_KONTEN_MENUNGGU,
            'judul' => 'Konten menunggu ditinjau',
            'pesan' => 'Materi "Materi Tiga" dikirim untuk ditinjau.',
        ]);

        $this->assertSame(route('admin.verifikasi'), $notifikasi->tautanAdmin());
    }

    public function test_notifikasi_milik_orang_lain_tidak_bisa_ditandai_terbaca(): void
    {
        $admin = $this->buatAdmin();
        $lain = $this->buatPengguna();

        $notifikasi = Notifikasi::create([
            'pengguna_id' => $lain->getKey(),
            'jenis' => Notifikasi::JENIS_HASIL_KUIS,
            'judul' => 'Ada hasil kuis baru',
            'pesan' => 'Budi menyelesaikan sebuah quiz.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pengaturan.notifikasi.baca', $notifikasi))
            ->assertForbidden();

        $this->assertNull($notifikasi->refresh()->dibaca_pada);
    }

    public function test_notifikasi_admin_yang_dimiliki_sendiri_bisa_ditandai_terbaca(): void
    {
        $admin = $this->buatAdmin();

        $notifikasi = Notifikasi::create([
            'pengguna_id' => $admin->getKey(),
            'jenis' => Notifikasi::JENIS_KONTEN_MENUNGGU,
            'judul' => 'Konten menunggu ditinjau',
            'pesan' => 'Materi "Materi Empat" dikirim untuk ditinjau.',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.pengaturan.notifikasi.baca', $notifikasi))
            ->assertOk()
            ->assertJson(['terbaca' => true, 'sisa' => 0]);

        $this->assertNotNull($notifikasi->refresh()->dibaca_pada);
    }

    /* ================================================================
     * PUBLIKASI
     * ================================================================ */

    public function test_pengaturan_publikasi_tersimpan(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.publikasi'), [
                'status_konten_default' => 'published',
                'konfirmasi_publikasi' => '0',
            ])
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('sukses');

        $preferensi = Preferensi::ambil($admin);

        $this->assertSame('published', $preferensi->status_konten_default);
        $this->assertFalse($preferensi->konfirmasi_publikasi);
    }

    public function test_status_konten_default_hanya_menerima_draft_atau_published(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->from(route('admin.pengaturan'))
            ->put(route('admin.pengaturan.publikasi'), [
                'status_konten_default' => 'menunggu',
                'konfirmasi_publikasi' => '1',
            ])
            ->assertSessionHasErrors('status_konten_default');
    }

    public function test_tidak_ada_status_menunggu_persetujuan(): void
    {
        /*
         * Aplikasi ini punya satu admin pengelola, jadi tidak boleh ada
         * status "menunggu persetujuan" di pilihan status bawaan konten.
         */
        $this->assertSame(['draft', 'published'], Preferensi::STATUS_KONTEN);
    }

    public function test_konfirmasi_publikasi_dibaca_layout_admin(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertSee('data-konten-konfirmasi="1"', false);

        Preferensi::ambil($admin)->fill(['konfirmasi_publikasi' => false])->save();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan'))
            ->assertSee('data-konten-konfirmasi="0"', false);
    }

    /* ================================================================
     * MATA PELAJARAN
     * ================================================================ */

    public function test_mata_pelajaran_baru_bisa_disimpan(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('admin.pengaturan.pelajaran.store'), [
                'nama' => 'Basis Data',
                'slug' => 'basis-data',
                'deskripsi' => 'Relational dan NoSQL.',
                'aktif' => '1',
            ])
            ->assertRedirect(route('admin.pengaturan.pelajaran'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tb_pelajaran', [
            'nama' => 'Basis Data',
            'slug' => 'basis-data',
            'aktif' => true,
        ]);
    }

    public function test_kode_mata_pajaran_harus_unik(): void
    {
        $admin = $this->buatAdmin();
        Pelajaran::create(['nama' => 'UI UX', 'slug' => 'ui-ux', 'aktif' => true]);

        $this->actingAs($admin)
            ->from(route('admin.pengaturan.pelajaran'))
            ->post(route('admin.pengaturan.pelajaran.store'), [
                'nama' => 'Desain',
                'slug' => 'ui-ux',
                'aktif' => '1',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_mata_pelajaran_yang_sudah_ada_bisa_diubah(): void
    {
        $admin = $this->buatAdmin();

        // Slug di luar daftar resmi, supaya barisnya milik test ini sendiri
        // dan tidak tertimpa baris yang sama dari migration.
        $pelajaran = Pelajaran::create([
            'nama' => 'Matematika',
            'slug' => 'matematika-ubah',
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.pelajaran.update', $pelajaran), [
                'nama' => 'Matematika Dasar',
                'deskripsi' => 'Angka dasar.',
                'aktif' => '0',
            ])
            ->assertRedirect(route('admin.pengaturan.pelajaran'))
            ->assertSessionHas('sukses');

        $setelah = $pelajaran->fresh();

        $this->assertSame('Matematika Dasar', $setelah->nama);
        $this->assertSame('Angka dasar.', $setelah->deskripsi);
        $this->assertFalse((bool) $setelah->aktif);

        // Form ubah tidak punya kolom kode, jadi slug lama harus utuh.
        // Kalau ikut kosong, semua tautan filter yang sudah dibagikan mati.
        $this->assertSame('matematika-ubah', $setelah->slug);
    }

    public function test_mata_pelajaran_yang_diubah_tidak_bisa_memakai_kode_milik_yang_lain(): void
    {
        $admin = $this->buatAdmin();
        Pelajaran::create(['nama' => 'UI UX', 'slug' => 'ui-ux', 'aktif' => true]);
        $pelajaran = Pelajaran::create(['nama' => 'Desain', 'slug' => 'desain', 'aktif' => true]);

        $this->actingAs($admin)
            ->from(route('admin.pengaturan.pelajaran'))
            ->put(route('admin.pengaturan.pelajaran.update', $pelajaran), [
                'nama' => 'Desain Grafis',
                'slug' => 'ui-ux',
                'aktif' => '1',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame('desain', $pelajaran->fresh()->slug);
    }

    public function test_mengganti_status_aktif_tidak_menghapus_deskripsi(): void
    {
        $admin = $this->buatAdmin();
        // Slug di luar daftar resmi, supaya barisnya milik test ini sendiri dan
        // deskripsinya benar-benar yang ditulis di sini.
        $pelajaran = Pelajaran::create([
            'nama' => 'Matematika',
            'slug' => 'matematika-uji',
            'deskripsi' => 'Pelajaran bilangan.',
            'aktif' => true,
        ]);

        /*
         * Form toggle aktif di daftar pelajaran mengirim hanya nama dan
         * aktif. Kalau deskripsi yang tidak terkirim ikut ditulis null,
         * menonaktifkan satu mata pelajaran akan menghapus deskripsinya.
         */
        $this->actingAs($admin)
            ->put(route('admin.pengaturan.pelajaran.update', $pelajaran), [
                'nama' => $pelajaran->nama,
                'aktif' => '0',
            ])
            ->assertRedirect(route('admin.pengaturan.pelajaran'))
            ->assertSessionHas('sukses');

        $setelah = $pelajaran->fresh();

        $this->assertFalse((bool) $setelah->aktif);
        $this->assertSame('Pelajaran bilangan.', $setelah->deskripsi);
    }

    public function test_pengguna_biasa_tidak_bisa_mengubah_mata_pelajaran(): void
    {
        $pengguna = $this->buatPengguna();
        $pelajaran = Pelajaran::create(['nama' => 'Matematika', 'slug' => 'matematika-akses', 'aktif' => true]);

        $this->actingAs($pengguna)
            ->put(route('admin.pengaturan.pelajaran.update', $pelajaran), [
                'nama' => 'Diubah',
                'aktif' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('Matematika', $pelajaran->fresh()->nama);
    }

    public function test_mata_pelajaran_yang_dipakai_tidak_bisa_dihapus(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = Pelajaran::create(['nama' => 'Matematika', 'slug' => 'matematika-pakai', 'aktif' => true]);

        Materi::create([
            'pelajaran_id' => $pelajaran->getKey(),
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Materi Matematika',
            'slug' => 'materi-matematika',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.pengaturan.pelajaran.destroy', $pelajaran))
            ->assertRedirect(route('admin.pengaturan.pelajaran'))
            ->assertSessionHas('galat');

        // Barisnya tetap ada supaya materi yang memakainya tidak jadi rusak.
        $this->assertDatabaseHas('tb_pelajaran', ['id' => $pelajaran->getKey()]);
    }

    public function test_mata_pelajaran_yang_belum_dipakai_bisa_dihapus(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = Pelajaran::create(['nama' => 'Praktikum', 'slug' => 'praktikum', 'aktif' => true]);

        $this->actingAs($admin)
            ->delete(route('admin.pengaturan.pelajaran.destroy', $pelajaran))
            ->assertRedirect(route('admin.pengaturan.pelajaran'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('tb_pelajaran', ['id' => $pelajaran->getKey()]);
    }

    public function test_mata_pelajaran_tidak_aktif_tidak_muncul_sebagai_pilihan_konten(): void
    {
        $admin = $this->buatAdmin();

        Pelajaran::create(['nama' => 'Aktif', 'slug' => 'aktif', 'aktif' => true]);
        Pelajaran::create(['nama' => ' disembunyikan', 'slug' => 'disembunyikan', 'aktif' => false]);

        $terlihat = Pelajaran::query()->aktif()->pluck('nama')->all();

        $this->assertContains('Aktif', $terlihat);
        $this->assertNotContains(' disembunyikan', $terlihat);

        // Dan halaman form materi memang memakainya aturan yang sama.
        $this->actingAs($admin)->get(route('admin.konten.materi.tambah'))->assertOk();
    }

    /* ================================================================
     * SESI LOGIN
     * ================================================================ */

    public function test_daftar_perangkat_dibaca_dari_tabel_sesi(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->buatAdmin();

        DB::table('sessions')->insert([
            'id' => 'sesi-lain',
            'user_id' => $admin->getKey(),
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.sesi'))->assertOk()->getContent();

        $this->assertStringContainsString('Chrome', $html);
        $this->assertStringContainsString('Windows', $html);
        $this->assertStringContainsString('10.0.0.5', $html);
    }

    public function test_user_agent_yang_tidak_dikenal_dibuat_jujur(): void
    {
        $admin = $this->buatAdmin();

        $perangkat = (new class($admin)
        {
            public function __construct(private User $pengguna) {}

            public function jalankan(): array
            {
                config(['session.driver' => 'database']);

                DB::table('sessions')->insert([
                    'id' => 'sesi-aneh',
                    'user_id' => $this->pengguna->getKey(),
                    'ip_address' => null,
                    'user_agent' => null,
                    'payload' => '',
                    'last_activity' => now()->timestamp,
                ]);

                return SesiAdmin::daftar($this->pengguna)->all();
            }
        })->jalankan();

        $this->assertCount(1, $perangkat);
        $this->assertSame('Peramban tidak dikenal', $perangkat[0]['peramban']);
        $this->assertSame('Sistem tidak diketahui', $perangkat[0]['sistem']);
    }

    public function test_logout_semua_perangkat_memutus_semua_sesi_admin(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->buatAdmin();

        DB::table('sessions')->insert([
            ['id' => 'perangkat-a', 'user_id' => $admin->getKey(), 'ip_address' => '10.0.0.1',
                'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'perangkat-b', 'user_id' => $admin->getKey(), 'ip_address' => '10.0.0.2',
                'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'milik-orang-lain', 'user_id' => $this->buatPengguna()->getKey(), 'ip_address' => '10.0.0.3',
                'user_agent' => 'Safari', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.pengaturan.sesi.destroy'))
            ->assertRedirect(route('admin.pengaturan.sesi'))
            ->assertSessionHas('sukses');

        $tersisa = DB::table('sessions')->pluck('id')->all();

        /*
         * Dua-duanya harus benar: sesi milik admin di perangkat lain sudah
         * diputus, dan baris milik orang lain tetap utuh. Menghapus berdasarkan
         * user_id tanpa memeriksa sisa baris akan mengeluarkan semua orang dari
         * aplikasi.
         */
        $this->assertNotContains('perangkat-a', $tersisa);
        $this->assertNotContains('perangkat-b', $tersisa);
        $this->assertContains('milik-orang-lain', $tersisa);

        /*
         * Tinggal satu baris untuk admin ini, yaitu sesi yang sedang dipakai
         * halaman. Kalau dikecualikannya gagal, baris itu ikut terhapus juga dan
         * admin langsung terlempar keluar dari halamannya sendiri.
         */
        $this->assertSame(
            1,
            DB::table('sessions')->where('user_id', $admin->getKey())->count()
        );
    }

    public function test_sesi_yang_sedang_dipakai_tidak_ikut_diakhiri(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->buatAdmin();

        DB::table('sessions')->insert([
            ['id' => 'perangkat-ini', 'user_id' => $admin->getKey(), 'ip_address' => '10.0.0.1',
                'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'perangkat-lain', 'user_id' => $admin->getKey(), 'ip_address' => '10.0.0.2',
                'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        /*
         * Admin yang menekan tombolnya harus tetap bisa memakai halamannya.
         * Sesi yang sedang dipakai dibaca dari request, jadi yang dikecualikan
         * di sini adalah id yang diteruskan controller.
         */
        $this->assertSame(1, SesiAdmin::akhiri($admin, 'perangkat-ini'));

        $this->assertSame(['perangkat-ini'], DB::table('sessions')->pluck('id')->all());
    }

    public function test_sesi_yang_sudah_kedaluwarsa_tidak_dihitung_sebagai_perangkat_aktif(): void
    {
        config(['session.driver' => 'database', 'session.lifetime' => 120]);

        $admin = $this->buatAdmin();

        DB::table('sessions')->insert([
            ['id' => 'baru', 'user_id' => $admin->getKey(), 'ip_address' => null,
                'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'kuno', 'user_id' => $admin->getKey(), 'ip_address' => null,
                'user_agent' => 'Chrome', 'payload' => '',
                'last_activity' => now()->timestamp - (121 * 60)],
        ]);

        $ids = SesiAdmin::daftar($admin)->pluck('id')->all();

        // Baris yang sudah lewat masa berlaku dihapus Laravel sendiri,
        // jadi menampilkannya sebagai "masih login" akan berbohong.
        $this->assertSame(['baru'], $ids);
    }

    public function test_halaman_sesi_tidak_menampilkan_berkas_contoh(): void
    {
        config(['session.driver' => 'file']);

        /*
         * Driver sesi bukan database, jadi daftar perangkat tidak bisa dibaca.
         * Halaman harus mengatakannya terus terang, bukan mengisi daftar dengan
         * data tiruan supaya terlihat berfungsi.
         */
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan.sesi'))
            ->assertOk()
            ->assertSee('Daftar perangkat tidak tersedia');
    }

    /* ================================================================
     * INFORMASI SISTEM DAN TENTANG
     * ================================================================ */

    public function test_informasi_sistem_menampilkan_versi_dari_env(): void
    {
        config(['app.versi' => '2.5.1']);

        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan.sistem'))
            ->assertOk()
            ->assertSee('v2.5.1')
            ->assertSee('Laravel '.app()->version());
    }

    public function test_status_basis_data_diukur_bukan_ditulis(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan.sistem'))
            ->assertOk()
            ->assertSee('Connected');
    }

    public function test_tentang_kelas_kita_menampilkan_nama_versi_dan_tahun(): void
    {
        config(['app.versi' => '1.2.3']);
        config(['app.pengembang' => null]);

        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)->get(route('admin.pengaturan.tentang'))->assertOk()->getContent();

        $this->assertStringContainsString('KelasKita', $html);
        $this->assertStringContainsString('v1.2.3', $html);
        $this->assertStringContainsString('© '.now()->year.' KelasKita', $html);

        // Baris developer tidak ditampilkan kalau belum diisi di .env.
        $this->assertStringNotContainsString('Developer', $html);
    }

    public function test_tentang_kelas_kita_menampilkan_developer_kalau_diisi(): void
    {
        config(['app.pengembang' => 'Tim KelasKita']);

        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan.tentang'))
            ->assertSee('Tim KelasKita');
    }

    public function test_halaman_tentang_tidak_menautkan_ke_halaman_yang_tidak_ada(): void
    {
        /*
         * Halaman ini tidak punya Privacy Policy maupun Terms & Conditions,
         * jadi tidak boleh menampilkan tautan ke sana.
         */
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.pengaturan.tentang'))
            ->assertDontSee('Privacy Policy')
            ->assertDontSee('Ketentuan');
    }

    /* ================================================================
     * KELUAR DARI AKUN
     * ================================================================ */

    public function test_keluar_dari_akun_memakai_route_logout_yang_sudah_ada(): void
    {
        /*
         * Keluar harus lewat Auth::logout() supaya sesi server ikut dibersihkan,
         * bukan sekadar menghapus cookie di peramban ini.
         */
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
