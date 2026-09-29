<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\SimpananMateri;
use App\Models\SimpananQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman "Simpan" (/user/simpanan): tujuan tombol bookmark di pojok
 * kanan atas kartu materi dan kartu quiz.
 *
 *   GET /user/simpanan?tab=materi|quiz  daftar kartu yang disimpan
 *
 * Dua tab memakai query string, sama seperti halaman "Karya Saya", dan
 * isinya selalu simpanan milik pengguna yang sedang login.
 */
class SimpananHalamanTest extends TestCase
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
        // firstOrCreate: beberapa test membuat lebih dari satu materi, dan
        // slug pelajaran dijaga unique di database.
        return Pelajaran::query()->firstOrCreate(
            ['slug' => 'pemrograman'],
            [
                'nama' => 'Pemrograman',
                'deskripsi' => 'Deskripsi Pemrograman',
                'ikon' => '</>',
                'aktif' => true,
            ],
        );
    }

    private function buatMateri(User $pembuat, string $nama = 'HTML Dasar'): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value(),
            'deskripsi' => 'Materi dasar HTML untuk membuat struktur halaman web.',
            'isi' => 'Isi materi.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
        ]);
    }

    private function buatQuiz(User $pembuat, string $judul = 'Latihan HTML'): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => 'Quiz dasar HTML.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }

    private function simpanMateri(User $pengguna, Materi $materi): void
    {
        SimpananMateri::query()->create([
            'pengguna_id' => $pengguna->getKey(),
            'materi_id' => $materi->getKey(),
        ]);
    }

    private function simpanQuiz(User $pengguna, Quiz $quiz): void
    {
        SimpananQuiz::query()->create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
        ]);
    }

    public function test_halaman_membutuhkan_login(): void
    {
        $this->get('/user/simpanan')->assertRedirect('/login');
    }

    public function test_menu_simpan_tampil_di_sidebar(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee(route('user.simpanan'), false)
            ->assertSee('Simpan')
            // Nama menu "Simpan", bukan "Simpanan".
            ->assertDontSee('Simpanan');
    }

    public function test_tab_materi_menampilkan_kartu_materi_yang_disimpan(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($pengguna, 'Struktur HTML');
        $this->simpanMateri($pengguna, $materi);

        $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->assertSee('Struktur HTML')
            ->assertSee(route('user.materi.detail', $materi->slug), false);
    }

    public function test_tab_quiz_menampilkan_kartu_quiz_yang_disimpan(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna, 'Latihan CSS');
        $this->simpanQuiz($pengguna, $quiz);

        $this->actingAs($pengguna)
            ->get(route('user.simpanan', ['tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Latihan CSS')
            ->assertSee(route('user.quiz.detail', $quiz), false);
    }

    public function test_simpanan_orang_lain_tidak_tampil(): void
    {
        $natania = $this->buatPengguna();
        $lain = $this->buatPengguna([
            'nama' => 'Sein',
            'email' => 'sein@example.com',
        ]);

        $this->simpanMateri($natania, $this->buatMateri($natania, 'Materi Rahasia Natania'));
        $this->simpanQuiz($natania, $this->buatQuiz($natania, 'Quiz Rahasia Natania'));

        $this->actingAs($lain)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->assertDontSee('Materi Rahasia Natania');

        $this->actingAs($lain)
            ->get(route('user.simpanan', ['tab' => 'quiz']))
            ->assertOk()
            ->assertDontSee('Quiz Rahasia Natania');
    }

    public function test_pencarian_menyaring_simpanan_pada_tab_aktif(): void
    {
        $pengguna = $this->buatPengguna();
        $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Struktur HTML'));
        $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Dasar CSS'));

        $this->actingAs($pengguna)
            ->get(route('user.simpanan', ['q' => 'CSS']))
            ->assertOk()
            ->assertSee('Dasar CSS')
            ->assertDontSee('Struktur HTML');
    }

    public function test_pencarian_hanya_ada_di_top_bar(): void
    {
        $pengguna = $this->buatPengguna();

        $isi = $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->getContent();

        // Tidak ada lagi kotak pencarian di sebelah kanan tab Materi/Quiz.
        $this->assertStringNotContainsString('data-cari-form', $isi);

        // Pencarian global di top bar yang tetap dipakai, dan mengarah
        // ke halaman ini juga.
        $this->assertStringContainsString('id="cari-topbar"', $isi);
        $this->assertStringContainsString('Cari simpan...', $isi);
    }

    public function test_daftar_tidak_pakai_pagination_tapi_muat_lagi(): void
    {
        $pengguna = $this->buatPengguna();

        // Satu halaman simpanan = 25 kartu (SimpananController::perHalaman),
        // jadi 26 kartu berarti masih ada halaman berikutnya.
        for ($nomor = 1; $nomor <= 26; $nomor++) {
            $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Materi '.$nomor));
        }

        $isi = $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->getContent();

        // Tidak ada lagi baris "Menampilkan ..." maupun tautan pagination.
        $this->assertStringNotContainsString('Menampilkan', $isi);
        $this->assertStringNotContainsString('rel="next"', $isi);

        // Yang menggantikannya tombol muat lagi + target grid.
        $this->assertStringContainsString('data-muat-lebih', $isi);
        $this->assertStringContainsString('data-simpan-grid', $isi);
    }

    public function test_daftar_berupa_kartu_lima_kolom_seperti_halaman_materi(): void
    {
        $pengguna = $this->buatPengguna();
        $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Struktur HTML'));
        $this->simpanQuiz($pengguna, $this->buatQuiz($pengguna, 'Latihan CSS'));

        $materi = $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->getContent();

        // Kartu yang sama dengan halaman Materi: lima kolom di layar
        // besar, dan 25 kartu per halaman supaya lima baris lima kolom
        // penuh tanpa kartu yatim.
        $this->assertStringContainsString('xl:grid-cols-5', $materi);
        $this->assertStringContainsString('kartu-materi', $materi);

        // Kepala halaman berupa papan hero, seperti Materi dan Quiz.
        $this->assertStringContainsString('simpan-kepala', $materi);

        $quiz = $this->actingAs($pengguna)
            ->get(route('user.simpanan', ['tab' => 'quiz']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('kartu-quiz', $quiz);
    }

    public function test_satu_halaman_memuat_25_simpanan(): void
    {
        $pengguna = $this->buatPengguna();

        // Grid lima kolom x lima baris: 25 kartu masih satu halaman
        // penuh, jadi tombol "Muat lagi" belum muncul.
        for ($nomor = 1; $nomor <= 25; $nomor++) {
            $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Materi '.$nomor));
        }

        $isi = $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-muat-lebih', $isi);

        // Setiap kartu punya satu tombol bookmark, jadi jumlah kemunculan
        // data-bookmark sama dengan jumlah kartu di halaman ini.
        $this->assertSame(25, substr_count($isi, 'data-bookmark="materi-'));
    }

    public function test_tombol_bookmark_ada_di_kartu_simpanan(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($pengguna, 'Struktur HTML');
        $this->simpanMateri($pengguna, $materi);

        $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->assertSee('data-bookmark="'.e($materi->slug).'"', false)
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_muat_lagi_mengirim_kartu_dan_tautan_berikutnya(): void
    {
        $pengguna = $this->buatPengguna();

        // 60 kartu = tiga halaman (25 + 25 + 10), jadi halaman kedua
        // masih punya halaman berikutnya.
        for ($nomor = 1; $nomor <= 60; $nomor++) {
            $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Materi '.$nomor));
        }

        $data = $this->actingAs($pengguna)
            ->get(route('user.simpanan.muat', ['page' => 2]))
            ->assertOk()
            ->json();

        // Kartu halaman kedua ikut terkirim sebagai HTML siap sisip,
        // bukan halaman penuh. Kartu halaman pertama tidak ikut.
        $this->assertStringContainsString('data-bookmark="materi-26"', $data['kartu']);
        $this->assertStringNotContainsString('data-bookmark="materi-1"', $data['kartu']);
        $this->assertNotNull($data['berikutnya']);
    }

    public function test_muat_lagi_tidak_mengirim_tautan_lagi_sudah_habis(): void
    {
        $pengguna = $this->buatPengguna();
        $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Struktur HTML'));

        $isi = $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->getContent();

        // Daftar muat satu halaman saja, jadi tidak ada tombol.
        $this->assertStringNotContainsString('data-muat-lebih', $isi);

        $data = $this->actingAs($pengguna)
            ->get(route('user.simpanan.muat'))
            ->assertOk()
            ->json();

        $this->assertNull($data['berikutnya']);
    }

    public function test_muat_lagi_membutuhkan_login(): void
    {
        $this->get(route('user.simpanan.muat'))->assertRedirect('/login');
    }

    public function test_angka_tab_mencerminkan_jumlah_simpanan(): void
    {
        $pengguna = $this->buatPengguna();
        $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Struktur HTML'));
        $this->simpanMateri($pengguna, $this->buatMateri($pengguna, 'Dasar CSS'));
        $this->simpanQuiz($pengguna, $this->buatQuiz($pengguna, 'Latihan CSS'));

        $isi = $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->getContent();

        // Angka tiap tab ada di .tab-karya__jumlah: materi = 2, quiz = 1.
        $this->assertSame(1, substr_count($isi, 'tab-karya__jumlah">2</span>'));
        $this->assertSame(1, substr_count($isi, 'tab-karya__jumlah">1</span>'));
    }

    public function test_simpanan_yang_tidak_terbit_lagi_tidak_tampil(): void
    {
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($pengguna, 'Materi Ditarik');
        $this->simpanMateri($pengguna, $materi);

        $materi->update(['status' => Materi::STATUS_DRAFT]);

        $this->actingAs($pengguna)
            ->get(route('user.simpanan'))
            ->assertOk()
            ->assertDontSee('Materi Ditarik')
            ->assertSee('Belum ada materi yang disimpan');
    }
}
