<?php

namespace Tests\Feature;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use App\Support\BerkasQuiz;
use App\Support\KodeQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Wizard "Buat Quiz" tiga langkah: Informations Dasar, Buat Soal, dan
 * Pengaturan.
 *
 * Yang diuji di sini adalah sisi server dari wizard, yaitu apa yang
 * benar-benar disimpan dan apa yang ditolak. Perilaku antar langkah
 * (berpindah, tambah soal, urutkan) Jalannya di resources/js/quiz-tambah.js
 * dan tidak bisa dipanggil dari test HTTP.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di
 * sini tidak menyentuh database sungguhan.
 */
class QuizWizardTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $nama = 'Natania', string $email = 'natania@example.com'): User
    {
        return User::create([
            'nama' => $nama,
            'email' => $email,
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

    private function buatPelajaran(string $nama = 'Pemrograman', string $slug = 'pemrograman'): Pelajaran
    {
        return Pelajaran::create([
            'nama' => $nama,
            'slug' => $slug,
            'deskripsi' => "Deskripsi $nama",
            'ikon' => '</>',
            'aktif' => true,
        ]);
    }

    /**
     * Satu baris soal bertipe pilihan ganda, supaya tiap test cukup menulis
     * pertanyaannya, daftar pilihannya, dan kunci jawabannya.
     *
     * @param  array<int, string>  $pilihan
     * @param  string|array<int, string>  $benar
     * @return array<string, mixed>
     */
    private function soalGanda(string $pertanyaan, array $pilihan, $benar, string $tingkat = 'Mudah'): array
    {
        $huruf = array_map(fn ($i) => chr(65 + $i), array_keys($pilihan));

        return [
            'pertanyaan' => $pertanyaan,
            'tipe' => Soal::TIPE_PILIHAN_GANDA,
            'pilihan' => array_combine($huruf, $pilihan),
            'benar' => (array) $benar,
            'tingkat_kesulitan' => $tingkat,
        ];
    }

    /**
     * Soal pertama dari quiz "HTML Dasar", dibuat oleh isianLengkap().
     */
    private function soalPertama(): Soal
    {
        return Quiz::query()
            ->where('judul', 'HTML Dasar')
            ->firstOrFail()
            ->soal()
            ->terurut()
            ->firstOrFail();
    }

    /**
     * Isian wizard yang sudah lengkap, supaya tiap test cukup mengubah
     * satu bagian yang memang sedang diuji.
     *
     * @return array<string, mixed>
     */
    private function isianLengkap(Pelajaran $pelajaran, array $ubah = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'judul' => 'HTML Dasar',
            'deskripsi' => 'Kuis pengenalan HTML.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'durasi' => 15,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'tampilkan_jawaban' => 1,
            'kode_akses' => 'K7F3P9',
            'soal' => [
                [
                    'pertanyaan' => 'Apa itu HTML?',
                    'tipe' => Soal::TIPE_PILIHAN_GANDA,
                    'pilihan' => ['A' => 'Markup', 'B' => 'Program', 'C' => 'Database', 'D' => 'Framework'],
                    'benar' => ['A'],
                    'pembahasan' => 'HTML adalah bahasa markup.',
                    'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
                ],
            ],
        ], $ubah);
    }

    public function test_halaman_buat_quiz_menampilkan_ketiga_tahapan_wizard(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        // Stepper memuat tiga langkah, berurutan dari Informasi Dasar.
        $this->assertStringContainsString('Informasi Dasar', $isi);
        $this->assertStringContainsString('Buat Soal', $isi);
        $this->assertStringContainsString('Pengaturan', $isi);

        $this->assertStringContainsString('data-wizard-stepper', $isi);
        $this->assertStringContainsString('data-wizard-panel="1"', $isi);
        $this->assertStringContainsString('data-wizard-panel="2"', $isi);
        $this->assertStringContainsString('data-wizard-panel="3"', $isi);
    }

    public function test_halaman_buat_quiz_menampilkan_kolom_setiap_langkah(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        // Langkah 1: informasi dasar.
        foreach (['judul', 'deskripsi', 'pelajaran_id', 'tingkat_kesulitan', 'thumbnail'] as $kolom) {
            $this->assertStringContainsString('name="'.$kolom.'"', $isi, "Kolom {$kolom} tidak ada.");
        }

        // Langkah 2 dan 3.
        $this->assertStringContainsString('Tambah Soal', $isi);
        $this->assertStringContainsString('name="visibilitas"', $isi);
        $this->assertStringContainsString('name="kode_akses"', $isi);
        $this->assertStringContainsString('name="tampilkan_jawaban"', $isi);
        $this->assertStringContainsString('name="durasi"', $isi);
    }

    public function test_kolom_kode_langsung_berisi_kode_acak(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        // Aturan kode dibuat oleh App\Support\KodeQuiz; view hanya
        // mengirim abjad dan panjangnya ke JavaScript.
        $this->assertMatchesRegularExpression(
            '/id="kode_akses"[^>]*value="[A-Z0-9]{'.preg_quote((string) KodeQuiz::PANJANG, '/').'}"/s',
            $isi,
        );
    }

    public function test_wizard_menyimpan_quiz_beserta_soal_dan_pengaturannya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'tampilkan_jawaban' => 0,
                'durasi' => 30,
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertSessionHas('sukses')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_quiz', [
            'judul' => 'HTML Dasar',
            'dibuat_oleh' => $user->getKey(),
            'pelajaran_id' => $pelajaran->id,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'K7F3P9',
            'durasi' => 30,
            'tampilkan_jawaban' => false,
        ]);

        $this->assertDatabaseHas('tb_soal', [
            'pertanyaan' => 'Apa itu HTML?',
            'jawaban_benar' => 'A',
            'pembahasan' => 'HTML adalah bahasa markup.',
            'urutan' => 1,
        ]);
    }

    public function test_kode_quiz_privat_disimpan_dengan_huruf_besar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'k7f3p9',
            ]))
            ->assertSessionHasNoErrors();

        // Kode diketik peserta bisa huruf kecil; yang tersimpan selalu
        // huruf besar supaya cocok dengan kode yang di-generate.
        $this->assertDatabaseHas('tb_quiz', ['kode_akses' => 'K7F3P9']);
    }

    public function test_quiz_publik_tidak_menyimpan_kode_akses(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran))
            ->assertSessionHasNoErrors();

        // Kolom kode_akses punya unique index, jadi kode publik tidak
        // boleh mengisi ruang unik itu tanpa alasan.
        $this->assertDatabaseHas('tb_quiz', ['visibilitas' => Quiz::VISIBILITAS_PUBLIK, 'kode_akses' => null]);
    }

    public function test_urutan_soal_diambil_dari_urutan_kartu_bukan_dari_nomor_isian(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $soal = [
            $this->soalGanda('Soal pertama', ['A', 'B', 'C', 'D'], 'A', 'Mudah'),
            $this->soalGanda('Soal kedua', ['A', 'B', 'C', 'D'], 'B', 'Sedang'),
            $this->soalGanda('Soal ketiga', ['A', 'B', 'C', 'D'], 'C', 'Sulit'),
        ];

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => $soal]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail();
        $tersimpan = $quiz->soal()->terurut()->get();

        $this->assertSame(
            ['Soal pertama', 'Soal kedua', 'Soal ketiga'],
            $tersimpan->pluck('pertanyaan')->all(),
        );
        $this->assertSame([1, 2, 3], $tersimpan->pluck('urutan')->all());
    }

    public function test_quiz_menolak_kategori_dan_kesulitan_yang_kosong(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($this->buatPelajaran('Kosong', 'kosong'), [
                'pelajaran_id' => null,
                'tingkat_kesulitan' => null,
            ]))
            ->assertSessionHasErrors(['pelajaran_id', 'tingkat_kesulitan']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_quiz_menolak_judul_dan_deskripsi_kosong(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'judul' => '',
                'deskripsi' => '',
            ]))
            ->assertSessionHasErrors(['judul', 'deskripsi']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    /* ============================================================
       TIPE PILIHAN GANDA
    ============================================================ */

    public function test_soal_pilihan_ganda_tersimpan_beserta_satu_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Tag judul terbesar', ['<h1>', '<h2>', '<h3>', '<p>'], 'A')],
            ]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::TIPE_PILIHAN_GANDA, $soal->tipe());
        $this->assertSame(['A' => '<h1>', 'B' => '<h2>', 'C' => '<h3>', 'D' => '<p>'], $soal->pilihan());
        $this->assertSame(['A'], $soal->hurufBenar());
    }

    /**
     * Kunci jawaban boleh menunjuk pilihan mana pun, bukan hanya A.
     *
     * Pertanyaan ini pernah benar secara teknis tapi tidak berguna, karena
     * radio jawaban benarnya terkunci di A dan pilihan lain tidak pernah
     * bisa dipilih.
     */
    public function test_jawaban_benar_pilihan_ganda_bisa_di_pilihan_manapun(): void
    {
        foreach (['B', 'C', 'D'] as $huruf) {
            $user = $this->buatPengguna("Pemilik {$huruf}", "pemilik-{$huruf}@example.com");
            $pelajaran = $this->buatPelajaran("Pelajaran {$huruf}", strtolower("pelajaran-{$huruf}"));

            $this->actingAs($user)
                ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                    'judul' => "Quiz Kunci {$huruf}",
                    'soal' => [$this->soalGanda("Soal dengan kunci {$huruf}", ['Satu', 'Dua', 'Tiga', 'Empat'], $huruf)],
                ]))
                ->assertSessionHasNoErrors();

            $soal = Quiz::query()->where('judul', "Quiz Kunci {$huruf}")->firstOrFail()->soal()->firstOrFail();

            $this->assertSame([$huruf], $soal->hurufBenar(), "Kunci jawaban {$huruf} tidak tersimpan.");
        }
    }

    public function test_soal_pilihan_ganda_ditolak_kurang_dari_dua_pilihan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Cuma satu pilihan', ['A'], 'A')],
            ]))
            ->assertSessionHasErrors(['soal.0.pilihan']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_soal_pilihan_ganda_ditolak_tanpa_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Tanpa kunci', ['A', 'B'], [])],
            ]))
            ->assertSessionHasErrors(['soal.0.benar']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_soal_pilihan_ganda_ditolak_kalau_jawaban_benarnya_lebih_dari_satu(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Dua kunci', ['A', 'B', 'C'], ['A', 'B'])],
            ]))
            ->assertSessionHasErrors(['soal.0.benar']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_soal_ditolak_kalau_jawaban_benar_menunjuk_pilihan_yang_kosong(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Kunci menunjuk pilihan kosong', ['A', 'B'], 'C')],
            ]))
            ->assertSessionHasErrors(['soal.0.benar']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_soal_ditolak_kalau_huruf_jawaban_benar_di_luar_daftar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Kunci huruf Z', ['A', 'B'], 'Z')],
            ]))
            ->assertSessionHasErrors(['soal.0.benar']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_pilihan_boleh_lebih_dari_empat_sampai_batas_maksimal(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $sepuluh = array_map(fn ($i) => 'Pilihan '.$i, range(1, Soal::MAKSIMAL_PILIHAN));
        $hurufSepuluh = Soal::hurufTersedia();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Sepuluh pilihan', $sepuluh, [$hurufSepuluh[9]])],
            ]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertCount(Soal::MAKSIMAL_PILIHAN, $soal->pilihan());
        $this->assertSame('J', $soal->hurufBenar()[0]);
    }

    /* ============================================================
       TIPE CHECKBOX / PILIHAN BANYAK
    ============================================================ */

    public function test_soal_pilihan_banyak_bisa_punya_lebih_dari_satu_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Mana teknologi dasar web', ['HTML', 'CSS', 'JavaScript', 'Word'], []);
        $baris['tipe'] = Soal::TIPE_PILIHAN_BANYAK;
        $baris['benar'] = ['A', 'B', 'C'];

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::TIPE_PILIHAN_BANYAK, $soal->tipe());
        $this->assertTrue($soal->tipeBanyakBenar());
        $this->assertSame(['A', 'B', 'C'], $soal->hurufBenar());
    }

    public function test_soal_pilihan_banyak_ditolak_tanpa_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Tanpa satu pun kunci', ['HTML', 'CSS'], []);
        $baris['tipe'] = Soal::TIPE_PILIHAN_BANYAK;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasErrors(['soal.0.benar']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    /* ============================================================
       TIPE DROPDOWN
    ============================================================ */

    public function test_soal_dropdown_tersimpan_dengan_satu_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Pilih bahasanya', ['JavaScript', 'PHP', 'Python'], ['B']);
        $baris['tipe'] = Soal::TIPE_DROPDOWN;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::TIPE_DROPDOWN, $soal->tipe());
        $this->assertSame(['B'], $soal->hurufBenar());
    }

    /* ============================================================
        TIPE BENAR / SALAH
    ============================================================ */

    public function test_soal_benar_salah_tersimpan_dengan_dua_pilihan_tetap(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Bahasa HTML itu bahasa pemrograman.', ['Benar', 'Salah'], ['A']);
        $baris['tipe'] = Soal::TIPE_BENAR_SALAH;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::TIPE_BENAR_SALAH, $soal->tipe());
        $this->assertSame(Soal::PILIHAN_BENAR_SALAH, array_values($soal->pilihan()));
        $this->assertSame(['A'], $soal->hurufBenar());
        $this->assertFalse($soal->tipeTeks());
        $this->assertTrue($soal->tipePakaiPilihan());
        $this->assertFalse($soal->tipeBanyakBenar());
    }

    public function test_soal_benar_salah_ditolak_tanpa_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Belum ditandai kuncinya.', ['Benar', 'Salah'], []);
        $baris['tipe'] = Soal::TIPE_BENAR_SALAH;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasErrors(['soal.0.benar']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_isian_benar_salah_tanpa_pilihan_diisi_dari_konstanta(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        // Pilihannya sengaja tidak dikirim: yang penting teks "Benar" dan
        // "Salah" dibuat sendiri oleh penyimpan, bukan bergantung isian
        // pengirim yang bisa datang dari format lama.
        $baris = $this->soalGanda('Tanpa pilihan ikut dikirim.', [], ['A']);
        $baris['tipe'] = Soal::TIPE_BENAR_SALAH;
        unset($baris['pilihan']);

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::PILIHAN_BENAR_SALAH, array_values($soal->pilihan()));
        $this->assertSame(['A'], $soal->hurufBenar());
    }

    /* ============================================================
        TIPE JAWABAN SINGKAT
    ============================================================ */

    public function test_soal_jawaban_singkat_tersimpan_beserta_kunci_teksnya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Kepanjangan HTML', [], []);
        $baris['tipe'] = Soal::TIPE_JAWABAN_SINGKAT;
        $baris['jawaban_teks'] = 'Hyper Text Markup Language';
        $baris['tococok_persis'] = true;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::TIPE_JAWABAN_SINGKAT, $soal->tipe());
        $this->assertTrue($soal->tipeTeks());
        $this->assertSame('Hyper Text Markup Language', $soal->kunciTeks());
        $this->assertTrue($soal->tococok_persis);

        // Tipe ini tidak punya pilihan sama sekali.
        $this->assertSame([], $soal->pilihan());
    }

    public function test_soal_jawaban_singkat_ditolak_tanpa_jawaban_benar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Tanpa kunci teks', [], []);
        $baris['tipe'] = Soal::TIPE_JAWABAN_SINGKAT;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasErrors(['soal.0.jawaban_teks']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_saklar_tocok_persis_yang_dimatikan_tersimpan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Bebas spasi', [], []);
        $baris['tipe'] = Soal::TIPE_JAWABAN_SINGKAT;
        $baris['jawaban_teks'] = 'Hyper Text Markup Language';
        $baris['tococok_persis'] = 0;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($this->soalPertama()->tococok_persis);
    }

    /* ============================================================
       TIPE PARAGRAF
    ============================================================ */

    public function test_soal_paragraf_tersimpan_dengan_jawaban_acuan_boleh_kosong(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $denganAcuan = $this->soalGanda('Jelaskan fungsi CSS', [], []);
        $denganAcuan['tipe'] = Soal::TIPE_PARAGRAF;
        $denganAcuan['jawaban_teks'] = 'CSS mengatur tampilan halaman web.';

        $tanpaAcuan = $this->soalGanda('Jelaskan fungsi HTML', [], []);
        $tanpaAcuan['tipe'] = Soal::TIPE_PARAGRAF;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$denganAcuan, $tanpaAcuan],
            ]))
            ->assertSessionHasNoErrors();

        $tersimpan = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail()->soal()->terurut()->get();

        $this->assertSame(Soal::TIPE_PARAGRAF, $tersimpan[0]->tipe());
        $this->assertSame('CSS mengatur tampilan halaman web.', $tersimpan[0]->kunciTeks());

        // Jawaban acuan boleh kosong: penilaiannya dibaca manual.
        $this->assertSame(Soal::TIPE_PARAGRAF, $tersimpan[1]->tipe());
        $this->assertNull($tersimpan[1]->jawaban_teks);
    }

    public function test_soal_ditolak_kalau_tipenya_tidak_dikenal(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Tipe ngawur', ['A', 'B'], ['A']);
        $baris['tipe'] = 'teks_panjang';

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasErrors(['soal.0.tipe']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_berbagai_tipe_bisa_campur_dalam_satu_quiz(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $singkat = $this->soalGanda('Kepanjangan HTML', [], []);
        $singkat['tipe'] = Soal::TIPE_JAWABAN_SINGKAT;
        $singkat['jawaban_teks'] = 'Hyper Text Markup Language';

        $paragraf = $this->soalGanda('Jelaskan fungsi CSS', [], []);
        $paragraf['tipe'] = Soal::TIPE_PARAGRAF;
        $paragraf['jawaban_teks'] = 'Mengatur tampilan.';

        $banyak = $this->soalGanda('Mana yang benar', ['HTML', 'CSS', 'Word'], ['A', 'B']);
        $banyak['tipe'] = Soal::TIPE_PILIHAN_BANYAK;

        $dropdown = $this->soalGanda('Pilih satu', ['Satu', 'Dua'], ['B']);
        $dropdown['tipe'] = Soal::TIPE_DROPDOWN;

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [
                    $this->soalGanda('Ganda dulu', ['Satu', 'Dua'], ['A']),
                    $banyak,
                    $dropdown,
                    $singkat,
                    $paragraf,
                ],
            ]))
            ->assertSessionHasNoErrors();

        $tersimpan = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail()->soal()->terurut()->get();

        $this->assertSame(
            [
                Soal::TIPE_PILIHAN_GANDA,
                Soal::TIPE_PILIHAN_BANYAK,
                Soal::TIPE_DROPDOWN,
                Soal::TIPE_JAWABAN_SINGKAT,
                Soal::TIPE_PARAGRAF,
            ],
            $tersimpan->pluck('tipe')->all(),
        );

        $this->assertSame([1, 2, 3, 4, 5], $tersimpan->pluck('urutan')->all());
    }

    /* ============================================================
       PILIHAN KOSONG DAN KOLOM CADANGAN
    ============================================================ */

    public function test_pilihan_kosong_di_tengah_dilewati(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $baris = $this->soalGanda('Ada pilihan kosong', ['Satu', 'Dua', 'Tiga'], ['A']);
        $baris['pilihan'] = ['A' => 'Satu', 'C' => 'Tiga', 'D' => ''];
        $baris['benar'] = ['A'];

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['soal' => [$baris]]))
            ->assertSessionHasNoErrors();

        /*
         * Huruf pilihan tidak diurutkan ulang diam-diam: yang dikirim B jadi
         * kosong lalu dilewati, dan pilihan C tetap bernama C. Builder sendiri
         * selalu mengirim huruf berurutan, jadi huruf yang bolong hanya mungkin
         * dari request yang dibuat manual.         */
        $this->assertSame(['A' => 'Satu', 'C' => 'Tiga'], $this->soalPertama()->pilihan());
    }

    /**
     * Kolom pilihan_a sampai pilihan_d di tb_soal NOT NULL, jadi tetap harus
     * diisi meski sumbernya sudah pindah ke tb_soal_pilihan. Kolom inilah yang
     * dibaca reader lama, jadi boleh jadi tidak sinkron dengan baris baru Ã¢â‚¬â€
     * yang jadi acuan tetap tb_soal_pilihan.
     */
    public function test_kolom_cadangan_tetap_diisi_untuk_soal_berdaftar(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [$this->soalGanda('Empat pilihan', ['Satu', 'Dua', 'Tiga', 'Empat'], 'C')],
            ]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama()->fresh();

        $this->assertSame('Satu', $soal->pilihan_a);
        $this->assertSame('Empat', $soal->pilihan_d);
        $this->assertSame('C', $soal->jawaban_benar);
    }

    public function test_soal_lama_tanpa_baris_pilihan_tetap_dibaca_dari_kolom_lama(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $user->getKey(),
            'judul' => 'Quiz Lama',
            'slug' => 'quiz-lama',
            'deskripsi' => 'Dibuat sebelum tb_soal_pilihan ada.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $soal = Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Soal lama',
            'pilihan_a' => 'Satu',
            'pilihan_b' => 'Dua',
            'pilihan_c' => 'Tiga',
            'pilihan_d' => 'Empat',
            'jawaban_benar' => 'B',
            'urutan' => 1,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);

        $fresh = $soal->fresh();

        $this->assertSame(
            ['A' => 'Satu', 'B' => 'Dua', 'C' => 'Tiga', 'D' => 'Empat'],
            $fresh->pilihan(),
        );
        $this->assertSame(['B'], $fresh->hurufBenar());
        $this->assertSame(Soal::TIPE_PILIHAN_GANDA, $fresh->tipe());
    }

    /* ============================================================
       FORMAT LAMA
    ============================================================ */

    /**
     * Form lama mengirim pilihan di kolom terpisah dan kunci jawaban di satu
     * field. Form yang masih terbuka di tab lain boleh memakai format itu,
     * jadi server harus tetap menerimanya.
     */
    public function test_format_lama_masih_diterima(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'soal' => [
                    [
                        'pertanyaan' => 'Format lama',
                        'pilihan_a' => 'Satu',
                        'pilihan_b' => 'Dua',
                        'pilihan_c' => 'Tiga',
                        'pilihan_d' => 'Empat',
                        'jawaban_benar' => 'C',
                        'tingkat_kesulitan' => 'Mudah',
                    ],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $soal = $this->soalPertama();

        $this->assertSame(Soal::TIPE_PILIHAN_GANDA, $soal->tipe());
        $this->assertSame(['C'], $soal->hurufBenar());
        $this->assertSame(['A' => 'Satu', 'B' => 'Dua', 'C' => 'Tiga', 'D' => 'Empat'], $soal->pilihan());
    }

    /* ============================================================
        MARKUP BUILDER
    ============================================================ */

    public function test_builder_menampilkan_seluruh_tipe_dan_tombol_dukungan(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('data-builder-daftar', $isi);
        $this->assertStringContainsString('data-builder-tambah', $isi);
        $this->assertStringContainsString('data-builder-duplikat', $isi);
        $this->assertStringContainsString('data-builder-hapus', $isi);
        $this->assertStringContainsString('data-builder-pilihan-tambah', $isi);
        $this->assertStringContainsString('data-builder-pilihan-hapus', $isi);
        $this->assertStringContainsString('data-builder-seret', $isi);

        // Setiap tipe punya blok jawabannya masing-masing.
        foreach (Soal::tipeTersedia() as $tipe) {
            $this->assertStringContainsString('data-builder-blok="'.$tipe.'"', $isi);
        }
    }

    /**
     * Nama tipe tidak lagi dipasang sebagai lencana mati di kepala card.
     * Yang ada sekarang tombol menu yang menuliskan tipe yang sedang
     * dipakai soal itu, lengkap dengan daftar pilihannya.
     *
     * Tingkat kesulitan juga menetap di kepala, bersebelahan di kiri
     * dropdown tipe, supaya badan card tinggal isian pertanyaan sampai
     * pembahasan.
     */
    public function test_kepala_card_mempunyai_menu_pilih_tipe(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('data-builder-tipe-tombol', $isi);
        $this->assertStringContainsString('data-builder-tipe-teks', $isi);
        $this->assertStringContainsString('data-builder-tipe-menu', $isi);
        $this->assertStringContainsString('builder-soal__gaget', $isi);
        $this->assertStringContainsString('data-builder-nomor', $isi);

        $this->assertStringNotContainsString('data-builder-lencana', $isi);
        $this->assertStringNotContainsString('data-builder-tipe-lencana', $isi);

        // Tingkat kesulitan ada di kepala card, sebelum dropdown tipe.
        $posTingkat = strpos($isi, 'data-builder-tingkat');
        $posTipe = strpos($isi, 'data-builder-tipe-pilih');

        $this->assertNotFalse($posTingkat, 'Select tingkat kesulitan tidak ada.');
        $this->assertNotFalse($posTipe, 'Dropdown tipe tidak ada.');
        $this->assertLessThan($posTipe, $posTingkat, 'Tingkat kesulitan harus di kiri dropdown tipe.');
        $this->assertStringContainsString('aria-label="Tingkat Kesulitan soal"', $isi);
    }

    /**
     * Card soal dibagi jadi tiga bagian datar — Pertanyaan, Jawaban, dan
     * Pembahasan — tanpa nomor bagian, supaya isinya terbaca sebagai satu
     * urutan pengerjaan, bukan tumpukan kartu kecil. Tingkat kesulitannya
     * sudah pindah ke kepala card, jadi tidak ikut jadi bagian badan.
     */
    public function test_card_soal_memakai_bagian_bertanda_dan_petunjuk(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('builder-blok', $isi);
        $this->assertStringContainsString('builder-petunjuk', $isi);

        foreach (['Pertanyaan', 'Pembahasan'] as $judul) {
            $this->assertStringContainsString($judul, $isi, "Judul bagian '{$judul}' tidak ada.");
        }

        $this->assertStringNotContainsString('builder-bagian__nomor', $isi);
        $this->assertStringNotContainsString('Kesulitan Soal', $isi);

        // Tingkat kesulitan sudah tidak jadi salah satu bagian badan card.
        $this->assertStringNotContainsString('label-form">Tingkat Kesulitan', $isi);
    }

    /**
     * Tipe soal dibaca dari dropdown, bukan dari teks mati. Dropdown hanya
     * menawarkan tipe yang boleh dipilih baru — termasuk Benar / Salah —
     * sementara tipe dropdown lama tidak ikut ditawarkan tapi blok
     * jawabannya tetap ada supaya soal lama masih bisa diedit.
     */
    public function test_tipe_soal_dipilih_lewat_dropdown(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('data-builder-tipe', $isi);

        foreach ([
            Soal::TIPE_PILIHAN_GANDA => 'Pilihan Ganda',
            Soal::TIPE_PILIHAN_BANYAK => 'Pilihan Ganda Kompleks',
            Soal::TIPE_BENAR_SALAH => 'Benar / Salah',
            Soal::TIPE_JAWABAN_SINGKAT => 'Isian Singkat',
            Soal::TIPE_PARAGRAF => 'Essay',
        ] as $tipe => $label) {
            $this->assertStringContainsString(
                'value="'.$tipe.'"',
                $isi,
                "Opsi tipe {$tipe} tidak ada di dropdown.",
            );
            $this->assertStringContainsString('>'.$label.'</option>', $isi);
        }

        $this->assertStringContainsString('data-builder-blok="'.Soal::TIPE_DROPDOWN.'"', $isi);
    }

    /**
     * Langkah 2 langsung membuka panduan empat langkah tanpa kepala
     * halaman, lalu sebuah kartu aksi yang memuat seluruh aksinya sendiri:
     * Kembali, Batal, Draft (kirim form lebih awal), dan Lanjut ke
     * Pengaturan yang didorong ke kanan. Baris navigasi bawah tetap ada
     * untuk langkah 1 dan 3, lengkap dengan pasangan Kembali dan Batal.
     */
    public function test_langkah_dua_punya_panduan_dan_aksi(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('Buat soal dalam 4 langkah sederhana', $isi);

        foreach (['Tulis Pertanyaan', 'Pilih Tipe Jawaban', 'Isi Jawaban', 'Atur Kesulitan'] as $langkah) {
            $this->assertStringContainsString($langkah, $isi, "Langkah panduan '{$langkah}' tidak ada.");
        }

        // Kepala halaman lama tidak ikut kembali.
        $this->assertStringNotContainsString('panel-kepala', $isi);
        $this->assertStringNotContainsString('Buat soal baru untuk menguji pemahaman siswa', $isi);
        $this->assertStringNotContainsString('Simpan Soal', $isi);

        // Isi kartu aksi di bawah daftar soal.
        preg_match('/<div class="panel-aksi">(?<baris>.*?)<\/div>/s', $isi, $temuan);

        $this->assertNotEmpty($temuan['baris'] ?? null, 'Kartu aksi langkah 2 tidak ada.');

        foreach (['data-wizard-kembali-alias', 'data-wizard-batal', 'data-wizard-draft', 'data-wizard-lanjut-alias'] as $aksi) {
            $this->assertStringContainsString($aksi, $temuan['baris'], "Aksi '{$aksi}' tidak ada di kartu.");
        }

        $this->assertStringContainsString('Lanjut ke Pengaturan', $temuan['baris']);

        // Urutannya Kembali, Batal, Draft, lalu Lanjut ke Pengaturan.
        $urutan = [
            'data-wizard-kembali-alias',
            'data-wizard-batal',
            'data-wizard-draft',
            'data-wizard-lanjut-alias',
        ];

        $posisi = array_map(fn (string $cari) => strpos($temuan['baris'], $cari), $urutan);

        $urut = $posisi;
        sort($urut);

        $this->assertSame($urut, $posisi, 'Urutan kartu aksi harus Kembali, Batal, Draft, lalu Lanjut.');

        // Baris navigasi bawah masih dipakai langkah 1 dan 3.
        preg_match('/<div class="mt-5" data-wizard-nav>(?<nav>.*?)<\/div>/s', $isi, $temuanNav);

        $this->assertNotEmpty($temuanNav['nav'] ?? null, 'Baris navigasi bawah tidak ada.');
        $this->assertStringContainsString('data-wizard-kembali', $temuanNav['nav']);
        $this->assertStringContainsString('data-wizard-batal', $temuanNav['nav']);
        $this->assertStringContainsString('data-wizard-lanjut-teks', $temuanNav['nav']);
    }

    /**
     * Batas pilihan jawaban disansom Builder lewat data-maksimal, supaya
     * tombol Tambah Pilihan mati di angka yang sama dengan batas di server
     * (App\Models\Soal::MAKSIMAL_PILIHAN).
     */
    public function test_batas_pilihan_belajar_dari_konstanta_model(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $isi = $this->actingAs($user)->get('/user/quiz/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('data-maksimal="'.Soal::MAKSIMAL_PILIHAN.'"', $isi);
    }

    public function test_form_ubah_membuka_builder_dengan_isian_awal_soal(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $user->getKey(),
            'judul' => 'Quiz Enam Pilihan',
            'slug' => 'quiz-enam-pilihan',
            'deskripsi' => 'Soalnya punya enam pilihan.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $soal = Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Mana yang benar?',
            'pilihan_a' => 'Satu',
            'pilihan_b' => 'Dua',
            'pilihan_c' => 'Tiga',
            'pilihan_d' => 'Empat',
            'jawaban_benar' => 'A',
            'urutan' => 1,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);

        $soal->pilihanSoal()->createMany([
            ['huruf' => 'A', 'teks' => 'Satu', 'urutan' => 1, 'benar' => false],
            ['huruf' => 'B', 'teks' => 'Dua', 'urutan' => 2, 'benar' => true],
            ['huruf' => 'C', 'teks' => 'Tiga', 'urutan' => 3, 'benar' => false],
            ['huruf' => 'D', 'teks' => 'Empat', 'urutan' => 4, 'benar' => false],
        ]);

        $isi = $this->actingAs($user)->get(route('user.quiz.edit', $quiz))->assertOk()->getContent();

        /*
         * Isian awal dikirim sebagai JSON, jadi teks tiap pilihan ikut
         * terkirim ke JavaScript. Pemetaannya pernah salah sehingga kartu soal
         * hanya menampilkan huruf A sampai D tanpa teksnya.
         */
        $this->assertStringContainsString('"pertanyaan":"Mana yang benar?"', $isi);
        $this->assertStringContainsString('"teks":"Empat"', $isi);
        $this->assertStringContainsString('"benar":["B"]', $isi);
    }

    public function test_quiz_privat_menolak_isian_tanpa_kode(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => null,
            ]))
            ->assertSessionHasErrors(['kode_akses']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    public function test_kode_akses_yang_sudah_dipakai_quiz_lain_ditolak(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $user->getKey(),
            'judul' => 'Quiz Kode Kembar',
            'slug' => 'quiz-kode-kembar',
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'K7F3P9',
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'K7F3P9',
            ]))
            ->assertSessionHasErrors(['kode_akses']);
    }

    public function test_saklar_jawaban_yang_dimatikan_tersimpan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, ['tampilkan_jawaban' => 0]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail();

        $this->assertFalse($quiz->tampilkan_jawaban);
        $this->assertFalse($quiz->menampilkanJawaban());
    }

    public function test_saklar_jawaban_tanpa_field_artinya_menampilkan_jawaban(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $isian = $this->isianLengkap($pelajaran);
        unset($isian['tampilkan_jawaban']);

        $this->actingAs($user)->post('/user/quiz/tambah', $isian)->assertSessionHasNoErrors();

        $this->assertTrue(Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail()->menampilkanJawaban());
    }

    public function test_thumbnail_yang_diunggah_tersimpan_di_disk_publik(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('sampul.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail();

        $this->assertNotNull($quiz->thumbnail);
        $this->assertStringStartsWith(BerkasQuiz::FOLDER.'/', $quiz->thumbnail);
        Storage::disk('public')->assertExists($quiz->thumbnail);
    }

    public function test_thumbnail_yahunya_ditolak(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'thumbnail' => UploadedFile::fake()->create('dokumen.pdf', 8, 'application/pdf'),
            ]))
            ->assertSessionHasErrors(['thumbnail']);

        $this->assertDatabaseCount('tb_quiz', 0);
    }

    /**
     * Mengganti thumbnail harus menyimpan berkas barunya, membuang yang
     * lamanya, dan menyisakan kolom yang menunjuk berkas yang benar-benar
     * ada di disk — kalau tidak, gambarnya tampil rusak di semua halaman.
     */
    public function test_thumbnail_baru_saat_edit_mengganti_berkas_lama(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('lama.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail();
        $lama = $quiz->thumbnail;

        $this->assertNotNull($lama);
        Storage::disk('public')->assertExists($lama);

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->isianLengkap($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('baru.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $tersimpan = $quiz->fresh()->thumbnail;

        $this->assertNotNull($tersimpan);
        $this->assertNotSame($lama, $tersimpan);
        Storage::disk('public')->assertExists($tersimpan);
        Storage::disk('public')->assertMissing($lama);
    }

    /**
     * Tombol Hapus pada thumbnail dikirim sebagai thumbnail_hapus. Tanpa
     * bendera itu dibaca controller, tombolnya hanya berhenti di pratinjau
     * browser dan gambarnya muncul lagi setelah halaman dimuat ulang.
     */
    public function test_thumbnail_quiz_bisa_dihapus_lewat_bendera_hapus(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('lama.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail();
        $lama = $quiz->thumbnail;

        // Form edit memang menitipkan bendera itu.
        $this->actingAs($user)
            ->get(route('user.quiz.edit', $quiz))
            ->assertOk()
            ->assertSee('name="thumbnail_hapus"', false)
            ->assertSee('data-wizard-thumbnail-hapus-flag', false);

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->isianLengkap($pelajaran, [
                'thumbnail_hapus' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $setelah = $quiz->fresh();

        $this->assertNull($setelah->thumbnail);
        Storage::disk('public')->assertMissing($lama);
    }

    /**
     * Galat thumbnail harus selalu siap di DOM: JavaScript menolak berkas
     * yang kebesaran atau formatnya salah lewat elemen itu, dan tampilGalat()
     * diam saja kalau elemennya tidak ada.
     */
    public function test_kolom_galat_thumbnail_selalu_dirender_meski_kosong(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $this->actingAs($user)
            ->get('/user/quiz/tambah')
            ->assertOk()
            ->assertSee('data-wizard-thumbnail-galat', false);
    }

    public function test_form_ubah_membuka_wizard_dengan_isi_quiz_yang_tersimpan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $user->getKey(),
            'judul' => 'JavaScript Dasar',
            'slug' => 'javascript-dasar',
            'deskripsi' => 'Kuis tentang JavaScript.',
            'tingkat_kesulitan' => Quiz::TINGKAT_SULIT,
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'JS1234',
            'tampilkan_jawaban' => false,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Apa fungsi JavaScript?',
            'pilihan_a' => 'Memberikan interaksi pada web',
            'pilihan_b' => 'Menyimpan basis data',
            'pilihan_c' => 'Menggambar Layout',
            'pilihan_d' => 'Menyiapkan server',
            'jawaban_benar' => 'A',
            'pembahasan' => 'JavaScript membuat halaman web interaktif.',
            'urutan' => 1,
            'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
            'aktif' => true,
        ]);

        $isi = $this->actingAs($user)
            ->get(route('user.quiz.edit', $quiz))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="JavaScript Dasar"', $isi);
        $this->assertStringContainsString('Kuis tentang JavaScript.', $isi);
        $this->assertStringContainsString('Apa fungsi JavaScript?', $isi);
        $this->assertStringContainsString('Memberikan interaksi pada web', $isi);
        $this->assertStringContainsString('value="JS1234"', $isi);

        // Tingkat kesulitan quiz terisi, bukan nilai bawaan.
        $this->assertMatchesRegularExpression(
            '/value="'.preg_quote(Quiz::TINGKAT_SULIT, '/').'" selected/',
            $isi,
        );
    }

    public function test_mengubah_quiz_menyimpan_ulang_kesulitan_jawaban_dan_soalnya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $user->getKey(),
            'judul' => 'Quiz Lama',
            'slug' => 'quiz-lama',
            'deskripsi' => 'Deskripsi lama.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'tampilkan_jawaban' => true,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), $this->isianLengkap($pelajaran, [
                'judul' => 'Quiz Baru',
                'tingkat_kesulitan' => Quiz::TINGKAT_SULIT,
                'tampilkan_jawaban' => 0,
                'soal' => [
                    ['pertanyaan' => 'Soal pengganti', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban_benar' => 'D', 'tingkat_kesulitan' => 'Sulit'],
                ],
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertSessionHasNoErrors();

        $quiz->refresh();

        $this->assertSame('Quiz Baru', $quiz->judul);
        $this->assertSame(Quiz::TINGKAT_SULIT, $quiz->tingkat_kesulitan);
        $this->assertFalse($quiz->tampilkan_jawaban);

        // Soal lama diganti seluruhnya, bukan ditambahkan.
        $this->assertSame(1, $quiz->soal()->count());
        $this->assertSame('Soal pengganti', $quiz->soal()->first()->pertanyaan);
    }

    public function test_quiz_yang_bukan_milik_pengguna_tidak_bisa_diubah(): void
    {
        $siti = $this->buatPengguna('Siti', 'siti@example.com');
        $natania = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $siti->getKey(),
            'judul' => 'Quiz Siti',
            'slug' => 'quiz-siti',
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $this->actingAs($natania)
            ->put(route('user.quiz.update', $quiz), $this->isianLengkap($pelajaran))
            ->assertForbidden();
    }

    public function test_menghapus_quiz_juga_menghapus_thumbnailnya(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/quiz/tambah', $this->isianLengkap($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('sampul.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->where('judul', 'HTML Dasar')->firstOrFail();
        $thumbnail = $quiz->thumbnail;

        Storage::disk('public')->assertExists($thumbnail);

        $this->actingAs($user)
            ->delete(route('user.quiz.destroy', $quiz))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']));

        // Berkas tidak boleh tertinggal sebagai gambar yatim di disk.
        Storage::disk('public')->assertMissing($thumbnail);
        $this->assertDatabaseCount('tb_quiz', 0);
    }
}
