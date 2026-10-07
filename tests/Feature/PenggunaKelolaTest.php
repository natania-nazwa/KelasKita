<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aksi hapus pada halaman "Pengguna" di area admin.
 *
 * Yang diuji di sini bukan hanya aksinya berhasil, tapi juga penjagaannya:
 * akun sendiri dan admin terakhir tidak boleh dihapus, dan yang tersimpan
 * harus benar-benar berubah di database. Status aktif tidak diuji di sini
 * karena tidak lagi bisa diubah dari halaman ini — aktif/nonaktif dihitung
 * otomatis dari kapan terakhir pengguna membuka aplikasi.
 *
 * Test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini tidak
 * menyentuh database sungguhan.
 */
class PenggunaKelolaTest extends TestCase
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

    private function buatAdmin(string $email = 'admin@example.com'): User
    {
        return $this->buatPengguna([
            'nama' => 'Admin KelasKita',
            'email' => $email,
            'peran' => User::PERAN_ADMIN,
        ]);
    }

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => 'matematika'], [
            'nama' => 'Matematika',
            'deskripsi' => 'Deskripsi Matematika',
            'aktif' => true,
        ]);
    }

    private function buatMateri(User $pembuat): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'nama' => 'Materi Uji',
            'slug' => 'materi-uji',
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
        ]);
    }

    private function buatQuiz(User $pembuat): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => 'Quiz Uji',
            'slug' => 'quiz-uji',
            'deskripsi' => 'Ringkasan quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }

    /*
     * =================================================================
     * Hapus
     * =================================================================
     */

    public function test_admin_bisa_menghapus_pengguna_lain(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->actingAs($admin)
            ->delete(route('admin.pengguna.destroy', $siswa))
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('tb_pengguna', ['id' => $siswa->getKey()]);
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->delete(route('admin.pengguna.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertDatabaseHas('tb_pengguna', ['id' => $admin->getKey()]);
    }

    public function test_admin_bisa_menghapus_admin_lain_saat_adminnya_lebih_dari_satu(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdmin('admin2@example.com');

        $this->actingAs($admin)
            ->delete(route('admin.pengguna.destroy', $adminLain))
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('tb_pengguna', ['id' => $adminLain->getKey()]);
    }

    public function test_menghapus_pengguna_ikut_menghapus_data_yang_menggantung(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $materi = $this->buatMateri($siswa);
        $quiz = $this->buatQuiz($siswa);

        PengerjaanQuiz::create([
            'pengguna_id' => $siswa->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => 10,
            'jumlah_dijawab' => 10,
            'jumlah_benar' => 8,
            'jumlah_salah' => 2,
            'nilai' => 80,
            'dimulai_pada' => now()->subMinutes(10),
            'selesai_pada' => now(),
        ]);

        $this->actingAs($admin)->delete(route('admin.pengguna.destroy', $siswa));

        $this->assertDatabaseMissing('tb_pengguna', ['id' => $siswa->getKey()]);
        $this->assertDatabaseMissing('tb_quiz', ['id' => $quiz->getKey()]);
        $this->assertDatabaseMissing('tb_pengerjaan_quiz', ['pengguna_id' => $siswa->getKey()]);

        // Materi tidak ikut hilang: kolom pembuatnya nullOnDelete, jadi
        // materinya tetap ada sebagai konten tanpa pemilik.
        $this->assertDatabaseHas('tb_materi', ['id' => $materi->getKey(), 'dibuat_oleh' => null]);
    }

    /*
     * =================================================================
     * Hak akses
     * =================================================================
     */

    public function test_non_admin_tidak_bisa_menghapus(): void
    {
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $target = $this->buatPengguna(['nama' => 'Rina', 'email' => 'rina@example.com']);

        $this->actingAs($siswa)
            ->delete(route('admin.pengguna.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('tb_pengguna', ['id' => $target->getKey()]);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $target = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->delete(route('admin.pengguna.destroy', $target))
            ->assertRedirect(route('login'));
    }
}
