<?php

namespace Tests\Feature;

use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman detail satu quiz dan tombol "Mulai Quiz" di dalamnya.
 *
 * Yang dijaga di sini adalah kontrak yang dibaca pengguna dari halaman ini:
 *   - keempat isi kartu informasi (kategori, kesulitan, jumlah soal, durasi)
 *     benar-benar berasal dari data quiz, bukan teks mati;
 *   - daftar soal menampilkan pertanyaannya dan pilihan jawabannya, tapi TIDAK
 *     pernah jawaban benar maupun pembahasan, karena halaman ini dibuka
 *     sebelum quiz dikerjakan;
 *   - tautan Kembali dan Mulai Quiz menuju route yang benar;
 *   - quiz tanpa soal, dan quiz draft milik orang lain, ditolak di kedua
 *     halaman (detail dan mulai).
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini
 * tidak menyentuh database sungguhan.
 */
class QuizDetailTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Admin',
            'email' => 'admin@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Belajar menulis kode.',
            'ikon' => '</>',
            'aktif' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatQuiz(?User $pembuat, array $atribut = []): Quiz
    {
        return Quiz::create(array_merge([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat?->getKey(),
            'judul' => 'HTML & CSS Dasar',
            'slug' => 'html-css-dasar',
            'deskripsi' => 'Kuis untuk menguji pemahaman dasar HTML dan CSS.',
            'durasi' => 15,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ], $atribut));
    }

    private function buatSoal(Quiz $quiz, int $urutan = 1): Soal
    {
        return Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Apa tag untuk membuat judul terbesar di HTML?',
            'pilihan_a' => '<h6>',
            'pilihan_b' => '<h1>',
            'pilihan_c' => '<title>',
            'pilihan_d' => '<head>',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Tag h1 dipakai untuk judul tingkat satu.',
            'urutan' => $urutan,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);
    }

    public function test_kartu_informasi_menampilkan_kategori_kesulitan_jumlah_soal_dan_durasi(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);

        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertSee('Informasi Quiz')
            ->assertSee('Kategori')
            ->assertSee('Pemrograman')
            ->assertSee('Tingkat Kesulitan')
            ->assertSee('Mudah')
            ->assertSee('Jumlah Soal')
            ->assertSee('2 Soal')
            ->assertSee('Durasi')
            ->assertSee('± 15 Menit');
    }

    public function test_kartu_utama_menampilkan_judul_pembuat_dan_metadata(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        $isi = $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('HTML &amp; CSS Dasar', $isi);
        $this->assertStringContainsString('Dibuat oleh', $isi);
        $this->assertStringContainsString('Kuis untuk menguji pemahaman dasar HTML dan CSS.', $isi);
    }

    public function test_daftar_soal_menampilkan_pertanyaan_dan_pilihan_jawabannya(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertSee('Daftar Soal')
            ->assertSee('Apa tag untuk membuat judul terbesar di HTML?')
            ->assertSee('&lt;h1&gt;', false)
            // Baris soal adalah tombol akordion yang bisa dilipat.
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="soal-1-isi"', false);
    }

    public function test_daftar_soal_tidak_membocorkan_jawaban_benar_maupun_pembahasan(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            // Isi jawaban benar dan pembahasan tidak boleh masuk ke halaman ini.
            ->assertDontSee('Tag h1 dipakai untuk judul tingkat satu.');
    }

    public function test_soal_yang_dimatikan_tidak_muncul_di_daftar(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Soal yang sudah dinonaktifkan.',
            'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Tidak dipakai.',
            'urutan' => 2,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => false,
        ]);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertDontSee('Soal yang sudah dinonaktifkan.');
    }

    public function test_tombol_kembali_dan_mulai_quiz_menuju_route_yang_benar(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertSee(route('user.quiz'), false)
            ->assertSee('Mulai Quiz')
            ->assertSee('Bagikan')
            ->assertSee(route('user.quiz.mulai', $quiz), false)
            // Tombol Bagikan membuka dialog berisi tautan halaman ini.
            ->assertSee(route('user.quiz.detail', $quiz), false);
    }

    public function test_quiz_tanpa_soal_menampilkan_empty_state_dan_menolak_dimulai(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertSee('Belum ada soal')
            ->assertSee('Quiz ini belum memiliki soal.');

        $this->actingAs($user)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertRedirect(route('user.quiz.detail', $quiz))
            ->assertSessionHasErrors(['quiz' => 'Quiz ini belum memiliki soal.']);

        // Tidak ada sesi yang boleh dibuat untuk quiz kosong.
        $this->assertDatabaseCount('tb_sesi_quiz', 0);
    }

    public function test_mulai_quiz_membuat_sesi_yang_langsung_berjalan_dan_membuka_soal_pertama(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);

        $this->actingAs($user)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertRedirect();

        $sesi = SesiQuiz::query()->sole();

        $this->assertSame($quiz->getKey(), $sesi->quiz_id);
        $this->assertSame($user->getKey(), $sesi->host_id);
        $this->assertSame(SesiQuiz::STATUS_DIMULAI, $sesi->status);
    }

    public function test_mulai_quiz_membawa_kembali_ke_sesi_yang_masih_berjalan(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $user->getKey(),
            'kode' => 'ABC123',
            'status' => SesiQuiz::STATUS_DIMULAI,
        ]);

        $this->actingAs($user)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 1]));

        // Tidak dibuat sesi kedua untuk quiz yang sedang dikerjakan.
        $this->assertDatabaseCount('tb_sesi_quiz', 1);
    }

    public function test_mulai_quiz_membuka_sesi_baru_setelah_latihan_sebelumnya_selesai(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user);
        $this->buatSoal($quiz);

        $lama = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $user->getKey(),
            'kode' => 'ABC123',
            'status' => SesiQuiz::STATUS_DIMULAI,
        ]);

        PengerjaanQuiz::create([
            'sesi_id' => $lama->getKey(),
            'pengguna_id' => $user->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => 1,
            'jumlah_dijawab' => 1,
            'jumlah_benar' => 1,
            'nilai' => 100,
            'dimulai_pada' => now(),
            'selesai_pada' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertRedirect();

        // Sesi lama ditutup supaya tidak ikut terambil lagi, dan sesi baru
        // dibuat untuk latihan yang baru.
        $this->assertSame(SesiQuiz::STATUS_SELESAI, $lama->fresh()->status);
        $this->assertDatabaseCount('tb_sesi_quiz', 2);
    }

    public function test_mulai_quiz_orang_lain_menolak_quiz_draft_milik_pengguna_lain(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $quiz = $this->buatQuiz($orangLain, [
            'judul' => 'Quiz Draft Siti',
            'slug' => 'quiz-draft-siti',
            'status' => Quiz::STATUS_DRAFT,
        ]);
        $this->buatSoal($quiz);

        $this->actingAs($user)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertNotFound();

        $this->assertDatabaseCount('tb_sesi_quiz', 0);
    }

    public function test_halaman_detail_quiz_membutuhkan_login(): void
    {
        $quiz = $this->buatQuiz($this->buatPengguna());

        $this->get(route('user.quiz.detail', $quiz))->assertRedirect('/login');
        $this->get(route('user.quiz.mulai', $quiz))->assertRedirect('/login');
    }
}
