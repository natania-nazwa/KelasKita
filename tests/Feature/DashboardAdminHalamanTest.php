<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\RiwayatLogin;
use App\Models\Soal;
use App\Models\User;
use App\Support\StatistikAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dashboard admin: bagian yang paling mudah berbohong.
 *
 * Tiga hal diuji di sini: login yang berhasil benar-benar dicatat (dan yang
 * gagal tidak), grafik login menghitung JUMLAH login bukan jumlah orang,
 * dan setiap bagian punya empty state supaya halaman yang datanya masih
 * kosong tidak menampilkan angka karangan.
 *
 * Test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini
 * tidak menyentuh database sungguhan.
 */
class DashboardAdminHalamanTest extends TestCase
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
            'nama' => 'Pemilik',
            'email' => 'admin@example.com',
            'peran' => 'admin',
        ]);
    }

    private function buatPelajaran(string $nama): Pelajaran
    {
        /*
         * firstOrCreate, bukan create: migration sudah mengisi baris resmi
         * daftar mata pelajaran, jadi slug yang sama bisa sudah ada. create
         * akan bentrok dengan batasan unik kolom slug.
         */
        return Pelajaran::query()->firstOrCreate(
            ['slug' => Str::slug($nama)],
            [
                'nama' => $nama,
                'deskripsi' => "Deskripsi $nama",
                'aktif' => true,
            ]
        );
    }

    private function buatMateri(Pelajaran $pelajaran, User $pembuat, string $nama, string $status = Materi::STATUS_PUBLISHED): Materi
    {
        return Materi::create([
            'pelajaran_id' => $pelajaran->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'nama' => $nama,
            'slug' => Str::slug($nama).'-'.Str::random(4),
            'deskripsi' => "Ringkasan $nama",
            'isi' => 'Isi materi untuk pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    private function buatQuiz(Pelajaran $pelajaran, User $pembuat, string $judul, string $status = Quiz::STATUS_PUBLISHED): Quiz
    {
        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.Str::random(4),
            'deskripsi' => "Ringkasan $judul",
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);

        Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'PertanyaanSingkat',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Pembahasan singkat.',
            'urutan' => 1,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);

        return $quiz;
    }

    private function buatPengerjaan(Quiz $quiz, User $pengguna): PengerjaanQuiz
    {
        return PengerjaanQuiz::create([
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => 1,
            'jumlah_dijawab' => 1,
            'jumlah_benar' => 1,
            'jumlah_salah' => 0,
            'nilai' => 100,
            'dimulai_pada' => now(),
            'selesai_pada' => now(),
        ]);
    }

    /*
     * =================================================================
     * Pencatatan login
     * =================================================================
     */

    public function test_login_berhasil_mencatat_satu_baris_riwayat_login(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect('/admin/dashboard');

        $this->assertSame(1, RiwayatLogin::where('pengguna_id', $admin->getKey())->count());
    }

    public function test_login_gagal_tidak_mencatat_riwayat_login(): void
    {
        $this->buatPengguna();

        $this->post('/login', [
            'email' => 'budi@example.com',
            'password' => 'salah-sekali',
        ]);

        // Grafik "jumlah login" menghitung kejadian yang benar-benar terjadi,
        // bukan percobaan yang ditolak.
        $this->assertSame(0, RiwayatLogin::count());
    }

    public function test_grafik_login_menghitung_jumlah_login_bukan_jumlah_orang(): void
    {
        $pengguna = $this->buatPengguna();

        // Satu orang, tiga kali masuk.
        foreach (range(1, 3) as $percobaan) {
            $this->post('/login', [
                'email' => 'budi@example.com',
                'password' => 'rahasia123',
            ]);

            $this->post('/logout');
        }

        $this->assertSame(3, RiwayatLogin::where('pengguna_id', $pengguna->getKey())->count());

        $deret = StatistikAdmin::loginMingguan();
        $mingguIni = $deret[array_key_last($deret)];

        // Tiga, bukan satu: inilah beda "jumlah login" dan "jumlah orang".
        $this->assertSame(3, $mingguIni['nilai']);
    }

    /*
     * =================================================================
     * Bentuk deret mingguan
     * =================================================================
     */

    public function test_login_mingguan_menghasilkan_enam_minggu(): void
    {
        $this->assertSame(6, StatistikAdmin::mingguLogin());
        $this->assertCount(6, StatistikAdmin::loginMingguan());
    }

    public function test_setiap_minggu_memakai_label_dinamis_yang_pendek(): void
    {
        foreach (StatistikAdmin::loginMingguan() as $minggu) {
            $this->assertNotSame('', $minggu['label']);
            $this->assertNotSame('', $minggu['bulan']);
            $this->assertNotSame('', $minggu['rentang']);
            $this->assertIsInt($minggu['nilai']);

            /*
             * Label dan bulan tidak boleh memuat tahun. Kalau tidak, label
             * sumbu X jadi terlalu panjang dan enam label akan saling
             * bertabrakan; teks lengkap ada di "rentang" untuk tooltip.
             */
            $this->assertStringNotContainsString((string) now()->year, $minggu['label']);
            $this->assertStringNotContainsString((string) now()->year, $minggu['bulan']);
        }
    }

    public function test_deret_mingguan_berurutan_dari_minggu_lama_ke_minggu_berjalan(): void
    {
        $deret = StatistikAdmin::loginMingguan();

        $awalMingguIni = now()->startOfWeek();

        foreach ($deret as $posisi => $minggu) {
            // Enam minggu terakhir: posisi 0 = lima minggu lalu, posisi
            // terakhir = minggu yang sedang berjalan.
            $diharapkan = $awalMingguIni->copy()->subWeeks(5 - $posisi);

            $this->assertSame(
                $diharapkan->toDateString(),
                $minggu['mulai'],
                "Minggu ke-$posisi dimulai dari tanggal yang salah.",
            );
        }
    }

    public function test_login_lama_tidak_ikut_terhitung_di_minggu_mana_saja(): void
    {
        $pengguna = $this->buatPengguna();

        // Satu login, tapi terjadi lebih dari enam minggu lalu.
        //
        // created_at sengaja tidak ada di daftar Fillable RiwayatLogin, jadi
        // timestamp harus dipasang lewat forceFill: kalau ikut dikirim ke
        // create(), ia diam-diam diabaikan dan baris ini tercipta untuk
        // "sekarang", bukan untuk tanggal yang dimaksud.
        $riwayat = RiwayatLogin::create(['pengguna_id' => $pengguna->getKey()]);
        $riwayat->forceFill(['created_at' => now()->subWeeks(9)])->save();

        $total = collect(StatistikAdmin::loginMingguan())->sum('nilai');

        $this->assertSame(0, $total);
    }

    /*
     * =================================================================
     * Tampilan dashboard
     * =================================================================
     */

    public function test_dashboard_admin_menampilkan_semua_bagian(): void
    {
        $this->actingAs($this->buatAdmin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Selamat datang')
            ->assertSee('Pengguna')
            ->assertSee('Materi')
            ->assertSee('Quiz')
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Perlu Ditinjau')
            ->assertSee('Menu Cepat')
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('Pelajaran yang Disukai')
            ->assertSee('Aktivitas Login Mingguan')
            ->assertDontSee('total-menunggu=', false);
    }

    public function test_dashboard_admin_meneruskan_data_hasil_hitung_dari_server(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman');

        $this->buatMateri($pelajaran, $siswa, 'Materi Tetap');
        $this->buatMateri($pelajaran, $siswa, 'Materi Menunggu', Materi::STATUS_PENDING);
        $this->buatQuiz($pelajaran, $siswa, 'Quiz Menunggu', Quiz::STATUS_PENDING);

        $ringkasan = StatistikAdmin::ringkasan();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('ringkasan', $ringkasan)
            ->assertViewHas('loginMingguan', StatistikAdmin::loginMingguan());

        // Dua pengguna. Kartu materi dan quiz menampilkan yang bertambah
        // dalam 30 hari terakhir, jadi satu materi terbit yang dihitung;
        // materi dan quiz pending tidak masuk ke kartu tapi tetap tercatat
        // di antrean verifikasi.
        $this->assertSame(2, $ringkasan['pengguna']);
        $this->assertSame(1, $ringkasan['materi']);
        $this->assertSame(0, $ringkasan['quiz']);
        $this->assertSame(1, $ringkasan['materi_menunggu']);
        $this->assertSame(1, $ringkasan['quiz_menunggu']);
    }

    public function test_kartu_materi_dan_quiz_mengabaikan_konten_lama_dan_yang_belum_terbit(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman');

        $materiBaru = $this->buatMateri($pelajaran, $siswa, 'Materi Baru');
        $quizBaru = $this->buatQuiz($pelajaran, $siswa, 'Quiz Baru');
        $materiLama = $this->buatMateri($pelajaran, $siswa, 'Materi Lama');
        $quizLama = $this->buatQuiz($pelajaran, $siswa, 'Quiz Lama');

        $this->buatMateri($pelajaran, $siswa, 'Materi Pending', Materi::STATUS_PENDING);
        $this->buatQuiz($pelajaran, $siswa, 'Quiz Pending', Quiz::STATUS_PENDING);

        // Dipindahkan ke luar jendela 30 hari, jadi tidak boleh menambah
        // angka kartu meski statusnya sudah terbit.
        $materiLama->forceFill(['created_at' => now()->subDays(45)])->save();
        $quizLama->forceFill(['created_at' => now()->subDays(45)])->save();

        $ringkasan = StatistikAdmin::ringkasan();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Materi')
            ->assertSee('Quiz');

        $this->assertSame(1, $ringkasan['materi']);
        $this->assertSame(1, $ringkasan['quiz']);

        // Angka kartu sama persis dengan jumlah baru pada rentang yang
        // sama, jadi baris persentase di bawahnya bisa dibandingkan.
        $this->assertSame($ringkasan['perubahan']['materi']['bulan_ini'], $ringkasan['materi']);
        $this->assertSame($ringkasan['perubahan']['quiz']['bulan_ini'], $ringkasan['quiz']);
    }

    public function test_kartu_menunggu_verifikasi_menampilkan_rincian_asli(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman');

        $this->buatMateri($pelajaran, $siswa, 'Materi Menunggu', Materi::STATUS_PENDING);
        $this->buatQuiz($pelajaran, $siswa, 'Quiz Menunggu', Quiz::STATUS_PENDING);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('1 materi · 1 quiz')
            ->assertSee('Materi Menunggu')
            ->assertSee('Quiz Menunggu')
            // Label kategori di baris meta, supaya tidak jadi deretan kata
            // tanpa konteks.
            ->assertSee('Kategori: Pemrograman')
            ->assertSee('Menunggu Verifikasi');
    }

    public function test_dashboard_admin_menampilkan_empty_state_saat_belum_ada_aktivitas(): void
    {
        $this->actingAs($this->buatAdmin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Belum ada data aktivitas pembelajaran')
            ->assertSee('Semua sudah ditinjau')
            ->assertSee('Belum ada aktivitas');
    }

    public function test_donat_pelajaran_menampilkan_porsi_dihitung_dari_pengerjaan(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $matematika = $this->buatPelajaran('Matematika');
        $pplg = $this->buatPelajaran('PPLG');

        // Tiga pengerjaan Matematika, satu pengerjaan PPLG: donat harus
        // menampilkan keduanya dengan porsi 75% dan 25%.
        foreach (range(1, 3) as $percobaan) {
            $this->buatPengerjaan($this->buatQuiz($matematika, $siswa, "Quiz Matematika $percobaan"), $siswa);
        }

        $this->buatPengerjaan($this->buatQuiz($pplg, $siswa, 'Quiz PPLG'), $siswa);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('PPLG')
            ->assertSee('75%')
            ->assertSee('25%');
    }

    public function test_dua_kartu_analytics_sejajar_dan_sama_tinggi(): void
    {
        $html = $this->actingAs($this->buatAdmin())->get('/admin/dashboard')->assertOk()->getContent();

        /*
         * Kedua kartu analytics harus jadi dua anak langsung dari satu
         * grid yang sama. Kalau salah satunya dibungkus div lain,
         * atau salah satunya memakai align-self/height sendiri, tinggi
         * keduanya jadi berbeda dan tepi bawahnya tidak rata.
         */
        $awal = strpos($html, 'ad-grid--analitik');
        $this->assertNotFalse($awal, 'Baris analytics harus ada.');

        /*
         * Ketiga kartu di baris ini semuanya elemen <section>: kartu
         * kiri, kartu kanan, lalu baris grid-nya. Jadi penutup baris
         * adalah </section> KETIGA setelah titik awal, bukan yang
         * pertama -- yang pertama cuma penutup kartu kiri.
         */
        $akhir = $awal;

        for ($i = 0; $i < 3; $i++) {
            $akhir = strpos($html, '</section>', (int) $akhir);
            $this->assertNotFalse($akhir, 'Baris analytics harus ditutup.');
            $akhir += strlen('</section>');
        }

        $baris = substr($html, (int) $awal, (int) $akhir - (int) $awal);

        // Tepat dua kartu di dalam baris itu, tanpa wrapper tambahan.
        $this->assertSame(2, substr_count($baris, 'class="ad-kartu ad-analitik"'));

        /*
         * Root cause yang pernah membuat kartu kanan turun 28px: kedua
         * kartu ikut memakai .ad-seksi. Kelas itu tidak punya deklarasi
         * sendiri, satu-satunya aturannya .ad-seksi + .ad-seksi
         * { margin-top } -- untuk menjeda antara section halaman. Karena
         * kedua kartu adalah sibling bersebelahan di dalam grid, rule itu
         * menyala pada kartu kedua dan memberinya margin-top 1,75rem.
         *
         * Kartu di dalam grid TIDAK BOLEH memakai .ad-seksi.
         */
        $this->assertStringNotContainsString('ad-seksi ad-kartu ad-analitik', $baris);
        $this->assertStringNotContainsString('ad-kartu ad-analitik ad-seksi', $baris);

        // Tidak ada yang menahan tinggi salah satu kartu.
        $this->assertStringNotContainsString('ad-analitik--ringkas', $baris);

        foreach (['align-self', 'translate-y', 'ad-kartu--tinggi'] as $yangDilarang) {
            $this->assertStringNotContainsString($yangDilarang, $baris);
        }

        // Isi keduanya dipusatkan vertikal supaya card yang lebih pendek
        // tidak menyisakan ruang kosong menumpuk di bawah.
        $this->assertStringNotContainsString('ad-analitik--ringkas', $html);
    }

    public function test_kaki_kartu_analytics_menampilkan_keterangan_sumber_data(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        $this->buatPengerjaan(
            $this->buatQuiz($this->buatPelajaran('Matematika'), $siswa, 'Quiz Matematika'),
            $siswa
        );

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        // Keterangan asal datamuch di bawah pie chart.
        $this->assertStringContainsString('Data berdasarkan aktivitas pembelajaran pengguna', $html);

        // Rekap login di bawah grafik, dengan rentang minggunya ditulis
        // dari jumlah data, bukan angka mati di markup.
        $this->assertStringContainsString('Total login dalam 6 minggu terakhir', $html);

        // Donat tetap di kiri, legendarinya di kanan (pakai susun baris,
        // bukan susun kolom).
        $this->assertStringContainsString('ad-donat__susun', $html);
    }

    public function test_perlu_ditinjau_menampilkan_empat_antrean_terbaru(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman');

        // Enam materi menunggu. Kartu harus menampilkan empat yang
        // terbaru, bukan empat pertama yang kebetulan terambil.
        foreach (range(1, 6) as $urutan) {
            $materi = $this->buatMateri($pelajaran, $siswa, "Materi Antre $urutan", Materi::STATUS_PENDING);
            $materi->forceFill(['created_at' => now()->addSecond($urutan)])->save();
        }

        $halaman = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        // Empat terbaru: nomor 3 sampai 6.
        $halaman->assertSee('Materi Antre 6')
            ->assertSee('Materi Antre 5')
            ->assertSee('Materi Antre 4')
            ->assertSee('Materi Antre 3');

        /*
         * Pemeriksaan "yang lama tidak ikut tampil" harus dibatasi ke
         * daftar "Perlu Ditinjau" saja. Materi Antre 1 dan 2 memang tidak
         * ada di sana, tapi namanya tetap muncul di card "Aktivitas
         * Terbaru", jadi assertDontSee() ke seluruh halaman akan salah dan
         * lulus tanpa benar-benar menguji apa pun.
         */
        $daftar = self::potongDaftarTinjau($halaman->getContent());

        $this->assertStringNotContainsString('Materi Antre 2', $daftar);
        $this->assertStringNotContainsString('Materi Antre 1', $daftar);

        // Tepat empat baris, bukan "minimal empat".
        $this->assertSame(4, substr_count($daftar, 'ad-tinjau__item'));
    }

    public function test_tombol_tinjau_menuju_verifikasi_dan_membuka_panel_konten_nya(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);
        $pelajaran = $this->buatPelajaran('Pemrograman');

        $materi = $this->buatMateri($pelajaran, $siswa, 'Materi Menunggu', Materi::STATUS_PENDING);
        $quiz = $this->buatQuiz($pelajaran, $siswa, 'Quiz Menunggu', Quiz::STATUS_PENDING);

        $halaman = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $daftar = self::potongDaftarTinjau($halaman->getContent());

        $tautanMateri = route('admin.verifikasi', ['pilih' => 'materi:'.$materi->getKey()]);
        $tautanQuiz = route('admin.verifikasi', ['pilih' => 'quiz:'.$quiz->getKey()]);

        /*
         * Tombol "Tinjau" harus membuka Verifikasi, masing-masing dengan
         * konten yang ditekan.
         *
         * Tujuannya tidak boleh katalog Materi atau Quiz: kedua halaman itu
         * hanya menampilkan konten yang sudah tayang, sedangkan antrean di
         * sini justru konten yang menunggu, jadi admin akan mendarat di
         * halaman yang tidak memuat konten itu.
         */
        $this->assertStringContainsString($tautanMateri, $daftar);
        $this->assertStringContainsString($tautanQuiz, $daftar);
        $this->assertStringNotContainsString(route('admin.materi'), $daftar);
        $this->assertStringNotContainsString(route('admin.quiz'), $daftar);

        // Tujuan tautan itu harus benar-benar berguna: panel review kontennya terbuka.
        $this->actingAs($admin)->get($tautanMateri)
            ->assertOk()
            ->assertSee('data-vf-id="'.$materi->getKey().'"', false)
            ->assertSee('Materi Menunggu');

        $this->actingAs($admin)->get($tautanQuiz)
            ->assertOk()
            ->assertSee('data-vf-id="'.$quiz->getKey().'"', false)
            ->assertSee('Quiz Menunggu');
    }

    /**
     * Potong HTML dashboard hanya bagian daftar "Perlu Ditinjau".
     *
     * Baris tinjau adalah satu-satunya elemen <article> di halaman ini
     * (baris aktivitas memakai <div>), jadi batas bawahnya bisa dicari dari
     * </article> terakhir. Dipisah jadi method sendiri supaya test di atas
     * tetap enak dibaca.
     */
    private static function potongDaftarTinjau(string $html): string
    {
        $awal = strpos($html, 'ad-tinjau--besar');
        $akhir = strrpos($html, '</article>');

        if ($awal === false || $akhir === false || $akhir < $awal) {
            return '';
        }

        return substr($html, $awal, $akhir - $awal);
    }

    public function test_kartu_kutipan_menampilkan_teks_dan_ilustrasi_buku(): void
    {
        $html = $this->actingAs($this->buatAdmin())->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Konten berkualitas, untuk pembelajaran yang lebih baik.', $html);
        $this->assertStringContainsString('images/buku.png', $html);
    }

    public function test_iringan_donat_tidak_saling_menumpuk(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatPengguna(['nama' => 'Sari', 'email' => 'sari@example.com']);

        foreach (['Matematika', 'PPLG', 'Bahasa Inggris', 'IPA'] as $nama) {
            $this->buatPengerjaan($this->buatQuiz($this->buatPelajaran($nama), $siswa, "Quiz $nama"), $siswa);
        }

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $cocok = preg_match_all(
            '/stroke-dasharray="([0-9.]+)\s+[0-9.]+"\s+stroke-dashoffset="(-?[0-9.]+)"/',
            $html,
            $pasangan,
            PREG_SET_ORDER
        );

        $this->assertSame(4, $cocok, 'Keempat pelajaran harus muncul sebagai iringan donat.');

        /*
         * Setiap iringan digeser sebesar jumlah panjang iringan-iringan
         * sebelumnya, jadi offset-nya terus makin negatif.
         *
         * Kalau offset-nya semua sama, semua iringan mulai dari jam 12 dan
         * saling menutupi: donat terlihat seperti satu busur acak, bukan
         * lingkaran yang terbagi. Ini yang membuat donat terlihat berantakan
         * sebelum offset-nya ditambahkan.
         */
        $offset = array_map(fn (array $pasangan): float => (float) $pasangan[2], $pasangan);

        $this->assertGreaterThanOrEqual(0, $offset[0], 'Iringan pertama dimulai tepat di jam 12, jadi offset-nya nol.');

        $sebelumnya = $offset[0];

        foreach (array_slice($offset, 1) as $satu) {
            $this->assertLessThan($sebelumnya, $satu, 'Offset iringan berikutnya harus lebih negatif dari sebelumnya.');
            $sebelumnya = $satu;
        }

        // Panjang busur + geser tidak boleh melebihi keliling lingkaran,
        // kalau tidak iringan terakhir menimpa iringan pertama.
        foreach ($pasangan as $satu) {
            $this->assertLessThanOrEqual(
                2 * M_PI * 52 + 0.01,
                (float) $satu[1] - (float) $satu[2],
                'Panjang busur dan gesernya melebihi keliling lingkaran.',
            );
        }
    }

    /**
     * Ikon Pengaturan di header ponsel tanpa kartu putih.
     *
     * Header ponsel (.ad-hp) menggantikan sidebar di bawah 768px, dan tombol
     * di kananannya memakai .ad-hp__tombol — kotak putih bertepi. Avatar
     * sudah sejak awal memakai variannya yang polos; ikon roda
     * (/admin/pengaturan) ikut disamakan.
     *
     * Alasannya: yang harus terbaca di header adalah ikonnya. Lingkaran
     * avatar sudah punya bentuk sendiri, dan ikon roda berada di atas header
     * yang sudah berlatar — jadi kotak putih di belakang keduanya bukan
     * tombol, melainkan kartu putih kecil yang tidak diminta.
     *
     * Dua sisi yang dijaga, karena hanya salah satu yang bisa membuat
     * perubahan ini berbalik arah:
     *
     *   - tombolnya memakai variannya yang polos, dan aturan CSS itu benar
     *     benar menghapus latar dan garisnya;
     *   - tombolnya tetap punya aria-label. Menghapus kotak tidak boleh
     *     membuat ikon kehilangan nama untuk pembaca layar.
     */
    public function test_ikon_pengaturan_di_header_ponsel_tanpa_kartu_putih(): void
    {
        $html = $this->actingAs($this->buatAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="ad-hp__tombol ad-hp__tombol--polos"', $html);
        $this->assertStringContainsString('aria-label="Pengaturan"', $html);

        // Avatar ikut memakai aturan polos yang sama, jadi keduanya konsisten.
        $this->assertStringContainsString('ad-hp__tombol--avatar', $html);

        $css = (string) preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/admin.css')));

        $this->assertMatchesRegularExpression(
            '/\.ad-hp__tombol--avatar,\s*\.ad-hp__tombol--polos\s*\{\s*border-color: transparent;\s*background-color: transparent;/',
            $css,
            'Varian tombol polos harus menghapus garis dan latar putihnya.'
        );

        // Kotak putih tetap ada di aturan dasarnya: kelas dasar tidak ikut berubah,
        // jadi halaman lain yang memakainya tetap konsisten dengan bentuk tombol admin.
        preg_match('/\.ad-hp__tombol\s*\{([^}]*)\}/', $css, $cocok);
        $this->assertStringContainsString('background-color: var(--ad-permukaan)', $cocok[1] ?? '');
    }
}
