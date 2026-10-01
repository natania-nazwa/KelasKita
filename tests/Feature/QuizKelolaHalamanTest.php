<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use App\Support\DaftarMateriAdmin;
use App\Support\DaftarQuizAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman "Quiz" di area admin: daftar quiz yang sudah terbit, detailnya,
 * form edit, dan hapus.
 *
 * Batas yang dijaga test di sini sama persis dengan yang dijaga
 * MateriKelolaHalamanTest untuk halaman Materi, karena kedua halaman itu
 * sengaja ditiru satu sama lain: hanya konten yang sudah terbit, tanpa
 * keputusan persetujuan, dengan detail yang memakai komponen tampilan milik
 * pengguna. Dua hal itu yang paling mudah rusak diam-diam kalau ada yang
 * mengubah salah satu sisinya saja.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml).
 */
class QuizKelolaHalamanTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

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

    private function buatQuiz(
        ?User $pemilik,
        string $status,
        string $judul = 'Quiz Uji',
        ?Pelajaran $pelajaran = null,
        ?string $terbitPada = null,
    ): Quiz {
        $this->urutan++;

        $quiz = Quiz::create([
            'pelajaran_id' => ($pelajaran ?? $this->buatPelajaran())->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value().'-'.$this->urutan,
            'deskripsi' => 'Ringkasan quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'durasi' => 15,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
            'dipublish_pada' => $terbitPada,
        ]);

        $this->buatSoal($quiz);

        return $quiz;
    }

    private function buatTerbit(
        ?User $pemilik,
        string $judul = 'Quiz Terbit',
        ?Pelajaran $pelajaran = null,
        ?string $terbitPada = null,
    ): Quiz {
        return $this->buatQuiz(
            $pemilik,
            Quiz::STATUS_PUBLISHED,
            $judul,
            $pelajaran,
            $terbitPada ?? now()->toDateTimeString(),
        );
    }

    private function buatSoal(Quiz $quiz, int $urutan = 1): Soal
    {
        return $quiz->soal()->create([
            'pertanyaan' => "Pertanyaan nomor $urutan",
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
     * @return array<string, mixed>
     */
    private function dataForm(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'judul' => 'Quiz Diperbarui',
            'deskripsi' => 'Deskripsi quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'tampilkan_jawaban' => '1',
            'soal' => [
                ['pertanyaan' => 'Apa itu variabel?', 'pilihan' => ['A' => 'Penyimpan nilai', 'B' => 'Label gambar'], 'benar' => ['A'], 'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH],
            ],
        ], $tambahan);
    }

    /*
     * =============================================================
     * BATAS HALAMAN: HANYA QUIZ YANG SUDAH TERBIT
     * =============================================================
     */

    public function test_halaman_quiz_hanya_menampilkan_quiz_yang_sudah_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatQuiz($pemilik, Quiz::STATUS_PUBLISHED, 'Quiz Terbit');
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Menunggu');
        $this->buatQuiz($pemilik, Quiz::STATUS_REJECTED, 'Quiz Ditolak');
        $this->buatQuiz($pemilik, Quiz::STATUS_DRAFT, 'Quiz Draft');

        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertSee('Quiz Terbit')
            ->assertDontSee('Quiz Menunggu')
            ->assertDontSee('Quiz Ditolak')
            ->assertDontSee('Quiz Draft');
    }

    public function test_halaman_quiz_tidak_menampilkan_keputusan_verifikasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        /*
         * Setujui/Tolak milik halaman Verifikasi — sama seperti materi. Halaman
         * Quiz tidak pernah punya salah satunya, jadi tidak boleh muncul di
         * sini meski ada quiz yang statusnya bukan published.
         */
        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertDontSee('Menunggu Verifikasi')
            ->assertDontSee('Tinjau');
    }

    public function test_hero_menjelaskan_bahwa_halaman_ini_untuk_quiz_terbit(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertSee('Kelola quiz yang telah dipublikasikan');
    }

    /*
     * =============================================================
     * KESAMAAN DENGAN HALAMAN MATERI
     * =============================================================
     *
     * Yang diuji di sini bukan tampilan per se, tapi bahwa kedua halaman
     * benar-benar memakai satu kerangka yang sama. Kalau keduanya nanti
     * memakai kelas CSS berbeda, halaman yang satu akan terlihat seperti
     * aplikasi yang berbeda — persis yang tidak diinginkan.
     */

    public function test_kedua_halaman_memakai_kerangka_dan_kelas_yang_sama(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Terbit');
        $this->buatMateri($pemilik, 'Materi Terbit');

        $quiz = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();
        $materi = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();

        foreach ([
            'ad-hero--konten',
            'ad-hero-konten__susun',
            'ad-hero-konten__teks',
            'ad-hero-konten__judul',
            'ad-hero-konten__sub',
            'ad-hero-konten__gambar',
            'ad-kartu ad-alat-kotak',
            'ad-alat-baris__field',
            'ad-alat-baris__aksi',
            'ad-daftar-kartu',
            'ad-kartu-daftar',
            'ad-kartu-daftar__gambar',
            'ad-kartu-daftar__badan',
            'ad-kartu-daftar__aksen',
            'ad-kartu-daftar__judul',
            'ad-kartu-daftar__pembuat',
            'ad-kartu-daftar__meta',
            'ad-kartu-daftar__tanggal',
            'ad-kartu-daftar__kaki',
            'ad-kartu-daftar__aksi',
            'ad-titik__menu',
            'ad-paginasi',
            'data-dialog-hapus',
        ] as $kelas) {
            $this->assertStringContainsString($kelas, $quiz, "Kelas {$kelas} tidak ada di halaman Quiz.");
            $this->assertStringContainsString($kelas, $materi, "Kelas {$kelas} tidak ada di halaman Materi.");
        }
    }

    public function test_teks_hero_dan_gambarnya_berdampingan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertStringContainsString('ad-hero-konten__gambar', $html);
        $this->assertStringContainsString(asset('images/cover.png'), $html);

        $this->assertLessThan(
            strpos($html, 'ad-hero-konten__gambar'),
            strpos($html, 'Kelola quiz yang telah dipublikasikan'),
            'Gambar harus di sebelah kanan teks, bukan di atas atau di kiri.',
        );

        // Murni dekoratif: alt kosong, disembunyikan dari pembaca layar.
        $this->assertSame(1, preg_match('/<img[^>]*ad-hero-konten__gambar[^>]*>/', $html, $tag));
        $this->assertStringContainsString('alt=""', $tag[0]);
        $this->assertStringContainsString('aria-hidden="true"', $tag[0]);
    }

    public function test_gambar_hero_terkunci_dari_dua_sisi_agar_tidak_mendorong_judul(): void
    {
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-hero-konten__gambar\s*\{([^}]*)\}/', $css, $cocok));

        $aturan = $this->tanpaKomentar($cocok[1]);

        $this->assertMatchesRegularExpression('/width:\s*[\d.]+rem/', $aturan);
        $this->assertMatchesRegularExpression('/height:\s*[\d.]+rem/', $aturan);
        $this->assertStringContainsString('object-fit: contain', $aturan);
        $this->assertStringContainsString('flex-shrink: 0', $aturan);
    }

    /*
     * =============================================================
     * PENCARIAN DAN FILTER
     * =============================================================
     */

    public function test_pencarian_menemukan_quiz_lewat_judul(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Katakana');
        $this->buatTerbit($pemilik, 'Quiz Lain');

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'Katakana']))
            ->assertOk()
            ->assertSee('Quiz Katakana')
            ->assertDontSee('Quiz Lain');
    }

    public function test_pencarian_menemukan_quiz_lewat_nama_pembuat(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(['nama' => 'Natania', 'email' => 'natania@example.com']), 'Quiz Satu');
        $this->buatTerbit($this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']), 'Quiz Dua');

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'Natania']))
            ->assertOk()
            ->assertSee('Quiz Satu')
            ->assertDontSee('Quiz Dua');
    }

    public function test_pencarian_menemukan_quiz_lewat_kategori(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Satu', $this->buatPelajaran('Teknologi', 'teknologi'));
        $this->buatTerbit($pemilik, 'Quiz Dua', $this->buatPelajaran('Matematika', 'matematika'));

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'Teknologi']))
            ->assertOk()
            ->assertSee('Quiz Satu')
            ->assertDontSee('Quiz Dua');
    }

    public function test_filter_kategori_mengerucutkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Satu', $this->buatPelajaran('Teknologi', 'teknologi'));
        $this->buatTerbit($pemilik, 'Quiz Dua', $this->buatPelajaran('Matematika', 'matematika'));

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['kategori' => 'teknologi']))
            ->assertOk()
            ->assertSee('Quiz Satu')
            ->assertDontSee('Quiz Dua');
    }

    public function test_filter_kategori_tidak_menggabungkan_dengan_pencarian(): void
    {
        /*
         * Pencarian dan filter harus saling mengunci, bukan saling menimpa:
         * mengetik kata kunci di satu kategori tidak boleh membuat "atau" ikut
         * meloloskan quiz dari kategori lain. Ini penjaga yang sama dengan di
         * halaman Materi, dan paling mudah rusak kalau salah satu dari kedua
         * query dibungkus ulang.
         */
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $teknologi = $this->buatPelajaran('Teknologi', 'teknologi');
        $matematika = $this->buatPelajaran('Matematika', 'matematika');

        $this->buatTerbit($pemilik, 'Quiz Sepakat', $teknologi);
        $this->buatTerbit($pemilik, 'Quiz Sepakat Lain', $matematika);
        $this->buatTerbit($pemilik, 'Quiz Berbeda', $teknologi);

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'Sepakat', 'kategori' => 'teknologi']))
            ->assertOk()
            ->assertSee('Quiz Sepakat')
            ->assertDontSee('Quiz Sepakat Lain')
            ->assertDontSee('Quiz Berbeda');
    }

    public function test_filter_pembuat_mengerucutkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $satu = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $dua = $this->buatPengguna(['nama' => 'Andi', 'email' => 'andi@example.com']);
        $this->buatTerbit($satu, 'Quiz Siti');
        $this->buatTerbit($dua, 'Quiz Andi');

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['pembuat' => $satu->getKey()]))
            ->assertOk()
            ->assertSee('Quiz Siti')
            ->assertDontSee('Quiz Andi');
    }

    public function test_urutan_paling_lama_menampilkan_quiz_terlama_dulu(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Lama', null, now()->subDays(5)->toDateTimeString());
        $this->buatTerbit($pemilik, 'Quiz Baru', null, now()->toDateTimeString());

        $halaman = $this->actingAs($admin)
            ->get(route('admin.quiz', ['urut' => 'terlama']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(strpos($halaman, 'Quiz Baru'), strpos($halaman, 'Quiz Lama'));
    }

    public function test_urutan_tak_dikenal_tidak_membuat_halaman_kosong(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit');

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['urut' => 'ngawur']))
            ->assertOk()
            ->assertSee('Quiz Terbit');
    }

    public function test_urutan_hanya_ada_terbaru_dan_terlama(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'value="terbaru"'));
        $this->assertSame(1, substr_count($html, 'value="terlama"'));
    }

    public function test_pencarian_dan_ketiga_filter_tampil_tanpa_hanya_dukungan_javascript(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit', $this->buatPelajaran('Teknologi', 'teknologi'));

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        // Satu form GET untuk ketiga filter sekaligus.
        $this->assertStringContainsString('method="GET"', $html);
        $this->assertStringContainsString(route('admin.quiz'), $html);

        // Tiap select punya label yang bisa dibaca pembaca layar, walau
        // labelnya disembunyikan karena teks select sudah menyebut apa yang
        // disaring.
        foreach (['saring-kategori', 'saring-urut', 'saring-pembuat'] as $id) {
            $this->assertStringContainsString('for="'.$id.'"', $html);
        }

        // Tombol "Terapkan" mengirim form; tanpa JavaScript filter tetap jalan.
        $this->assertStringContainsString('Terapkan', $html);
    }

    public function test_kolom_cari_ada_di_kartu_filter_dan_juga_di_topbar(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Katakana');
        $this->buatTerbit($pemilik, 'Quiz Lain');

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        /*
         * Dua tempat mencari, sengaja. Dulu kolom di kartu filter dihapus
         * karena dianggap kembar dengan topbar, dan hasilnya satu-satunya
         * tempat mencari jadi kotak kecil di layar atas yang menulis "Cari
         * materi, quiz, pengguna" padahal isinya cuma satu daftar.
         */
        $this->assertStringContainsString('id="cari-quiz"', $html);
        $this->assertStringContainsString('placeholder="Cari quiz..."', $html);

        // Topbar-nya ikut mencari quiz, dan tidak lagi menjanjikan pencarian
        // global yang memang tidak ada di aplikasi ini.
        $this->assertStringContainsString('id="cari-ad"', $html);
        $this->assertStringContainsString('action="'.route('admin.quiz').'"', $html);
        $this->assertStringNotContainsString('Cari materi, quiz, pengguna', $html);

        // Form filter tetap GET ke halaman ini, jadi Enter di kolom cari
        // langsung menyaring.
        $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'Katakana']))
            ->assertOk()
            ->assertSee('Quiz Katakana')
            ->assertDontSee('Quiz Lain');
    }

    public function test_topbar_menulis_jujur_soal_yang_benar_benar_dicari(): void
    {
        $admin = $this->buatAdmin();

        // Di halaman Materi, topbar mencari materi.
        $materi = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();
        $this->assertStringContainsString('placeholder="Cari materi..."', $materi);
        $this->assertStringContainsString('action="'.route('admin.materi').'"', $materi);

        // Di halaman Quiz, topbar mencari quiz.
        $quiz = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();
        $this->assertStringContainsString('placeholder="Cari quiz..."', $quiz);
        $this->assertStringContainsString('action="'.route('admin.quiz').'"', $quiz);

        // Di halaman lain, topbar memakai default Materi dan tetap jujur.
        $lain = $this->actingAs($admin)->get('/admin/pengaturan')->assertOk()->getContent();
        $this->assertStringContainsString('placeholder="Cari materi..."', $lain);
        $this->assertStringNotContainsString('placeholder="Cari quiz..."', $lain);
    }

    public function test_kata_kunci_aktif_tetap_ikut_dibawa_saat_menyaring(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatTerbit($pemilik, 'Quiz Sepakat', $this->buatPelajaran('Teknologi', 'teknologi'));

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'Sepakat']))
            ->assertOk()
            ->getContent();

        // Kolomnya terlihat dan sudah terisi, jadi menyaring kategori tidak
        // menghapus kata kunci yang sedang aktif. Tidak ada lagi input
        // tersembunyi untuk "q": input yang terlihat itulah yang mengirimnya.
        $this->assertStringContainsString('<input id="cari-quiz" name="q" type="search" value="Sepakat"', $html);
        $this->assertStringNotContainsString('type="hidden" name="q"', $html);
    }

    public function test_hapus_filter_aktif_khwa_saring_tidak_ada(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    public function test_kategori_tanpa_quiz_tidak_ditawarkan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran('Kosong', 'kosong');
        $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit', $this->buatPelajaran('Teknologi', 'teknologi'));

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        // Kategori tanpa quiz published tidak boleh muncul sebagai pilihan,
        // supaya memilihnya tidak pernah menghasilkan daftar kosong tanpa
        // alasan yang terlihat.
        $this->assertStringNotContainsString('value="kosong"', $html);
        $this->assertStringContainsString('value="teknologi"', $html);
    }

    public function test_total_kategori_menghitung_semua_quiz_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $this->buatPelajaran('Matematika', 'matematika');
        $this->buatTerbit($pemilik, 'Quiz Satu', $this->buatPelajaran('Teknologi', 'teknologi'));
        $this->buatTerbit($pemilik, 'Quiz Dua', $this->buatPelajaran('Teknologi', 'teknologi'));
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Menunggu', $this->buatPelajaran('Teknologi', 'teknologi'));

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        // Dua quiz terbit, satu masih menunggu — yang menunggu tidak dihitung.
        $this->assertStringContainsString('Semua kategori (2)', $html);
    }

    /*
     * =============================================================
     * EMPTY STATE
     * =============================================================
     */

    public function test_empty_state_muncul_saat_belum_ada_quiz_terbit(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertSee('Belum ada quiz')
            ->assertSee('Quiz yang telah disetujui akan muncul di sini.');
    }

    public function test_empty_state_muncul_saat_pencarian_tidak_mencocokkan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna());

        $this->actingAs($admin)
            ->get(route('admin.quiz', ['q' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Quiz tidak ditemukan')
            ->assertSee('Coba gunakan kata kunci yang berbeda.');
    }

    public function test_quiz_yang_belum_terbit_tidak_mengubah_empty_state(): void
    {
        $admin = $this->buatAdmin();
        $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING, 'Quiz Menunggu');

        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertSee('Belum ada quiz')
            ->assertDontSee('Quiz Menunggu');
    }

    /*
     * =============================================================
     * BENTUK KARTU
     * =============================================================
     */

    public function test_kartu_vertikal_dengan_blok_gambar_di_atas(): void
    {
        $admin = $this->buatAdmin();
        $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit');

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $gambar = strpos($html, 'ad-kartu-daftar__gambar');
        $badan = strpos($html, 'ad-kartu-daftar__badan');

        $this->assertIsInt($gambar);
        $this->assertIsInt($badan);
        $this->assertLessThan($badan, $gambar, 'Blok gambar harus di atas badan kartu.');
    }

    public function test_kartu_menampilkan_kategori_soal_dan_pembuat(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit(
            $this->buatPengguna(['nama' => 'Natania', 'email' => 'natania@example.com']),
            'Quiz Uji Kartu',
            $this->buatPelajaran('Teknologi', 'teknologi'),
        );

        $this->buatSoal($quiz, 2);

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertStringContainsString('Teknologi', $html);
        $this->assertStringContainsString('Dibuat oleh:', $html);
        $this->assertStringContainsString('Natania', $html);

        // Dua soal aktif, dan keduanya dihitung tanpa query per baris.
        $this->assertStringContainsString('2 Soal', $html);
    }

    public function test_kartu_menampilkan_durasi_yang_bukan_angka_nol(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit');
        $quiz->forceFill(['durasi' => null])->save();

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        // Durasi kosong berarti tanpa batas waktu, bukan "0 menit".
        $this->assertStringContainsString('Tanpa batas waktu', $html);
        $this->assertStringNotContainsString('0 menit', $html);
    }

    public function test_daftar_menampilkan_kartu_dengan_aksi_lihat(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit');

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.quiz.show', $quiz), $html);
        $this->assertStringContainsString('ad-titik__menu', $html);
    }

    public function test_kaki_kartu_boleh_membungkus_janga_neluber(): void
    {
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-kartu-daftar__kaki\s*\{([^}]*)\}/', $css, $cocok));

        $aturan = $this->tanpaKomentar($cocok[1]);

        $this->assertStringContainsString('flex-wrap: wrap', $aturan);
        $this->assertStringContainsString('margin-top: auto', $aturan);
    }

    public function test_empty_state_membentang_penuh_satu_baris(): void
    {
        $css = file_get_contents(resource_path('css/admin.css'));

        // Tanpa grid-column, pesan "belum ada data" hanya mengisi satu kolom
        // dan terbaca sebagai kartu sempit, bukan sebagai daftar yang kosong.
        $this->assertSame(1, preg_match('/\.ad-daftar-kartu\s*>\s*\.ad-kosong\s*\{([^}]*)\}/', $css, $cocok));
        $this->assertStringContainsString('grid-column: 1 / -1', $this->tanpaKomentar($cocok[1]));
    }

    public function test_gambar_hero_yang_lebih_lebar_dipotong_bukan_dikecilkan(): void
    {
        /*
         * cover.png (828x552) di kotak hero bujur sangkar akan mengecil
         * hanya jadi dua pertiga tinggi kotaknya kalau object-fit-nya
         * contain, dan hero-nya jadi terlihat kosong di kanan. Modifier
         * --penuh membuatnya mengisi kotak persis seperti buku.png, supaya
         * bobot visual kedua halaman sama.
         */
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-hero-konten__gambar--penuh\s*\{([^}]*)\}/', $css, $cocok));
        $this->assertStringContainsString('object-fit: cover', $this->tanpaKomentar($cocok[1]));

        $admin = $this->buatAdmin();

        $halaman = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();
        $this->assertStringContainsString('ad-hero-konten__gambar--penuh', $halaman);

        // Halaman Materi tidak memakainya: gambarnya sudah bujur sangkar.
        $materi = $this->actingAs($admin)->get('/admin/materi')->assertOk()->getContent();
        $this->assertStringNotContainsString('ad-hero-konten__gambar--penuh', $materi);
    }

    public function test_kartu_tidak_memotong_menu_tiga_titik(): void
    {
        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertSame(1, preg_match('/\.ad-kartu-daftar\s*\{([^}]*)\}/', $css, $kartu));

        // overflow: hidden akan memotong menu tiga titik tepat di tepi kartu.
        $this->assertStringNotContainsString('overflow: hidden', $this->tanpaKomentar($kartu[1]));

        // Pembulatan sudut kartu ditangani blok gambarnya sendiri.
        $this->assertSame(1, preg_match('/\.ad-kartu-daftar__gambar\s*\{([^}]*)\}/', $css, $gambar));
        $this->assertStringContainsString('border-radius: 1.05rem 1.05rem 0 0', $this->tanpaKomentar($gambar[1]));
    }

    public function test_paginasi_tanpa_kartu_putih_dan_tanpa_jumlah_data(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        for ($i = 1; $i <= 21; $i++) {
            $this->buatTerbit($pemilik, 'Quiz Paginasi '.$i);
        }

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertStringNotContainsString('Menampilkan', $html);
        $this->assertStringContainsString('ad-paginasi', $html);
    }

    public function test_daftar_dipaginasi_dua_puluh_per_halaman(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        // Tanggal terbit berbeda-beda: kalau semuanya now() dalam detik yang
        // sama, urutannya seri dan kartu bisa mendarat di halaman mana saja.
        for ($i = 1; $i <= 21; $i++) {
            $this->buatTerbit(
                $pemilik,
                'Quiz Paginasi '.$i,
                null,
                now()->subMinutes(22 - $i)->toDateTimeString(),
            );
        }

        /*
         * Jumlah kartu dihitung, bukan judulnya: "Quiz Paginasi 2" adalah
         * awalan dari "Quiz Paginasi 21", jadi assertsSee pada nomor akan
         * selalu cocok begitu saja. Yang benar-benar diuji di sini adalah
         * batas 20 per halaman.
         */
        $halamanSatu = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();
        $halamanDua = $this->actingAs($admin)
            ->get(route('admin.quiz', ['page' => 2]))
            ->assertOk()
            ->getContent();

        $this->assertSame(20, substr_count($halamanSatu, 'ad-kartu-daftar__judul'));
        $this->assertSame(1, substr_count($halamanDua, 'ad-kartu-daftar__judul'));

        // Yang terbaru di halaman pertama, yang terlama di halaman kedua.
        $this->assertStringContainsString('Quiz Paginasi 21', $halamanSatu);
        $this->assertStringNotContainsString('Quiz Paginasi 1"', $halamanSatu);
        $this->assertStringContainsString('Quiz Paginasi 1"', $halamanDua);
        $this->assertStringNotContainsString('Quiz Paginasi 2"', $halamanDua);
    }

    public function test_jumlah_kartu_per_halaman_sama_dengan_halaman_materi(): void
    {
        /*
         * 20 = 5 baris penuh pada grid empat kolom, sama seperti
         * DaftarMateriAdmin::perHalaman() di halaman Materi.
         *
         * Tidak disamakan dengan DaftarQuiz::perHalaman() (12): halaman Quiz
         * milik pengguna memakai grid tiga kolom, sedangkan dua halaman admin
         * sengaja memakai grid empat kolom yang sama satu sama lain.
         */
        $this->assertSame(20, DaftarQuizAdmin::perHalaman());
        $this->assertSame(DaftarMateriAdmin::perHalaman(), DaftarQuizAdmin::perHalaman());
        $this->assertSame(20 % 4, 0);
    }

    /*
     * =============================================================
     * TOMBOL EDIT: HANYA UNTUK QUIZ BUATAN ADMIN SENDIRI
     * =============================================================
     */

    public function test_menu_edit_muncul_untuk_quiz_yang_dibuat_admin(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin, 'Quiz Buatan Admin');

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.quiz.edit', $quiz), $html);
    }

    public function test_menu_edit_tidak_muncul_untuk_quiz_buatan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Buatan Pengguna');

        $html = $this->actingAs($admin)->get('/admin/quiz')->assertOk()->getContent();

        // Menu masih ada, tapi isinya hanya Lihat dan Hapus.
        $this->assertStringNotContainsString(route('admin.quiz.edit', $quiz), $html);
        $this->assertStringContainsString(route('admin.quiz.show', $quiz), $html);
        $this->assertStringContainsString(route('admin.quiz.destroy', $quiz), $html);
    }

    /*
     * =============================================================
     * DETAIL: PAKAI KOMPONEN TAMPILAN MILIK PENGGUNA
     * =============================================================
     */

    public function test_detail_dapat_dibuka_untuk_quiz_dari_semua_status(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();

        foreach ([
            Quiz::STATUS_PENDING,
            Quiz::STATUS_PUBLISHED,
            Quiz::STATUS_REJECTED,
            Quiz::STATUS_DRAFT,
        ] as $status) {
            $quiz = $this->buatQuiz($pemilik, $status, 'Quiz-'.$status);

            $this->actingAs($admin)
                ->get(route('admin.quiz.show', $quiz))
                ->assertOk()
                ->assertSee('Quiz-'.$status);
        }
    }

    public function test_detail_memakai_komponen_yang_sama_dengan_halaman_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Uji Detail');
        $this->buatSoal($quiz, 2);

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz.show', $quiz))
            ->assertOk()
            ->getContent();

        // Komponen yang sama: kartu utama, kartu informasi, daftar soal.
        $this->assertStringContainsString('detail-quiz__kartu', $html);
        $this->assertStringContainsString('info-quiz', $html);
        $this->assertStringContainsString('daftar-soal', $html);

        // Isinya sama persis: metadata dan kedua soalnya ikut tampil.
        $this->assertStringContainsString('Informasi Quiz', $html);
        $this->assertStringContainsString('Jumlah Soal', $html);
        $this->assertStringContainsString('Pertanyaan nomor 1', $html);
        $this->assertStringContainsString('Pertanyaan nomor 2', $html);
    }

    public function test_detail_tidak_membocorkan_jawaban_maupun_pembahasan(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Uji Detail');

        $soal = $quiz->soal()->first();
        $soal->forceFill(['pembahasan' => 'Kunci pembahasannya soal ini.'])->save();

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz.show', $quiz))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Kunci pembahasannya', $html);
        $this->assertStringNotContainsString('Pilihan A', $html);
    }

    public function test_detail_tidak_menampilkan_aksi_yang_milik_pembaca(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin, 'Quiz Terbit');

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz.show', $quiz))
            ->assertOk()
            ->getContent();

        // Admin tidak akan mengerjakan, membagikan, atau membuka sesi quiz ini.
        $this->assertStringNotContainsString('Mulai Quiz', $html);
        $this->assertStringNotContainsString('Buka Sesi', $html);
        $this->assertStringNotContainsString('data-bagikan-buka', $html);
    }

    public function test_detail_admin_hanya_menampilkan_kembali_ke_daftar_quiz(): void
    {
        /*
         * Halaman detail hanya punya tombol kembali. Form edit disalakan dari
         * menu tiga titik pada kartu di daftar — di sana letaknya berdampingan
         * dengan Hapus — jadi di sini tidak ada jalan kedua untuk mengelola.
         */
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Terbit');

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz.show', $quiz))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Kembali ke Quiz', $html);
        $this->assertStringContainsString(route('admin.quiz'), $html);

        $this->assertStringNotContainsString(route('admin.quiz.edit', $quiz), $html);
        $this->assertStringNotContainsString('Edit Quiz', $html);

        // Tautan "Lihat semua" di Daftar Soal milik halaman pengguna, dan
        // admin/quiz tidak punya daftar yang bisa ditunjuknya.
        $this->assertStringNotContainsString('Lihat semua', $html);
    }

    /*
     * =============================================================
     * EDIT DAN HAPUS
     * =============================================================
     */

    public function test_admin_bisa_membuka_form_edit_quiz_yang_dibuatnya_sendiri(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin, 'Quiz Buatan Admin');

        $this->actingAs($admin)
            ->get(route('admin.quiz.edit', $quiz))
            ->assertOk()
            ->assertSee('Edit Quiz')
            ->assertSee('Quiz Buatan Admin')
            // Form milik pemilik menyebut "Ajukan Persetujuan"; di sini tidak.
            ->assertDontSee('Ajukan Persetujuan')
            ->assertDontSee('Catatan pendukung');
    }

    public function test_form_edit_memakai_wizard_yang_sama_dengan_form_pemilik(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin, 'Quiz Buatan Admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz.edit', $quiz))
            ->assertOk()
            ->getContent();

        // Satu form untuk ketiga langkah, dan builder soal yang sama.
        $this->assertStringContainsString('data-wizard-quiz', $html);
        $this->assertStringContainsString('data-wizard-form', $html);
        $this->assertSame(3, substr_count($html, 'data-wizard-panel='));
        $this->assertStringContainsString('data-builder-awal', $html);
        $this->assertStringContainsString(route('admin.quiz.update', $quiz), $html);

        // Soal yang sudah tersimpan ikut terbawa ke builder.
        $this->assertStringContainsString('Pertanyaan nomor 1', $html);
    }

    public function test_form_edit_mengarahkan_batal_ke_daftar_quiz_bukan_karya_saya(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin, 'Quiz Buatan Admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.quiz.edit', $quiz))
            ->assertOk()
            ->getContent();

        preg_match_all('/<a[^>]*data-wizard-batal[^>]*>/', $html, $cocok);

        $this->assertNotEmpty($cocok[0]);

        foreach ($cocok[0] as $tombol) {
            $this->assertStringContainsString(route('admin.quiz'), $tombol);
        }

        // Teks tombol yang mengirim form lebih awal berarti "simpan", bukan
        // "simpan sebagai draft".
        $this->assertStringContainsString('Simpan Perubahan', $html);
        $this->assertStringNotContainsString('>Draft<', $html);
    }

    public function test_admin_tidak_bisa_mengedit_quiz_buatan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Buatan Pengguna');

        // Menu-nya tidak menampilkan Edit, dan URL yang diketik manual
        // ditolak dengan aturan yang sama.
        $this->actingAs($admin)
            ->get(route('admin.quiz.edit', $quiz))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.quiz.update', $quiz), $this->dataForm($this->buatPelajaran()))
            ->assertForbidden();

        $this->assertSame('Quiz Buatan Pengguna', $quiz->refresh()->judul);
    }

    public function test_admin_bisa_memperbarui_quiz_dan_statusnya_tetap_terbit(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatTerbit($admin, 'Judul Lama');
        $terbitPada = $quiz->dipublish_pada;

        $this->actingAs($admin)
            ->put(route('admin.quiz.update', $quiz), $this->dataForm($pelajaran, [
                'judul' => 'Judul Baru',
                'durasi' => 30,
            ]))
            ->assertRedirect(route('admin.quiz.show', $quiz));

        $quiz->refresh();

        $this->assertSame('Judul Baru', $quiz->judul);
        $this->assertSame(Quiz::TINGKAT_SEDANG, $quiz->tingkat_kesulitan);
        $this->assertSame(30, $quiz->durasi);

        // Berbeda dari revisi pemilik, suntingan admin tidak menarik quiz dari
        // daftar dan tidak menggeser tanggal terbitnya.
        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
        $this->assertTrue($terbitPada->equalTo($quiz->dipublish_pada));

        // Soalnya ikut tergantikan, sesuai isian yang dikirim.
        $this->assertSame('Apa itu variabel?', $quiz->soal()->first()->pertanyaan);

        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertSee('Judul Baru');
    }

    public function test_update_menolak_isian_tidak_valid(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin, 'Judul Lama');

        $this->actingAs($admin)
            ->put(route('admin.quiz.update', $quiz), $this->dataForm($this->buatPelajaran(), [
                'judul' => '',
                'soal' => [],
            ]))
            ->assertSessionHasErrors(['judul', 'soal']);

        $this->assertSame('Judul Lama', $quiz->refresh()->judul);
    }

    public function test_quiz_yang_diubah_admin_ke_mode_kode_ditarik_dari_daftar(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatTerbit($admin, 'Akan Jadi Kode');

        $this->actingAs($admin)
            ->put(route('admin.quiz.update', $quiz), $this->dataForm($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'KODE01',
            ]))
            ->assertRedirect(route('admin.quiz.show', $quiz))
            ->assertSessionHas('sukses');

        // Mode kode tidak tayang untuk semua pengguna, jadi tidak boleh tetap
        // published di halaman Quiz.
        $this->assertSame(Quiz::STATUS_DRAFT, $quiz->refresh()->status);

        $this->actingAs($admin)
            ->get('/admin/quiz')
            ->assertOk()
            ->assertDontSee('Akan Jadi Kode');
    }

    public function test_admin_bisa_menghapus_quiz_buatan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Dihapus');

        $this->actingAs($admin)
            ->delete(route('admin.quiz.destroy', $quiz))
            ->assertRedirect(route('admin.quiz'));

        $this->assertDatabaseMissing('tb_quiz', ['id' => $quiz->getKey()]);
    }

    public function test_menghapus_quiz_ikut_membersihkan_soalnya(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($this->buatPengguna(), 'Quiz Dihapus');
        $soal = $this->buatSoal($quiz, 2);

        $this->actingAs($admin)
            ->delete(route('admin.quiz.destroy', $quiz))
            ->assertRedirect(route('admin.quiz'));

        // Tidak boleh tertinggal sebagai baris yatim.
        $this->assertDatabaseMissing('tb_soal', ['id' => $soal->getKey()]);
        $this->assertDatabaseCount('tb_soal', 0);
    }

    public function test_aksi_kelola_menolak_quiz_yang_tidak_ada(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin);

        $this->actingAs($admin)->get(route('admin.quiz.show', $quiz->getKey() + 999))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.quiz.edit', $quiz->getKey() + 999))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.quiz.destroy', $quiz->getKey() + 999))->assertNotFound();
    }

    /*
     * =============================================================
     * AKSES
     * =============================================================
     */

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_kelola_dan_aksinya(): void
    {
        $user = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $quiz = $this->buatTerbit($admin);

        $this->actingAs($user)->get('/admin/quiz')->assertForbidden();
        $this->actingAs($user)->get(route('admin.quiz.show', $quiz))->assertForbidden();
        $this->actingAs($user)->get(route('admin.quiz.edit', $quiz))->assertForbidden();
        $this->actingAs($user)->put(route('admin.quiz.update', $quiz), $this->dataForm($this->buatPelajaran()))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.quiz.destroy', $quiz))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login_pada_halaman_kelola_dan_aksinya(): void
    {
        // tb_quiz.dibuat_oleh tidak boleh kosong, jadi quiznya harus punya
        // pemilik — di sini pemilik(admin) yang buat, supaya yang diuji benar-
        // benar cuma middleware "auth", bukan penjagaan kepemilikan.
        $quiz = $this->buatTerbit($this->buatAdmin());

        $this->get('/admin/quiz')->assertRedirect(route('login'));
        $this->get(route('admin.quiz.show', $quiz))->assertRedirect(route('login'));
        $this->get(route('admin.quiz.edit', $quiz))->assertRedirect(route('login'));
        $this->delete(route('admin.quiz.destroy', $quiz))->assertRedirect(route('login'));
    }

    /*
     * =============================================================
     * BANTUAN
     * =============================================================
     */

    private function buatMateri(?User $pemilik, string $nama): void
    {
        Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-materi',
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi untuk pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
            'jumlah_dilihat' => 0,
            'dipublish_pada' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Isi blok CSS tanpa komentar di dalamnya, supaya assertion di atas tidak
     * ikut cocok dengan angka yang kebetulan tertulis pada komentar.
     */
    private function tanpaKomentar(string $aturan): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $aturan);
    }
}
