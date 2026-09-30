<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman "Verifikasi" di area admin.
 *
 * Halaman ini adalah daftar gabungan materi dan quiz yang menunggu
 * persetujuan. Yang diuji di sini: daftarnya benar-benar gabungan, badge
 * jumlahnya sesuai data, proteksi admin tetap berlaku, dan keputusan yang
 * diambil dari halaman ini mengembalikan admin ke halaman ini lagi (lewat
 * field "kembali") tanpa mengubah perilaku lama.
 */
class VerifikasiHalamanTest extends TestCase
{
    use RefreshDatabase;

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

    private function buatMateri(?User $pemilik, string $status, string $nama = 'Materi Uji'): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran('Matematika', 'matematika')->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.Materi::query()->count(),
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    private function buatQuiz(?User $pemilik, string $status, string $judul = 'Quiz Uji'): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran('Matematika', 'matematika')->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value().'-'.Quiz::query()->count(),
            'deskripsi' => 'Ringkasan quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);
    }

    public function test_admin_melihat_semua_konten_menunggu_di_satu_halaman(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $materi = $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $quiz = $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Antre');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Tayang');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk()
            ->assertSee('Verifikasi')
            ->assertSee('Materi Antre')
            ->assertSee('Quiz Antre')
            ->assertDontSee('Materi Tayang')
            ->assertSee('2');
    }

    public function test_halaman_verifikasi_kosong_saat_tidak_ada_yang_menunggu(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk()
            ->assertSee('Tidak ada konten yang menunggu verifikasi.');
    }

    public function test_menu_verifikasi_tidak_tampil_badge_saat_tidak_ada_yang_menunggu(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Verifikasi')
            ->assertDontSee('bg-[#F47FA5]');
    }

    public function test_non_admin_tidak_bisa_membuka_halaman_verifikasi(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get(route('admin.verifikasi'))
            ->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get(route('admin.verifikasi'))->assertRedirect(route('login'));
    }

    public function test_materi_disetujui_dari_halaman_verifikasi_kembali_ke_verifikasi(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING);

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.setujui', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
            ])
            ->assertRedirect(route('admin.verifikasi'))
            ->assertSessionHas('sukses');

        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->refresh()->status);
    }

    public function test_quiz_ditolak_dari_halaman_verifikasi_kembali_ke_verifikasi(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING);

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.quiz.tolak', $quiz), [
                'status' => Quiz::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
                'alasan' => 'Soal belum lengkap.',
            ])
            ->assertRedirect(route('admin.verifikasi'))
            ->assertSessionHas('sukses');

        $this->assertSame(Quiz::STATUS_REJECTED, $quiz->refresh()->status);
    }
}
