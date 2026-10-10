<?php

namespace Tests\Feature;

use App\Models\JawabanQuiz;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Hasil: statistik, riwayat, filter status, pencarian,
 * pengurutan, dan kepemilikan data.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di
 * sini tidak menyentuh database sungguhan.
 *
 * Angka statistik diuji lewat teks yang benar-benar dirender halaman,
 * bukan lewat nilai balasan API, supaya yang dicek benar-benar yang
 * dilihat pengguna.
 */
class HasilHalamanTest extends TestCase
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
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'ikon' => '</>',
            'aktif' => true,
        ]);
    }

    /**
     * Quiz selalu butuh pembuat karena kolom dibuat_oleh tidak nullable.
     * Kalau pemanggil tidak menyebutkannya, dipakai satu akun awak yang
     * sama supaya tidak ada user tambahan di tiap pemanggilan.
     */
    private function buatQuiz(string $judul, ?User $pembuat = null): Quiz
    {
        $pembuat ??= $this->pembuatAwak ??= $this->buatPengguna('Guru KelasKita', 'guru@example.com');

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

    /**
     * Buat satu pengerjaan yang sudah selesai.
     *
     * Nilai dan waktunya bisa diatur supaya statistik bisa diuji dengan
     * angka yang pasti, bukan hasil happenstance.
     */
    private function buatPengerjaan(
        User $pengguna,
        Quiz $quiz,
        int $nilai = 80,
        int $benar = 4,
        int $salah = 1,
        int $soal = 5,
        int $menit = 15,
        bool $selesai = true,
    ): PengerjaanQuiz {
        $mulai = now()->subMinutes($menit + 5);

        $pengerjaan = PengerjaanQuiz::create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => $soal,
            'jumlah_dijawab' => $benar + $salah,
            'jumlah_benar' => $benar,
            'jumlah_salah' => $salah,
            'nilai' => $nilai,
            'dimulai_pada' => $mulai,
            'selesai_pada' => $selesai ? $mulai->copy()->addMinutes($menit) : null,
        ]);

        // Satu baris jawaban supaya hitungUlang() punya bahan, sama seperti
        // alur sesi quiz yang sebenarnya.
        $soalPertama = Soal::query()->where('quiz_id', $quiz->getKey())->orderBy('urutan')->first();

        if ($soalPertama !== null) {
            JawabanQuiz::create([
                'pengerjaan_quiz_id' => $pengerjaan->getKey(),
                'soal_id' => $soalPertama->getKey(),
                'jawaban_dipilih' => $benar > 0 ? $soalPertama->jawaban_benar : 'B',
                'jawaban_benar' => $soalPertama->jawaban_benar,
                'benar' => $benar > 0,
                'dijawab_pada' => $mulai,
            ]);
        }

        return $pengerjaan->refresh();
    }

    public function test_halaman_hasil_menampilkan_statistik_dari_database(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');

        $this->buatPengerjaan($user, $quiz, nilai: 100, benar: 5, salah: 0, soal: 5, menit: 20);
        $this->buatPengerjaan($user, $quiz, nilai: 60, benar: 3, salah: 2, soal: 5, menit: 10);

        $respons = $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk();

        // Total quiz selesai: kedua pengerjaan di atas sudah ditutup.
        $respons->assertSee('Total Quiz Dikerjakan');
        $respons->assertSee('Rata-rata Nilai');
        $respons->assertSee('Nilai Tertinggi');
        $respons->assertSee('Waktu Belajar');

        // Rata-rata (100 + 60) / 2 = 80
        $respons->assertSee('80');
        $respons->assertSee('100');

        // Durasi tiap pengerjaan: 20 menit dan 10 menit.
        $respons->assertSee('20 menit');
        $respons->assertSee('10 menit');

        $respons->assertSee('Statistik Belajar');
        $respons->assertSee('Quiz Terpopuler');
    }

    /**
     * Alur lengkap: dari menekan "Mulai Quiz" sampai catatannya masuk ke
     * menu Hasil. Test lain di file ini membuat tb_pengerjaan_quiz langsung,
     * jadi tidak membuktikan apa pun kalau pengerjaan yang dihasilkan
     * SesiKerjakanController ternyata tidak ikut terbaca di sini.
     */
    public function test_pengerjaan_dari_alur_quiz_benar_benar_masuk_ke_menu_hasil(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('Alur Lengkap');
        $this->buatSoal($quiz);
        $this->buatSoal($quiz, 2);

        // Mulai Quiz -> soal pertama -> soal kedua (otomatis selesai).
        $this->actingAs($user)->get(route('user.quiz.mulai', $quiz));

        $sesi = SesiQuiz::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()])
            ->assertRedirect(route('user.judulsoal.soal', [$quiz->slug, 2]));

        // Baris pengerjaan dibuat begitu soal pertama dibuka.
        $pengerjaan = PengerjaanQuiz::query()->sole();

        // Menjawab soal terakhir mengarahkan ke kartu hasil, bukan ke
        // /user/sesi/{sesi}/hasil, dan id pengerjaannya ikut supaya kartu itu
        // menampilkan nilai sesi yang baru saja ditutup.
        $this->actingAs($user)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 2]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()])
            ->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        // Di-refresh supaya yang dibaca adalah baris setelah ditutup, bukan
        // snapshot yang diambil sebelum soal kedua dijawab.
        $pengerjaan->refresh();

        $this->assertSame($user->getKey(), $pengerjaan->pengguna_id);
        $this->assertNotNull($pengerjaan->selesai_pada, 'Pengerjaan harus ditutup, kalau tidak tidak masuk tab Selesai.');
        $this->assertSame(100, (int) $pengerjaan->nilai);

        $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('Alur Lengkap')
            ->assertSee('1')
            ->assertSee(route('user.hasil.detail', $pengerjaan->getKey()), false)
            // Tab default adalah "semua", tapi tab Selesai harus ikut
            // menghitungnya karena pengerjaannya sudah ditutup.
            ->assertSee('Selesai');
    }

    /**
     * Pengerjaan yang belum ditutup (user masih di tengah mengerjakan)
     * tetap harus muncul di menu Hasil, di tab "Proses". Kalau ini hilang,
     * pengguna yang menutup tab di tengah akan kehilangan riwayatnya.
     */
    public function test_pengerjaan_yang_masih_proses_masuk_ke_menu_hasil(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('Masih Dikerjakan');
        $this->buatSoal($quiz);
        $this->buatSoal($quiz, 2);

        $this->actingAs($user)->get(route('user.quiz.mulai', $quiz));
        $sesi = SesiQuiz::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), ['jawaban' => 'A', 'sesi' => $sesi->getKey()]);

        $pengerjaan = PengerjaanQuiz::query()->sole();

        $this->assertNull($pengerjaan->selesai_pada);

        $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('Masih Dikerjakan')
            ->assertSee(route('user.hasil.detail', $pengerjaan->getKey()), false);
    }

    /**
     * Thumbnail quiz yang diunggah disimpan sebagai path relatif di disk
     * publik, termasuk nama foldernya. Memotongnya dengan basename()
     * membuat URL menunjuk berkas yang tidak ada, jadi gambarnya rusak
     * persis di halaman yang baru kita pastikan datanya masuk.
     */
    public function test_thumbnail_quiz_pakai_path_lengkap_bukan_basename(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('Quiz Bergambar');
        $quiz->update(['thumbnail' => 'thumbnails-quiz/contoh.jpg']);
        $this->buatPengerjaan($user, $quiz, nilai: 90, benar: 5, salah: 0, soal: 5, menit: 10);

        $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('thumbnails-quiz/contoh.jpg', false)
            ->assertDontSee('storage/contoh.jpg', false);
    }

    public function test_berita_menampilkan_kategori_soal_dan_tombol_detail(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');
        $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 100, benar: 10, salah: 0, soal: 10, menit: 15);

        $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('Riwayat Hasil Quiz')
            ->assertSee('HTML Dasar')
            ->assertSee('Pemrograman')
            ->assertSee('10 soal')
            ->assertSee('15 menit')
            ->assertSee('Lihat Detail')
            ->assertSee(route('user.hasil.detail', $pengerjaan->getKey()), false);
    }

    public function test_angka_statistik_mengikuti_data(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');
        $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 100, benar: 10, salah: 0, soal: 10, menit: 15);

        $respons = $this->actingAs($user)->get('/user/hasil')->assertOk();

        // Kartu statistik memakai angka dari baris riwayat yang sama:
        // satu quiz selesai, nilai tertinggi 100, durasi 15 menit.
        $respons->assertSee('Total Quiz Dikerjakan');
        $respons->assertSee('15 menit');
        $respons->assertSee('100');
        $respons->assertSee(route('user.hasil.detail', $pengerjaan->getKey()), false);

        // Pembanding minggu lalu tidak dikarang kalau memang belum ada.
        $respons->assertSee('Belum ada pembanding minggu lalu');
        $respons->assertDontSee('Naik');
        $respons->assertDontSee('Turun');
    }

    public function test_tab_status_memisahkan_selesai_proses_dan_gagal(): void
    {
        $user = $this->buatPengguna();
        $quizLulus = $this->buatQuiz('Quiz Lulus');
        $quizGagal = $this->buatQuiz('Quiz Gagal');
        $quizProses = $this->buatQuiz('Quiz Proses');

        // Di atas ambang lulus 70.
        $lulus = $this->buatPengerjaan($user, $quizLulus, nilai: 90, selesai: true);
        // Di bawah ambang lulus 70.
        $gagal = $this->buatPengerjaan($user, $quizGagal, nilai: 40, selesai: true);
        // Belum ditutup.
        $proses = $this->buatPengerjaan($user, $quizProses, nilai: 0, selesai: false);

        $semua = $this->actingAs($user)->get('/user/hasil')->assertOk();
        $semua->assertSee(route('user.hasil.detail', $lulus->getKey()), false);
        $semua->assertSee(route('user.hasil.detail', $gagal->getKey()), false);
        $semua->assertSee(route('user.hasil.detail', $proses->getKey()), false);

        // Pengecekan dilakukan lewat tautan "Lihat Detail", karena itu
        // yang hanya ada di daftar riwayat. Kartu "Quiz Terpopuler" di
        // kolom kanan sengaja tidak ikut filter, dia Always menampilkan
        // quiz yang paling sering dikerjakan.
        $this->actingAs($user)->get('/user/hasil?status=selesai')
            ->assertOk()
            ->assertSee(route('user.hasil.detail', $lulus->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $gagal->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $proses->getKey()), false);

        $this->actingAs($user)->get('/user/hasil?status=gagal')
            ->assertOk()
            ->assertSee(route('user.hasil.detail', $gagal->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $lulus->getKey()), false);

        $this->actingAs($user)->get('/user/hasil?status=proses')
            ->assertOk()
            ->assertSee(route('user.hasil.detail', $proses->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $lulus->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $gagal->getKey()), false);
    }

    public function test_parameter_status_asing_diabaikan(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('Quiz Satu');
        $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 90, selesai: true);

        // URL yang diketik manual dengan status ngawur harus jatuh ke
        // tab Semua, bukan halaman kosong tanpa penjelasan.
        $this->actingAs($user)
            ->get('/user/hasil?status=ngawur')
            ->assertOk()
            ->assertSee(route('user.hasil.detail', $pengerjaan->getKey()), false);
    }

    public function test_status_ambang_lulus_berasal_dari_konstanta_model(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('Quiz Ambang');

        $tepatAmbang = $this->buatPengerjaan($user, $quiz, nilai: PengerjaanQuiz::NILAI_LULUS, selesai: true);
        $satuDiBawah = $this->buatPengerjaan($user, $quiz, nilai: PengerjaanQuiz::NILAI_LULUS - 1, selesai: true);

        $this->assertSame('selesai', $tepatAmbang->status());
        $this->assertSame('gagal', $satuDiBawah->status());
    }

    public function test_pencarian_mencocokkan_judul_quiz(): void
    {
        $user = $this->buatPengguna();
        $html = $this->buatQuiz('HTML Dasar');
        $css = $this->buatQuiz('CSS Dasar');

        $cocok = $this->buatPengerjaan($user, $html);
        $tidakCocok = $this->buatPengerjaan($user, $css);

        $this->actingAs($user)->get('/user/hasil?q=html')
            ->assertOk()
            ->assertSee(route('user.hasil.detail', $cocok->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $tidakCocok->getKey()), false);
    }

    public function test_pengurutan_nilai_tertinggi_dan_terendah(): void
    {
        $user = $this->buatPengguna();

        $rendah = $this->buatPengerjaan($user, $this->buatQuiz('Nilai Rendah'), nilai: 30);
        $tinggi = $this->buatPengerjaan($user, $this->buatQuiz('Nilai Tinggi'), nilai: 95);

        // created_at kedua pengerjaan dibuat berdekatan, jadi diberi
        // selisih eksplisit supaya urutan "terbaru" tidak ambigu.
        $rendah->forceFill(['created_at' => now()->subDay()])->save();
        $tinggi->forceFill(['created_at' => now()])->save();

        // Yang dicek adalah tautan "Lihat Detail" masing-masing baris,
        // lalu dibandingkan posisinya di HTML. Kartu "Quiz Terpopuler"
        // ikut memuat keduanya, jadi tidak bisa dipakai sebagai patokan.
        $urutan = function (string $urut) use ($user, $rendah, $tinggi) {
            $isi = $this->actingAs($user)->get('/user/hasil?urut='.$urut)
                ->assertOk()
                ->getContent();

            $posisiRendah = strpos($isi, route('user.hasil.detail', $rendah->getKey()));
            $posisiTinggi = strpos($isi, route('user.hasil.detail', $tinggi->getKey()));

            $this->assertIsInt($posisiRendah, 'Baris nilai rendah tidak muncul di urutan '.$urut);
            $this->assertIsInt($posisiTinggi, 'Baris nilai tinggi tidak muncul di urutan '.$urut);

            return $posisiTinggi < $posisiRendah ? 'tinggi' : 'rendah';
        };

        $this->assertSame('tinggi', $urutan('nilai-tinggi'));
        $this->assertSame('rendah', $urutan('nilai-rendah'));
        $this->assertSame('tinggi', $urutan('terbaru'));
        $this->assertSame('rendah', $urutan('terlama'));
    }

    public function test_halaman_hanya_menampilkan_hasil_milik_pengguna_yang_login(): void
    {
        $saya = $this->buatPengguna('Natania', 'saya@example.com');
        $orangLain = $this->buatPengguna('Amara', 'amara@example.com');

        $quizSaya = $this->buatQuiz('Quiz Milik Saya');
        $quizOrangLain = $this->buatQuiz('Quiz Milik Orang Lain');

        $this->buatPengerjaan($saya, $quizSaya);
        $milikOrangLain = $this->buatPengerjaan($orangLain, $quizOrangLain);

        $this->actingAs($saya)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('Quiz Milik Saya')
            ->assertDontSee('Quiz Milik Orang Lain');

        // Membuka detail milik orang lain harus ditolak, bukan disembunyikan.
        $this->actingAs($saya)
            ->get('/user/hasil/'.$milikOrangLain->getKey())
            ->assertForbidden();

        $this->actingAs($saya)->get('/user/hasil/9999')->assertNotFound();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/user/hasil')->assertRedirect('/login');
        $this->get('/user/hasil/1')->assertRedirect('/login');
    }

    public function test_halaman_kosong_menampilkan_kartu_nol_dan_empty_state_di_riwayat(): void
    {
        $user = $this->buatPengguna();

        $respons = $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk();

        // Keempat kartu tetap dirender dengan angka 0. Itu jawaban
        // database, bukan data dummy.
        $respons->assertSee('Total Quiz Dikerjakan');
        $respons->assertSee('Rata-rata Nilai');
        $respons->assertSee('Nilai Tertinggi');
        $respons->assertSee('Waktu Belajar');

        // Dan tidak ada catatan pembanding yang dikarang.
        $respons->assertSee('Belum ada quiz selesai');
        $respons->assertDontSee('dari minggu lalu');

        // Empty state muncul di dalam area Riwayat Hasil Quiz, bukan
        // menggantikan seluruh halaman.
        $respons->assertSee('Riwayat Hasil Quiz');
        $respons->assertSee('Belum Ada Hasil Quiz');
        $respons->assertSee('Kamu belum memiliki riwayat pengerjaan quiz');
        $respons->assertSee('Mulai Quiz');

        // Sidebar juga tetap ada, dengan pesan yang jujur.
        $respons->assertSee('Statistik Belajar');
        $respons->assertSee('Quiz yang paling sering kamu kerjakan akan muncul di sini.');
    }

    public function test_halaman_detail_menampilkan_nilai_dan_daftar_soal(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');
        $soalA = $this->buatSoal($quiz, 1, 'A');
        $this->buatSoal($quiz, 2, 'B');

        $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 50, benar: 1, salah: 1, soal: 2, menit: 8);

        $this->actingAs($user)
            ->get('/user/hasil/'.$pengerjaan->getKey())
            ->assertOk()
            ->assertSee('HTML Dasar')
            ->assertSee('Jawabanmu per Soal')
            ->assertSee('Tidak Dijawab')
            ->assertSee($soalA->pertanyaan)
            ->assertSee('8 menit')
            ->assertSee('Kembali ke Hasil');
    }

    /**
     * Halaman detail satu pengerjaan tidak punya yang bisa dicari dan
     * hanya berisi rincian jawaban satu pengerjaan, jadi seluruh kepala
     * halaman ikut pergi: kolom cari, lonceng notifikasi, dan chip akun
     * (pp) semuanya disembunyikan, sama seperti halaman hasil quiz lain.
     *
     * Sebelumnya halaman ini hanya menyembunyikan kolom cari dan tetap
     * menampilkan lonceng; sekarang ketiganya pergi supaya peserta
     * langsung fokus ke angka nilai dan rincian jawabannya.
     */
    public function test_halaman_detail_menyingkirkan_kolom_cari_lonceng_dan_chip_akun(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');
        $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 80, benar: 4, salah: 1, soal: 5, menit: 15);

        // Daftar hasil tetap searchable seperti sebelumnya.
        $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('id="cari-topbar"', false)
            ->assertSee('id="cari-mobile"', false);

        $detail = $this->actingAs($user)
            ->get('/user/hasil/'.$pengerjaan->getKey())
            ->assertOk();

        $detail->assertDontSee('id="cari-topbar"', false);
        $detail->assertDontSee('id="cari-mobile"', false);

        // Top bar desktop hilang seluruhnya, jadi lonceng desktop ikut pergi.
        $detail->assertDontSee('data-app-topbar', false);

        // Lonceng notifikasi mobile dan chip akun (pp) ikut disembunyikan.
        $detail->assertDontSee('data-notif', false);
        $detail->assertDontSee('Menu profil', false);
    }

    /**
     * Tombol "Lihat Halaman Quiz" hanya untuk quiz yang benar-benar tayang:
     * quiz berstatus lain menolak pengunjung selain pembuatnya di halaman
     * detail, dan quiz mode kode tidak pernah terbit. Menampilkan tombol
     * pada pengerjaan quiz seperti itu berarti menyiapkan peserta untuk
     * membuka tautan yang berakhir 404.
     */
    public function test_tombol_lihat_halaman_quiz_hanya_untuk_quiz_yang_terbit(): void
    {
        $user = $this->buatPengguna();
        $terbit = $this->buatQuiz('Quiz Terbit');
        $draft = $this->buatQuiz('Quiz Draft');
        $draft->update(['status' => Quiz::STATUS_DRAFT]);
        $kode = $this->buatQuiz('Quiz Kode');
        $kode->update([
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'status' => Quiz::STATUS_DRAFT,
            'kode_akses' => 'KODE7X',
        ]);

        // Quiz terbit: tombol tampil dan menuju halaman detail-nya.
        $pengerjaanTerbit = $this->buatPengerjaan($user, $terbit, nilai: 80, benar: 4, salah: 1, soal: 5);
        $this->actingAs($user)
            ->get('/user/hasil/'.$pengerjaanTerbit->getKey())
            ->assertOk()
            ->assertSee('Lihat Halaman Quiz')
            ->assertSee(route('user.quiz.detail', $terbit));

        // Quiz draft dan quiz mode kode: tombol tidak ikut dirender.
        foreach ([$draft, $kode] as $quiz) {
            $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 70, benar: 4, salah: 1, soal: 5);

            $this->actingAs($user)
                ->get('/user/hasil/'.$pengerjaan->getKey())
                ->assertOk()
                ->assertDontSee('Lihat Halaman Quiz');
        }
    }

    /**
     * Wadah <ul> pilihan dan butir <li>-nya harus punya kelas berbeda.
     *
     * .hasil-soal__pilihan memang display:flex untuk menyusun huruf +
     * teks + tag dalam satu baris. Kalau <ul> memakainya juga, wadah
     * itu ikut jadi flex row: keempat pilihan berdiri berjajar saling
     * dorong, huruf A/B/C/D yang flex-shrink:0 merebut ruang lebih
     * dulu, dan teks jawabannya yang gepeng di antaranya.
     */
    public function test_wadah_pilihan_tidak_memakai_kelas_butir_yang_sama(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');
        $this->buatSoal($quiz, 1, 'A');
        $pengerjaan = $this->buatPengerjaan($user, $quiz, nilai: 80, benar: 4, salah: 1, soal: 5, menit: 15);

        $html = $this->actingAs($user)
            ->get('/user/hasil/'.$pengerjaan->getKey())
            ->assertOk()
            ->getContent();

        preg_match('/<ul class="([^"]*)"[^>]*>\s*<li class="hasil-soal__pilihan/', $html, $wadah);

        $this->assertNotSame([], $wadah, 'Daftar pilihan tidak ditemukan di halaman detail.');
        $this->assertStringContainsString('hasil-soal__pilihan-daftar', $wadah[1]);
        $this->assertStringNotContainsString(
            'hasil-soal__pilihan"',
            $wadah[1],
            'Wadah <ul> memakai kelas butir, jadi ikut display:flex dan pilihan berjajar.',
        );
    }

    public function test_catatan_mingguan_dihitung_dari_data_bukan_dikarang(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('HTML Dasar');

        // Dua quiz selesai 10 hari lalu = periode pembanding.
        $lama1 = $this->buatPengerjaan($user, $quiz, nilai: 50, menit: 10);
        $lama1->forceFill([
            'dimulai_pada' => now()->subDays(10)->subMinutes(10),
            'selesai_pada' => now()->subDays(10),
        ])->save();

        $lama2 = $this->buatPengerjaan($user, $quiz, nilai: 70, menit: 20);
        $lama2->forceFill([
            'dimulai_pada' => now()->subDays(9)->subMinutes(20),
            'selesai_pada' => now()->subDays(9),
        ])->save();

        // Dua quiz selesai 1 hari lalu = periode ini.
        $baru1 = $this->buatPengerjaan($user, $quiz, nilai: 70, menit: 15);
        $baru1->forceFill([
            'dimulai_pada' => now()->subDays(2)->subMinutes(15),
            'selesai_pada' => now()->subDays(2),
        ])->save();

        $baru2 = $this->buatPengerjaan($user, $quiz, nilai: 100, menit: 25);
        $baru2->forceFill([
            'dimulai_pada' => now()->subDays(1)->subMinutes(25),
            'selesai_pada' => now()->subDays(1),
        ])->save();

        $respons = $this->actingAs($user)->get('/user/hasil')->assertOk();

        // Jumlah quiz: 2 - 2 = 0, jadi teksnya "sama", bukan panah palsu.
        $respons->assertSee('Sama seperti minggu lalu');

        // Rata-rata naik dari 60 ke 85 = 25 poin.
        $respons->assertSee('Naik 25% dari minggu lalu');

        // Nilai tertinggi naik dari 70 ke 100 = 30 poin.
        $respons->assertSee('Naik 30 dari minggu lalu');

        // Waktu belajar: 30 menit jadi 40 menit = 10 menit lebih.
        $respons->assertSee('Naik 10 menit dari minggu lalu');

        // Rata-rata keseluruhan (50+70+70+100) / 4 = 72,5.
        $respons->assertSee('Rata-rata Nilai');
    }

    public function test_riwayat_per_quiz_membatasi_dasar(): void
    {
        $user = $this->buatPengguna();
        $html = $this->buatQuiz('HTML Dasar');
        $css = $this->buatQuiz('CSS Dasar');

        $milikHtml = $this->buatPengerjaan($user, $html);
        $milikCss = $this->buatPengerjaan($user, $css);

        $this->actingAs($user)
            ->get(route('user.hasil.daftar', $html))
            ->assertOk()
            ->assertSee('HTML Dasar')
            ->assertSee(route('user.hasil.detail', $milikHtml->getKey()), false)
            ->assertDontSee(route('user.hasil.detail', $milikCss->getKey()), false);
    }

    public function test_berita_menampilkan_waktu_belajar_dalam_jam(): void
    {
        $user = $this->buatPengguna();
        $quiz = $this->buatQuiz('Quiz Panjang');

        $this->buatPengerjaan($user, $quiz, menit: 45);
        $this->buatPengerjaan($user, $quiz, menit: 30);

        // 45 + 30 = 75 menit, jadi ditulis sebagai 1,3 jam. Angka dan
        // satuan berada di elemen terpisah supaya styling-nya bisa
        // berbeda, jadi keduanya dicek terpisah.
        $this->actingAs($user)
            ->get('/user/hasil')
            ->assertOk()
            ->assertSee('Waktu Belajar')
            ->assertSee('1,3')
            ->assertSee('jam');
    }
}
