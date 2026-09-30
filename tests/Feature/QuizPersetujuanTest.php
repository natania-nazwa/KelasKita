<?php

namespace Tests\Feature;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur persetujuan quiz mode publik oleh admin.
 *
 * Yang diuji di sini adalah inti dari fitur ini: quiz publik buatan pengguna
 * tidak pernah tayang sebelum disetujui, admin memutuskan lewat satu tempat
 * saja, quiz yang sudah ditolak dua kali tidak bisa masuk daftar tunggu lagi,
 * dan quiz mode kode tidak pernah ikut alur ini sama sekali.
 *
 * Test ini sengaja tidak mengulang aturan validasi field yang sudah diuji di
 * QuizWizardTest; yang di sini hanya percabangan status.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml).
 */
class QuizPersetujuanTest extends TestCase
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

    private function buatQuiz(?User $pemilik, string $status, string $judul = 'Quiz Uji'): Quiz
    {
        $quiz = Quiz::create([
            'pelajaran_id' => $this->buatPelajaran('Matematika', 'matematika')->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value().'-'.Quiz::query()->count(),
            'deskripsi' => 'Ringkasan quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);

        $this->buatSoal($quiz);

        return $quiz;
    }

    private function buatSoal(Quiz $quiz, int $urutan = 1): void
    {
        $quiz->soal()->create([
            'pertanyaan' => "Pertanyaan nomor $urutan",
            // Kolom pilihan_a sampai pilihan_f masih NOT NULL di tabel, jadi
            // harus diisi apa adanya walau builder modern menulis pilihannya
            // ke tb_soal_pilihan.
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'jawaban_benar' => 'A',
            'urutan' => $urutan,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);
    }

    /**
     * Buka satu halaman sekali supaya pesan sukses dari permintaan sebelumnya
     * habis. Tanpa ini, judul quiz ikut terbaca di halaman berikutnya bukan
     * karena quiz-nya tayang, tapi karena pesannya masih tersimpan di flash.
     */
    private function habiskanFlash(): void
    {
        $this->get(route('user.dashboard'))->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'judul' => 'Quiz Diajukan',
            'deskripsi' => 'Deskripsi quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'soal' => [
                ['pertanyaan' => 'Apa itu variabel?', 'pilihan' => ['A' => 'Penyimpan nilai', 'B' => 'Label gambar'], 'benar' => ['A'], 'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH],
            ],
        ], $tambahan);
    }

    /*
     * =====================================================================
     * PENGAJUKAN
     * =====================================================================
     */

    public function test_quiz_baru_tidak_langsung_tayang_walau_tombol_persetujuan_ditekan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post(route('user.quiz.tambah.store'), $this->dataForm($pelajaran, ['publikasikan' => '1']))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->sole();

        $this->assertSame(Quiz::STATUS_PENDING, $quiz->status);
        $this->assertNull($quiz->dipublish_pada);

        $this->habiskanFlash();

        $this->actingAs($user)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertDontSee($quiz->judul);
    }

    public function test_quiz_baru_tanpa_pengajuan_tetap_draft_dan_tidak_ikut_menunggu(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post(route('user.quiz.tambah.store'), $this->dataForm($pelajaran))
            ->assertSessionHasNoErrors();

        $this->assertSame(Quiz::STATUS_DRAFT, Quiz::query()->sole()->status);
        $this->assertSame(0, Quiz::query()->menunggu()->count());
    }

    /*
     * =====================================================================
     * KEPUTUSAN ADMIN
     * =====================================================================
     */

    public function test_admin_menyetujui_quiz_lalu_quiz_tayang(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING);

        $this->actingAs($admin)
            ->from(route('admin.quiz', ['status' => Quiz::STATUS_PENDING]))
            ->post(route('admin.quiz.setujui', $quiz))
            ->assertRedirect(route('admin.quiz', ['status' => Quiz::STATUS_PENDING]))
            ->assertSessionHas('sukses');

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
        $this->assertNotNull($quiz->dipublish_pada);

        // Setelah terbit, quiz bisa dibuka dan dikerjakan lewat halaman detail.
        $pembaca = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);

        $this->actingAs($pembaca)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertSee($quiz->judul);
    }

    public function test_quiz_ditolak_menyimpan_alasan_dan_menghitung_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING);

        $this->actingAs($admin)
            ->post(route('admin.quiz.tolak', $quiz), ['alasan' => 'Soal nomor 1 belum punya pembahasan.'])
            ->assertRedirect();

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_REJECTED, $quiz->status);
        $this->assertSame('Soal nomor 1 belum punya pembahasan.', $quiz->catatan_admin);
        $this->assertSame(1, $quiz->jumlah_ditolak);
    }

    public function test_penolakan_tanpa_alasan_ditolak_dengan_pesan(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING);

        $this->actingAs($admin)
            ->post(route('admin.quiz.tolak', $quiz), ['alasan' => ''])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(Quiz::STATUS_PENDING, $quiz->refresh()->status);
    }

    public function test_admin_tidak_bisa_memutuskan_quiz_yang_belum_menunggu(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        foreach ([Quiz::STATUS_DRAFT, Quiz::STATUS_PUBLISHED, Quiz::STATUS_REJECTED] as $status) {
            $quiz = $this->buatQuiz($pemilik, $status, 'Quiz '.$status);

            $this->actingAs($admin)
                ->post(route('admin.quiz.setujui', $quiz))
                ->assertNotFound();

            $this->actingAs($admin)
                ->post(route('admin.quiz.tolak', $quiz), ['alasan' => 'Alasan apa pun.'])
                ->assertNotFound();

            $this->assertSame($status, $quiz->refresh()->status);
        }
    }

    /**
     * Halaman admin/quiz bukan lagi tempat memutuskan persetujuan.
     *
     * Dulu halaman itutinjau dengan tab per status. Sekarang jadi halaman
     * kelola quiz yang sudah terbit — sama seperti halaman Materi — jadi
     * quiz yang masih menunggu tidak muncul di sana, dan keputusan tetap
     * diambil di menu Verifikasi (lihat VerifikasiHalamanTest).
     */
    public function test_halaman_quiz_hanya_menampilkan_quiz_yang_sudah_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Menunggu');
        $this->buatQuiz($pemilik, Quiz::STATUS_PUBLISHED, 'Quiz Terbit');

        $this->actingAs($admin)
            ->get(route('admin.quiz'))
            ->assertOk()
            ->assertSee('Quiz Terbit')
            ->assertDontSee('Quiz Menunggu');

        // Parameter status lama tidak lagi berarti apa pun, dan tidak boleh
        // diam-diam membuka daftar yang tidak pernah ada.
        $this->actingAs($admin)
            ->get(route('admin.quiz', ['status' => Quiz::STATUS_PENDING]))
            ->assertOk()
            ->assertDontSee('Quiz Menunggu');
    }

    public function test_keputusan_quiz_tidak_lagi_ada_di_halaman_quiz(): void
    {
        $admin = $this->buatAdmin();
        $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PUBLISHED, 'Quiz Terbit');

        $this->actingAs($admin)
            ->get(route('admin.quiz'))
            ->assertOk()
            ->assertDontSee('Setujui &amp; Terbitkan')
            ->assertDontSee('Tolak Quiz')
            ->assertDontSee('Menunggu Persetujuan');
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_quiz(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_PENDING);

        $this->actingAs($user)
            ->get(route('admin.quiz'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.quiz.setujui', $quiz))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.quiz'))->assertRedirect(route('login'));
    }

    /*
     * =====================================================================
     * PENGAJUKAN ULANG SETELAH DITOLAK
     * =====================================================================
     */

    public function test_pengajuan_ulang_wajib_disertai_catatan_pendukung(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_REJECTED);
        $quiz->tolak('Pembahasan soal masih kosong.');
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->dataForm($pelajaran, ['publikasikan' => '1']))
            ->assertSessionHasErrors('catatan_pengajuan');

        $this->assertSame(Quiz::STATUS_REJECTED, $quiz->refresh()->status);
    }

    public function test_pengajuan_ulang_dengan_catatan_masuk_ke_daftar_tunggu(): void
    {
        $user = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_REJECTED);
        $quiz->tolak('Pembahasan soal masih kosong.');
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->dataForm($pelajaran, [
                'publikasikan' => '1',
                'catatan_pengajuan' => 'Semua soal sudah saya beri pembahasan.',
            ]))
            ->assertSessionHasNoErrors();

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_PENDING, $quiz->status);
        $this->assertNull($quiz->catatan_admin, 'Catatan penolakan lama harus dibersihkan saat mengajukan ulang.');
        $this->assertSame('Semua soal sudah saya beri pembahasan.', $quiz->catatan_pengajuan);

        $this->actingAs($admin)->get(route('admin.quiz'))->assertOk()->assertSee($quiz->judul);
    }

    public function test_quiz_yang_sudah_ditolak_dua_kali_tidak_bisa_diajukan_lagi(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_REJECTED);
        $quiz->tolak('Pembahasan soal masih kosong.');
        $quiz->tolak('Soal nomor 2 masih salah.');
        $pelajaran = $this->buatPelajaran();

        $this->assertFalse($quiz->bolehDiajukan());
        $this->assertSame(0, $quiz->sisaPengajuan());

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->dataForm($pelajaran, [
                'publikasikan' => '1',
                'catatan_pengajuan' => 'Semua soal sudah diperbaiki.',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Quiz::STATUS_REJECTED, $quiz->refresh()->status);
    }

    public function test_revisi_quiz_terbit_kembali_menunggu_dan_berhenti_tayang(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_DRAFT);
        $quiz->setujui();
        $pelajaran = $this->buatPelajaran();

        $this->assertTrue($quiz->bolehDiajukan());

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->dataForm($pelajaran, ['publikasikan' => '1']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']));

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_PENDING, $quiz->status);
        $this->assertNull($quiz->dipublish_pada, 'Quiz yang menunggu keputusan tidak boleh punya tanggal terbit.');

        $this->habiskanFlash();

        $this->actingAs($user)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertDontSee($quiz->judul);
    }

    public function test_quiz_terbit_yang_belum_dikirim_kembali_tetap_tayang(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_DRAFT);
        $quiz->setujui();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->dataForm($pelajaran))
            ->assertSessionHasNoErrors();

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
        $this->assertNotNull($quiz->dipublish_pada);
    }

    /*
     * =====================================================================
     * TAMPILAN
     * =====================================================================
     */

    public function test_catatan_pendukung_hanya_tampil_pada_quiz_yang_ditolak(): void
    {
        $user = $this->buatPengguna();

        $draft = $this->buatQuiz($user, Quiz::STATUS_DRAFT, 'Quiz Draft');
        $terbit = $this->buatQuiz($user, Quiz::STATUS_PUBLISHED, 'Quiz Terbit');
        $ditolak = $this->buatQuiz($user, Quiz::STATUS_REJECTED, 'Quiz Ditolak');
        $ditolak->tolak('Pembahasan soal masih kosong.');

        // Quiz yang belum pernah dinilai admin tidak dimintai catatan, jadi
        // isian yang bisa menyesatkan itu tidak ikut dirender.
        foreach ([$draft, $terbit] as $quiz) {
            $this->actingAs($user)
                ->get(route('user.quiz.edit', $quiz))
                ->assertOk()
                ->assertDontSee('name="catatan_pengajuan"', false)
                ->assertDontSee('data-catatan-isian', false);
        }

        $halaman = $this->actingAs($user)
            ->get(route('user.quiz.edit', $ditolak))
            ->assertOk()
            ->assertSee('Alasan ditolak admin')
            ->assertSee('name="catatan_pengajuan"', false)
            ->assertSee('data-catatan-isian', false)
            ->baseResponse->getContent();

        // Tertutup sampai saklar "Ajukan Persetujuan" dinyalakan.
        $this->assertMatchesRegularExpression('/data-catatan-wadah\s+hidden/', $halaman);
    }

    /**
     * Alasan penolakan admin dibaca balik di "Karya Saya", supaya pemiliknya
     * tahu apa yang harus diperbaiki sebelum mengajukan ulang.
     */
    public function test_karya_saya_menampilkan_alasan_penolakan(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_DRAFT, 'Quiz Menunggu Tinjauan');
        $quiz->tolak('Pembahasan soal masih kosong.');

        $this->actingAs($user)
            ->get(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Quiz Menunggu Tinjauan')
            ->assertSee('Alasan ditolak admin')
            ->assertSee('Pembahasan soal masih kosong.');
    }

    /**
     * Quiz yang sudah ditolak BATAS_PENGAJUAN_ULANG kali tidak bisa diajukan
     * lagi, jadi saklarnya dimatikan. Alasannya ditulis di form, bukan
     *biancarkan pemilik mengira formnya yang rusak.
     */
    public function test_saklar_pengajuan_dimatikan_kalem_quiz_habis_ditolak(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_DRAFT);
        $quiz->tolak('Pembahasan soal masih kosong.');
        $quiz->tolak('Soal nomor 2 masih salah.');

        $halaman = $this->actingAs($user)
            ->get(route('user.quiz.edit', $quiz))
            ->assertOk()
            ->assertSee('tidak bisa diajukan lagi')
            ->assertDontSee('name="catatan_pengajuan"', false)
            ->baseResponse->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="saklar-publikasikan"[^>]*\sdisabled/',
            $halaman,
        );
    }

    public function test_halaman_buat_quiz_menampilkan_saklar_pengajuan(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get(route('user.quiz.tambah'))
            ->assertOk()
            ->assertSee('Ajukan Persetujuan Admin')
            ->assertSee('name="publikasikan"', false)
            ->assertSee('data-wizard-approval-area', false);
    }

    /*
     * =====================================================================
     * QUIZ MODE KODE DI HALAMAN KARYA SAYA DAN DETAIL
     * =====================================================================
     */

    /**
     * Kartu di "Karya Saya" untuk quiz mode kode menampilkan kodenya dan
     * tombol "Buka Sesi" yang langsung menuju lobby. Tanpa dua hal itu,
     * pemilik harus membuka halaman detail dulu hanya untuk menemukan
     * jalan masuk ke sesinya.
     */
    public function test_kartu_quiz_mode_kode_menampilkan_kode_dan_tombol_buka_sesi(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_DRAFT, 'Quiz Kode Kelas');
        $quiz->update([
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'K7F3P9',
        ]);

        $this->actingAs($user)
            ->get(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Quiz Kode Kelas')
            ->assertSee('Kode K7F3P9')
            ->assertSee('Buka Sesi')
            ->assertSee(route('user.sesi.buka', $quiz), false);
    }

    /**
     * Halaman detail quiz mode kode menampilkan kodenya untuk dibagikan,
     * plus tombol membuka sesi. Quiz mode publik tidak punya bagian ini:
     * tidak ada kode yang perlu dibagikan.
     */
    public function test_halaman_detail_menampilkan_jalur_lobby_hanya_untuk_quiz_mode_kode(): void
    {
        $user = $this->buatPengguna();

        $kode = $this->buatQuiz($user, Quiz::STATUS_DRAFT, 'Quiz Kode Kelas');
        $kode->update([
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'K7F3P9',
        ]);

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $kode))
            ->assertOk()
            ->assertSee('Buka Sesi')
            ->assertSee('K7F3P9');

        $publik = $this->buatQuiz($user, Quiz::STATUS_DRAFT, 'Quiz Publik Kelas');

        $this->actingAs($user)
            ->get(route('user.quiz.detail', $publik))
            ->assertOk()
            ->assertDontSee('Buka Sesi');
    }

    /*
     * =====================================================================
     * QUIZ MODE KODE
     * =====================================================================
     */

    /**
     * Quiz mode kode tidak pernah ikut persetujuan admin. Yang berbasis kode
     * adalah miliknya sendiri, bukan milik seluruh pengguna, jadi tidak ada
     * yang perlu disetujui dan quiznya boleh langsung dipakai.
     */
    public function test_quiz_mode_kode_tidak_perlu_persetujuan_admin(): void
    {
        $user = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post(route('user.quiz.tambah.store'), $this->dataForm($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'K7F3P9',
                'publikasikan' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->sole();

        $this->assertTrue($quiz->pakaiKode());
        $this->assertSame(Quiz::STATUS_DRAFT, $quiz->status);
        $this->assertFalse($quiz->bolehDiajukan());
        $this->assertFalse($quiz->perluPersetujuan());

        // Tidak muncul di halaman tinjauan admin, dan tidak tayang di halaman
        // Quiz untuk semua pengguna. Flash dari permintaan sebelumnya
        // dibuat dulu, karena pesannya sendiri memuat judul quiz.
        $this->habiskanFlash();

        $this->actingAs($admin)
            ->get(route('admin.quiz'))
            ->assertOk()
            ->assertDontSee($quiz->judul);

        $this->actingAs($user)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertDontSee($quiz->judul);
    }

    /**
     * Quiz yang tayang lalu diganti pemilik menjadi mode kode ditarik dari
     * halaman Quiz. Kalau tidak, quiz privat akan ikut tayang ke semua orang.
     */
    public function test_quiz_terbit_yang_dijadi_mode_kode_berhenti_tayang(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz($user, Quiz::STATUS_DRAFT);
        $quiz->setujui();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->dataForm($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'K7F3P9',
            ]))
            ->assertSessionHasNoErrors();

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_DRAFT, $quiz->status);
        $this->assertNull($quiz->dipublish_pada);
        $this->assertSame('K7F3P9', $quiz->kode_akses);

        $this->habiskanFlash();

        $this->actingAs($user)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertDontSee($quiz->judul);
    }
}
