<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard pengguna: section "Materi Terbaru" dan "Quiz Terbaru".
 *
 * Dua section itu harus menampilkan karya yang benar-benar ada di database,
 * bukan data contoh, jadi test di sini membuat lebih dari empat materi dan
 * quiz lalu memastikan hanya yang terbaru empat yang tampil.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di
 * sini tidak menyentuh database sungguhan.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Penanda detik untuk fixture.
     *
     * created_at ditulis eksplisit dengan detik yang berbeda satu per record.
     * Kalau dibiarkan now(), semua materi/quiz dalam satu test jatuh pada
     * detik yang sama sehingga urutan "terbaru" tidak bisa dibedakan.
     */
    private int $urutan = 0;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi pemrograman',
            'aktif' => true,
        ]);
    }

    private function buatMateri(Pelajaran $pelajaran, ?User $pembuat, string $nama, string $status = Materi::STATUS_PUBLISHED): Materi
    {
        $materi = Materi::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $pembuat?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value(),
            'deskripsi' => "Ringkasan $nama",
            'isi' => 'Isi materi untuk pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);

        $materi->forceFill(['created_at' => now()->addSecond($this->urutan++)])->save();

        return $materi;
    }

    private function buatQuiz(Pelajaran $pelajaran, ?User $pembuat, string $judul, string $status = Quiz::STATUS_PUBLISHED): Quiz
    {
        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $pembuat?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => "Ringkasan $judul",
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);

        $quiz->forceFill(['created_at' => now()->addSecond($this->urutan++)])->save();

        return $quiz;
    }

    private function buatSoal(Quiz $quiz, string $pertanyaan): Soal
    {
        return Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => $pertanyaan,
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Pembahasan singkat.',
            'urutan' => 1,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);
    }

    public function test_materi_terbaru_menampilkan_empat_materi_terbaru(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $daftar = [];
        foreach (range(1, 5) as $urutan) {
            $daftar[$urutan] = $this->buatMateri($pelajaran, $user, "Materi Angka $urutan");
        }

        $dashboard = $this->actingAs($user)->get('/user/dashboard')->assertOk();

        // Empat materi terbaru: angka 5, 4, 3, dan 2.
        $dashboard->assertSee('Materi Angka 5')
            ->assertSee('Materi Angka 4')
            ->assertSee('Materi Angka 3')
            ->assertSee('Materi Angka 2');

        // Materi paling lama tidak ikut tampil.
        $dashboard->assertDontSee('Materi Angka 1');
    }

    public function test_materi_terbaru_hanya_menampilkan_materi_yang_sudah_terbit(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->buatMateri($pelajaran, $user, 'Materi Sudah Terbit');
        $this->buatMateri($pelajaran, $user, 'Materi Masih Draft', Materi::STATUS_DRAFT);

        $this->actingAs($user)
            ->get('/user/dashboard')
            ->assertOk()
            ->assertSee('Materi Sudah Terbit')
            ->assertDontSee('Materi Masih Draft');
    }

    public function test_quiz_terbaru_menampilkan_empat_quiz_terbaru_lengkap_soalnya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        foreach (range(1, 5) as $urutan) {
            $this->buatSoal($this->buatQuiz($pelajaran, $user, "Quiz Angka $urutan"), "Soal $urutan");
        }

        $dashboard = $this->actingAs($user)->get('/user/dashboard')->assertOk();

        $dashboard->assertSee('Quiz Angka 5')
            ->assertSee('Quiz Angka 4')
            ->assertSee('Quiz Angka 3')
            ->assertSee('Quiz Angka 2')
            ->assertDontSee('Quiz Angka 1');

        // Jumlah soal dan durasi ikut terbaca di kartu.
        $dashboard->assertSee('1 Soal')
            ->assertSee('10 Menit');
    }

    public function test_quiz_terbaru_menandai_quiz_milik_pengguna_yang_login(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna([
            'nama' => 'Sari',
            'email' => 'sari@example.com',
        ]);
        $pelajaran = $this->buatPelajaran();

        $this->buatQuiz($pelajaran, $user, 'Quiz Milik Saya');
        $this->buatQuiz($pelajaran, $orangLain, 'Quiz Milik Orang Lain');

        $this->actingAs($user)
            ->get('/user/dashboard')
            ->assertOk()
            ->assertSee('Quiz Saya');
    }

    public function test_dashboard_menampilkan_pesan_kosong_saat_belum_ada_karya(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get('/user/dashboard')
            ->assertOk()
            ->assertSee('Belum ada materi terbaru')
            ->assertSee('Belum ada quiz terbaru');
    }
}
