<?php

namespace Tests\Feature;

use App\Models\JawabanQuiz;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\PesertaQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\SoalRagu;
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

    /**
     * Pelajaran yang dipakai quiz di test ini.
     *
     * Dibuat sekali lalu dipakai ulang. Slug-nya unik di database, jadi
     * membuat baris baru setiap dipanggil akan menabrak unique index —
     * padahal test yang memakai lebih dari satu quiz hanya butuh satu
     * pelajaran yang sama.
     */
    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::firstOrCreate(
            ['slug' => 'matematika'],
            [
                'nama' => 'Matematika',
                'deskripsi' => 'Pelajaran matematika.',
                'ikon' => '</>',
                'aktif' => true,
            ],
        );
    }

    /**
     * Quiz untuk test ini.
     *
     *_opsional_ $kode menentukan mode aksesnya, karena dua mode itu punya
     * alur yang benar-benar berbeda:
     *   - null (default): quiz mode publik, tidak punya kode gabung.
     *   - diisi: quiz mode kode, satu-satunya cara mengujinya bersama-sama
     *     adalah lewat kode itu.
     */
    private function buatQuiz(User $pembuat, string $judul = 'Quiz Pecahan', ?string $kode = null): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 10,
            'visibilitas' => $kode === null ? Quiz::VISIBILITAS_PUBLIK : Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => $kode,
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

    /**
     * Soal dengan tipe dan pilihan tertentu, untuk menguji lima tipe yang
     * bisa dibuat Quiz Builder. Baris pilihannya ditulis ke
     * tb_soal_pilihan, sumber kebenaran baru; kolom pilihan_a sampai
     * pilihan_f sengaja dikosongkan supaya test ini benar-benar memakai
     * jalur baca yang sama dengan soal yang dibuat lewat builder.
     *
     * @param  array<int, string>  $hurufBenar
     * @param  array<string, string>  $pilihan
     */
    private function buatSoalTipe(
        Quiz $quiz,
        string $tipe,
        array $hurufBenar = ['A'],
        array $pilihan = [],
        int $urutan = 1,
        ?string $kunciTeks = null,
        bool $tococokPersis = true,
    ): Soal {
        $soal = Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => "Soal bertipe $tipe",
            'tipe' => $tipe,
            // Kolom pilihan_a sampai pilihan_f masih NOT NULL dan sengaja
            // dibiarkan ada di tabel, jadi harus diisi apa adanya. Isiannya
            // cuma cadangan; semua pembaca baru memakai tb_soal_pilihan.
            'pilihan_a' => '',
            'pilihan_b' => '',
            'pilihan_c' => '',
            'pilihan_d' => '',
            'jawaban_benar' => $hurufBenar[0] ?? 'A',
            'jawaban_teks' => $kunciTeks,
            'tococok_persis' => $tococokPersis,
            'urutan' => $urutan,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);

        $urutanPilihan = 0;

        foreach ($pilihan as $huruf => $teks) {
            $urutanPilihan++;

            $soal->pilihanSoal()->create([
                'huruf' => $huruf,
                'teks' => $teks,
                'urutan' => $urutanPilihan,
                'benar' => in_array($huruf, $hurufBenar, true),
            ]);
        }

        return $soal->refresh();
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

    /**
     * Kode yang dipakai test alur gabung. Mengikuti abjad dan panjang yang
     * sama dengan App\Support\KodeQuiz supaya angka di test ini bukan
     * keliru karena bentuk kodenya berbeda dari yang dibuat wizard.
     */
    private const KODE = 'K7F3P9';

    public function test_pemilik_quiz_bisa_membuka_sesi_dan_masuk_ke_lobby(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);

        $respons = $this->actingAs($host)
            ->from(route('user.quiz.detail', $quiz))
            ->post(route('user.sesi.buka', $quiz));

        $sesi = SesiQuiz::query()->firstOrFail();

        $respons->assertRedirect(route('user.sesi.lobby', $sesi));
        $this->assertSame(SesiQuiz::STATUS_MENUNGGU, $sesi->status);
        $this->assertSame($host->getKey(), $sesi->host_id);

        // Kode sesi sama persis dengan kode yang harus diketik peserta. Kalau
        // berbeda, angka yang tampil di lobby tidak akan pernah bisa dipakai.
        $this->assertSame(self::KODE, $sesi->kode);
    }

    public function test_quiz_publik_tidak_bisa_dijalankan_sebagai_sesi_kode(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);

        $this->actingAs($host)
            ->from(route('user.quiz.detail', $quiz))
            ->post(route('user.sesi.buka', $quiz))
            ->assertSessionHasErrors('sesi');

        $this->assertSame(0, SesiQuiz::query()->count());
    }

    public function test_orang_lain_tidak_bisa_membuka_sesi_quiz_milik_orang(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
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
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);

        $this->actingAs($host)
            ->from(route('user.quiz.detail', $quiz))
            ->post(route('user.sesi.buka', $quiz))
            ->assertSessionHasErrors('sesi');

        $this->assertSame(0, SesiQuiz::query()->count());
    }

    public function test_membuka_sesi_kedua_mengarahkan_kembali_ke_lobby_yang_sama(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
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

    /**
     * Garis putus-putus yang menyambung angka 1-2-3 di blok "Cara kerjanya"
     * harus hilang di posisi mobile, dan tetap ada di desktop.
     *
     * Dua-duanya dikunci di sini karena perbaikannya mudah hilang diam-diam.
     * Aturan dasarnya memakai :not(:last-child), jadi aturan yang
     * menyembunyikannya di layar kecil wajib memakai selektor yang sama:
     * kalau ditulis polos sebagai ".lobi-langkah__item::after", garisnya
     * kalah spesifisitas dan tetap tampil di HP. Aturan dengan :not() yang
     * salah tidak akan ketahuan kalau hanya dilihat di lebar desktop.
     */
    public function test_garis_penghubung_langkah_hilang_di_mobile_dan_tetap_di_desktop(): void
    {
        $peserta = $this->buatPengguna();

        $this->actingAs($peserta)
            ->get(route('user.sesi.gabung'))
            ->assertOk()
            ->assertSee('Cara kerjanya')
            ->assertSee('lobi-langkah__item', false);

        $css = preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(resource_path('css/app.css')));

        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*63\.999rem\)\s*\{\s*\.lobi-langkah__item:not\(:last-child\)::after\s*\{\s*content:\s*none/',
            (string) $css,
            'Garis penghubung harus disembunyikan sampai batas lg (1024px), dengan selektor :not(:last-child) yang sama seperti aturan dasarnya.'
        );
    }

    public function test_peserta_bisa_gabung_dengan_kode_dan_diarahkan_ke_lobby(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_MENUNGGU, self::KODE);

        // Huruf kecil harus tetap menemukan kode yang disimpan huruf besar.
        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'k7f3p9'])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertDatabaseHas('tb_peserta_quiz', [
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $peserta->getKey(),
            'status' => PesertaQuiz::STATUS_LOBBY,
        ]);
    }

    /**
     * Peserta boleh masuk lebih dulu sebelum host membuka lobby. Sesi
     * dibuat saat itu juga, tapi host-nya pemilik quiz, bukan peserta yang
     * kebetulan lebih dulu mengetik kode. Kalau tidak, pemilik quiz
     * kehilangan hak memulai quiznya sendiri.
     */
    public function test_peserta_yang_masuk_lebih_dulu_membuat_lobi_dengan_host_pemilik_quiz(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => self::KODE])
            ->assertRedirect(route('user.sesi.lobby', SesiQuiz::query()->firstOrFail()));

        $sesi = SesiQuiz::query()->firstOrFail();

        $this->assertSame($host->getKey(), $sesi->host_id);
        $this->assertSame(self::KODE, $sesi->kode);

        // Host yang baru membuka lobby-nya menemukan sesi yang sama, bukan
        // lobby baru yang tidak berisi peserta yang sudah masuk.
        $this->actingAs($host)
            ->post(route('user.sesi.buka', $quiz))
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertSame(1, SesiQuiz::query()->count());
    }

    public function test_kode_yang_salah_ditolak_dengan_pesan_yang_jelas(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'ZZZ999'])
            ->assertSessionHasErrors(['kode' => 'Kode quiz tidak ditemukan.']);

        $this->assertSame(0, PesertaQuiz::query()->count());
    }

    public function test_quiz_mode_kode_tanpa_soal_ditolak_dengan_pesan_yang_jelas(): void
    {
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($this->buatPengguna(), 'Quiz Pecahan', self::KODE);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => self::KODE])
            ->assertSessionHasErrors(['kode' => 'Quiz ini belum punya soal, jadi belum bisa dimulai.']);

        $this->assertSame(0, SesiQuiz::query()->count());
    }

    /**
     * Kode yang sama boleh dipakai lagi untuk ronde berikutnya. Sesi lama
     * yang sudah ditutup tidak dipakai ulang, karena pesertanya yang lama
     * sudah melihat hasilnya.
     */
    public function test_kode_yang_sama_dipakai_ulang_untuk_ronde_berikutnya(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);
        $lama = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_SELESAI, self::KODE);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => self::KODE]);

        $baru = SesiQuiz::query()
            ->whereKeyNot($lama->getKey())
            ->firstOrFail();

        $this->assertSame(SesiQuiz::STATUS_MENUNGGU, $baru->status);
        $this->assertSame($host->getKey(), $baru->host_id);
        $this->assertSame(self::KODE, $baru->kode);
    }

    public function test_peserta_yang_sudah_bergabung_tidak_didaftarkan_dua_kali(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_MENUNGGU, self::KODE);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => self::KODE])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertSame(1, PesertaQuiz::query()->count());
    }

    public function test_host_yang_mengetik_kodenya_diarahkan_ke_lobby_miliknya_sendiri(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host, 'Quiz Pecahan', self::KODE);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_MENUNGGU, self::KODE);

        $this->actingAs($host)
            ->post(route('user.sesi.gabung.store'), ['kode' => self::KODE])
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
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()])
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
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 1]));

        $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
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
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertRedirect(route('user.sesi.hasil', $sesi));

        $this->actingAs($peserta)
            ->get(route('user.sesi.lobby', $sesi))
            ->assertRedirect(route('user.sesi.hasil', $sesi));
    }

    /*
     * =====================================================================
     * NILAI SENDIRI DI HALAMAN HASIL
     *
     * Tiga aturan yang saling terkait:
     *   - orang yang mengerjakan quiznya sendiri sebagai host sesi solo
     *     harus melihat angkanya sendiri, bukan rekap peserta yang kosong;
     *   - host sesi mode kode tidak boleh menjawab, karena tugasnya memandu
     *     dan pesertanya yang masuk lewat kode;
     *   - host sesi solo tetap boleh menjawab, itu justru maksud alur
     *     "publikan" lalu "Mulai Quiz".
     * =====================================================================
     */

    /**
     * Ini bug yang dilaporkan: pengguna mengikuti alur publish -> Mulai Quiz,
     * menjawab sendiri, lalu membuka halaman hasil. Karena ia host sesi solonya
     * sendiri, halaman ini sebelumnya memilih blok rekap dan menampilkan
     * "0 peserta" sementara nilainya sendiri tidak pernah dirender. Nilai
     * 100-nya ada di database tapi tidak terlihat.
     *
     * Sekarang alur itu berakhir di kartu hasil (/user/uiux-design/hasil),
     * jadi angka yang ditunjukkan di sini adalah kartu itu, bukan lagi
     * /user/sesi/{sesi}/hasil. Halaman hasil sesi tetap diuji terpisah di
     * bawah supaya tidak ikut hilang.
     */
    public function test_host_sesi_solo_melihat_nilainya_sendiri_bukan_rekap_kosong(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Seputar Teknologi');
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));
        $sesi = SesiQuiz::query()->firstOrFail();

        $respons = $this->actingAs($pengguna)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        $respons->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        $this->assertSame(100, $this->nilai($pengguna));

        $this->actingAs($pengguna)
            ->get(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]))
            ->assertOk()
            ->assertSee('Nilai Kamu')
            // Sesi solo tidak punya peserta, jadi rekap nilai peserta tidak
            // boleh muncul: memunculkannya hanya mengembalikan pesan
            // "0 peserta" yang membuat pemilik mengira nilainya hilang.
            ->assertDontSee('Rekap nilai peserta')
            ->assertDontSee('Belum ada peserta yang bergabung ke sesi ini.');
    }

    /**
     * Sesi solo yang belum dijawab tetap menampilkan "belum mengerjakan",
     * bukan angka nol yang terlihat seperti nilai akhir.
     */
    public function test_sesi_solo_yang_belum_dijawab_menampilkan_pesan_belum_mengerjakan(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Quiz Belum Dikerjakan');
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $pengguna, SesiQuiz::STATUS_DIMULAI);

        $this->actingAs($pengguna)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSee('Kamu belum mengerjakan soal')
            ->assertDontSee('Rekap nilai peserta');
    }

    /**
     * Host mode kode memandu, bukan ikut menjawab. Kalau dia ikut mengerjakan,
     * dia akan muncul di rekap seolah-olah dia salah satu peserta yang masuk
     * lewat kode.
     */
    public function test_host_mode_kode_tidak_bisa_mengerjakan_soal(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Kode', self::KODE);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI, self::KODE);
        $this->ikut($sesi, $peserta);

        // GET dan POST keduanya ditolak, bukan cuma GET. Kalau hanya GET
        // yang ditutup, host masih bisa mengirim jawaban langsung ke URL.
        $this->actingAs($host)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->actingAs($host)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()])
            ->assertRedirect(route('user.sesi.lobby', $sesi));

        $this->assertNull($this->nilai($host), 'Host tidak boleh punya pengerjaan di sesinya sendiri.');
    }

    /**
     * Tombol "Buka Soal" hanya ada untuk host sesi solo. Host mode kode
     * memandu, jadi diberi tautan itu hanya mengarahkan ke halaman yang akan
     * menolaknya.
     */
    public function test_lobby_host_mode_kode_tidak_menampilkan_tombol_buka_soal(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);

        $kode = $this->buatQuiz($host, 'Quiz Kode', self::KODE);
        $this->buatSoal($kode);
        $sesiKode = $this->buatSesi($kode, $host, SesiQuiz::STATUS_DIMULAI, self::KODE);
        $this->ikut($sesiKode, $peserta);

        $this->actingAs($host)
            ->get(route('user.sesi.lobby', $sesiKode))
            ->assertOk()
            ->assertDontSee('Buka Soal')
            ->assertSee('Akhiri Quiz');

        // Sesi solo berasal dari quiz publik yang dibuka lewat tombol
        // "Mulai Quiz", bukan dari quiz mode kode.
        $solo = $this->buatQuiz($host, 'Quiz Solo');
        $this->buatSoal($solo);
        $sesiSolo = $this->buatSesi($solo, $host, SesiQuiz::STATUS_DIMULAI, 'ABC123');

        $this->actingAs($host)
            ->get(route('user.sesi.lobby', $sesiSolo))
            ->assertOk()
            ->assertSee('Buka Soal');
    }

    /**
     * Host mode kode tetap boleh memulai, lalu melihat peringkat yang masuk
     * lewat kode. Ia tidak dikunci keluar dari sesinya sendiri.
     */
    public function test_host_mode_kode_tetap_melihat_peringkat_peserta(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Kode', self::KODE);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI, self::KODE);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);
        $sesi->tutup();

        $this->actingAs($host)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSee('Rekap nilai peserta')
            ->assertSee('Irma')
            ->assertSee('1 peserta');
    }

    /**
     * Host sesi solo boleh mengerjakan: itu justru maksud alur "publikan"
     * lalu "Mulai Quiz", di mana tidak ada peserta lain.
     */
    public function test_host_sesi_solo_tetap_bisa_mengerjakan_soal(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Quiz Solo');
        $this->buatSoal($quiz);

        // "Mulai Quiz" mengirim ke soal pertama, jadi halaman itulah yang
        // membuktikan host sesi solo boleh mengerjakan.
        $this->actingAs($pengguna)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 1]));

        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertOk()
            ->assertSee('Pertanyaan nomor 1');
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
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()])
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 2]));

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

        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);
        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'B', 'sesi' => $sesi->getKey()]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        $this->assertSame(1, JawabanQuiz::query()->count());
        $this->assertSame(1, $pengerjaan->jumlah_dijawab);
        $this->assertSame(0, $pengerjaan->jumlah_benar);
        $this->assertSame(0, $pengerjaan->nilai);
    }

    /*
     * =====================================================================
     * WAKTU HABIS
     *
     * Quiz berbatas waktu harus menjelaskan ke peserta kenapa soalnya
     * berhenti, bukan memantulkan mereka ke halaman hasil tanpa alasan.
     * Yang diuji di sini bagian yang bisa diuji tanpa peramban: dialognya
     * benar-benar ada, form yang menutup pengerjaan tetap ada, dan
     * keduanya tidak muncul di quiz tanpa batas waktu.
     * =====================================================================
     */

    public function test_quiz_berbatas_waktu_punya_dialog_waktu_habis(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);

        // buatQuiz() memakai durasi 10 menit.
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $isi = $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        // Dialognya ada, dan ada satu jalan keluar saja.
        $this->assertStringContainsString('data-soal-dialog-habis', $isi);
        $this->assertStringContainsString('Waktu Anda Habis', $isi);
        $this->assertStringContainsString('data-soal-dialog-habis-tombol', $isi);
        $this->assertStringContainsString('Lihat Hasil', $isi);

        // Tidak ada tombol batal: membatalkannya sama dengan membiarkan
        // peserta menjawab di luar waktunya.
        $this->assertStringNotContainsString('data-soal-dialog-habis-batal', $isi);

        // Form yang menutup pengerjaan tetap ada, dan tetap menuju aksi
        // "selesai" yang sama dengan tombol manual.
        $this->assertStringContainsString('data-soal-waktu-habis', $isi);
        $this->assertStringContainsString(route('user.sesi.selesai', $sesi->getKey()), $isi);

        // Halaman yang bisa dikunci ditandai, dan dialognya berada di luar
        // elemen itu supaya tombolnya tetap bisa diklik.
        $this->assertStringContainsString('data-soal-halaman', $isi);
    }

    public function test_quiz_tanpa_batas_waktu_tidak_punya_dialog_waktu_habis(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);

        $quiz = $this->buatQuiz($host);
        $quiz->update(['durasi' => 0]);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $isi = $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        // Tanpa batas waktu tidak ada timer, jadi tidak boleh ada dialog
        // yang tidak akan pernah muncul.
        $this->assertStringNotContainsString('data-soal-dialog-habis', $isi);
        $this->assertStringNotContainsString('Waktu Anda Habis', $isi);
        $this->assertStringNotContainsString('data-soal-waktu-habis', $isi);
    }

    /**
     * Sisa waktu dikirim ke halaman sebagai data-sisa, dan dihitung dari
     * kolom dimulai_pada. Kalau dihitung dari saat halaman dibuka, refresh
     * akan mengulang waktu dari awal.
     */
    public function test_sisa_waktu_dihitung_dari_mulai_bukan_dari_halaman_dibuka(): void
    {
        $host = $this->buatPengguna();
        $quiz = $this->buatQuiz($host);
        $quiz->update(['durasi' => 10]);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);

        $this->actingAs($host)->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey());

        // Mundurkan mulai pengerjaan 8 menit dari 10 menit batasnya.
        PengerjaanQuiz::query()->firstOrFail()->forceFill([
            'dimulai_pada' => now()->subMinutes(8),
        ])->save();

        $isi = $this->actingAs($host)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/data-sisa="(1[0-9]\d|2\d\d)"/', $isi);
    }

    /**
     * Ini bug yang dilaporkan: pengguna mengerjakan quiz milik orang lain
     * yang sudah dipublikasikan, waktunya habis, lalu menekan "Mulai Quiz"
     * lagi untuk mengulang.
     *
     * Sesi lamanya masih berstatus started karena pengguna tidak pernah
     * menekan tombol di dialog, jadi sesi itulah yang dipakai ulang. Akibatnya
     * pengguna mendarat di soal pertama dengan sisa waktu 0, dan dialog
     * "Waktu Anda Habis" langsung muncul di halaman yang seharusnya baru.
     *
     * Percobaan baru harus dapat waktu penuh dan tanpa dialog, sedangkan
     * percobaan lama ikut ditutup supaya tidak menggantung di menu Hasil.
     */
    public function test_mulai_quiz_lagi_setelah_waktu_habis_membuka_percobaan_baru_tanpa_dialog(): void
    {
        $pemilik = $this->buatPengguna(['nama' => 'Rangga', 'email' => 'rangga@example.com']);
        $pengguna = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);

        // Quiz milik orang lain: statusnya sudah terbit, jadi yang sedang
        // login hanya peserta biasa, bukan pemilik.
        $quiz = $this->buatQuiz($pemilik, 'Belajar Pemrograman Dasar');
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));
        $sesiLama = SesiQuiz::query()->firstOrFail();

        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesiLama->getKey())
            ->assertOk();

        // Waktunya habis, tapi peserta menutup tab tanpa menekan tombol dialog.
        PengerjaanQuiz::query()->firstOrFail()->forceFill([
            'dimulai_pada' => now()->subMinutes(11),
        ])->save();

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        // Sesi baru, bukan sesi lama yang dipakai ulang.
        $sesiBaru = SesiQuiz::query()->latest('id')->firstOrFail();
        $this->assertNotSame($sesiLama->getKey(), $sesiBaru->getKey());
        $this->assertSame(SesiQuiz::STATUS_SELESAI, $sesiLama->refresh()->status);

        // Percobaan lama ditutup dengan benar, bukan menggantung di menu Hasil.
        $pengerjaanLama = PengerjaanQuiz::query()
            ->where('sesi_id', $sesiLama->getKey())
            ->firstOrFail();
        $this->assertTrue($pengerjaanLama->sudahSelesai());

        $isi = $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesiBaru->getKey())
            ->assertOk()
            ->getContent();

        // Timer penuh: dialog "Waktu Anda Habis" hanya dipicu JavaScript dari
        // angka ini, jadi sisa waktu yang masih besar adalah bukti pop up
        // tidak akan muncul di percobaan baru ini.
        $this->assertMatchesRegularExpression('/data-sisa="(5\d\d|6\d\d)"/', $isi);
        $this->assertStringNotContainsString('data-sisa="0"', $isi);
    }

    /**
     * Percobaan yang masih punya waktu harus diteruskan, bukan dianggap
     * habis. Tanpa ini, menekan "Mulai Quiz" di tengah jalan akan
     * memotong pengerjaan yang sedang berjalan.
     */
    public function test_mulai_quiz_lagi_selama_waktu_masih_ada_melanjutkan_sesi_yang_sama(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna);
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));
        $sesi = SesiQuiz::query()->firstOrFail();

        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk();

        PengerjaanQuiz::query()->firstOrFail()->forceFill([
            'dimulai_pada' => now()->subMinutes(3),
        ])->save();

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        $this->assertSame(1, SesiQuiz::query()->count());
        $this->assertSame(SesiQuiz::STATUS_DIMULAI, $sesi->refresh()->status);
        $this->assertFalse(
            PengerjaanQuiz::query()->firstOrFail()->sudahSelesai()
        );
    }

    /**
     * Quiz tanpa batas waktu tidak punya hitungan waktu, jadi percobaan
     * lamanya tidak boleh ikut ditutup hanya karena "Mulai Quiz" ditekan lagi.
     */
    public function test_mulai_quiz_lagi_tanpa_batas_waktu_melanjutkan_sesi_yang_sama(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna);
        $quiz->update(['durasi' => 0]);
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));
        $sesi = SesiQuiz::query()->firstOrFail();

        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk();

        PengerjaanQuiz::query()->firstOrFail()->forceFill([
            'dimulai_pada' => now()->subDays(3),
        ])->save();

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        $this->assertSame(1, SesiQuiz::query()->count());
        $this->assertSame(SesiQuiz::STATUS_DIMULAI, $sesi->refresh()->status);
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
        $respons = $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);

        $respons->assertRedirect(route('user.uiux.hasil', [
            'pengerjaan' => PengerjaanQuiz::query()->firstOrFail()->getKey(),
        ]));

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

        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);

        $this->actingAs($peserta)
            ->post(route('user.sesi.selesai', $sesi))
            ->assertRedirect(route('user.uiux.hasil', [
                'pengerjaan' => PengerjaanQuiz::query()->firstOrFail()->getKey(),
            ]));

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
            ->from(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => '', 'sesi' => $sesi->getKey()])
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
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

        $respons = $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        $respons->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        $sesi->tutup();

        // URL /user/sesi/{sesi}/hasil milik peserta yang sudah punya nilai
        // sekarang dialingihkan ke kartu hasil, supaya "halaman hasil quiz"
        // hanya punya satu tampilan. Host sesi mode kode tetap di sini
        // (dijalankan test lain).
        $this->actingAs($peserta)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));
    }

    /**
     * Rekap nilai hanya ada di sesi mode kode, karena itu satu-satunya tempat
     * yang perlu melihat nilai semua orang. Sesi solo tidak punya peserta
     * sama sekali, jadi rekap di sana akan selalu kosong.
     */
    public function test_halaman_hasil_host_menampilkan_daftar_nilai_terurut_dari_tertinggi(): void
    {
        $host = $this->buatPengguna();
        $andi = $this->buatPengguna(['nama' => 'Andi', 'email' => 'andi@example.com']);
        $budi = $this->buatPengguna(['nama' => 'Budi', 'email' => 'budi@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Kode', self::KODE);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI, self::KODE);

        $this->ikut($sesi, $andi);
        $this->ikut($sesi, $budi);

        // Andi benar, Budi salah, jadi urutannya harus turun dari 100 ke 0.
        // Kalau keduanya tidak dijawab, urutannya hanya urutan gabung dan
        // test ini jadi tidak membuktikan apa pun soal penilaian.
        $this->actingAs($andi)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);
        $this->actingAs($budi)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'B', 'sesi' => $sesi->getKey()]);

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

    /*
     * =====================================================================
     * MENJADIKAN SESI DARI SESSION (TANPA ID DI URL)
     * =====================================================================
     * URL halaman soal sengaja tidak menyebut id sesi, jadi sesi yang
     * sedang dikerjakan dibaca dari session pengguna. Tiga hal yang harus
     * benar di sini:
     *   - halaman soal tetap bisa dibuka tanpa id sesi di URL, karena itu
     *     yang terjadi setelah menekan "Mulai Quiz";
     *   - tanpa sesi sama sekali, pengguna tidak mendarat di halaman acak
     *     tapi dikirim ke daftar quiz dengan penjelasan;
     *   - id sesi di URL tidak memintas pemeriksaan akses.
     */

    public function test_mulai_quiz_membuka_soal_pertama_tanpa_id_sesi_di_url(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna);
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)
            ->get(route('user.quiz.mulai', $quiz))
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 1]));

        // Tidak ada ?sesi= di URL, jadi sesi hanya bisa ditemukan lewat
        // session yang diisi controller.
        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertOk()
            ->assertSee('Pertanyaan nomor 1');
    }

    /**
     * Top bar dihapus di halaman mengerjakan soal, sama seperti di halaman
     * yang memang butuh fokus penuh. Pencarian global, tombol notifikasi,
     * dan chip akun tidak boleh ikut terender di sini, sementara navigasi
     * mobile harus tetap naik ke top-0 karena tidak ada lagi baris 4rem
     * di atasnya.
     */
    public function test_halaman_mengerjakan_soal_tanpa_top_bar(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna);
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        $respons = $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertOk();

        $respons->assertDontSee('data-app-topbar', false)
            ->assertDontSee('id="cari-topbar"', false)
            ->assertDontSee('Notifikasi', false)
            ->assertSee('sticky top-0', false);
    }

    /**
     * Hanya halaman menjawab soal yang memakai slug judul. Halaman detail,
     * edit, dan hasil tetap memakai angka id, jadi tidak ada tautan lama
     * yang ikut berubah. Halaman detail memakai segmen literal
     * "detail-quiz" supaya "/quiz/tambah" dan "/quiz/gabung" tidak pernah
     * tertelan sebagai id quiz.
     */
    public function test_halaman_bukan_mengerjakan_soal_tetap_memakai_id(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Seputar Teknologi');
        $this->buatSoal($quiz);
        $id = $quiz->getKey();

        $this->actingAs($pengguna)
            ->get("/user/quiz/detail-quiz/{$id}")
            ->assertOk()
            ->assertSee('Seputar Teknologi');

        $this->actingAs($pengguna)
            ->get("/user/quiz/{$id}/edit")
            ->assertOk();

        $this->actingAs($pengguna)
            ->get("/user/hasil/quiz/{$id}")
            ->assertOk();

        // Tidak ikut jadi slug.
        $this->actingAs($pengguna)
            ->get("/user/quiz/detail-quiz/{$quiz->slug}")
            ->assertNotFound();
    }

    /**
     * Segmen literal harus menang atas route berparameter. Tanpa urutan ini
     * "/quiz/tambah" akan ditelan sebagai id quiz dan form tambah quiz tidak
     * akan pernah terbuka; hal yang sama berlaku untuk "/quiz/gabung".
     */
    public function test_segmen_literal_tidak_ditelan_sebagai_id_quiz(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get('/user/quiz/tambah')
            ->assertOk();

        $this->actingAs($pengguna)
            ->get('/user/quiz/gabung')
            ->assertOk();

        $this->actingAs($pengguna)
            ->get('/user/quiz')
            ->assertOk();
    }

    /**
     * Angka dibaca sebagai id, sedangkan huruf dan angka campuran dibaca
     * sebagai slug. Jadi slug yang memuat angka tidak tertukar dengan id.
     */
    public function test_slug_yang_memuat_angka_tidak_tertukar_dengan_id(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pengguna->getKey(),
            'judul' => 'Kelas 2024',
            'slug' => 'kelas-2024',
            'deskripsi' => 'Deskripsi.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $this->actingAs($pengguna)
            ->get(route('user.quiz.detail', $quiz))
            ->assertOk()
            ->assertSee('Kelas 2024');
    }

    /**
     * Halaman menjawab soal memakai slug judul quiz di URL-nya, bukan
     * kalimat tetap, supaya yang terlihat di address bar langsung memberi
     * tahu quiz mana yang sedang dikerjakan.
     */
    public function test_url_halaman_soal_memakai_slug_judul_quiz(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Seputar Teknologi');
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $pengguna, SesiQuiz::STATUS_DIMULAI);

        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk();

        $this->assertStringEndsWith(
            '/user/seputar-teknologi/soal/1?sesi='.$sesi->getKey(),
            route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey(),
        );
    }

    /**
     * Daftar soal jadi dialog, bukan deretan kotak yang menempel di
     * bawah kartu soal. Yang diuji bentuknya: ada tombol pembuka di
     * kepala halaman, ada kotak nomor 1..N, dan kotak yang soalannya
     * sudah tersimpan ditandai "terjawab" supaya warnanya beda dari yang
     * belum. Satu query sudah cukup untuk dua hal itu, jadi tidak ada
     * alasan kotak nomor tidak bisa jadi navigator.
     */
    public function test_dialog_daftar_soal_menandai_soal_yang_sudah_dijawab(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Maya', 'email' => 'maya@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Tiga Soal');
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $this->buatSoal($quiz, 3);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        // Satu soal dijawab dulu supaya ada beda antara yang sudah dan
        // yang belum. Menjawab soal 1 tanpa sesi di URL tetap butuh sesi
        // dari session, jadi dibaca dari tautan soal seperti di aplikasi.
        $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk();

        $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ])
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 2]));

        $isi = $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 2]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-soal-nav-buka', $isi);
        $this->assertStringContainsString('data-soal-nav-dialog', $isi);

        // Dua kartu ringkasan: total soal dan yang sudah dijawab.
        $this->assertStringContainsString('Total soal', $isi);
        $this->assertStringContainsString('Sudah dijawab', $isi);

        // Tiga kotak, satu per soal, dan tiap kotak menunjuk ke tautan
        // soal itu — jadi tetap bisa dipakai tanpa JavaScript. Yang
        // dihitung adalah kelas dasarnya (diakhiri spasi), bukan
        // "soal-nav__tombol" polos, karena dua modifikasi warnanya juga
        // memuat nama kelas itu.
        $this->assertSame(3, substr_count($isi, 'class="soal-nav__tombol '));
        $this->assertStringContainsString(route('user.judulsoal.soal', [$quiz->slug, 3]), $isi);

        // Soal 1 sudah dijawab, soal 2 yang sedang dibuka dan belum
        // dijawab, soal 3 belum dibuka sama sekali. Warna background-nya
        // yang membedakan keduanya: soal 1 memakai --terjawab (ungu
        // pekat), soal 2 hanya --kini (cincin).
        $this->assertSame(1, substr_count($isi, 'soal-nav__tombol--terjawab'));
        $this->assertStringContainsString('aria-label="Soal 1, sudah dijawab"', $isi);
        $this->assertStringContainsString('aria-label="Soal 2, belum dijawab"', $isi);
        $this->assertStringContainsString('soal-nav__tombol--kini', $isi);
        $this->assertStringContainsString('aria-current="true"', $isi);

        // Legenda "Sudah dijawab / Belum dijawab" dihapus: perbedaan
        // warna antar kotak sudah cukup jelas tanpa penjelasan tertulis.
        $this->assertStringNotContainsString('soal-nav__keterangan', $isi);
    }

    /**
     * Tanda "ragu": satu aksi untuk dua arah. Yang diuji bukan cuma
     * toggling-nya, tapi juga tiga hal yang mudah rusak di sekitarnya:
     * tandanya tidak boleh ikut terhitung sebagai jawaban, kotak di
     * dialog harus ikut berubah jadi kuning, dan tombol "Selanjutnya"
     * harus tetap mengirim form jawaban padahal kini ia berada di luar
     * form itu.
     */
    public function test_tanda_ragu_menyalakan_dan_mematikan_kotak_soal(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Maya', 'email' => 'maya@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Dua Soal');
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $tautan = fn (int $nomor) => route('user.judulsoal.soal', [$quiz->slug, $nomor]);

        $this->actingAs($peserta)->get($tautan(1).'?sesi='.$sesi->getKey());

        // Belum ditandai: tombolnya belum tertekan dan kotak soal 1 di
        // dialog masih putih (tidak punya kelas ragu).
        $belum = $this->actingAs($peserta)
            ->get($tautan(1).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-pressed="false"', $belum);
        $this->assertStringNotContainsString('soal-nav__tombol--ragu', $belum);

        // Menandai.
        $this->actingAs($peserta)
            ->post(route('user.judulsoal.ragu', [$quiz->slug, 1]), ['sesi' => $sesi->getKey()])
            ->assertRedirect($tautan(1));

        $this->assertSame(1, SoalRagu::query()->count());

        $sudah = $this->actingAs($peserta)
            ->get($tautan(1).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-pressed="true"', $sudah);
        $this->assertStringContainsString('soal-ragu--ada', $sudah);
        $this->assertStringContainsString('soal-nav__tombol--ragu', $sudah);
        $this->assertStringContainsString('aria-label="Soal 1, ragu, perlu ditinjau lagi"', $sudah);

        /*
         * Menandai ragu bukan menjawab: jumlah jawaban yang tersimpan
         * harus tetap nol, dan nilai pun tidak boleh tersentuh.
         */
        $this->assertSame(0, JawabanQuiz::query()->count());
        $this->assertSame(0, PengerjaanQuiz::query()->firstOrFail()->jumlah_dijawab);

        // Melepas lagi.
        $this->actingAs($peserta)
            ->post(route('user.judulsoal.ragu', [$quiz->slug, 1]), ['sesi' => $sesi->getKey()])
            ->assertRedirect($tautan(1));

        $this->assertSame(0, SoalRagu::query()->count());
    }

    /**
     * Tanda ragu milik satu pengerjaan, bukan milik pengguna + quiz.
     * Kalau tidak begitu, menandai ragu di percobaan pertama akan ikut
     * muncul di percobaan berikutnya untuk quiz yang sama.
     */
    public function test_tanda_ragu_tidak_bocor_ke_percobaan_lain(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Quiz Dua Soal');
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);

        // Percobaan pertama.
        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));
        $sesiPertama = SesiQuiz::query()->firstOrFail();

        $this->actingAs($pengguna)
            ->post(route('user.judulsoal.ragu', [$quiz->slug, 1]), ['sesi' => $sesiPertama->getKey()])
            ->assertRedirect();

        $this->assertSame(1, SoalRagu::query()->count());

        // Percobaan kedua, sesi baru untuk quiz yang sama.
        SesiQuiz::query()->whereKey($sesiPertama->getKey())->delete();
        PengerjaanQuiz::query()->delete();
        SoalRagu::query()->delete();
        JawabanQuiz::query()->delete();

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        $isi = $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('soal-nav__tombol--ragu', $isi);
        $this->assertStringContainsString('aria-pressed="false"', $isi);
    }

    /**
     * Tombol "Ragu" tidak boleh menjadi jalan submits isian jawaban.
     * Kalau ia ikut mengirim form jawaban, satu klik untuk menandai
     * soal akan diam-diam ikut menyimpan jawaban — termasuk jawaban
     * kosong yang harus ditolak.
     */
    public function test_tombol_ragu_tidak_mengirim_jawaban(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Quiz Dua Soal');
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));
        $sesi = SesiQuiz::query()->firstOrFail();

        // Isian sengaja dikosongkan: kalau tombolnya ikut mengirim form
        // jawaban, permintaan ini akan gagal validasi.
        $this->actingAs($pengguna)
            ->post(route('user.judulsoal.ragu', [$quiz->slug, 1]), ['sesi' => $sesi->getKey()])
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 1]));

        $this->assertSame(0, JawabanQuiz::query()->count());
    }

    /**
     * Pemeriksaan isian di resources/js/quiz-kerjakan.js mencari tombol
     * "Selanjutnya" di seluruh dokumen, karena di markup ia berada di
     * luar form jawaban. Yang diuji di sini hanya bentuk markupnya:
     * atribut form-nya harus menunjuk ke form yang benar, kalau tidak
     * tombolnya tidak akan mengirim apa pun.
     */
    public function test_tombol_selanjutnya_mengirim_form_jawaban_laluar_form(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Quiz Dua Soal');
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);

        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        $isi = $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="soal-jawab"', $isi);
        $this->assertStringContainsString('form="soal-jawab"', $isi);

        // Teks tombolnya "Selanjutnya", bukan lagi "Simpan & Lanjut".
        $this->assertStringContainsString('Selanjutnya', $isi);
        $this->assertStringNotContainsString('Simpan & Lanjut', $isi);
    }

    /**
     * Teks "Soal N dari M" dihapus dari kepala halaman karena angka yang
     * sama sudah ada di dalam dialog daftar soal. Yang boleh hilang cuma
     * teksnya: posisi soal tetap harus terbaca, jadi teksnya disimpan
     * sebagai sr-only di dalam progress bar dan atribut aria-valuenow
     * progress bar tetap terisi.
     */
    public function test_kepala_halaman_tidak_lagi_menulis_posisi_soal(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Maya', 'email' => 'maya@example.com']);
        $quiz = $this->buatQuiz($host, 'Quiz Dua Soal');
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $isi = $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 2]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        // Kelas .soal-kepala__posisi dipakai untuk teks yang sudah dihapus,
        // jadi tidak boleh ada di markup lagi.
        $this->assertStringNotContainsString('soal-kepala__posisi', $isi);

        // Tapi posisi soal tetap terbaca: lewat sr-only di dalam progress
        // bar dan lewat atribut aria-valuenow.
        $this->assertStringContainsString('aria-valuenow="2"', $isi);
        $this->assertStringContainsString('sr-only">Soal 2 dari 2</span>', $isi);
    }

    public function test_halaman_soal_tanpa_sesi_dikirim_ke_daftar_quiz(): void
    {
        $host = $this->buatPengguna();
        $pengguna = $this->buatPengguna(['nama' => 'Rafi', 'email' => 'rafi@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);

        $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertRedirect(route('user.quiz'))
            ->assertSessionHasErrors('sesi');

        $this->actingAs($pengguna)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A'])
            ->assertRedirect(route('user.quiz'))
            ->assertSessionHasErrors('sesi');
    }

    /**
     * URL menjawab soal menyebut quiz-nya sendiri, tapi id sesinya tetap
     * dibaca dari session. Kalau keduanya tidak cocok, sesi quiz lain tidak
     * boleh dipakai untuk membuka soal quiz ini.
     */
    public function test_url_quiz_yang_beda_dari_sesi_tidak_membuka_soal(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Lala', 'email' => 'lala@example.com']);
        $quizA = $this->buatQuiz($host, 'Quiz A');
        $quizB = $this->buatQuiz($host, 'Quiz B');
        $this->buatSoal($quizB);
        $sesi = $this->buatSesi($quizB, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        // Sesi benar-benar milik quiz B, tapi URL menyebut quiz A.
        $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quizA->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertRedirect(route('user.quiz'))
            ->assertSessionHasErrors('sesi');

        $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quizA->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ])
            ->assertRedirect(route('user.quiz'))
            ->assertSessionHasErrors('sesi');

        $this->assertDatabaseCount('tb_jawaban_quiz', 0);
    }

    public function test_id_sesi_di_url_tidak_membuka_akses_ke_sesi_orang_lain(): void
    {
        $host = $this->buatPengguna();
        $penyusup = $this->buatPengguna(['nama' => 'Keyla', 'email' => 'keyla@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);

        $this->actingAs($penyusup)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertForbidden();
    }

    public function test_soal_yang_sudah_dijawab_terlihat_terpilih_saat_dibuka_lagi(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz, 1);
        $this->buatSoal($quiz, 2);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban' => 'B',
            'sesi' => $sesi->getKey(),
        ]);

        // Kembali ke soal pertama lewat tombol "Sebelumnya": jawaban B
        // harus masih terpilih, bukan kosong.
        $html = $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="B"[^>]*\schecked/s', $html);
    }

    /*
     * =====================================================================
     * LIMA TIPE SOAL
     * =====================================================================
     * Quiz Builder bisa membuat lima tipe. Perbedaannya bukan cuma tampilan:
     * isian yang dikirim, cara menyimpan jawaban, dan cara menilainya
     * semuanya berbeda, jadi masing-masing diuji lewat jalur HTTP yang
     * dipakai peserta sungguhan.
     */

    public function test_soal_pilihan_banyak_benar_hanya_diterima_tepat_berikutnya(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe(
            $quiz,
            Soal::TIPE_PILIHAN_BANYAK,
            ['A', 'C'],
            ['A' => 'HTML', 'B' => 'CSS', 'C' => 'JavaScript'],
        );
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        // Tepat sama dengan kuncinya: benar.
        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban' => ['C', 'A'],
            'sesi' => $sesi->getKey(),
        ]);

        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'jawaban_dipilih' => 'AC',
            'benar' => true,
        ]);

        // Kurang satu huruf: sudah salah, bukan "benar sebagian".
        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban' => ['A'],
            'sesi' => $sesi->getKey(),
        ]);

        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'jawaban_dipilih' => 'A',
            'benar' => false,
        ]);
    }

    public function test_soal_pilihan_banyak_menolak_kosong(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe(
            $quiz,
            Soal::TIPE_PILIHAN_BANYAK,
            ['A'],
            ['A' => 'HTML', 'B' => 'CSS'],
        );
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->from(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => [], 'sesi' => $sesi->getKey()])
            ->assertSessionHasErrors('jawaban');

        $this->assertSame(0, JawabanQuiz::query()->count());
    }

    public function test_soal_dropdown_dinilai_seperti_pilihan_ganda(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe(
            $quiz,
            Soal::TIPE_DROPDOWN,
            ['B'],
            ['A' => 'Kecil', 'B' => 'Sedang', 'C' => 'Besar'],
        );
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban' => 'B',
            'sesi' => $sesi->getKey(),
        ]);

        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'jawaban_dipilih' => 'B',
            'benar' => true,
        ]);
    }

    public function test_jawaban_singkat_dibandingkan_dengan_kunci_teks(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe(
            $quiz,
            Soal::TIPE_JAWABAN_SINGKAT,
            kunciTeks: 'Cascading Style Sheets',
            tococokPersis: false,
        );
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        // Mode longgar: huruf besar, spasi berlebih, dan tanda baca
        // diabaikan, tapi isinya tetap sama.
        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban_teks' => '  cascading   style sheets. ',
            'sesi' => $sesi->getKey(),
        ]);

        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'jawaban_teks' => 'cascading   style sheets.',
            'benar' => true,
        ]);
    }

    public function test_jawaban_singkat_memakai_huruf_yang_salah_ditolak(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe(
            $quiz,
            Soal::TIPE_JAWABAN_SINGKAT,
            kunciTeks: 'HTML',
            tococokPersis: true,
        );
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban_teks' => 'html',
            'sesi' => $sesi->getKey(),
        ]);

        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'jawaban_teks' => 'html',
            'benar' => false,
        ]);
    }

    public function test_soal_teks_menolak_kosong(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe($quiz, Soal::TIPE_PARAGRAF, kunciTeks: 'Jawaban acuan.');
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->from(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban_teks' => '   ', 'sesi' => $sesi->getKey()])
            ->assertSessionHasErrors('jawaban_teks');

        $this->assertSame(0, JawabanQuiz::query()->count());
    }

    public function test_jawaban_paragraf_disimpan_tanpa_dinilai_otomatis(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoal($quiz, 1);
        $this->buatSoalTipe($quiz, Soal::TIPE_PARAGRAF, kunciTeks: 'Acuan.', urutan: 2);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
            'jawaban' => 'A',
            'sesi' => $sesi->getKey(),
        ]);
        $this->actingAs($peserta)->post(route('user.judulsoal.jawab', [$quiz->slug, 2]), [
            'jawaban_teks' => 'Esai peserta yang panjangnya beberapa kalimat.',
            'sesi' => $sesi->getKey(),
        ]);

        // Teksnya tersimpan utuh supaya guru bisa menilai nanti.
        $this->assertDatabaseHas('tb_jawaban_quiz', [
            'jawaban_teks' => 'Esai peserta yang panjangnya beberapa kalimat.',
        ]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        // Dua soal sudah dijawab, tapi yang paragraf belum dinilai: tidak
        // boleh dihitung salah, dan harus terlihat di penghitung khusus.
        $this->assertSame(2, $pengerjaan->jumlah_dijawab);
        $this->assertSame(1, $pengerjaan->jumlah_benar);
        $this->assertSame(0, $pengerjaan->jumlah_salah);
        $this->assertSame(1, $pengerjaan->jumlahMenungguNilai());
        $this->assertSame(50, $pengerjaan->nilai);
    }

    public function test_soal_tanpa_pilihan_menampilkan_petunjuk_ikut_tipenya(): void
    {
        $host = $this->buatPengguna();
        $peserta = $this->buatPengguna(['nama' => 'Irma', 'email' => 'irma@example.com']);
        $quiz = $this->buatQuiz($host);
        $this->buatSoalTipe(
            $quiz,
            Soal::TIPE_PILIHAN_BANYAK,
            ['A', 'B'],
            ['A' => 'HTML', 'B' => 'CSS'],
        );
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]).'?sesi='.$sesi->getKey())
            ->assertOk()
            ->assertSee('Pilih satu atau lebih jawaban yang benar.')
            ->assertSee('name="jawaban[]"', false);
    }
}
