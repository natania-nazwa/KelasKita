<?php

namespace Tests\Feature;

use App\Models\JawabanQuiz;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\PesertaQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur lobby quiz: masuk dengan kode, ruang tunggu, mulai, mengerjakan,
 * dan hasil.
 *
 * Yang diuji di sini adalah aturan main fitur ini, terutama tiga hal yang
 * harus tetap benar walau guard-nya nanti dipindah:
 *   - soal tidak boleh dibuka sebelum host memulai (dibuka lewat URL
 *     langsung, bukan cuma lewat tombol di halaman);
 *   - hanya host dan peserta sesi itu yang boleh masuk;
 *   - status sesi tidak pernah dimundur atau kelewat satu langkah.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di
 * sini tidak menyentuh database sungguhan.
 */
class QuizLobbyTest extends TestCase
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

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Matematika',
            'slug' => 'matematika',
            'deskripsi' => 'Pelajaran matematika.',
            'ikon' => '</>',
            'aktif' => true,
        ]);
    }

    private function buatQuiz(User $pembuat, string $judul = 'Quiz Pecahan'): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
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

    private function buatSesi(
        Quiz $quiz,
        User $host,
        string $status = SesiQuiz::STATUS_MENUNGGU,
        string $kode = 'ABC123',
    ): SesiQuiz {
        return SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $host->getKey(),
            'kode' => $kode,
            'status' => $status,
        ]);
    }

    private function ikut(SesiQuiz $sesi, User $pengguna, string $status = PesertaQuiz::STATUS_LOBBY): PesertaQuiz
    {
        return $sesi->peserta()->create([
            'pengguna_id' => $pengguna->getKey(),
            'status' => $status,
            'bergabung_pada' => now(),
        ]);
    }

    /**
     * Nilai peserta diambil dari pengerjaannya, bukan dari akunnya, jadi
     * test butuh jalan ini untuk mengecek penilaian tanpa membaca view.
     */
    private function nilai(User $pengguna): ?int
    {
        return PengerjaanQuiz::query()
            ->where('pengguna_id', $pengguna->getKey())
            ->value('nilai');
    }

    /*
     * =====================================================================
     * MEMBUKA SESI SEBAGAI HOST
     * =====================================================================
     */

    public function test_pemilik_quiz_bisa_membuka_sesi_dan_masuk_ke_lobby(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);

        $respons = $this->actingAs($host)
            ->from(route('user.quiz.detail', $quiz))
            ->post(route('user.sesi.buka', $quiz));

        $sesi = SesiQuiz::query()->firstOrFail();

        $respons->assertRedirect(route('user.sesi.lobby', $sesi));
        $this->assertSame(SesiQuiz::STATUS_MENUNGGU, $sesi->status);
        $this->assertSame($host->getKey(), $sesi->host_id);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}[0-9]{3}$/', $sesi->kode);
    }

    public function test_orang_lain_tidak_bisa_membuka_sesi_quiz_milik_orang(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);

        $penyusup = $this->buatPengguna(['nama' => 'Keyla', 'email' => 'keyla@example.com']);

        $this->actingAs($penyusup)
            ->post(route('user.sesi.buka', $quiz))
            ->assertForbidden();

        $this->assertSame(0, SesiQuiz::query()->count());
    }

    public function test_quiz_tanpa_soal_tidak_bisa_dijalankan_sebagai_sesi(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);

        $this->actingAs($host)
            ->from(route('user.quiz.detail', $quiz))
            ->post(route('user.sesi.buka', $quiz))
            ->assertSessionHasErrors('sesi');

        $this->assertSame(0, SesiQuiz::query()->count());
    }

    public function test_membuka_sesi_kedua_mengarahkan_kembali_ke_lobby_yang_sama(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);

        $pertama = $this->actingAs($host)->post(route('user.sesi.buka', $quiz));
        $sesi = SesiQuiz::query()->firstOrFail();

        $pertama->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->actingAs($host)
            ->post(route('user.sesi.buka', $quiz))
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertSame(1, SesiQuiz::query()->count());
    }

    /*
     * =====================================================================
     * GABUNG DENGAN KODE
     * =====================================================================
     */

    public function test_peserta_bisa_gabung_dengan_kode_dan_diarahkan_ke_lobby(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'abc123'])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertDatabaseHas('tb_peserta_quiz', [
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $peserta->getKey(),
            'status' => PesertaQuiz::STATUS_LOBBY,
        ]);
    }

    public function test_kode_yang_salah_ditolak_dengan_pesan_yang_jelas(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $this->buatSesi($quiz, $host, SesiQuiz::STATUS_MENUNGGU, 'ABC123');

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'ZZZ999'])
            ->assertSessionHasErrors(['kode' => 'Kode quiz tidak ditemukan.']);

        $this->assertSame(0, PesertaQuiz::query()->count());
    }

    public function test_quiz_yang_sudah_selesai_tidak_bisa_digabung_lagi(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $this->buatSesi($quiz, $host, SesiQuiz::STATUS_SELESAI);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'ABC123'])
            ->assertSessionHasErrors(['kode' => 'Quiz ini sudah selesai.']);

        $this->assertSame(0, PesertaQuiz::query()->count());
    }

    public function test_peserta_yang_sudah_bergabung_tidak_didaftarkan_dua_kali(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'ABC123'])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertSame(1, PesertaQuiz::query()->count());
    }

    public function test_host_yang_mengetik_kodenya_diarahkan_ke_lobby_miliknya_sendiri(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);

        $this->actingAs($host)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'ABC123'])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertSame(0, PesertaQuiz::query()->count());
    }

    /*
     * =====================================================================
     * AKSES KE LOBBY
     * =====================================================================
     */

    public function test_orang_lain_tidak_bisa_membuka_lobby_sesi_orang_lain(): void
    {
        $host = $this->buatPengguna();
        $penyusup = $this->buatPengguna(['nama' => 'Keyla', 'email' => 'keyla@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);

        $this->actingAs($penyusup)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertForbidden();

        $this->actingAs($penyusup)
            ->get(route('user.sesi.data', $sesi))
            ->assertForbidden();
    }

    public function test_host_melihat_kode_sedangkan_peserta_tidak(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        // Yang dibedakan adalah alamat aksi "Mulai", bukan teksnya: kalimat
        // "menekan tombol Mulai Quiz" memang sengaja muncul di halaman
        // peserta sebagai penjelasan apa yang sedang ditunggu.
        $alamatMulai = route('user.sesi.mulai', $sesi);

        $this->actingAs($host)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertOk()
            ->assertSee('ABC123')
            ->assertSee($alamatMulai, escape: false);

        $this->actingAs($peserta)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertOk()
            ->assertDontSee($alamatMulai, escape: false)
            ->assertSee('Menunggu host memulai quiz');
    }

    public function test_endpoint_status_mengirim_daftar_peserta_untuk_polling(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($host)
            ->getJson(route('user.sesi.data', $sesi))
            ->assertOk()
            ->assertJsonPath('status', SesiQuiz::STATUS_MENUNGGU)
            ->assertJsonPath('adalah_host', true)
            ->assertJsonPath('jumlah_peserta', 1)
            ->assertJsonPath('peserta.0.nama', 'Irma');
    }

    /*
     * =====================================================================
     * ATURAN UTAMA: SOAL TERTUTUP SAMPAI HOST MEMULAI
     * =====================================================================
     */

    public function test_peserta_tidak_bisa_membuka_soal_selagi_sesi_masih_menunggu(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        // URL soal dibuka langsung, bukan lewat tautan di lobby.
        $this->actingAs($peserta)
            ->get(route('user.sesi.soal', [$sesi, 1]))
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->actingAs($peserta)
            ->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A'])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertSame(0, PengerjaanQuiz::query()->count());
        $this->assertSame(0, JawabanQuiz::query()->count());
    }

    public function test_host_tertahan_di_lobby_sampai_ia_sendiri_menekan_mulai(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);

        $this->actingAs($host)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertOk();

        $sesi->refresh();
        $this->assertSame(SesiQuiz::STATUS_MENUNGGU, $sesi->status);
    }

    /*
     * =====================================================================
     * MULAI DAN AKHIRI
     * =====================================================================
     */

    public function test_host_memulai_quiz_dan_peserta_langsung_masuk_ke_soal(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($host)
            ->post(route('user.sesi.mulai', $sesi))
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $sesi->refresh();
        $this->assertSame(SesiQuiz::STATUS_DIMULAI, $sesi->status);
        $this->assertNotNull($sesi->dimulai_pada);

        // Peserta yang masih di lobby langsung dikirim ke soal pertama.
        $this->actingAs($peserta)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertRedirect(route('user.sesi.soal', [$sesi, 1]));

        $this->actingAs($peserta)
            ->get(route('user.sesi.soal', [$sesi, 1]))
            ->assertOk();
    }

    public function test_peserta_tidak_boleh_memulai_quiz(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->post(route('user.sesi.mulai', $sesi))
            ->assertForbidden();

        $this->assertSame(SesiQuiz::STATUS_MENUNGGU, $sesi->refresh()->status);
    }

    public function test_menekan_mulai_dua_kali_tidak_menutup_quiz(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);

        $this->actingAs($host)->post(route('user.sesi.mulai', $sesi));

        // Tombol yang ter-tekan dua kali (mis. refresh setelah menekan).
        $this->actingAs($host)
            ->post(route('user.sesi.mulai', $sesi))
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $sesi->refresh();
        $this->assertSame(SesiQuiz::STATUS_DIMULAI, $sesi->status);
        $this->assertNull($sesi->selesai_pada);
    }

    public function test_mengakhiri_quiz_sebelum_dimulai_tetap_menutup_sesi(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);

        $this->actingAs($host)
            ->post(route('user.sesi.akhiri', $sesi))
            ->assertRedirect(route('user.sesi.hasil', $sesi));

        $sesi->refresh();
        $this->assertSame(SesiQuiz::STATUS_SELESAI, $sesi->status);
    }

    public function test_peserta_diarahkan_ke_hasil_setelah_host_mengakhiri_quiz(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($host)->post(route('user.sesi.mulai', $sesi));
        $this->actingAs($host)->post(route('user.sesi.akhiri', $sesi));

        $this->actingAs($peserta)
            ->get(route('user.sesi.soal', [$sesi, 1]))
            ->assertRedirect(route('user.sesi.hasil', $sesi));

        $this->actingAs($peserta)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertRedirect(route('user.sesi.hasil', $sesi));
    }

    /*
     * =====================================================================
     * MENGERJAKAN SOAL DAN HASIL
     * =====================================================================
     */

    public function test_menjawab_soal_pertama_menyimpan_jawaban_dan_lanjut_ke_soal_kedua(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $soalPertama = $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A'])
            ->assertRedirect(route('user.sesi.soal', [$sesi, 2]));

        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'soal_id' => $soalPertama->getKey(),
            'jawaban_dipilih' => 'A',
            'benar' => true,
        ]);

        // Membuka soal menandai peserta sebagai sedang mengerjakan, supaya
        // nama di lobby host tidak lagi menampilkan "Siap".
        $this->assertSame(
            PesertaQuiz::STATUS_MENGERJAKAN,
            $sesi->peserta()->where('pengguna_id', $peserta->getKey())->value('status'),
        );
    }

    public function test_mengubah_jawaban_menghitung_ulang_nilai_bukan_menambah(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $soal = $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A']);
        $this->actingAs($peserta)->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'B']);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        $this->assertSame(1, JawabanQuiz::query()->count());
        $this->assertSame(1, $pengerjaan->jumlah_dijawab);
        $this->assertSame(0, $pengerjaan->jumlah_benar);
        $this->assertSame(0, $pengerjaan->nilai);
    }

    public function test_menjawab_soal_terakhir_langsung_menampilkan_hasil(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        // Ini yang pernah salah: tujuan redirect-nya route POST, sehingga
        // browser hanya menerima 405 kalau form dikirim ulang.
        $this->actingAs($peserta)
            ->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A'])
            ->assertRedirect(route('user.sesi.hasil', $sesi));

        $this->assertSame(
            PesertaQuiz::STATUS_SELESAI,
            $sesi->peserta()->where('pengguna_id', $peserta->getKey())->value('status'),
        );
    }

    public function test_tombol_selesai_menutup_pengerjaan_di_tengah_jalur(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $soalPertama = $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A']);

        $this->actingAs($peserta)
            ->post(route('user.sesi.selesai', $sesi))
            ->assertRedirect(route('user.sesi.hasil', $sesi));

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        $this->assertNotNull($pengerjaan->selesai_pada);
        $this->assertSame(1, $pengerjaan->jumlah_benar);
        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'soal_id' => $soalPertama->getKey(),
        ]);
    }

    public function test_jawaban_kosong_ditolak_dengan_pesan_yang_membantu(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->from(route('user.sesi.soal', [$sesi, 1]))
            ->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => ''])
            ->assertRedirect(route('user.sesi.soal', [$sesi, 1]))
            ->assertSessionHasErrors('jawaban');

        $this->assertSame(0, JawabanQuiz::query()->count());
    }

    public function test_halaman_hasil_peserta_menampilkan_nilai_sendiri(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);

        // Jawaban harus masuk selagi sesi berjalan: sesi yang sudah selesai
        // memang menutup pintu jawaban, jadi mengisinya lewat jalur itu
        // hanya akan menguji guard yang sudah diuji test lain.
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A'])
            ->assertRedirect(route('user.sesi.hasil', $sesi));

        $sesi->tutup();

        $this->actingAs($peserta)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSee('100')
            ->assertSee('Nilai kamu');
    }

    public function test_halaman_hasil_host_menampilkan_daftar_nilai_terurut_dari_tertinggi(): void
    {
        $host = $this->buatPengguna();
        $andi = $this->buatPengguna(['nama' => 'Andi', 'email' => 'andi@example.com']);
        $budi = $this->buatPengguna(['nama' => 'Budi', 'email' => 'budi@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);

        $this->ikut($sesi, $andi);
        $this->ikut($sesi, $budi);

        // Andi benar, Budi salah, jadi urutannya harus turun dari 100 ke 0.
        // Kalau keduanya tidak dijawab, urutannya hanya urutan gabung dan
        // test ini jadi tidak membuktikan apa pun soal penilaian.
        $this->actingAs($andi)->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'A']);
        $this->actingAs($budi)->post(route('user.sesi.jawab', [$sesi, 1]), ['jawaban' => 'B']);

        $sesi->tutup();

        $this->assertSame(100, $this->nilai($andi));
        $this->assertSame(0, $this->nilai($budi));

        $this->actingAs($host)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSeeInOrder(['Andi', 'Budi']);
    }

    public function test_hanya_host_dan_peserta_yang_bisa_membuka_halaman_hasil(): void
    {
        $host = $this->buatPengguna();
        $penyusup = $this->buatPengguna(['nama' => 'Keyla', 'email' => 'keyla@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_SELESAI);

        $this->actingAs($penyusup)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertForbidden();
    }
}
