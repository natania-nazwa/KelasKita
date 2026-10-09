<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol "Lihat Quiz" di hero landing page.
 *
 * Tombol ini tidak pernah membawa langsung ke /user/quiz. Yang muncul
 * selalu dialog "Lihat Quiz Mudah & Seru" — baik untuk tamu maupun yang
 * sudah login — karena isinya sama untuk keduanya; yang berbeda hanya
 * tombol aksinya di dalam dialog.
 */
class LandingTombolQuizTest extends TestCase
{
    /*
     * RefreshDatabase wajib di sini. Landing page membaca daftar mata
     * pelajaran untuk kartu kategorinya, jadi tanpa tabel halamannya
     * membalas 500 dan test untuk tombol "Lihat Quiz" tidak pernah sampai
     * ke assertion yang memang memeriksa auth.
     */
    use RefreshDatabase;

    /**
     * actingAs() memakai objek model yang ada di memory. Kita refresh()
     * supaya atribut seperti "terakhir_aktivitas" terisi dari database,
     * meniru kondisi guard saat request HTTP sungguhan.
     */
    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    public function test_tombol_hero_selalu_menuju_dialog_bukan_daftar_quiz(): void
    {
        // Tamu.
        $this->get('/')
            ->assertOk()
            ->assertSee('href="#quiz"', false)
            ->assertDontSee(route('user.quiz'), false);

        // Sudah login pun sama: tidak pernah dialihkan ke /user/quiz.
        $this->actingAs($this->buatPengguna())
            ->get('/')
            ->assertOk()
            ->assertSee('href="#quiz"', false);
    }

    public function test_dialog_tertutup_sampai_tombol_dit(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-quiz-buka', $html);

        // Dialog adalah .modal dari app.css: display:none sebagai keadaan
        // awal, jadi kelas .is-buka (yang dipasang initAksesQuiz saat
        // tombol ditekan) belum boleh ada di HTML awal.
        $this->assertSame(
            1,
            preg_match('/<div[^>]*data-quiz-akses[^>]*>/s', $html, $dialog),
            'Dialog akses quiz tidak ditemukan di landing page.'
        );
        $this->assertStringContainsString('role="dialog"', $dialog[0]);
        $this->assertStringContainsString('aria-modal="true"', $dialog[0]);
        $this->assertStringContainsString('aria-hidden="true"', $dialog[0]);
        $this->assertStringNotContainsString('is-buka', $dialog[0]);
    }

    public function test_dialog_sama_penuhnya_untuk_tamu_dan_yang_sudah_login(): void
    {
        $cek = function (?User $pengguna = null) {
            $permintaan = $pengguna ? $this->actingAs($pengguna) : $this;

            return $permintaan->get('/')->assertOk();
        };

        /*
         * Dulu dialog punya dua wajah: "Login Sekarang" untuk tamu dan
         * "Buka Daftar Quiz" untuk yang sudah login. Itu yang membuat
         * alurnya terasa berbeda-bedanya. Sekarang isinya harus identik,
         * jadi kedua keadaan diperiksa terhadap assertion yang sama.
         */
        foreach ([$cek(), $cek($this->buatPengguna())] as $halaman) {
            $halaman
                ->assertSee('Lihat Quiz')
                ->assertSee('Mudah &amp; Seru', false)
                ->assertSee('Kamu bisa melihat semua quiz yang tersedia di KelasKita.')
                ->assertSee('kamu perlu login terlebih dahulu.')
                ->assertSee('Login Sekarang')
                ->assertSee('href="'.route('login').'"', false);
        }
    }

    public function test_tidak_ada_tautan_langsung_ke_daftar_quiz_di_landing(): void
    {
        // TAMU maupun yang sudah login: landing page tidak pernah punya
        // tautan langsung ke daftar quiz. Satu-satunya jalan ke sana
        // adalah lewat route /user/quiz yang dijaga middleware "auth".
        foreach ([$this->get('/'), $this->actingAs($this->buatPengguna())->get('/')] as $halaman) {
            $halaman
                ->assertOk()
                ->assertDontSee(route('user.quiz'), false);
        }
    }

    public function test_halaman_quiz_tetap_melindungi_isinya_dari_tamu(): void
    {
        $this->get('/user/quiz')->assertRedirect(route('login'));
    }

    public function test_alur_tamu_lihat_quiz_lalu_login_ke_daftar_quiz(): void
    {
        $this->buatPengguna();

        // 1. Landing page untuk tamu menyodorkan pengingat, bukan soal.
        $this->get('/')->assertOk()->assertSee('Login Sekarang');

        // 2. Halaman login tetap bisa dibuka.
        $this->get(route('login'))->assertOk();

        // 3. Login berhasil -> masuk ke akunnya.
        $this->post('/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect(route('user.dashboard'));

        $this->assertAuthenticated();

        // 4. Daftar quiz tetap hanya bisa dibuka lewat route-nya sendiri,
        //    yang dijaga middleware "auth".
        $this->get('/user/quiz')->assertOk();
    }
}
