<?php

namespace Tests\Feature;

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
 * Halaman "Hasil Quiz" kartu besar: /user/uiux-design/hasil.
 *
 * Yang diuji adalah isi halaman: nilai, rincian jawaban, waktu pengerjaan,
 * dan detail quiz harus berasal dari tb_pengerjaan_quiz dan tb_quiz, bukan
 * dari angka yang ditulis di view. Karena itu setiap angka di assertion
 * dibuat dari data yang benar-benar disimpan, dan ada test khusus yang
 * memastikan halaman tidak menampilkan nilai milik orang lain.
 */
class UiuxHasilTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $nama = 'Natania', ?string $email = null): User
    {
        return User::create([
            'nama' => $nama,
            'email' => $email ?? str($nama)->slug()->value().'@example.com',
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

    private ?Pelajaran $pelajaran = null;

    private ?User $pembuatAwak = null;

    private function buatPelajaran(): Pelajaran
    {
        return $this->pelajaran ??= Pelajaran::create([
            'nama' => 'Matematika',
            'slug' => 'matematika',
            'deskripsi' => 'Deskripsi Matematika',
            'ikon' => '123',
            'aktif' => true,
        ]);
    }

    private function buatQuiz(array $atribut = []): Quiz
    {
        $pembuat = $this->pembuatAwak ??= $this->buatPengguna('Guru KelasKita', 'guru@example.com');
        $judul = $atribut['judul'] ?? 'Matematika - Bab 1';

        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 30,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
            ...$atribut,
        ]);
    }

    private function buatSoal(Quiz $quiz, int $urutan = 1, string $kunci = 'A'): Soal
    {
        return Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => "Pertanyaan nomor $urutan",
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'jawaban_benar' => $kunci,
            'pembahasan' => 'Pembahasan singkat.',
            'urutan' => $urutan,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);
    }

    private function buatPengerjaan(
        User $pengguna,
        Quiz $quiz,
        int $nilai = 80,
        int $benar = 16,
        int $salah = 4,
        int $soal = 20,
        int $menit = 12,
        bool $selesai = true,
    ): PengerjaanQuiz {
        $mulai = now()->subMinutes($menit + 5);

        return PengerjaanQuiz::create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => $soal,
            'jumlah_dijawab' => $benar + $salah,
            'jumlah_benar' => $benar,
            'jumlah_salah' => $salah,
            'nilai' => $nilai,
            'dimulai_pada' => $mulai,
            'selesai_pada' => $selesai ? $mulai->copy()->addMinutes($menit) : null,
        ])->refresh();
    }

    public function test_halaman_menampilkan_nilai_dan_rincian_dari_database(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz(['tingkat_kesulitan' => Quiz::TINGKAT_SEDANG]);
        $this->buatPengerjaan($user, $quiz, nilai: 80, benar: 16, salah: 4, soal: 20, menit: 12);

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Quiz Selesai!')
            ->assertSee('Nilai Kamu')
            ->assertSee('80')
            // Skala 0-100 ditulis di sebelah angka besarnya.
            ->assertSee('/100')
            ->assertSee('Jawaban Benar')
            ->assertSee('Jawaban Salah')
            ->assertSee('Waktu Pengerjaan')
            ->assertSee('Total Soal')
            ->assertSee('16')
            ->assertSee('4')
            ->assertSee('12 menit')
            ->assertSee('20 soal');
    }

    public function test_kartu_statistik_menulis_keterangan_dari_data(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz();
        $this->buatPengerjaan($user, $quiz, nilai: 100, benar: 10, salah: 0, soal: 10, menit: 5);

        $respons = $this->actingAs($user)->get('/user/uiux-design/hasil')->assertOk();

        $respons->assertSee('dari 10 soal');
        // Quiz punya durasi 30 menit, jadi batas waktu boleh ditulis.
        $respons->assertSee('maksimal 30 menit');
    }

    public function test_detail_quiz_menampilkan_data_asli(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz([
            'judul' => 'Matematika - Bab 1',
            'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
        ]);
        $this->buatPengerjaan($user, $quiz);

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Detail Quiz')
            ->assertSee('Matematika - Bab 1')
            ->assertSee('Matematika')
            ->assertSee('Tanggal Pengerjaan')
            ->assertSee('Sedang')
            ->assertSee('Jumlah Soal')
            ->assertSee('20 soal');
    }

    /**
     * Penjelasan rumus nilai harus ikut tampil, dan contohnya ditulis dari
     * angka yang benar-benar ada di halaman supaya tidak pernah bertentangan
     * dengan nilai besar di atasnya.
     */
    public function test_penjelasan_perhitungan_nilai_mengikuti_data(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz();
        $this->buatPengerjaan($user, $quiz, nilai: 80, benar: 16, salah: 4, soal: 20);

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Nilai kamu dihitung berdasarkan jumlah jawaban benar dari total soal, dengan skala 0 - 100.')
            ->assertSee('16 jawaban benar dari 20 soal = nilai 80.');
    }

    public function test_tombol_kembali_dan_tombol_detail_mengarah_ke_rute_yang_benar(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz();
        $pengerjaan = $this->buatPengerjaan($user, $quiz);

        $respons = $this->actingAs($user)->get('/user/uiux-design/hasil')->assertOk();

        $respons->assertSee('Kembali ke Daftar Quiz')
            ->assertSee(route('user.quiz'))
            ->assertSee('Kembali ke Beranda')
            ->assertSee(route('user.dashboard'))
            ->assertSee('Lihat Hasil Detail')
            // Rute detail sudah ada sebelumnya, jadi tidak ada sistem baru.
            ->assertSee(route('user.hasil.detail', $pengerjaan->getKey()), false);
    }

    public function test_halaman_membuka_pengerjaan_yang_dipilih_lewat_query_string(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz();

        $lama = $this->buatPengerjaan($user, $quiz, nilai: 40, benar: 4, salah: 6, soal: 10, menit: 9);
        $lama->forceFill(['created_at' => now()->subWeek()])->save();

        $baru = $this->buatPengerjaan($user, $quiz, nilai: 90, benar: 9, salah: 1, soal: 10, menit: 4);
        $baru->forceFill(['created_at' => now()])->save();

        // Tanpa parameter yang dibuka adalah pengerjaan terbaru.
        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('9 jawaban benar dari 10 soal = nilai 90.');

        // Dengan parameter, yang dibuka adalah pengerjaan itu juga.
        $this->actingAs($user)
            ->get('/user/uiux-design/hasil?pengerjaan='.$lama->getKey())
            ->assertOk()
            ->assertSee('4 jawaban benar dari 10 soal = nilai 40.');
    }

    /**
     * Pengerjaan milik orang lain tidak boleh tampil, bahkan kalau id-nya
     * diketik manual di query string. Bukan disembunyikan di view: query-nya
     * sendiri sudah dibatasi ke pengguna yang sedang login.
     */
    public function test_halaman_tidak_membuka_pengerjaan_milik_orang_lain(): void
    {
        $saya = $this->buatPengguna('Natania', 'saya@example.com');
        $orangLain = $this->buatPengguna('Amara', 'amara@example.com');

        $milikSaya = $this->buatPengerjaan($saya, $this->buatQuiz(['judul' => 'Quiz Milik Saya']));
        $milikOrangLain = $this->buatPengerjaan($orangLain, $this->buatQuiz(['judul' => 'Quiz Milik Orang Lain']));

        $this->actingAs($saya)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Quiz Milik Saya')
            ->assertDontSee('Quiz Milik Orang Lain');

        $this->actingAs($saya)
            ->get('/user/uiux-design/hasil?pengerjaan='.$milikOrangLain->getKey())
            ->assertOk()
            ->assertDontSee('Quiz Milik Orang Lain');

        $this->assertNotSame($milikSaya->getKey(), $milikOrangLain->getKey());
    }

    public function test_tanpa_pengerjaan_menampilkan_kartu_kosong(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Data hasil quiz tidak ditemukan')
            ->assertSee('Silakan kembali ke daftar quiz dan coba lagi.')
            ->assertSee('Kembali ke Daftar Quiz')
            ->assertSee(route('user.quiz'))
            // Tidak ada angka yang dikarang saat data-nya memang tidak ada.
            ->assertDontSee('Quiz Selesai!');
    }

    /**
     * Skor memakai status yang sudah ada di PengerjaanQuiz (ambang lulus 70),
     * jadi halaman ini tidak menentukan predikat baru.
     */
    public function test_lencana_status_mengikuti_ambang_lulus_yang_sudah_ada(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz();

        $this->buatPengerjaan($user, $quiz, nilai: 80, selesai: true);

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Ambang lulus: '.PengerjaanQuiz::NILAI_LULUS);

        $gagal = $this->buatPengerjaan($user, $quiz, nilai: 40, selesai: true);
        $gagal->forceFill(['created_at' => now()])->save();

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Gagal')
            ->assertSee('belum sampai ambang lulus');
    }

    /**
     * Pengerjaan yang belum ditutup punya status "Dalam Proses", dan nilainya
     * masih bisa bergerak. Halaman tidak boleh menyebutnya selesai.
     */
    public function test_pengerjaan_yang_masih_dikerjakan_ditandai_sebagai_proses(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz();
        $this->buatPengerjaan($user, $quiz, nilai: 0, benar: 0, salah: 0, soal: 10, selesai: false);

        $this->actingAs($user)
            ->get('/user/uiux-design/hasil')
            ->assertOk()
            ->assertSee('Dalam Proses')
            ->assertSee('Quiz Belum Selesai')
            ->assertSee('masih bisa berubah')
            ->assertDontSee('Quiz Selesai!')
            ->assertDontSee('Quiz Selesai!', false);
    }

    public function test_menu_quiz_dijalankan_di_halaman_ini(): void
    {
        $user = $this->buatPengguna();
        $this->buatPengerjaan($user, $this->buatQuiz());

        $isi = $this->actingAs($user)->get('/user/uiux-design/hasil')->assertOk()->getContent();

        // Menu Quiz di sidebar memakai kelas "bg-primary text-white" ketika
        // aktif, jadi satu kelas itu saja yang dicari.
        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('user.quiz'), '/').'"\s+class="[^"]*bg-primary text-white[^"]*"/',
            $isi,
            'Menu Quiz di sidebar harus aktif di halaman hasil ini.'
        );
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/user/uiux-design/hasil')->assertRedirect('/login');
    }

    /*
     * =====================================================================
     * ALUR SEBENARNYA
     *
     * Test di atas memakai "?pengerjaan=" atau tanpa parameter sama sekali
     * untuk menguji isi kartu. Yang diuji di sini adalah hal yang paling
     * mudah luput: halaman ini harus benar-benar yang dibuka setelah siswa
     * selesai menjawab, bukan cuma halaman yang bisa dibuka manual.
     * halaman ini harus benar-benar yang dibuka setelah siswa selesai
     * menjawab, bukan cuma halaman yang bisa dibuka manual.
     * =====================================================================
     */

    public function test_selesai_mengerjakan_quiz_publik_diarahkan_ke_kartu_ini(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz(['judul' => 'UI/UX Design']);
        $this->buatSoal($quiz);

        // Alur publik: Mulai Quiz -> jawab soal -> selesai.
        $this->actingAs($user)->get(route('user.quiz.mulai', $quiz))->assertRedirect();

        $sesi = SesiQuiz::query()->firstOrFail();

        $respons = $this->actingAs($user)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        // Bukan /user/sesi/{sesi}/hasil lagi.
        $respons->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        // Dan halaman yang benar-benar terbuka itu memang kartu hasil.
        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ])
            ->assertOk()
            ->assertSee('Quiz Selesai!')
            ->assertSee('Nilai Kamu')
            ->assertSee('Detail Quiz')
            ->assertSee('UI/UX Design');
    }

    /**
     * Host sesi mode KODE tugasnya memandu, bukan ikut menjawab, dan satu-
     * satunya halaman yang punya rekap nilai semua peserta adalah
     * /user/sesi/{sesi}/hasil. Jadi hostnya tidak boleh ikut dialihkan.
     */
    public function test_host_sesi_mode_kode_tetap_membuka_halaman_hasil_sesi(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $quiz = $this->buatQuiz(['judul' => 'Quiz Kode', 'visibilitas' => Quiz::VISIBILITAS_PRIVAT]);

        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $host->getKey(),
            'kode' => $quiz->kode_akses ?: 'KODE01',
            'status' => SesiQuiz::STATUS_MENUNGGU,
        ]);

        // Host menutup sesi dari lobby.
        $this->actingAs($host)
            ->post(route('user.sesi.akhiri', $sesi))
            ->assertRedirect(route('user.sesi.hasil', $sesi));
    }

    /**
     * Peserta sesi mode KODE punya nilai sendiri, jadi dia ikut dapat kartu
     * hasil. Yang tetap di halaman hasil sesi cuma host-nya, karena rekap
     * peserta tidak ada di kartu.
     */
    public function test_url_lama_halaman_hasil_sesi_juga_membuka_kartu_ini(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuiz([
            'judul' => 'Quiz Kode',
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
        ]);
        $this->buatSoal($quiz);

        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $host->getKey(),
            'kode' => 'KODE01',
            'status' => SesiQuiz::STATUS_DIMULAI,
        ]);

        PesertaQuiz::create([
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $peserta->getKey(),
            'status' => PesertaQuiz::STATUS_MENGERJAKAN,
        ]);

        $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        // Tautan lama / bookmark lama ikut membuka tampilan yang sama.
        $this->actingAs($peserta)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        // Dan hostnya tetap di halaman rekap.
        $this->actingAs($host)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSee('Rekap nilai peserta');
    }
}
