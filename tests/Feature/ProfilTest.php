<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\User;
use App\Support\AktivitasHarian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
 *   5. Hapus akun: password wajib benar, akun beserta seluruh isinya
 *      hilang, materinya sendiri tetap tayang, dan akun orang lain tidak
 *      ikut tersentuh.
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

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'ikon' => '</>',
            'aktif' => true,
        ]);
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

    public function test_dialog_hapus_akun_menanyakan_password_dan_tidak_menembakkan_password_ke_markup(): void
    {
        $user = $this->buatPengguna();

        $isi = $this->actingAs($user)->get(route('user.profil'))->getContent();

        // Dialog sudah jadi form sungguhan yang menunjuk endpoint hapus akun,
        // lengkap dengan kolom password dan tombol Batal.
        $this->assertStringContainsString('id="form-hapus-akun"', $isi);
        $this->assertStringContainsString('action="'.route('user.profil.hapus').'"', $isi);
        $this->assertStringContainsString('name="kata_sandi"', $isi);
        $this->assertStringContainsString('Hapus akun?', $isi);
        $this->assertStringContainsString('data-dialog-batal', $isi);

        // Isi password tidak pernah ditulis di markup: kolomnya kosong
        // dan bertipe password, bukan teks.
        $this->assertStringNotContainsString('value="rahasia123"', $isi);
        $this->assertStringNotContainsString('data-belum-ada-endpoint', $isi);
    }

    public function test_akun_bisa_dihapus_jika_password_benar(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.profil'))
            ->delete(route('user.profil.hapus'), ['kata_sandi' => 'rahasia123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('tb_pengguna', ['id' => $user->getKey()]);
        $this->assertGuest();
    }

    public function test_hapus_akun_menampilkan_kabar_berhasil_di_halaman_masuk(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->delete(route('user.profil.hapus'), ['kata_sandi' => 'rahasia123'])
            ->assertSessionHas('sukses', 'Akunmu sudah dihapus. Terima kasih sudah belajar di KelasKita.');

        // Pesannya harus benar-benar tampil, bukan cuma ikut tersimpan di
        // session: halaman masuk tidak punya tempat lain untuk kabar
        // berhasil ini.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Akunmu sudah dihapus. Terima kasih sudah belajar di KelasKita.');
    }

    public function test_password_yang_salah_tidak_menghapus_akun(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.profil'))
            ->delete(route('user.profil.hapus'), ['kata_sandi' => 'salah-total'])
            ->assertRedirect(route('user.profil'))
            ->assertSessionHasErrors('kata_sandi');

        // Akun, berkasnya, dan sesinya tetap utuh: satu klik dengan
        // password salah tidak boleh menghapus apa pun.
        $this->assertDatabaseHas('tb_pengguna', ['id' => $user->getKey()]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_wajib_diisi_untuk_menghapus_akun(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->from(route('user.profil'))
            ->delete(route('user.profil.hapus'), ['kata_sandi' => ''])
            ->assertSessionHasErrors('kata_sandi');

        $this->assertDatabaseHas('tb_pengguna', ['id' => $user->getKey()]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_hapus_akun_menghapus_berkas_foto_dan_thumbnail_miliknya(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna(['foto_profil' => 'foto-profil/saya.png']);
        Storage::disk('public')->put('foto-profil/saya.png', 'foto');

        $quiz = Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $user->getKey(),
            'judul' => 'Quiz Buatan Saya',
            'slug' => 'quiz-buatan-saya',
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
            'thumbnail' => 'thumbnail-quiz/saya.jpg',
        ]);
        Storage::disk('public')->put('thumbnail-quiz/saya.jpg', 'thumb');

        $this->actingAs($user)->delete(route('user.profil.hapus'), ['kata_sandi' => 'rahasia123']);

        // Cascade di database tidak menyentuh disk, jadi berkas tetap
        // dihapus sendiri setelah baris akun hilang.
        Storage::disk('public')->assertMissing('foto-profil/saya.png');
        Storage::disk('public')->assertMissing('thumbnail-quiz/saya.jpg');
        $this->assertDatabaseMissing('tb_quiz', ['id' => $quiz->getKey()]);
    }

    public function test_hapus_akun_membawa_serta_notifikasi_dan_aktivitas_miliknya(): void
    {
        $user = $this->buatPengguna();

        Notifikasi::create([
            'pengguna_id' => $user->getKey(),
            'jenis' => Notifikasi::JENIS_KARYA_DISETUJUI,
            'judul' => 'Materi kamu disetujui',
            'pesan' => 'Materi kamu sudah tayang.',
            'konten_tipe' => Notifikasi::KONTEN_MATERI,
            'konten_id' => null,
        ]);

        AktivitasHarian::bacaMateri($user);

        $this->assertDatabaseHas('tb_notifikasi', ['pengguna_id' => $user->getKey()]);
        $this->assertDatabaseHas('tb_aktivitas_harian', ['pengguna_id' => $user->getKey()]);

        $this->actingAs($user)->delete(route('user.profil.hapus'), ['kata_sandi' => 'rahasia123']);

        $this->assertDatabaseCount('tb_notifikasi', 0);
        $this->assertDatabaseCount('tb_aktivitas_harian', 0);
    }

    public function test_hapus_akun_tidak_menghapus_materi_yang_sudah_diterbitkan(): void
    {
        $user = $this->buatPengguna();

        $materi = Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $user->getKey(),
            'nama' => 'Materi Tetap Tayang',
            'slug' => 'materi-tetap-tayang',
            'deskripsi' => 'Ringkasan materi.',
            'isi' => "Bab 1: Pendahuluan\n\nIsi materi.\n\nBab 2: Lanjutan\n\nIsi bab kedua.",
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
        ]);

        $this->actingAs($user)->delete(route('user.profil.hapus'), ['kata_sandi' => 'rahasia123']);

        // Materi pakai ON DELETE SET NULL, jadi karya yang sudah tayang
        // tidak ikut hilang bersama akun pembuatnya.
        $setelah = $materi->fresh();

        $this->assertNotNull($setelah);
        $this->assertNull($setelah->dibuat_oleh);
        $this->assertSame(Materi::STATUS_PUBLISHED, $setelah->status);
    }

    public function test_hapus_akun_tidak_menyentuh_akun_orang_lain(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Budi', 'email' => 'budi@example.com']);

        $this->actingAs($user)->delete(route('user.profil.hapus'), ['kata_sandi' => 'rahasia123']);

        // Rutenya tanpa parameter id, jadi tidak ada URL yang bisa dipakai
        // untuk menghapus akun orang lain.
        $this->assertDatabaseMissing('tb_pengguna', ['id' => $user->getKey()]);
        $this->assertDatabaseHas('tb_pengguna', ['id' => $orangLain->getKey()]);
        $this->assertTrue(Hash::check('rahasia123', $orangLain->fresh()->kata_sandi));
    }

    public function test_halaman_profil_membutuhkan_login(): void
    {
        $this->get('/user/profil')->assertRedirect('/login');
        $this->put('/user/profil')->assertRedirect('/login');
        $this->delete('/user/profil')->assertRedirect('/login');
        $this->delete('/user/profil/foto')->assertRedirect('/login');
        $this->put('/user/profil/kata-sandi')->assertRedirect('/login');
    }

    public function test_peran_dan_aktivitas_tidak_bisa_diubah_lewat_form_profil(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->put(route('user.profil.update'), [
                'nama' => 'Natania Nazwa Gisella',
                'email' => 'natania@gmail.com',
                // Percobaan menaikkan diri jadi admin.
                'peran' => 'admin',
                // Percobaan memalsukan "terakhir membuka" ke masa depan supaya
                // selamanya terbaca aktif.
                'terakhir_aktivitas' => now()->addYear()->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $setelah = $user->fresh();

        $this->assertSame(User::PERAN_USER, $setelah->peran);
        $this->assertFalse($setelah->isAdmin());
        $this->assertTrue(
            $setelah->terakhir_aktivitas->lessThanOrEqualTo(now()),
            'Aktivitas tidak boleh bisa dimajukan lewat form profil.',
        );
    }
}
