<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * actingAs() memakai objek model yang ada di memory.zjermel
     * Kita refresh() supaya atribut seperti "aktif" terisi dari database,
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

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/user/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_pendaftaran_selalu_berperan_user(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',

            // Percobaan sneak in peran lewat request harus diabaikan
            'peran' => 'admin',
        ]);

        $user = User::where('email', 'budi@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('user', $user->peran);
    }

    public function test_user_biasa_ditolak_di_admin(): void
    {
        $this->actingAs($this->buatPengguna())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_boleh_masuk_ke_admin(): void
    {
        $admin = $this->buatPengguna([
            'nama' => 'Pemilik',
            'email' => 'admin@example.com',
            'peran' => 'admin',
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_semua_halaman_user_bisa_diakses(): void
    {
        foreach ([$this->buatPengguna(), $this->buatPengguna(['email' => 'a@example.com', 'peran' => 'admin'])] as $user) {
            $this->actingAs($user)->get('/user/dashboard')->assertOk();
            $this->actingAs($user)->get('/user/materi')->assertOk();
            $this->actingAs($user)->get('/user/quiz')->assertOk();
            $this->actingAs($user)->get('/user/profil')->assertOk();
        }
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $this->buatPengguna(['aktif' => false]);

        $this->post('/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_ditolak_di_admin(): void
    {
        $admin = $this->buatPengguna([
            'nama' => 'Pemilik',
            'email' => 'admin@example.com',
            'peran' => 'admin',
            'aktif' => false,
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_diarahkan_ke_dashboard_admin_setelah_login(): void
    {
        $this->buatPengguna([
            'nama' => 'Pemilik',
            'email' => 'admin@example.com',
            'peran' => 'admin',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect('/admin/dashboard');
    }

    public function test_user_diarahkan_ke_dashboard_setelah_login(): void
    {
        $this->buatPengguna();

        $this->post('/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect('/user/dashboard');
    }

    public function test_halaman_auth_tetap_terbuka_walau_sudah_login(): void
    {
        $user = $this->buatPengguna();
        $admin = $this->buatPengguna([
            'email' => 'admin@example.com',
            'peran' => 'admin',
        ]);

        // Alur yang diminta: halaman login SELALU tampil, tidak dilemmas
        $this->actingAs($user)->get('/login')->assertOk()->assertSee('Selamat Datang di KelasKita');
        $this->actingAs($user)->get('/register')->assertOk()->assertSee('Buat Akun');

        $this->actingAs($admin)->get('/login')->assertOk();
        $this->actingAs($admin)->get('/register')->assertOk();
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_url_user_berprefiks_user_dan_url_lama_hilang(): void
    {
        $user = $this->buatPengguna();

        // URL lama sudah tidak ada, tidak ada lagi alias "/dashboard"
        $this->actingAs($user)->get('/dashboard')->assertNotFound();
        $this->actingAs($user)->get('/materi')->assertNotFound();
        $this->actingAs($user)->get('/quiz')->assertNotFound();
        $this->actingAs($user)->get('/profil')->assertNotFound();
    }

    public function test_url_admin_tetap_berprefiks_admin(): void
    {
        $admin = $this->buatPengguna([
            'email' => 'admin@example.com',
            'peran' => 'admin',
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_landing_page_tampilkan_tombol_auth_kapan_untuk_tamu(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login/Daftar')
            ->assertSee(url('/login'))
            ->assertSee(url('/register'));
    }

    public function test_landing_page_selalu_tampilkan_tombol_login_daftar(): void
    {
        // Tamu
        $this->get('/')
            ->assertOk()
            ->assertSee('Login/Daftar')
            ->assertSee(url('/login'))
            ->assertSee(url('/register'));

        // Sudah login pun tetap ke /login, bukan langsung ke dashboard
        $user = $this->buatPengguna();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Login/Daftar')
            ->assertSee(url('/login'))
            ->assertDontSee(route('user.dashboard'));
    }

    public function test_alur_lengkap_landing_ke_login_ke_daftar(): void
    {
        // 1. Dari landing page, tombol menuju /login
        $landing = $this->get('/')->assertOk();
        $landing->assertSee('href="'.url('/login').'"', false);

        // 2. Di halaman login ada link menuju /register
        $this->get('/login')
            ->assertOk()
            ->assertSee('Daftar')
            ->assertSee('href="'.url('/register').'"', false);

        // 3. Halaman register bisa dibuka
        $this->get('/register')->assertOk();

        // 4. Berhasil daftar -> langsung ke dashboard user
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('user.dashboard'));

        // 5. Berhasil login -> dashboard sesuai peran
        $this->post('/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect(route('user.dashboard'));
    }

    public function test_kirim_login_saat_sudah_login_berpindah_ke_akun_baru(): void
    {
        $lama = $this->buatPengguna();
        $baru = $this->buatPengguna([
            'nama' => 'Admin Baru',
            'email' => 'baru@example.com',
            'peran' => 'admin',
        ]);

        $this->actingAs($lama)
            ->post('/login', [
                'email' => 'baru@example.com',
                'password' => 'rahasia123',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($baru);
    }
}
