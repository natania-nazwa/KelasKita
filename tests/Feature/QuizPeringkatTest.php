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
 * Halaman peringkat sesi quiz mode kode: /user/sesi/{sesi}/peringkat.
 *
 * Yang diuji di sini bukan cosmetics podium, melainkan tiga hal yang harus
 * benar walau markup-nya nanti diganti:
 *   - hanya host dan peserta sesi itu yang boleh membuka;
 *   - urutan baris mengikuti nilai yang benar-benar tersimpan, dan dua nilai
 *     sama mendapat peringkat sama;
 *   - peserta yang belum menjawab tetap ikut tampil, bukan disembunyikan.
 *
 * Tombol "Lihat Peringkat" di kartu hasil juga diuji di sini, karena
 * keberadaannya bergantung pada sesi mode kode yang sama.
 */
class QuizPeringkatTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $nama, ?string $email = null): User
    {
        return User::create([
            'nama' => $nama,
            'email' => $email ?? str($nama)->slug()->value().'@example.com',
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

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
     * Quiz mode kode. Kode aksesnya ikut diisi supaya sesi yang dibuat
     * di bawah benar-benar sama dengan sesi yang dibuka lewat kode.
     */
    private function buatQuizKode(User $pembuat, string $judul = 'Quiz Pecahan'): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'K7F3P9',
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }

    private function buatQuizPublik(User $pembuat, string $judul = 'Quiz Publik'): Quiz
    {
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

    private function buatSesi(Quiz $quiz, User $host, string $status = SesiQuiz::STATUS_SELESAI): SesiQuiz
    {
        return SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $host->getKey(),
            'kode' => $quiz->kode_akses ?: 'ABC123',
            'status' => $status,
        ]);
    }

    private function ikut(SesiQuiz $sesi, User $pengguna, string $status = PesertaQuiz::STATUS_SELESAI): PesertaQuiz
    {
        return $sesi->peserta()->create([
            'pengguna_id' => $pengguna->getKey(),
            'status' => $status,
            'bergabung_pada' => now(),
        ]);
    }

    /**
     * Pengerjaan dengan nilai yang sudah ditentukan.
     *
     * Nilai ditulis langsung supaya test menguji cara peringkat dihitung,
     * bukan cara penilaian jawaban dihitung (itu sudah diuji di file lain).
     */
    private function buatPengerjaan(
        User $pengguna,
        Quiz $quiz,
        SesiQuiz $sesi,
        int $nilai,
        int $benar,
        bool $selesai = true,
    ): PengerjaanQuiz {
        $mulai = now()->subMinutes(10);

        return PengerjaanQuiz::create([
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => 10,
            'jumlah_dijawab' => $benar,
            'jumlah_benar' => $benar,
            'jumlah_salah' => 0,
            'nilai' => $nilai,
            'dimulai_pada' => $mulai,
            'selesai_pada' => $selesai ? $mulai->copy()->addMinutes(5) : null,
        ])->refresh();
    }

    /*
     * =====================================================================
     * AKSES
     * =====================================================================
     */

    public function test_peserta_dan_host_bisa_membuka_halaman_peringkat(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($peserta)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Peringkat Peserta');

        $this->actingAs($host)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk();
    }

    public function test_orang_lain_tidak_bisa_membuka_halaman_peringkat(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');
        $penyusup = $this->buatPengguna('Keyla', 'keyla@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);

        $this->actingAs($penyusup)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/user/sesi/1/peringkat')->assertRedirect('/login');
    }

    /*
     * =====================================================================
     * URUTAN DAN PERINGKAT
     * =====================================================================
     */

    public function test_urutan_baris_mengikuti_nilai_dari_database(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $rendah = $this->buatPengguna('Andi', 'andi@kelaskita.test');
        $tinggi = $this->buatPengguna('Budi', 'budi@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $rendah);
        $this->ikut($sesi, $tinggi);

        $this->buatPengerjaan($rendah, $quiz, $sesi, nilai: 40, benar: 4);
        $this->buatPengerjaan($tinggi, $quiz, $sesi, nilai: 90, benar: 9);

        $this->actingAs($host)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSeeInOrder(['Budi', 'Andi'])
            ->assertSee('40')
            ->assertSee('90');
    }

    /**
     * Dua nilai sama harus mendapat peringkat yang sama, dan peringkat
     * berikutnya meloncat. Kalau tidak, "peringkat 2" bisa dipakai dua orang
     * sehingga tidak ada yang benar-benar peringkat tiga.
     */
    public function test_nilai_sama_mendapat_peringkat_sama_dan_peringkat_berikutnya_meloncat(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $satu = $this->buatPengguna('Andi', 'andi@kelaskita.test');
        $dua = $this->buatPengguna('Budi', 'budi@kelaskita.test');
        $tiga = $this->buatPengguna('Citra', 'citra@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $satu);
        $this->ikut($sesi, $dua);
        $this->ikut($sesi, $tiga);

        $this->buatPengerjaan($satu, $quiz, $sesi, nilai: 80, benar: 8);
        $this->buatPengerjaan($dua, $quiz, $sesi, nilai: 80, benar: 8);
        $this->buatPengerjaan($tiga, $quiz, $sesi, nilai: 20, benar: 2);

        $isi = $this->actingAs($satu)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->getContent();

        // Angkanya dibaca dari kelas penanda petak podium, bukan dari teks
        // mentah, karena Blade menyisipkan baris baru di sekitar setiap
        // ekspresi sehingga ">1<" tidak pernah muncul apa adanya.
        //
        // Hasilnya 1, 1, 3 — tidak ada peringkat 2 di halaman ini, karena
        // dua orang mengikat di peringkat 1.
        $this->assertSame(2, substr_count($isi, 'peringkat-podium__petak--1'));
        $this->assertSame(0, substr_count($isi, 'peringkat-podium__petak--2'));
        $this->assertSame(1, substr_count($isi, 'peringkat-podium__petak--3'));
    }

    /**
     * Peserta yang belum menjawab tetap ada di daftar. Kalau disembunyikan,
     * ia terlihat seperti tidak pernah masuk, padahal namanya ada di lobby.
     */
    public function test_peserta_yang_belum_menjawab_ikut_tampil_dengan_label_khusus(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $selesai = $this->buatPengguna('Andi', 'andi@kelaskita.test');
        $belum = $this->buatPengguna('Bela', 'bela@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $selesai);
        $this->ikut($sesi, $belum, PesertaQuiz::STATUS_LOBBY);

        $this->buatPengerjaan($selesai, $quiz, $sesi, nilai: 70, benar: 7);

        $this->actingAs($host)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Bela')
            // Nihilnya jawaban ditulis sebagai keterangan, bukan sebagai nilai.
            ->assertSee('Belum menjawab');
    }

    /**
     * Halaman boleh dibuka saat quiz masih berjalan, karena peserta bisa
     * menekan "Selesai" di tengah jalan lalu langsung ingin tahu posisinya.
     */
    public function test_halaman_peringkat_bisa_dibuka_saat_quiz_masih_berjalan(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_DIMULAI);
        $this->ikut($sesi, $peserta, PesertaQuiz::STATUS_MENGERJAKAN);
        $this->buatPengerjaan($peserta, $quiz, $sesi, nilai: 50, benar: 5, selesai: false);

        $this->actingAs($peserta)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Peringkat Peserta')
            ->assertSee('#1');
    }

    /*
     * =====================================================================
     * POSISI DIRI SENDIRI DAN KOSONG
     * =====================================================================
     */

    public function test_peserta_melihat_posinya_sendiri(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $saya = $this->buatPengguna('Irma', 'irma@kelaskita.test');
        $lain = $this->buatPengguna('Andi', 'andi@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $saya);
        $this->ikut($sesi, $lain);

        $this->buatPengerjaan($lain, $quiz, $sesi, nilai: 90, benar: 9);
        $this->buatPengerjaan($saya, $quiz, $sesi, nilai: 60, benar: 6);

        $this->actingAs($saya)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Posisi kamu')
            // Rangking dua dari dua orang.
            ->assertSee('#2')
            ->assertSee('dari 2 peserta');
    }

    /**
     * Host tidak menjawab soal sesi mode kode, jadi ia tidak punya baris di
     * daftar. Halamannya tetap terbuka untuk dia, tapi tanpa kartu posisi
     * yang tidak bisa diisi apa pun.
     */
    public function test_host_tidak_melihat_kartu_posisi_karena_tidak_ikut_menjawab(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);
        $this->buatPengerjaan($peserta, $quiz, $sesi, nilai: 100, benar: 10);

        $this->actingAs($host)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertDontSee('Posisi kamu')
            ->assertSee('Kamu adalah pembuat quiz ini')
            // Namanya sendiri tidak boleh muncul di daftar peserta.
            ->assertSee('Irma')
            ->assertDontSee('>Guru<');
    }

    public function test_belum_ada_peserta_menampilkan_penjelasan_bukan_daftar_kosong(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host, SesiQuiz::STATUS_MENUNGGU);

        $this->actingAs($host)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Belum ada peserta')
            ->assertSee('0 peserta');
    }

    /**
     * Sesi solo tidak punya peserta sama sekali, jadi halaman peringkat tidak
     * punya apa-apa untuk ditampilkan. Diarahkan ke tujuan hasil yang biasa
     * supaya tidak ada halaman kosong yang muncul dari URL yang diketik manual.
     */
    public function test_sesi_solo_diarahkan_ke_tujuan_hasil_biasa(): void
    {
        $pengguna = $this->buatPengguna('Natania', 'natania@kelaskita.test');
        $quiz = $this->buatQuizPublik($pengguna);
        $sesi = $this->buatSesi($quiz, $pengguna, SesiQuiz::STATUS_SELESAI);

        $this->actingAs($pengguna)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertRedirect(route('user.sesi.hasil', $sesi));
    }

    /*
     * =====================================================================
     * TOMBOL "LIHAT PERINGKAT" DI KARTU HASIL
     * =====================================================================
     */

    public function test_kartu_hasil_peserta_mode_kode_menampilkan_tombol_lihat_peringkat(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $this->buatSoal($quiz);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);
        $pengerjaan = $this->buatPengerjaan($peserta, $quiz, $sesi, nilai: 100, benar: 10);

        $this->actingAs($peserta)
            ->get(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]))
            ->assertOk()
            ->assertSee('Lihat Peringkat')
            ->assertSee(route('user.sesi.peringkat', $sesi), escape: false);
    }

    /**
     * Quiz publik dikerjakan sendirian, jadi tidak ada siapa pun untuk
     * dibandingkan. Menampilkan tombol di sana hanya akan membuka daftar
     * berisi satu nama.
     */
    public function test_kartu_hasil_quiz_publik_tidak_menampilkan_tombol_peringkat(): void
    {
        $pengguna = $this->buatPengguna('Natania', 'natania@kelaskita.test');
        $quiz = $this->buatQuizPublik($pengguna);
        $sesi = $this->buatSesi($quiz, $pengguna);
        $pengerjaan = $this->buatPengerjaan($pengguna, $quiz, $sesi, nilai: 100, benar: 10);

        $this->actingAs($pengguna)
            ->get(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]))
            ->assertOk()
            ->assertDontSee('Lihat Peringkat');
    }

    /**
     * Tombolnya harus muncul tepat setelah peserta selesai mengerjakan lewat
     * kode, bukan cuma ketika URL hasil diketik manual. Ini yang paling mudah
     * luput: halamannya benar, tapi tidak pernah sampai ke sana.
     */
    public function test_tombol_peringkat_muncul_setelah_peserta_selesai_mengerjakan_lewat_kode(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $this->buatSoal($quiz);

        // Gabung lewat kode, bukan lewat tombol "Mulai Quiz".
        $this->actingAs($peserta)
            ->post(route('user.sesi.gabung.store'), ['kode' => 'k7f3p9'])
            ->assertRedirect();

        $sesi = SesiQuiz::query()->firstOrFail();

        $this->actingAs($host)->post(route('user.sesi.mulai', $sesi));

        $respons = $this->actingAs($peserta)
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ]);

        $pengerjaan = PengerjaanQuiz::query()->firstOrFail();

        $respons->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        $this->actingAs($peserta)
            ->followingRedirects()
            ->post(route('user.judulsoal.jawab', [$quiz->slug, 1]), [
                'jawaban' => 'A',
                'sesi' => $sesi->getKey(),
            ])
            ->assertOk()
            ->assertSee('Lihat Peringkat');

        // Dan tombol itu benar-benar membuka daftar yang ada isinya.
        $this->actingAs($peserta)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Irma')
            ->assertSee('1 peserta');
    }

    /**
     * Halaman rekap milik host juga harus punya jalan ke halaman peringkat,
     * supaya host dan peserta melihat urutan yang sama dari dua tampilan.
     */
    public function test_halaman_rekap_host_menyediakan_tautan_ke_halaman_peringkat(): void
    {
        $host = $this->buatPengguna('Guru', 'guru@kelaskita.test');
        $peserta = $this->buatPengguna('Irma', 'irma@kelaskita.test');

        $quiz = $this->buatQuizKode($host);
        $sesi = $this->buatSesi($quiz, $host);
        $this->ikut($sesi, $peserta);
        $this->buatPengerjaan($peserta, $quiz, $sesi, nilai: 100, benar: 10);

        $this->actingAs($host)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSee('Rekap nilai peserta')
            ->assertSee('Lihat Peringkat')
            ->assertSee(route('user.sesi.peringkat', $sesi), escape: false);
    }
}
