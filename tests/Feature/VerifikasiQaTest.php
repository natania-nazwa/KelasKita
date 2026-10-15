<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA fungsional menu "Verifikasi" milik admin.
 *
 * Tiap test mewakili satu janji fitur yang tertulis di UI atau di komentar
 * kode: tab, filter, pencarian, paginasi, panel review, dan keputusan.
 */
class VerifikasiQaTest extends TestCase
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

    private function buatPelajaran(string $nama, string $slug): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => $slug], [
            'nama' => $nama,
            'deskripsi' => "Deskripsi $nama",
            'aktif' => true,
        ]);
    }

    private function buatMateri(?User $pemilik, string $status, string $nama, string $slugPelajaran = 'matematika'): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran('Matematika', $slugPelajaran)->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.Materi::query()->count(),
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    private function buatQuiz(?User $pemilik, string $status, string $judul, string $slugPelajaran = 'matematika'): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran('Matematika', $slugPelajaran)->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value().'-'.Quiz::query()->count(),
            'deskripsi' => 'Ringkasan quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);
    }

    /**
     * Angka di tab jenis, sesuai urutan tampilnya di halaman:
     * "Semua" (0), "Materi" (1), "Quiz" (2).
     */
    private function jumlahTab(string $html, int $urutan = 0): ?int
    {
        preg_match_all('/<span class="ad-tab__jumlah">(\d+)<\/span>/', $html, $cocok);

        return isset($cocok[1][$urutan]) ? (int) $cocok[1][$urutan] : null;
    }

    /**
     * Angka ketiga pil status, dikunci sesuai nilai pilnya.
     *
     * @return array<string, int>
     */
    private function jumlahPil(string $html): array
    {
        preg_match_all('/class="ad-vf-pil ad-vf-pil--(\w+)[^"]*"[^>]*>.*?<b>(\d+)<\/b>/s', $html, $cocok, PREG_SET_ORDER);

        $hasil = [];

        foreach ($cocok as $butir) {
            $hasil[$butir[1]] = (int) $butir[2];
        }

        return $hasil;
    }

    /*
     * ====================================================================
     * TAB JENIS & TAB STATUS
     * ====================================================================
     */

    public function test_tab_jenis_materi_hanya_menampilkan_materi(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Antre');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['jenis' => 'materi']))
            ->assertOk()
            ->assertSee('Materi Antre')
            ->assertDontSee('Quiz Antre');
    }

    public function test_tab_jenis_quiz_hanya_menampilkan_quiz(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Antre');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['jenis' => 'quiz']))
            ->assertOk()
            ->assertSee('Quiz Antre')
            ->assertDontSee('Materi Antre');
    }

    public function test_tab_status_disetujui_hanya_menampilkan_konten_published(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Tayang');
        $this->buatMateri($pemilik, Materi::STATUS_REJECTED, 'Materi Gagal');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['status' => 'disetujui']))
            ->assertOk()
            ->assertSee('Materi Tayang')
            ->assertDontSee('Materi Antre')
            ->assertDontSee('Materi Gagal');
    }

    public function test_tab_status_ditolak_hanya_menampilkan_konten_rejected(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $this->buatMateri($pemilik, Materi::STATUS_REJECTED, 'Materi Gagal');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['status' => 'ditolak']))
            ->assertOk()
            ->assertSee('Materi Gagal')
            ->assertDontSee('Materi Antre');
    }

    public function test_query_tidak_dikenal_jatuh_ke_tab_bawaan(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Tayang');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['jenis' => 'aneh', 'status' => 'ngawur']))
            ->assertOk()
            ->assertSee('Materi Antre')
            ->assertDontSee('Materi Tayang');
    }

    /*
     * ====================================================================
     * PENCARIAN
     * ====================================================================
     */

    public function test_pencarian_mencocokkan_judul_materi_dan_quiz(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Katakana Dasar');
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Aljabar Linear');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['q' => 'katakana']))
            ->assertOk()
            ->assertSee('Katakana Dasar')
            ->assertDontSee('Aljabar Linear');
    }

    /**
     * Label filter menjanjikan "Cari judul atau pembuat" dan placeholder-nya
     * berbunyi "...atau pembuat...", jadi nama pembuat harus ikut dicari.
     */
    public function test_pencarian_mencocokkan_nama_pembuat(): void
    {
        $pemilik = $this->buatPengguna([
            'nama' => 'Siti Rahayu',
            'email' => 'siti@example.com',
        ]);
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Kalkulus Integral');
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Trigonometri');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['q' => 'Siti']))
            ->assertOk();

        $this->assertStringContainsString(
            'Kalkulus Integral',
            $respons->getContent(),
            'Pencarian dengan nama pembuat tidak menemukan materi miliknya.'
        );

        $this->assertStringContainsString(
            'Quiz Trigonometri',
            $respons->getContent(),
            'Pencarian dengan nama pembuat tidak menemukan quiz miliknya.'
        );
    }

    /*
     * ====================================================================
     * FILTER KATEGORI
     * ====================================================================
     */

    public function test_filter_kategori_hanya_menampilkan_kategori_terpilih(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Matematika', 'matematika');
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Bahasa', 'bahasa');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['kategori' => 'bahasa']))
            ->assertOk()
            ->assertSee('Materi Bahasa')
            ->assertDontSee('Materi Matematika');
    }

    public function test_filter_kategori_saat_ini_dipertahankan_oleh_tab(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Bahasa', 'bahasa');
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Matematika', 'matematika');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['jenis' => 'materi', 'kategori' => 'bahasa']))
            ->assertOk();

        $this->assertStringContainsString(
            'jenis=materi',
            $respons->getContent(),
            'Tab jenis harus mempertahankan filter kategori yang sedang aktif.'
        );
    }

    /*
     * ====================================================================
     * ANGKA TAB (BADGE)
     * ====================================================================
     */

    /**
     * Angka di tab harus sama dengan isi daftar. Pencarian menyaring daftar,
     * jadi angka tab juga harus ikut menyaring: kalau tidak, admin melihat
     * tab bercap "12" di atas daftar yang hanya berisi "1 konten".
     */
    public function test_angka_tab_ikut_pencarian(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Katakana Dasar');
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Aljabar Linear');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['q' => 'katakana']))
            ->assertOk();

        $this->assertSame(
            1,
            $this->jumlahTab($respons->getContent()),
            'Angka tab "Semua" harus ikut menyaring sesuai pencarian yang aktif.'
        );
    }

    public function test_angka_tab_quiz_ikut_pencarian(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Trigonometri');
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Statistika');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['jenis' => 'quiz', 'q' => 'Statistika']))
            ->assertOk();

        $this->assertSame(
            1,
            $this->jumlahTab($respons->getContent(), 2),
            'Angka tab "Quiz" harus ikut menyaring sesuai pencarian yang aktif.'
        );
    }

    /**
     * Angka di pil status harus benar-benar jumlah baris di tabel, bukan
     * selalu nol. Penerjemahan kunci status ("pending") ke kunci pil
     * ("menunggu") pernah dilewatkan, jadi ketiga pil tertulis "0"
     * walaupun daftarnya berisi konten.
     */
    public function test_angka_pil_status_mencerminkan_data(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre Dua');
        $this->buatMateri($pemilik, Materi::STATUS_PUBLISHED, 'Materi Tayang');
        $this->buatMateri($pemilik, Materi::STATUS_REJECTED, 'Materi Ditolak');
        $this->buatQuiz($pemilik, Quiz::STATUS_PENDING, 'Quiz Antre');
        $this->buatQuiz($pemilik, Quiz::STATUS_PUBLISHED, 'Quiz Tayang');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk();

        $this->assertSame(
            ['menunggu' => 3, 'disetujui' => 2, 'ditolak' => 1],
            $this->jumlahPil($respons->getContent()),
            'Tiap pil status harus menampilkan jumlah konten berstatus itu.'
        );
    }

    /*
     * ====================================================================
     * PESAN DAFTAR KOSONG
     * ====================================================================
     */

    /**
     * Pesan kosong ditulis sekali untuk semua tab, jadi tab "Disetujui"
     * yang kosong tetap memberi tahu "tidak ada ... yang menunggu
     * verifikasi" -- kalimat yang salah untuk status itu.
     */
    public function test_pesan_kosong_mengikuti_tab_status(): void
    {
        $admin = $this->buatAdmin();

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['status' => 'disetujui']))
            ->assertOk();

        $this->assertStringNotContainsString(
            'menunggu verifikasi',
            $respons->getContent(),
            'Tab "Disetujui" tidak boleh memakai pesan "menunggu verifikasi".'
        );
    }

    public function test_pesan_kosong_tidak_mengklaim_semua_sudah_diperiksa_saat_hasil_pencarian_kosong(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Katakana Dasar');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['q' => 'tidak-ada-sama-sekali']))
            ->assertOk();

        $this->assertStringNotContainsString(
            'Semua sudah diperiksa',
            $respons->getContent(),
            'Pencarian yang tidak cocok bukan berarti semua konten sudah diperiksa.'
        );
    }

    /*
     * ====================================================================
     * PAGINASI
     * ====================================================================
     */

    public function test_paginasi_muncul_ketika_lebih_dari_delapan_konten(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        foreach (range(1, 9) as $i) {
            $this->buatMateri($pemilik, Materi::STATUS_PENDING, "Materi $i");
        }

        $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk()
            ->assertSee('Menampilkan 1', false)
            ->assertSee('dari 9 data', false);
    }

    public function test_tautan_halaman_mempertahankan_filter_aktif(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        foreach (range(1, 9) as $i) {
            $this->buatMateri($pemilik, Materi::STATUS_PENDING, "Materi Katakana $i");
        }

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['q' => 'Katakana', 'jenis' => 'materi', 'kategori' => 'matematika']))
            ->assertOk();

        $isi = $respons->getContent();

        $this->assertStringContainsString('page=2', $isi, 'Paginasi tidak menghasilkan tautan halaman 2.');
        $this->assertStringContainsString('q=Katakana', $isi, 'Tautan halaman kehilangan pencarian yang aktif.');
        $this->assertStringContainsString('jenis=materi', $isi, 'Tautan halaman kehilangan tab jenis.');
    }

    public function test_halaman_di_luar_rentang_tidak_mengaku_konten_sudah_diperiksa(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();

        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['page' => 999]))
            ->assertOk();

        $this->assertStringNotContainsString(
            'Semua sudah diperiksa',
            $respons->getContent(),
            'Halaman di luar rentang tidak boleh terlihat seperti daftar yang bersih.'
        );
    }

    /*
     * ====================================================================
     * PANEL REVIEW
     * ====================================================================
     */

    public function test_panel_materi_pending_mempunyai_tombol_keputusan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Panel');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.panel', ['jenis' => 'materi', 'id' => $materi->id]))
            ->assertOk()
            ->assertSee('data-vf-buka-setujui', false)
            ->assertSee('data-vf-buka-tolak', false)
            ->assertSee(route('admin.materi.setujui', $materi->slug), false);
    }

    public function test_panel_materi_published_tanpa_tombol_keputusan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PUBLISHED, 'Materi Tayang');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.panel', ['jenis' => 'materi', 'id' => $materi->id]))
            ->assertOk()
            ->assertDontSee('data-vf-buka-setujui', false)
            ->assertDontSee('data-vf-buka-tolak', false)
            ->assertSee('sudah final');
    }

    public function test_panel_quiz_pending_mempunyai_tombol_keputusan(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING, 'Quiz Panel');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.panel', ['jenis' => 'quiz', 'id' => $quiz->id]))
            ->assertOk()
            ->assertSee('data-vf-buka-setujui', false)
            ->assertSee(route('admin.quiz.setujui', $quiz), false);
    }

    public function test_panel_mengembalikan_kartu_kosong_untuk_id_tidak_dikenal(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.panel', ['jenis' => 'quiz', 'id' => 99999]))
            ->assertOk()
            ->assertSee('Pilih konten untuk ditinjau');
    }

    public function test_panel_jenis_tidak_dikenal_tetap_menjawab_200(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.panel', ['jenis' => 'ngawur', 'id' => 1]))
            ->assertOk();
    }

    public function test_query_pilih_tidak_dikenal_jatuh_ke_baris_pertama(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Pertama');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['pilih' => 'materi:99999']))
            ->assertOk()
            ->assertSee('data-vf-id="'.$materi->id.'"', false);
    }

    public function test_query_pilih_format_salah_tidak_memicu_error(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Pertama');

        $this->actingAs($admin)
            ->get(route('admin.verifikasi', ['pilih' => 'apapun']))
            ->assertOk();
    }

    /*
     * ====================================================================
     * KEPUTUSAN: SETUJUI
     * ====================================================================
     */

    public function test_setujui_materi_dari_verifikasi_menampilkan_sukses(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Ok');

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.setujui', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
            ])
            ->assertRedirect(route('admin.verifikasi'))
            ->assertSessionHas('sukses');

        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->refresh()->status);

        $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk()
            ->assertSee('disetujui dan sekarang tayang');
    }

    public function test_setujui_quiz_dari_verifikasi_menampilkan_sukses(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING, 'Quiz Ok');

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.quiz.setujui', $quiz), [
                'status' => Quiz::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
            ])
            ->assertRedirect(route('admin.verifikasi'))
            ->assertSessionHas('sukses');

        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->refresh()->status);
    }

    /**
     * Setelah memutuskan, admin dikembali ke URL halaman Verifikasi yang
     * sedang dibuka, lengkap dengan tab, pencarian, kategori, dan halaman
     * yang tadi dipilih. Field "kembali_url" itulah yang membawa filternya.
     */
    public function test_keputusan_mempertahankan_filter_yang_aktif(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Ok');

        $tujuan = route('admin.verifikasi', [
            'jenis' => 'materi',
            'status' => 'menunggu',
            'q' => 'Materi',
            'kategori' => 'matematika',
            'page' => 2,
        ]);

        $this->actingAs($admin)
            ->from($tujuan)
            ->post(route('admin.materi.setujui', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
                'kembali_url' => $tujuan,
            ])
            ->assertRedirect($tujuan);
    }

    /**
     * "kembali_url" adalah field kiriman, jadi ia tidak boleh dipakai
     * untuk mengalihkan admin ke halaman lain. Nilai yang menunjuk ke
     * luar route Verifikasi diabaikan dan admin tetap ke route bawaan.
     */
    public function test_kembali_url_luar_route_verifikasi_diabaikan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Ok');

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.setujui', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
                'kembali_url' => 'http://contoh-asing.test/admin/verifikasi?q=penipuan',
            ])
            ->assertRedirect(route('admin.verifikasi'));
    }

    /*
     * ====================================================================
     * KEPUTUSAN: TOLAK
     * ====================================================================
     */

    public function test_tolak_materi_menyimpan_alasan_dan_menampilkan_sukses(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Salah');

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.tolak', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
                'alasan' => '  Contoh kode belum ada.  ',
            ])
            ->assertRedirect(route('admin.verifikasi'))
            ->assertSessionHas('sukses');

        $materi->refresh();

        $this->assertSame(Materi::STATUS_REJECTED, $materi->status);
        $this->assertSame('Contoh kode belum ada.', $materi->catatan_admin);
        $this->assertSame(1, $materi->jumlah_ditolak);
    }

    public function test_tolak_quiz_menyimpan_alasan_dan_menampilkan_sukses(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PENDING, 'Quiz Salah');

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.quiz.tolak', $quiz), [
                'status' => Quiz::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
                'alasan' => 'Kunci jawaban belum tepat.',
            ])
            ->assertRedirect(route('admin.verifikasi'))
            ->assertSessionHas('sukses');

        $quiz->refresh();

        $this->assertSame(Quiz::STATUS_REJECTED, $quiz->status);
        $this->assertSame('Kunci jawaban belum tepat.', $quiz->catatan_admin);
        $this->assertSame(1, $quiz->jumlah_ditolak);
    }

    public function test_tolak_tanpa_alasan_ditolak_dengan_pesan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Salah');

        $this->actingAs($admin)
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.tolak', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
            ])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(Materi::STATUS_PENDING, $materi->refresh()->status);
    }

    /**
     * Pesan galat harus terbaca di halaman yang dikembalikan, dan alasan
     * yang sempat diketik tidak hilang begitu dialog dibuka lagi.
     *
     * Redirect-nya diikuti dalam satu rantai permintaan, sama seperti
     * peramban. Memisahkannya jadi dua permintaan membuat cookie sesi
     * tidak ikut terkirim, dan galat yang tadinya sudah ada di sesi tidak
     * sempat dipulihkan oleh permintaan berikutnya.
     */
    public function test_galat_tolak_terlihat_dan_alasan_lama_dipertahankan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PENDING, 'Materi Salah');

        $respons = $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.tolak', $materi->slug), [
                'status' => Materi::STATUS_PENDING,
                'kembali' => 'admin.verifikasi',
                'alasan' => str_repeat('x', 501),
            ])
            ->assertOk()
            ->assertSee('Belum bisa diputuskan')
            ->assertSee('maksimal 500 karakter');

        $this->assertSame(Materi::STATUS_PENDING, $materi->refresh()->status);

        $this->assertStringContainsString(
            'data-awal="'.htmlspecialchars(str_repeat('x', 501), ENT_QUOTES).'"',
            $respons->getContent(),
            'Alasan yang gagal validasi harus dipertahankan di textarea dialog.'
        );
    }

    /*
     * ====================================================================
     * JAGAAN STATUS
     * ====================================================================
     */

    public function test_materi_yang_bukan_pending_tidak_bisa_diputuskan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($this->buatPengguna(), Materi::STATUS_PUBLISHED, 'Materi Tayang');

        $this->actingAs($admin)
            ->post(route('admin.materi.setujui', $materi->slug), [
                'kembali' => 'admin.verifikasi',
            ])
            ->assertNotFound();
    }

    public function test_quiz_yang_bukan_pending_tidak_bisa_diputuskan(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($this->buatPengguna(), Quiz::STATUS_PUBLISHED, 'Quiz Tayang');

        $this->actingAs($admin)
            ->post(route('admin.quiz.tolak', $quiz), [
                'kembali' => 'admin.verifikasi',
                'alasan' => 'Alasan.',
            ])
            ->assertNotFound();
    }

    /*
     * ====================================================================
     * AKSES
     * ====================================================================
     */

    public function test_panel_juga_dijaga_middleware_admin(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get(route('admin.verifikasi.panel', ['jenis' => 'materi', 'id' => 1]))
            ->assertForbidden();
    }

    public function test_pengguna_tamu_tidak_bisa_membuka_panel(): void
    {
        $this->get(route('admin.verifikasi.panel', ['jenis' => 'materi', 'id' => 1]))
            ->assertRedirect(route('login'));
    }

    /*
     * ====================================================================
     * TAUTAN & FORM DI HALAMAN
     * ====================================================================
     */

    public function test_dialog_keputusan_membawa_field_kembali(): void
    {
        $admin = $this->buatAdmin();

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk();

        $isi = $respons->getContent();

        $this->assertSame(
            2,
            substr_count($isi, 'name="kembali" value="admin.verifikasi"'),
            'Dua dialog (setujui dan tolak) harus sama-sama membawa field kembali.'
        );
    }

    public function test_setiap_baris_daftar_mempunyai_tautan_pilih_tanpa_javascript(): void
    {
        $pemilik = $this->buatPengguna();
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($pemilik, Materi::STATUS_PENDING, 'Materi Antre');

        $respons = $this->actingAs($admin)
            ->get(route('admin.verifikasi'))
            ->assertOk();

        $this->assertStringContainsString(
            'pilih=materi%3A'.$materi->id,
            $respons->getContent(),
            'Tanpa JavaScript, baris harus membuka halaman penuh lewat query pilih.'
        );
    }
}
