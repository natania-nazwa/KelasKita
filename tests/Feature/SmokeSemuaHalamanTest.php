<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\Materi;
use App\Models\Notifikasi;
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
 * QA fungsional seluruh menu: setiap halaman GET yang bisa dibuka admin dan
 * user digerbang sekali dengan data contoh yang lengkap, supaya kesalahan
 * render (500) ketahuan tanpa harus membuka tiap halaman satu per satu.
 */
class SmokeSemuaHalamanTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
            'aktif' => true,
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

    private function buatPelajaran(string $nama = 'Matematika', string $slug = 'matematika'): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => $slug], [
            'nama' => $nama,
            'deskripsi' => "Deskripsi $nama",
            'ikon' => '∑',
            'aktif' => true,
        ]);
    }

    private function buatMateri(User $pemilik, string $slug, string $nama): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pemilik->getKey(),
            'nama' => $nama,
            'slug' => $slug,
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
        ]);
    }

    private function buatQuiz(User $pemilik, string $slug, string $judul): Quiz
    {
        $quiz = Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pemilik->getKey(),
            'judul' => $judul,
            'slug' => $slug,
            'deskripsi' => 'Ringkasan quiz.',
            'durasi' => 10,
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Berapa 1+1?',
            'tipe' => Soal::TIPE_PILIHAN_GANDA,
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Pembahasan singkat.',
            'urutan' => 1,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);

        return $quiz;
    }

    public function test_semua_halaman_admin_dan_user_tidak_error_500(): void
    {
        $admin = $this->buatAdmin();
        $user = $this->buatPengguna();

        $materi = $this->buatMateri($user, 'materi-latihan', 'Materi Latihan');
        $quiz = $this->buatQuiz($user, 'quiz-latihan', 'Quiz Latihan');

        // Karya milik admin sendiri, supaya form edit admin benar-benar dibuka.
        $materiAdmin = $this->buatMateri($admin, 'materi-admin', 'Materi Admin');
        $quizAdmin = $this->buatQuiz($admin, 'quiz-admin', 'Quiz Admin');

        $jadwal = Jadwal::create([
            'dibuat_oleh' => $user->getKey(),
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:00',
            'pelajaran' => 'Matematika',
            'judul' => 'Aljabar',
            'kelas' => 'X-A',
            'ruang' => 'R1',
        ]);

        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $user->getKey(),
            'kode' => 'ABCD12',
            'status' => SesiQuiz::STATUS_MENUNGGU,
        ]);

        PesertaQuiz::create([
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $user->getKey(),
            'status' => PesertaQuiz::STATUS_LOBBY,
            'bergabung_pada' => now(),
        ]);

        $pengerjaan = PengerjaanQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'pengguna_id' => $user->getKey(),
            'sesi_id' => $sesi->getKey(),
            'jumlah_soal' => 1,
            'jumlah_dijawab' => 1,
            'jumlah_benar' => 1,
            'jumlah_salah' => 0,
            'nilai' => 100,
            'dimulai_pada' => now()->subMinutes(5),
            'selesai_pada' => now(),
        ]);

        Notifikasi::create([
            'pengguna_id' => $user->getKey(),
            'jenis' => Notifikasi::JENIS_QUIZ_BARU,
            'judul' => 'Quiz baru',
            'pesan' => 'Quiz baru tersedia.',
        ]);

        Notifikasi::create([
            'pengguna_id' => $admin->getKey(),
            'jenis' => Notifikasi::JENIS_KONTEN_MENUNGGU,
            'judul' => 'Konten menunggu',
            'pesan' => 'Ada karya menunggu.',
        ]);

        $halamanTamu = [
            'Landing' => route('landing'),
            'Login' => route('login'),
            'Register' => route('register'),
        ];

        $this->cekHalaman($halamanTamu);

        $halamanUser = [
            'Dashboard user' => route('user.dashboard'),
            'Jadwal' => route('user.jadwal'),
            'Jadwal tambah' => route('user.jadwal.tambah'),
            'Jadwal edit' => route('user.jadwal.edit', $jadwal),
            'Materi' => route('user.materi'),
            'Materi tambah' => route('user.materi.tambah'),
            'Materi detail' => route('user.materi.detail', $materi->slug),
            'Materi edit' => route('user.materi.edit', $materi->slug),
            'Quiz' => route('user.quiz'),
            'Quiz tambah' => route('user.quiz.tambah'),
            'Quiz gabung' => route('user.sesi.gabung'),
            'Quiz detail' => route('user.quiz.detail', $quiz),
            'Quiz edit' => route('user.quiz.edit', $quiz),
            'Karya saya' => route('user.karya-saya'),
            'Simpanan' => route('user.simpanan'),
            'Hasil' => route('user.hasil'),
            'Hasil daftar' => route('user.hasil.daftar', $quiz),
            'Hasil detail' => route('user.hasil.detail', $pengerjaan),
            'UIUX hasil' => route('user.uiux.hasil'),
            'Sesi lobby' => route('user.sesi.lobby', $sesi),
            'Sesi data' => route('user.sesi.data', $sesi),
            'Profil' => route('user.profil'),
        ];

        $this->cekHalaman($halamanUser, $user);

        /*
         * Sesi masih menunggu dan quiz-nya mode publik: peserta belum boleh
         * membuka hasil atau peringkat, jadi keduanya mengalihkan ke halaman
         * lain alih-alih merender. Yang penting di sini: bukan 500.
         */
        $this->actingAs($user)->get(route('user.sesi.hasil', $sesi))->assertRedirect();
        $this->actingAs($user)->get(route('user.sesi.peringkat', $sesi))->assertRedirect();

        // Halaman soal tidak dibuka langsung dari tautan; peserta dialihkan.
        $this->actingAs($user)
            ->get(route('user.judulsoal.soal', ['quiz' => $quiz->slug, 'nomor' => 1]))
            ->assertRedirect();

        $halamanAdmin = [
            'Dashboard admin' => route('admin.dashboard'),
            'Konten' => route('admin.konten'),
            'Konten materi tambah' => route('admin.konten.materi.tambah'),
            'Konten materi show' => route('admin.konten.materi.show', $materiAdmin->slug),
            'Konten materi edit' => route('admin.konten.materi.edit', $materiAdmin->slug),
            'Konten quiz tambah' => route('admin.konten.quiz.tambah'),
            'Konten quiz show' => route('admin.konten.quiz.show', $quizAdmin),
            'Konten quiz edit' => route('admin.konten.quiz.edit', $quizAdmin),
            'Materi' => route('admin.materi'),
            'Materi show' => route('admin.materi.show', $materiAdmin->slug),
            'Materi edit' => route('admin.materi.edit', $materiAdmin->slug),
            'Quiz' => route('admin.quiz'),
            'Quiz show' => route('admin.quiz.show', $quizAdmin),
            'Quiz edit' => route('admin.quiz.edit', $quizAdmin),
            'Verifikasi' => route('admin.verifikasi'),
            'Verifikasi materi' => route('admin.verifikasi', ['jenis' => 'materi']),
            'Verifikasi quiz' => route('admin.verifikasi', ['jenis' => 'quiz']),
            'Verifikasi panel' => route('admin.verifikasi.panel'),
            'Pengguna' => route('admin.pengguna'),
            'Pengguna admin' => route('admin.pengguna', ['peran' => 'admin']),
            'Pengguna nonaktif' => route('admin.pengguna', ['status' => 'nonaktif']),
            'Pengaturan' => route('admin.pengaturan'),
            'Pengaturan profil' => route('admin.pengaturan.profil'),
            'Pengaturan keamanan' => route('admin.pengaturan.keamanan'),
            'Pengaturan pelajaran' => route('admin.pengaturan.pelajaran'),
            'Pengaturan sesi' => route('admin.pengaturan.sesi'),
            'Pengaturan sistem' => route('admin.pengaturan.sistem'),
            'Pengaturan tentang' => route('admin.pengaturan.tentang'),
        ];

        $this->cekHalaman($halamanAdmin, $admin);
    }

    /**
     * Buka tiap halaman sekali dan kumpulkan status yang bukan 200, supaya
     * satu kali jalan langsung memperlihatkan semua halaman bermasalah,
     * bukan hanya yang pertama.
     *
     * @param  array<string, string>  $halaman
     * @param  array<int, int>  $izinkan  status selain 200 yang wajar (mis. 302)
     */
    private function cekHalaman(array $halaman, ?User $sebagai = null, array $izinkan = []): void
    {
        $bermasalah = [];

        foreach ($halaman as $label => $url) {
            $permintaan = $sebagai === null ? $this : $this->actingAs($sebagai);
            $status = $permintaan->get($url)->getStatusCode();

            if ($status === 200 || in_array($status, $izinkan, true)) {
                continue;
            }

            $bermasalah[] = "$label ($url) => $status";
        }

        $this->assertSame([], $bermasalah, 'Halaman tidak membalas 200: '.implode('; ', $bermasalah));
    }

    public function test_pengguna_baru_muncul_di_menu_pengguna_admin(): void
    {
        $admin = $this->buatAdmin();

        $this->get(route('admin.pengguna'))->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->get(route('admin.pengguna'))
            ->assertOk()
            ->assertDontSee('siswa.baru@example.test');

        $this->post(route('register'), [
            'name' => 'Siswa Baru',
            'email' => 'siswa.baru@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pengguna'))
            ->assertOk()
            ->assertSee('Siswa Baru')
            ->assertSee('siswa.baru@example.test');
    }
}
