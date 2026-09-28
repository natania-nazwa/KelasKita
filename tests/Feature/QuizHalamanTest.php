<?php

namespace Tests\Feature;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Quiz: daftar, tab, pencarian, form tambah, dan detail.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di
 * sini tidak menyentuh database sungguhan.
 */
class QuizHalamanTest extends TestCase
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

    private function buatPelajaran(string $nama, string $slug): Pelajaran
    {
        return Pelajaran::create([
            'nama' => $nama,
            'slug' => $slug,
            'deskripsi' => "Deskripsi $nama",
            'ikon' => '</>',
            'aktif' => true,
        ]);
    }

    private function buatQuiz(
        Pelajaran $pelajaran,
        ?User $pembuat,
        string $judul,
        string $deskripsi = 'Deskripsi quiz.',
        string $status = Quiz::STATUS_PUBLISHED,
    ): Quiz {
        return Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $pembuat?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => $deskripsi,
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);
    }

    private function buatSoal(Quiz $quiz, int $urutan = 1): Soal
    {
        return Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => "Pertanyaan nomor $urutan",
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Pembahasan singkat.',
            'urutan' => $urutan,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);
    }

    public function test_halaman_quiz_menampilkan_kartu_dari_database(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $quiz = $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar', 'Kuis untuk menguji pemahaman dasar HTML dan CSS.');
        $this->buatSoal($quiz);

        $this->actingAs($user)
            ->get('/user/quiz')
            ->assertOk()
            ->assertSee('HTML & CSS Dasar')
            ->assertSee('Pemrograman')
            ->assertSee('Natania')
            ->assertSee('1 Soal')
            // Kartu harus menuju halaman detail quiz.
            ->assertSee(route('user.quiz.detail', $quiz->getKey()), false);
    }

    public function test_kartu_menampilkan_lencana_kategori_dan_jumlah_soal(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Database', 'database');
        $quiz = $this->buatQuiz($pelajaran, $user, 'Basis Data');

        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $this->buatSoal($quiz, 3);

        $this->actingAs($user)
            ->get('/user/quiz')
            ->assertOk()
            ->assertSee('Database')
            ->assertSee('3 Soal');
    }

    public function test_pencarian_menyaring_berdasarkan_judul(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');
        $this->buatQuiz($pelajaran, $user, 'JavaScript Dasar');

        $this->actingAs($user)
            ->get('/user/quiz?q=JavaScript')
            ->assertOk()
            ->assertSee('JavaScript Dasar')
            ->assertDontSee('HTML & CSS Dasar');
    }

    public function test_pencarian_menyaring_berdasarkan_kategori(): void
    {
        $user = $this->buatPengguna();
        $kode = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $desain = $this->buatPelajaran('Desain Web', 'desain-web');
        $this->buatQuiz($kode, $user, 'Belajar Blade');
        $this->buatQuiz($desain, $user, 'Tipografi Modern');

        $this->actingAs($user)
            ->get('/user/quiz?q=Desain Web')
            ->assertOk()
            ->assertSee('Tipografi Modern')
            ->assertDontSee('Belajar Blade');
    }

    public function test_pencarian_yang_tidak_kecocokan_menampilkan_empty_state(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');

        $this->actingAs($user)
            ->get('/user/quiz?q=Kubernetes')
            ->assertOk()
            ->assertSee('Quiz tidak ditemukan')
            ->assertSee('Coba gunakan kata kunci lain.')
            ->assertSee('Reset Pencarian');
    }

    public function test_halaman_quiz_juga_menampilkan_quiz_pengguna_lain(): void
    {
        $budi = $this->buatPengguna();
        $siti = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $budi, 'Quiz Budi');
        $this->buatQuiz($pelajaran, $siti, 'Quiz Siti');

        // Halaman Quiz menampilkan seluruh quiz yang tayang, bukan hanya
        // milik sendiri.
        $this->actingAs($budi)
            ->get('/user/quiz')
            ->assertOk()
            ->assertSee('Quiz Budi')
            ->assertSee('Quiz Siti');
    }

    public function test_halaman_quiz_tidak_perlunya_tombol_buat(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');

        // Membuat quiz hanya lewat menu "Karya Saya".
        $this->actingAs($user)
            ->get('/user/quiz')
            ->assertOk()
            ->assertDontSee(route('user.quiz.tambah'), false);
    }

    public function test_quiz_yang_masih_draft_tetap_dikelola_di_karya_saya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'Quiz Belum Terbit', 'Deskripsi.', Quiz::STATUS_DRAFT);

        // Halaman Quiz umum hanya menampilkan quiz yang sudah terbit.
        $this->actingAs($user)
            ->get('/user/quiz')
            ->assertOk()
            ->assertDontSee('Quiz Belum Terbit');

        // Karya Saya tetap menampilkannya supaya pemilik bisa mengetahuinya.
        $this->actingAs($user)
            ->get('/user/karya-saya?tab=quiz')
            ->assertOk()
            ->assertSee('Quiz Belum Terbit');
    }

    public function test_halaman_quiz_membuka_form_buat(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->get('/user/quiz/tambah')
            ->assertOk()
            ->assertSee('Buat Quiz')
            ->assertSee('name="judul"', false);
    }

    public function test_halaman_quiz_menampilkan_kerangka_saat_memuat(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');

        $response = $this->actingAs($user)->get('/user/quiz');

        $response->assertOk();

        // Kerangka ikut di-render (hidden) supaya bisa ditampilkan JavaScript
        // saat tab diganti atau pencarian dikirim.
        $this->assertStringContainsString('data-quiz-rangka', $response->getContent());
        $this->assertStringContainsString('kartu-quiz--rangka', $response->getContent());
    }

    public function test_kartu_memiliki_tombol_bookmark_terpisah_dari_tautan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');

        $this->actingAs($user)
            ->get('/user/quiz')
            ->assertOk()
            // type="button" supaya diklik tidak mengirim form / mengikuti <a>.
            ->assertSee('type="button"', false)
            ->assertSee('data-bookmark-ruang="quiz"', false)
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_kolom_cari_memakai_placeholder_yang_tepat(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');

        $isi = $this->actingAs($user)->get('/user/quiz')->getContent();

        // Kolom cari di toolbar dan di top bar sama-sama berburu quiz.
        $this->assertStringContainsString('placeholder="Cari quiz..."', $isi);
        $this->assertStringContainsString('placeholder="Cari kuis..."', $isi);
    }

    public function test_quiz_baru_tersimpan_dengan_soalnya_dan_muncul_di_karya_saya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->post('/user/quiz/tambah', [
                'pelajaran_id' => $pelajaran->id,
                'judul' => 'Quiz Baru Saya',
                'deskripsi' => 'Deskripsi singkat.',
                'durasi' => 15,
                'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
                'soal' => [
                    ['pertanyaan' => 'Apa itu HTML?', 'pilihan_a' => 'Markup', 'pilihan_b' => 'Program', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban_benar' => 'A', 'tingkat_kesulitan' => 'Mudah'],
                ],
            ])
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tb_quiz', [
            'judul' => 'Quiz Baru Saya',
            'slug' => 'quiz-baru-saya',
            'dibuat_oleh' => $user->getKey(),
            'pelajaran_id' => $pelajaran->id,
            'status' => Quiz::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('tb_soal', [
            'pertanyaan' => 'Apa itu HTML?',
            'jawaban_benar' => 'A',
            'urutan' => 1,
        ]);

        $this->actingAs($user)
            ->get('/user/karya-saya?tab=quiz')
            ->assertOk()
            ->assertSee('Quiz Baru Saya');
    }

    public function test_form_buat_menolak_quiz_tanpa_soal(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->post('/user/quiz/tambah', [
                'pelajaran_id' => $pelajaran->id,
                'judul' => 'Quiz Tanpa Soal',
                'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
                'soal' => [],
            ])
            ->assertSessionHasErrors(['soal']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_form_buat_menolak_input_tidak_valid(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran('Pemrograman', 'pemrograman');

        $this->actingAs($user)
            ->post('/user/quiz/tambah', [
                'pelajaran_id' => null,
                'judul' => '',
                'visibilitas' => 'terbuka',
                'soal' => [
                    ['pertanyaan' => '', 'pilihan_a' => '', 'pilihan_b' => '', 'pilihan_c' => '', 'pilihan_d' => '', 'jawaban_benar' => 'Z', 'tingkat_kesulitan' => 'Tidak Dikenal'],
                ],
            ])
            ->assertSessionHasErrors([
                'pelajaran_id',
                'judul',
                'visibilitas',
                'soal.0.pertanyaan',
                'soal.0.pilihan_a',
                'soal.0.jawaban_benar',
                'soal.0.tingkat_kesulitan',
            ]);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_dibuat_oleh_selalu_mengikuti_pengguna_yang_login(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);

        $this->actingAs($user)->post('/user/quiz/tambah', [
            'pelajaran_id' => $pelajaran->id,
            'judul' => 'Quiz Curang',
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'dibuat_oleh' => $orangLain->getKey(),
            'soal' => [
                ['pertanyaan' => 'Apa itu HTTP?', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban_benar' => 'A', 'tingkat_kesulitan' => 'Mudah'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_quiz', [
            'judul' => 'Quiz Curang',
            'dibuat_oleh' => $user->getKey(),
        ]);
    }

    public function test_slug_duplikat_diberi_akhiran_otomatis(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $this->buatQuiz($pelajaran, $user, 'Judul Kembar');

        $this->actingAs($user)->post('/user/quiz/tambah', [
            'pelajaran_id' => $pelajaran->id,
            'judul' => 'Judul Kembar',
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'soal' => [
                ['pertanyaan' => 'Apa itu CSS?', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban_benar' => 'A', 'tingkat_kesulitan' => 'Mudah'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_quiz', ['slug' => 'judul-kembar-2']);
    }

    public function test_halaman_detail_quiz_menampilkan_soalnya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $quiz = $this->buatQuiz($pelajaran, $user, 'HTML & CSS Dasar');
        $this->buatSoal($quiz);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz->getKey()))
            ->assertOk()
            ->assertSee('HTML & CSS Dasar')
            ->assertSee('Pertanyaan nomor 1')
            ->assertSee('1 Soal');
    }

    public function test_detail_quiz_orang_lain_yang_belum_terbit_menghasilkan_404(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $quiz = $this->buatQuiz($pelajaran, $orangLain, 'Quiz Draft Siti', 'Deskripsi.', Quiz::STATUS_DRAFT);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz->getKey()))
            ->assertNotFound();
    }

    public function test_pemilik_tetap_bisa_membuka_quiz_sendiri_yang_masih_draft(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Draft Saya', 'Deskripsi.', Quiz::STATUS_DRAFT);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $quiz->getKey()))
            ->assertOk()
            ->assertSee('Quiz Draft Saya')
            ->assertSee('Draft');
    }

    public function test_halaman_quiz_membutuhkan_login(): void
    {
        $this->get('/user/quiz')->assertRedirect('/login');
        $this->get('/user/quiz/tambah')->assertRedirect('/login');
        $this->post('/user/quiz/tambah')->assertRedirect('/login');
    }
}
