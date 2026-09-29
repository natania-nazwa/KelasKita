<?php

namespace Tests\Feature;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\SimpananQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pintu simpan quiz: tombol bookmark di pojok kanan atas kartu quiz
 * (halaman Quiz, dashboard, dan Simpanan).
 *
 *   POST /user/quiz/detail-quiz/{quiz}/simpan  balik status simpan
 *   GET  /user/simpanan/quiz                   daftar id quiz yang disimpan
 *
 * Keduanya dipanggil fetch dari resources/js/app.js, kembaran dari
 * SimpananMateriTest.
 */
class SimpananQuizTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Natania',
            'email' => 'natania@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatQuiz(string $status = Quiz::STATUS_PUBLISHED, ?User $pembuat = null): Quiz
    {
        $pelajaran = Pelajaran::query()->firstOrCreate(
            ['slug' => 'pemrograman'],
            [
                'nama' => 'Pemrograman',
                'deskripsi' => 'Deskripsi Pemrograman',
                'ikon' => '</>',
                'aktif' => true,
            ],
        );

        return Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => ($pembuat ?? $this->buatPengguna())->getKey(),
            'judul' => 'HTML Dasar',
            'slug' => 'html-dasar',
            'deskripsi' => 'Quiz dasar HTML.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);
    }

    public function test_toggle_menyimpan_quiz_lalu_melepaskannya(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz(pembuat: $pengguna);

        $this->actingAs($pengguna)
            ->postJson(route('user.quiz.simpan', $quiz->getKey()))
            ->assertOk()
            ->assertJson(['tersimpan' => true]);

        $this->assertDatabaseHas('tb_simpanan_quiz', [
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
        ]);

        $this->actingAs($pengguna)
            ->postJson(route('user.quiz.simpan', $quiz->getKey()))
            ->assertOk()
            ->assertJson(['tersimpan' => false]);

        $this->assertDatabaseMissing('tb_simpanan_quiz', [
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
        ]);
    }

    public function test_toggle_membutuhkan_login(): void
    {
        $quiz = $this->buatQuiz();

        $this->post(route('user.quiz.simpan', $quiz->getKey()))->assertRedirect('/login');
    }

    public function test_toggle_mengembalikan_404_untuk_quiz_yang_belum_terbit(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz(Quiz::STATUS_DRAFT, $pengguna);

        $this->actingAs($pengguna)
            ->postJson(route('user.quiz.simpan', $quiz->getKey()))
            ->assertNotFound();
    }

    public function test_daftar_simpanan_hanya_mengembalikan_id_pengguna_itu(): void
    {
        $natania = $this->buatPengguna();
        $quiz = $this->buatQuiz(pembuat: $natania);

        $lain = $this->buatPengguna([
            'nama' => 'Sein',
            'email' => 'sein@example.com',
        ]);

        SimpananQuiz::query()->create([
            'pengguna_id' => $natania->getKey(),
            'quiz_id' => $quiz->getKey(),
        ]);

        $this->actingAs($lain)
            ->get(route('user.simpanan.quiz'))
            ->assertOk()
            ->assertJson(['id' => []]);

        $this->actingAs($natania)
            ->get(route('user.simpanan.quiz'))
            ->assertOk()
            ->assertJson(['id' => [(string) $quiz->getKey()]]);
    }

    public function test_daftar_simpanan_mengabaikan_quiz_yang_sudah_tidak_terbit(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz(pembuat: $pengguna);

        SimpananQuiz::query()->create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
        ]);

        $quiz->update(['status' => Quiz::STATUS_DRAFT]);

        $this->actingAs($pengguna)
            ->get(route('user.simpanan.quiz'))
            ->assertOk()
            ->assertJson(['id' => []]);
    }

    public function test_daftar_simpanan_membutuhkan_login(): void
    {
        $this->get(route('user.simpanan.quiz'))->assertRedirect('/login');
    }
}
