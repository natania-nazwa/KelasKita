<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman Profil: melihat, mengubah, dan mengelola data akun sendiri.
 *
 * Yang diuji di sini:
 *   1. Halaman memakai data pengguna yang sedang login, bukan data
 *      yang ditulis langsung di markup.
 *   2. Inisial avatar selalu satu huruf pertama nama depan, dan muncul
 *      otomatis begitu foto profil dihapus.
 *   3. Simpan profil, hapus foto, dan ubah password bekerja, termasuk
 *      berkas fotonya di disk publik.
 *   4. Aturan validasi kedua form (email unik, konfirmasi password) dan
 *      password lama benar-benar diperiksa.
 *   5. Hapus akun belum punya endpoint, jadi tidak ada aksi yang bisa
 *      menghapus akun dari halaman ini.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml) dan disk publik
 * palsu, jadi tidak menyentuh database sungguhan.
 */
class ProfilTest extends TestCase
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

    public function test_halaman_profil_membuka_dengan_judul_dan_data_pengguna(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get(route('user.profil'))
            ->assertOk()
            ->assertSee('Profil')
            ->assertSee('Kelola informasi akun dan preferensi kamu.')
            ->assertSee('Natania Nazwa Gisella')
            ->assertSee('natania@gmail.com')
            ->assertSee('Informasi Akun')
            ->assertSee('Keamanan')
            ->assertSee('Tampilan')
            ->assertSee('Zona Berbahaya');
    }

    public function test_halaman_profil_menampilkan_tanggal_bergabung_dari_created_at(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get(route('user.profil'))
            ->assertOk()
            ->assertSee('Bergabung sejak '.$user->created_at->translatedFormat('d F Y'))
            ->assertSee($user->created_at->translatedFormat('d F Y'));
    }

    public function test_tanpa_foto_profil_avatar_menampilkan_satu_huruf_nama_depan(): void
    {
        $user = $this->buatPengguna();

        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        // "Natania Nazwa Gisella" disingkat jadi "N", bukan "NG".
        $this->assertStringContainsString('data-profil-inisial', $isi);
        $this->assertStringContainsString('>N</span>', $isi);

        // Tidak ada <img> avatar dan tidak ada gambar placeholder apa pun.
        $this->assertStringNotContainsString('data-profil-foto', $isi);
        $this->assertStringNotContainsString('storage/foto-profil', $isi);
    }

    public function test_inisial_mengambil_satu_huruf_pertama_nama_depan(): void
    {
        $this->assertSame('N', $this->buatPengguna()->inisial());
        $this->assertSame('B', $this->buatPengguna(['nama' => 'Budi Santoso', 'email' => 'budi@example.com'])->inisial());
        $this->assertSame('S', $this->buatPengguna(['nama' => '  Siti  Aminah', 'email' => 'siti@example.com'])->inisial());
        $this->assertSame('?', $this->buatPengguna(['nama' => '   ', 'email' => 'anonim@example.com'])->inisial());
    }

    public function test_dengan_foto_profil_avatar_menampilkan_foto_dan_bukan_inisial(): void
    {
        $user = $this->buatPengguna(['foto_profil' => 'foto-profil/contoh.png']);

        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        $this->assertStringContainsString('storage/foto-profil/contoh.png', $isi);
        $this->assertStringContainsString('data-profil-foto', $isi);
        $this->assertStringNotContainsString('data-profil-inisial', $isi);
    }

    public function test_foto_profil_url_kosong_saat_tidak_ada_foto(): void
    {
        $this->assertNull($this->buatPengguna()->fotoProfilUrl());

        $adaFoto = $this->buatPengguna([
            'email' => 'adafoto@example.com',
            'foto_profil' => 'foto-profil/a.png',
        ]);

        // Host-nya ikut dari APP_URL, jadi yang dicek hanya bagian path.
        $this->assertStringEndsWith('/storage/foto-profil/a.png', $adaFoto->fotoProfilUrl());
    }

    public function test_nama_dan_email_bisa_diubah(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => 'Natania N. Gisella',
                'email' => 'natania.baru@gmail.com',
            ])
            ->assertRedirect(route('user.profil'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('tb_pengguna', [
            'id' => $user->getKey(),
            'nama' => 'Natania N. Gisella',
            'email' => 'natania.baru@gmail.com',
        ]);
    }

    public function test_nama_baru_langsung_tampil_di_header_setelah_diubah(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)->put(route('user.profil.update'), [
            'nama' => 'Natania Baru',
            'email' => 'natania@gmail.com',
        ])->assertSessionHasNoErrors();

        // Halaman yang sama memuat top bar, jadi nama baru harus ikut
        // muncul di header, bukan hanya di kartu profil.
        $this->actingAs($user)
            ->get(route('user.profil'))
            ->assertOk()
            ->assertSee('Natania Baru');
    }

    public function test_menyimpan_email_tanpa_perubahan_tidak_ditolak_sebagai_duplikat(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => 'Natania Nazwa Gisella',
                'email' => 'natania@gmail.com',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_email_yang_sudah_dipakai_orang_lain_ditolak(): void
    {
        $user = $this->buatPengguna();
        $this->buatPengguna(['nama' => 'Budi', 'email' => 'budi@example.com']);

        $this->actingAs($user)
            ->from(route('user.profil'))
            ->put(route('user.profil.update'), [
                'nama' => 'Natania Nazwa Gisella',
                'email' => 'budi@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('tb_pengguna', [
            'id' => $user->getKey(),
            'email' => 'natania@gmail.com',
        ]);
    }

    public function test_field_wajib_tidak_boleh_kosong(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), ['nama' => '', 'email' => ''])
            ->assertSessionHasErrors(['nama', 'email']);
    }

    public function test_foto_profil_bisa_diunggah(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => $user->nama,
                'email' => $user->email,
                'foto_profil' => UploadedFile::fake()->image('foto.png', 480, 480),
            ])
            ->assertSessionHasNoErrors();

        $tersimpan = $user->fresh()->foto_profil;

        $this->assertStringStartsWith('foto-profil/', $tersimpan);
        Storage::disk('public')->assertExists($tersimpan);
    }

    public function test_foto_baru_mengganti_berkas_lama(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna(['foto_profil' => 'foto-profil/lama.png']);
        Storage::disk('public')->put('foto-profil/lama.png', 'lama');

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => $user->nama,
                'email' => $user->email,
                'foto_profil' => UploadedFile::fake()->image('baru.png', 480, 480),
            ])
            ->assertSessionHasNoErrors();

        $tersimpan = $user->fresh()->foto_profil;

        $this->assertNotSame('foto-profil/lama.png', $tersimpan);
        Storage::disk('public')->assertMissing('foto-profil/lama.png');
        Storage::disk('public')->assertExists($tersimpan);
    }

    public function test_mengoreksi_email_tanpa_foto_baru_tidak_menghapus_foto_lama(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna(['foto_profil' => 'foto-profil/lama.png']);
        Storage::disk('public')->put('foto-profil/lama.png', 'lama');

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => $user->nama,
                'email' => 'baru@gmail.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('foto-profil/lama.png', $user->fresh()->foto_profil);
        Storage::disk('public')->assertExists('foto-profil/lama.png');
    }

    public function test_foto_profil_berformat_salah_ditolak(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => $user->nama,
                'email' => $user->email,
                'foto_profil' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('foto_profil');

        $this->assertNull($user->fresh()->foto_profil);
    }

    public function test_foto_profil_dapat_dihapus_dan_avatar_kembali_ke_inisial(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna(['foto_profil' => 'foto-profil/lama.png']);
        Storage::disk('public')->put('foto-profil/lama.png', 'lama');

        $this->actingAs($user)
            ->delete(route('user.profil.foto.destroy'))
            ->assertRedirect(route('user.profil'))
            ->assertSessionHas('sukses', 'Foto profil berhasil dihapus.');

        $setelah = $user->fresh();

        $this->assertNull($setelah->foto_profil);
        $this->assertNull($setelah->fotoProfilUrl());
        Storage::disk('public')->assertMissing('foto-profil/lama.png');

        // Tidak ada gambar placeholder yang menggantung: avatar kembali
        // menampilkan inisial nama depan.
        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        $this->assertStringContainsString('data-profil-inisial', $isi);
        $this->assertStringNotContainsString('foto-profil/lama.png', $isi);
    }

    public function test_menghapus_foto_pada_akun_tanpa_foto_menghasilkan_404(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->delete(route('user.profil.foto.destroy'))
            ->assertNotFound();
    }

    public function test_password_bisa_diubah_jika_password_lama_benar(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.kata-sandi'), [
                'kata_sandi_lama' => 'rahasia123',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'katasandibaru',
            ])
            ->assertRedirect(route('user.profil'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', 'Password berhasil diubah.');

        // Disimpan sebagai hash, bukan teks sandi polos. Password lama
        // harus ikut tidak berlaku karena yang tersimpan sudah diganti.
        $terbaru = $user->fresh();

        $this->assertNotSame('katasandibaru', $terbaru->kata_sandi);
        $this->assertTrue(Hash::check('katasandibaru', $terbaru->kata_sandi));
        $this->assertFalse(Hash::check('rahasia123', $terbaru->kata_sandi));
    }

    public function test_password_lama_yang_salah_ditolak(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.kata-sandi'), [
                'kata_sandi_lama' => 'salah-total',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'katasandibaru',
            ])
            ->assertSessionHasErrors('kata_sandi_lama');

        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->kata_sandi));
    }

    public function test_konfirmasi_password_yang_tidak_sama_ditolak(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.kata-sandi'), [
                'kata_sandi_lama' => 'rahasia123',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'bedanya',
            ])
            ->assertSessionHasErrors(['kata_sandi_baru', 'kata_sandi_baru_konfirmasi']);

        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->kata_sandi));
    }

    public function test_password_baru_wajib_diisi_dan_minimal_delapan_karakter(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.kata-sandi'), [
                'kata_sandi_lama' => 'rahasia123',
                'kata_sandi_baru' => '',
                'kata_sandi_baru_konfirmasi' => '',
            ])
            ->assertSessionHasErrors(['kata_sandi_baru', 'kata_sandi_baru_konfirmasi']);

        $this->actingAs($user)
            ->put(route('user.profil.kata-sandi'), [
                'kata_sandi_lama' => 'rahasia123',
                'kata_sandi_baru' => 'pendek',
                'kata_sandi_baru_konfirmasi' => 'pendek',
            ])
            ->assertSessionHasErrors('kata_sandi_baru');
    }

    public function test_password_lama_wajib_diisi(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.kata-sandi'), [
                'kata_sandi_lama' => '',
                'kata_sandi_baru' => 'katasandibaru',
                'kata_sandi_baru_konfirmasi' => 'katasandibaru',
            ])
            ->assertSessionHasErrors('kata_sandi_lama');
    }

    public function test_dialog_ubah_password_memiliki_tombol_batal_dan_tidak_bocor(): void
    {
        $user = $this->buatPengguna();

        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        $this->assertStringContainsString('id="form-kata-sandi"', $isi);
        $this->assertStringContainsString('data-dialog-batal', $isi);
        $this->assertStringContainsString('Ubah Password', $isi);

        // Isi password tidak pernah ditulis di markup: kolomnya kosong
        // dan bertipe password, bukan teks.
        $this->assertStringNotContainsString('value="rahasia123"', $isi);
    }

    public function test_dialog_edit_profil_membuka_dengan_nilai_lama(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get(route('user.profil'))
            ->assertOk()
            ->assertSee('value="Natania Nazwa Gisella"', false)
            ->assertSee('value="natania@gmail.com"', false)
            ->assertSee('Ganti Foto')
            ->assertSee('Simpan Perubahan')
            ->assertSee('Edit Profil');
    }

    public function test_tombol_hapus_foto_tidak_muncul_saat_belum_ada_foto(): void
    {
        $user = $this->buatPengguna();

        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        // Tanpa foto tidak ada yang perlu dihapus, jadi tombolnya tidak
        // dirender sama sekali (bukan hanya dinonaktifkan).
        $this->assertStringNotContainsString('data-dialog-buka="dialog-hapus-foto"', $isi);
    }

    public function test_tombol_hapus_foto_muncul_saat_foto_ada(): void
    {
        $user = $this->buatPengguna(['foto_profil' => 'foto-profil/a.png']);

        $this->actingAs($user)
            ->get(route('user.profil'))
            ->assertOk()
            ->assertSee('Hapus Foto')
            ->assertSee('Hapus foto profil?')
            ->assertSee('Ya, Hapus Foto');
    }

    public function test_zona_berbahaya_tidak_pernah_menghapus_akun(): void
    {
        $user = $this->buatPengguna();

        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        // Konfirmasi sudah ada lengkap dengan tombol Batal...
        $this->assertStringContainsString('Hapus akun?', $isi);
        $this->assertStringContainsString('data-dialog-batal', $isi);
        $this->assertStringContainsString('Ya, Hapus Akun', $isi);

        // ...tapi dialog itu tidak pernah jadi <form>, jadi tidak ada
        // jalur apa pun untuk menghapus akun dari halaman ini.
        $this->assertStringNotContainsString('id="form-hapus-akun"', $isi);
        $this->assertStringContainsString('data-belum-ada-endpoint', $isi);
        $this->assertDatabaseHas('tb_pengguna', ['id' => $user->getKey()]);
    }

    public function test_tidak_ada_route_hapus_akun(): void
    {
        // Endpoint-nya memang belum dibuat, jadi tidak boleh ada rute
        // yang menghapus akun, supaya tidak dikira sudah siap.
        $adaRuteHapusAkun = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($rute) => str_contains($rute->uri(), 'profil')
                && in_array('DELETE', $rute->methods(), true)
                && str_contains($rute->uri(), 'akun'));

        $this->assertFalse($adaRuteHapusAkun);
    }

    public function test_halaman_profil_membutuhkan_login(): void
    {
        $this->get('/user/profil')->assertRedirect('/login');
        $this->put('/user/profil')->assertRedirect('/login');
        $this->delete('/user/profil/foto')->assertRedirect('/login');
        $this->put('/user/profil/kata-sandi')->assertRedirect('/login');
    }

    public function test_peran_dan_status_aktif_tidak_bisa_diubah_lewat_form_profil(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => 'Natania Nazwa Gisella',
                'email' => 'natania@gmail.com',
                // Percobaan menaikkan diri jadi admin.
                'peran' => 'admin',
                'aktif' => true,
            ])
            ->assertSessionHasNoErrors();

        $setelah = $user->fresh();

        $this->assertSame(User::PERAN_USER, $setelah->peran);
        $this->assertFalse($setelah->isAdmin());
    }
}
