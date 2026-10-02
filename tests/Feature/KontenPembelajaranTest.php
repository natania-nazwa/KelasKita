<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use App\Support\IsianSoalQuiz;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Menu "Konten Pembelajaran" di area admin.
 *
 * Yang diuji di sini adalah kontrak fitur ini, yaitu apa yang harus benar
 * dan apa yang tidak boleh terjadi:
 *
 *   - draft dan published sama-sama bisa dikelola admin, dan tidak ada
 *     tahap persetujuan di antara keduanya;
 *   - konten yang terbit benar-benar muncul di halaman pengguna dan
 *     disertai notifikasi, sementara draft tidak pernah muncul;
 *   - memindahkan konten ke draft lagi langsung menariknya dari sisi
 *     pengguna;
 *   - halaman dan aksinya tetap tertutup untuk selain admin.
 *
 * Test ini sengaja tidak mengulang aturan validasi field, karena aturan itu
 * milik MateriIsianRequest dan QuizIsianRequest yang sudah diuji di test
 * form masing-masing.
 */
class KontenPembelajaranTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
            'peran' => User::PERAN_USER,
            'aktif' => true,
        ], $atribut))->refresh();
    }

    private function buatAdmin(string $email = 'admin@example.com'): User
    {
        return $this->buatPengguna([
            'nama' => 'Admin',
            'email' => $email,
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

    private function buatMateri(?User $pemilik, string $status, string $nama = 'Materi Uji'): Materi
    {
        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.Materi::query()->count(),
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk sebuah pengujian.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => $status,
        ]);
    }

    private function buatQuiz(?User $pemilik, string $status, string $judul = 'Quiz Uji'): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->id,
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
     * @return array<string, mixed>
     */
    private function dataMateri(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Materi Dari Form',
            'isi' => 'Isi materi yang sudah lebih dari dua puluh karakter.',
            'tingkat_kesulitan' => 'Mudah',
            'aksi' => 'draft',
        ], $tambahan);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataQuiz(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'judul' => 'Quiz Dari Form',
            'deskripsi' => 'Ringkasan quiz.',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'tampilkan_jawaban' => 1,
            'durasi' => 15,
            'aksi' => 'draft',
            'soal' => [[
                'pertanyaan' => 'Apa itu HTML?',
                'tipe' => Soal::TIPE_PILIHAN_GANDA,
                'pilihan' => ['A' => 'Bahasa', 'B' => 'Markup'],
                'benar' => ['B'],
                'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            ]],
        ], $tambahan);
    }

    /**
     * Notifikasi milik pengguna.
     *
     * Notifikasi admin tentang karyanya sendiri (App\Support\NotifikasiAdmin)
     * sengaja tidak dihitung di sini: yang dijamin fitur ini adalah notifikasi
     * yang sampai ke pengguna, jadi hanya itu yang dihitung.
     */
    private function notifikasiPengguna(): Collection
    {
        return Notifikasi::query()
            ->whereIn('pengguna_id', User::query()->where('peran', User::PERAN_USER)->pluck('id'))
            ->get();
    }

    /* ================= Halaman daftar ================= */

    public function test_admin_melihat_halaman_konten_pembelajaran_dengan_kedua_kartu_aksi(): void
    {
        $admin = $this->buatAdmin();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Konten Pembelajaran')
            ->assertSee('Kelola materi dan kuis untuk mendukung proses pembelajaran di Kelas Kita.')
            ->assertSee('Tambah Materi')
            ->assertSee('Buat materi pembelajaran untuk peserta didik.')
            ->assertSee('Tambah Kuis')
            ->assertSee('Buat kuis untuk menguji pemahaman peserta didik.')
            ->baseResponse->getContent();

        /*
         * Dua kartu aksi harus berdampingan dengan lebar sama di desktop,
         * dan susunannya vertikal di mobile. Tanpa ini, "Tambah Materi" dan
         * "Tambah Kuis" bisa tetap muncul tapi tidak berdampingan — dan itu
         * justru bagian yang paling kelihatan dari halaman ini.
         */
        $this->assertStringContainsString('ad-konten-aksi__kartu', $halaman);

        /*
         * Dihitung dari "kartu" diikuti modifier-nya, bukan dari
         * "ad-konten-aksi__kartu" polos: nama modifier sudah diawali nama
         * kartu, jadi menghitung plain-nya akan menghitung tiap kartu dua kali
         * begitu modifier ditambahkan.
         */
        $this->assertSame(2, substr_count($halaman, 'ad-konten-aksi__kartu ad-konten-aksi__kartu--'));
        $this->assertSame(2, substr_count($halaman, 'ad-konten-aksi__tombol'));

        /*
         * Masing-masing kartu aksi harus punya modifiernya sendiri, karena
         * modifier itu yang membedakan warnanya. Kalau salah satu hilang, dua
         * kartu kembali sama persis padahal isinya beda jenis.
         */
        $this->assertStringContainsString('ad-konten-aksi__kartu--materi', $halaman);
        $this->assertStringContainsString('ad-konten-aksi__kartu--kuis', $halaman);
    }

    /**
     * Urutan bagian halaman: kartu aksi, baris alat, tab, daftar.
     *
     * Baris alat (cari + filter) menempel di bawah dua kartu aksi, bukan di
     * bawah tab. Dulu tab berdiri sendiri di antaranya, sehingga baris alat
     * terputus dari kartu aksi oleh satu baris yang tidak ada hubungannya
     * dengan pencarian.
     *
     * Yang diperiksa posisinya, bukan hanya keberadaannya: keempat bagiannya
     * memang ada di halaman, jadi tanpa dibandingkan posisinya test ini akan
     * lolos meski urutannya dibalik lagi.
     */
    public function test_baris_alat_tepat_di_bawah_kartu_aksi_dan_di_atas_tab(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->baseResponse->getContent();

        $posisi = [
            'kartu aksi' => strpos($halaman, 'class="ad-konten-aksi"'),
            'baris alat' => strpos($halaman, 'ad-konten-alat-kotak'),
            'tab' => strpos($halaman, '<nav class="ad-konten-tab"'),
            'daftar' => strpos($halaman, 'data-konten-daftar'),
        ];

        foreach ($posisi as $bagian => $tempat) {
            $this->assertNotFalse($tempat, "Bagian {$bagian} tidak ada di halaman.");
        }

        $this->assertLessThan(
            $posisi['baris alat'],
            $posisi['kartu aksi'],
            'Kartu aksi harus di atas baris alat.'
        );

        $this->assertLessThan($posisi['tab'], $posisi['baris alat'], 'Baris alat harus di atas tab.');

        $this->assertLessThan($posisi['daftar'], $posisi['tab'], 'Tab harus di atas daftar.');
    }

    public function test_daftar_menampilkan_draft_dan_published_bersama(): void
    {
        $admin = $this->buatAdmin();

        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Sudah Tayang');

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Materi Mentah')
            ->assertSee('Materi Sudah Tayang')
            ->assertSee('Draft')
            ->assertSee('Published');
    }

    public function test_daftar_berupa_grid_kartu_sama_seperti_karya_saya(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Mentah');

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        /*
         * Daftar di halaman ini adalah grid kartu, sama seperti "Karya Saya"
         * milik pengguna, dan memakai komponen yang sama: x-admin.konten-kartu
         * yang sudah memakai kelas kartu-materi, karya-kartu, karya-info, dan
         * karya-aksi. Dua-duanya ditolak kalau kartu ditulis ulang di sini:
         * grid-nya akan melebar sendiri karena satu judul tanpa spasi, dan isi
         * yang sama bisa muncul dua kali kalau kartu lama dibiarkan.
         */
        $halaman->assertSee('ad-konten-daftar', false)
            ->assertSee('data-konten-daftar', false)
            ->assertSee('kartu-materi karya-kartu kartu-konten', false);

        // Komponen baris yang pernah dipakai di sini sudah tidak boleh ada.
        $halaman->assertDontSee('ad-konten-baris', false);

        /*
         * Empat kolom di layar lebar. Yang dijaga lewat admin.css karena
         * jumlah kolom tidak bisa dibaca dari HTML.
         */
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        preg_match('/\.ad-konten-daftar\s*\{([^}]*)\}/', $css, $cocok);

        $this->assertNotEmpty($cocok, 'Aturan .ad-konten-daftar tidak ada di admin.css.');
        $this->assertStringContainsString('display: grid', $cocok[1]);

        $this->assertStringContainsString('repeat(4, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('repeat(2, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('repeat(3, minmax(0, 1fr))', $css);

        // "Karya Saya" milik pengguna tidak boleh ikut berubah.
        $karyaSaya = $this->actingAs($admin)
            ->get(route('user.karya-saya'))
            ->assertOk();

        $karyaSaya->assertSee('grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 xl:grid-cols-3', false)
            ->assertSee('kartu-materi karya-kartu', false);
    }

    public function test_kartu_menampilkan_judul_metadata_tanggal_dan_status(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Pengenalan HTML');

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        $halaman->assertSee('Pengenalan HTML')
            ->assertSee('Materi')
            ->assertSee('Bab')
            ->assertSee('Published');

        $isi = $halaman->baseResponse->getContent();

        /*
         * Judul dan kategori jadi tautan, dan tanggal lewat karya-info seperti
         * di kartu pengguna. Keduanya penting: tanpa tautan judul, kartu satu
         *-satunya cara untuk membuka halaman detailnya.
         */
        $this->assertStringContainsString('karya-judul', $isi);
        $this->assertStringContainsString('karya-info__butir', $isi);
        $this->assertStringContainsString('kartu-materi__lencana', $isi);

        /*
         * Kelas tujuan sudah dihapus dari seluruh aplikasi, jadi kartu tidak
         * boleh lagi menaruh lencana kelas — termasuk kotak kosong untuk konten
         * yang belum punya kelas, yang cuma menambah ruang kosong tanpa
         * memberi informasi apa pun.
         */
        $this->assertStringNotContainsString('ad-konten-lencana', $isi);
        $this->assertStringNotContainsString('Kelas belum ditentukan', $isi);
    }

    public function test_lencana_status_memakai_warna_terbit_dan_draft(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Tayang');

        $isi = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->baseResponse->getContent();

        /*
         * Dua warna status harus benar-benar berbeda. Kalau keduanya memakai
         * kelas yang sama, admin tidak bisa membedakan draft dari yang sudah
         * tayang hanya dari warna — dan warna itu justru pembeda utama di
         * daftar ini.
         *
         * Lencana status di kartu ini milik "Karya Saya" (karya-status--*).
         * Dipakai apa adanya, jadi warna draft dan terbit di kedua tempat
         * dijamin sama.
         */
        $this->assertStringContainsString('karya-status--draft', $isi);
        $this->assertStringContainsString('karya-status--terbit', $isi);
    }

    public function test_menu_aksi_sesuai_status_tidak_menampilkan_publish_dua_kali(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Sudah Tayang');

        /*
         * Label aksinya dibaca dari data-konten-terbit-tombol, bukan dari teks
         * tombol. Ini bukan detail teknis: label itu yang dipakai dialog
         * konfirmasi, jadi kalau salah di sini, admin menekan "Batalkan
         * Publikasi" tapi dialognya menawarkan "Publish Sekarang".
         *
         * Teks tombolnya sendiri tidak diuji lewat ">Publish<" karena Blade
         * menuliskan label di baris sendiri, jadi spasi-newline membuat
         * pola itu tidak pernah cocok.
         */
        $halamanMateri = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        $halamanMateri->assertSee('data-konten-terbit-judul="Publish konten?"', false)
            ->assertSee('data-konten-terbit-tombol="Publish Sekarang"', false)
            ->assertDontSee('data-konten-terbit-judul="Batalkan publikasi?"', false);

        $halamanQuiz = $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk();

        $halamanQuiz->assertSee('data-konten-terbit-judul="Batalkan publikasi?"', false)
            ->assertSee('data-konten-terbit-tombol="Batalkan Publikasi"', false)
            ->assertDontSee('data-konten-terbit-judul="Publish konten?"', false);

        /*
         * Aksi lain tetap tersedia di kedua status.
         *
         * Yang diperiksa lewat URL tujuan, bukan teks tombolnya. Dua alasan:
         * Blade menuliskan label di baris sendiri sehingga pola ">Lihat<"
         * tidak pernah cocok, dan URL-nya justru yang benar-benar dipakai
         * admin — kalau salah di situ, aksinya akan menuju tempat lain.
         */
        $halamanMateri->assertSee(route('admin.materi.show', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.edit', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.duplikat', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.publish', $materi->slug), false);

        $halamanQuiz->assertSee(route('admin.quiz.show', $quiz), false)
            ->assertSee(route('admin.konten.quiz.edit', $quiz), false)
            ->assertSee(route('admin.konten.quiz.duplikat', $quiz), false)
            ->assertSee(route('admin.konten.quiz.publish', $quiz), false);
    }

    public function test_menu_aksi_dibuka_dengan_tombol_titik_tiga(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $isi = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->baseResponse->getContent();

        /*
         * Aksi tidak lagi berupa lima tombol yang selalu terbuka: semuanya
         * masuk ke satu menu di kanan baris. Pemicunya harus punya
         * aria-expanded supaya pembaca tahu keadaan terbuka atau tertutup, dan
         * menunya harus disembunyikan lewat atribut hidden di markup supaya
         * tidak pernah bisa difokus keyboard sebelum dibuka.
         */
        $this->assertStringContainsString('data-konten-menu-tombol', $isi);
        $this->assertStringContainsString('aria-expanded="false"', $isi);
        $this->assertStringContainsString('aria-haspopup="menu"', $isi);
        $this->assertStringContainsString('data-konten-menu-isi', $isi);
        $this->assertStringContainsString('role="menu"', $isi);
    }

    public function test_dialog_publish_bicara_tentang_batal_publikasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Sudah Tayang');

        $isi = $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk()
            ->baseResponse->getContent();

        /*
         * Aksi publish punya dua arah. Kalau kalimat dialog-nya tidak ikut
         * berubah, admin yang hanya ingin menarik konten akan menekan dialog
         * bertuliskan "Publish konten?" dan tidak pernah diberi tahu bahwa ia
         * sedang membatalkan.
         */
        $this->assertStringContainsString('data-konten-terbit-judul="Batalkan publikasi?"', $isi);
        $this->assertStringContainsString('data-konten-terbit-tombol="Batalkan Publikasi"', $isi);
        $this->assertStringContainsString('akan ditarik dari halaman pengguna', $isi);
    }

    public function test_dialog_hapus_lebih_jelas_untuk_konten_yang_sudah_tayang(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Tayang');

        $isi = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->baseResponse->getContent();

        /*
         * Menghapus materi yang sudah tayang lebih berbahaya daripada
         * menghapus draft: yang ikut hilang bukan cuma barisnya di daftar
         * admin, tapi juga halamannya di sisi pengguna. Kalau kalimatnya
         * sama untuk keduanya, admin tidak tahu bedanya.
         */
        $this->assertStringContainsString('sudah tayang untuk pengguna', $isi);
    }

    public function test_kartu_menampilkan_kategori_dan_jumlah_bab_atau_soal(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran('Matematika', 'matematika');
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Matematika');
        $materi->update(['pelajaran_id' => $pelajaran->getKey()]);

        $quiz = $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Matematika');
        $quiz->update(['pelajaran_id' => $pelajaran->getKey()]);

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Bab');

        $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Soal');
    }

    public function test_daftar_menyaring_berdasarkan_status(): void
    {
        $admin = $this->buatAdmin();

        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Sudah Tayang');

        $this->actingAs($admin)
            ->get(route('admin.konten', ['status' => Materi::STATUS_DRAFT]))
            ->assertOk()
            ->assertSee('Materi Mentah')
            ->assertDontSee('Materi Sudah Tayang');
    }

    public function test_daftar_mencari_dan_menyaring_kategori(): void
    {
        $admin = $this->buatAdmin();
        $pemrograman = $this->buatPelajaran('Pemrograman', 'pemrograman');
        $matematika = $this->buatPelajaran('Matematika', 'matematika');

        Materi::create([
            'pelajaran_id' => $pemrograman->id,
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Belajar Blade',
            'slug' => 'belajar-blade',
            'isi' => 'IsiBlade',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_DRAFT,
        ]);

        Materi::create([
            'pelajaran_id' => $matematika->id,
            'dibuat_oleh' => $admin->getKey(),
            'nama' => 'Belajar Pecahan',
            'slug' => 'belajar-pecahan',
            'isi' => 'IsiPecahan',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_DRAFT,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.konten', ['q' => 'blade']))
            ->assertOk()
            ->assertSee('Belajar Blade')
            ->assertDontSee('Belajar Pecahan');

        $this->actingAs($admin)
            ->get(route('admin.konten', ['kategori' => 'matematika']))
            ->assertOk()
            ->assertSee('Belajar Pecahan')
            ->assertDontSee('Belajar Blade');
    }

    public function test_hapus_filter_menuju_halaman_bersih_dan_tabnya_tetap_sama(): void
    {
        $admin = $this->buatAdmin();

        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Batu');
        $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Sellang');

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten', [
                'tab' => 'quiz',
                'status' => Quiz::STATUS_DRAFT,
                'urut' => 'terlama',
            ]))
            ->assertOk()
            ->assertSee('Quiz Batu')
            ->assertDontSee('Quiz Sellang');

        // Form "Hapus filter" tidak punya field apa pun kecuali tab, jadi
        // tujuannya pasti halaman polos yang tabnya sama.
        $halaman->assertSee('<form method="GET" action="'.route('admin.konten').'"', false);
        $halaman->assertSee('<input type="hidden" name="tab" value="quiz">', false);

        $setelahDihapus = $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk();

        $setelahDihapus->assertSee('Quiz Batu')
            ->assertSee('Quiz Sellang')
            ->assertDontSee('Quiz tidak ditemukan');
    }

    public function test_hapus_filter_tetap_hidup_walaupun_tidak_ada_filter_aktif(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        // Tidak pernah diberi disabled: tombol yang kelihatan seperti tombol
        // tapi mati saat diklik dibaca sebagai tombol rusak.
        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Hapus filter')
            ->assertDontSee('disabled', false);
    }

    public function test_ringkasan_menampilkan_filter_yang_sedang_aktif(): void
    {
        $admin = $this->buatAdmin();
        $matematika = $this->buatPelajaran('Matematika', 'matematika');
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Belajar Pecahan');

        $this->actingAs($admin)
            ->get(route('admin.konten', [
                'q' => 'pecahan',
                'status' => Materi::STATUS_DRAFT,
                'kategori' => $matematika->slug,
                'urut' => 'terlama',
            ]))
            ->assertOk()
            ->assertSee('Kata kunci')
            ->assertSee('"pecahan"', false)
            ->assertSee('Status')
            ->assertSee('Kategori')
            ->assertSee('Matematika')
            ->assertSee('Urutan')
            ->assertSee('Terlama');
    }

    public function test_tanpa_filter_aktif_ringkasan_tidak_muncul(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertDontSee('ad-alat-baris__simpul', false);
    }

    public function test_baris_alat_memuat_cari_kategori_status_dan_urut(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        /*
         * Empat kontrol dalam satu wadah: kotak cari, Kategori, Status,
         * dan Urutan. Semuanya terkirim lewat SATU form supaya "Terapkan"
         * benar-benar menerapkan semua filter sekaligus — kalau terbagi dua
         * form, satu tombol hanya berlaku untuk sebagian filter.
         */
        $halaman->assertSee('class="ad-konten-alat"', false)
            ->assertSee('data-konten-saring', false)
            ->assertSee('name="q"', false)
            ->assertSee('name="kategori"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="urut"', false)
            ->assertSee('Semua Kategori', false)
            ->assertSee('Semua Status', false)
            ->assertDontSee('Semua Kelas', false);

        // "Hapus filter" form-nya sendiri, isinya cuma tab.
        $halaman->assertSee('class="ad-konten-alat__aksi"', false)
            ->assertSee('<input type="hidden" name="tab" value="materi">', false);

        /*
         * Select lain boleh langsung mengirim form, tapi kotak cari tidak:
         * isinya belum selesai diketik, jadi mengirim tiap ketikan akan memuat
         * ulang halaman berkali-kali.
         */
        $this->assertSame(3, substr_count($halaman->baseResponse->getContent(), 'data-konten-saring-pilih'));

        /*
         * Filter kelas sudah dihapus, jadi query ?kelas= di URL tidak boleh
         * diam-diam ikut menyaring: kalau tidak, admin yang punya tautan lama
         * akan melihat daftar kosong tanpa penjelasan apa pun.
         */
        $this->actingAs($admin)
            ->get(route('admin.konten', ['kelas' => 'RPL 2']))
            ->assertOk()
            ->assertSee('Materi Mentah');
    }

    /**
     * Isi aturan CSS tanpa blok komentar, supaya yang diperiksa benar-benar
     * deklarasi dan bukan kalimat penjelas yang kebetulan menyebut properti
     * yang sama.
     */
    private function tanpaKomentar(string $aturan): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $aturan);
    }

    /**
     * Kotak cari dan semua filter harus sebaris, tidak ada yang turun.
     *
     * Yang diuji perilakunya, bukan warnanya:(search + tiga select +
     * "Terapkan" + "Hapus filter" adalah satu baris dari 768px ke atas).
     * Aturannya diperiksa lewat isi admin.css karena mustahil dibuktikan
     * dari HTML — yang bisa dibuktikan dari HTML cuma elemennya ada.
     */
    public function test_baris_alat_tetap_satu_baris_di_tablet_dan_ke_atas(): void
    {
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        $ambil = function (string $selector) use ($css): string {
            preg_match('/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/', $css, $cocok);

            $this->assertNotEmpty($cocok, "Aturan untuk {$selector} tidak ada di admin.css.");

            return $cocok[1];
        };

        /*
         * Wadahnya GRID, bukan flex, dan grid tidak punya aturan yang bisa
         * membungkus. Flex punya (flex-wrap), dan itulah akar masalahnya:
         * flex memutuskan baris dari lebar konten SEBELUM dipendekkan,
         * sehingga isian yang muat kalau dipendekkan tetap diturunkan.
         */
        $wadah = $ambil('.ad-konten-alat-kotak');

        $this->assertStringContainsString('display: grid', $wadah);
        $this->assertStringNotContainsString('flex-wrap', $wadah);

        // Dua kolom: kolom 1 untuk cari + filter + Terapkan, kolom 2 untuk
        // "Hapus filter". minmax(0, 1fr) yang membuat kolom 1 boleh menyempit
        // sampai nol; 1fr biasa akan menyisakan ruang untuk isi terpanjang.
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) auto', $wadah);

        // Isi kolom 1 tidak boleh membungkus, dan anak-anaknya boleh
        // menyempit di bawah lebar teksnya (min-width: 0) alih-alih
        // mendorong baris lain turun.
        $isi = $ambil('.ad-konten-alat');

        $this->assertStringContainsString('flex-wrap: nowrap', $isi);

        foreach (['.ad-konten-alat__cari', '.ad-konten-alat__field'] as $selector) {
            $this->assertStringContainsString('min-width: 0', $ambil($selector));
        }

        /*
         * Yang boleh turun ke baris berikutnya hanya baris simpul filter
         * aktif, dan itu karena ia diberi grid-column penuh. Tanpa itu, ia
         * akan ikut berdiri di baris yang sama dengan kontrolnya.
         */
        $this->assertStringContainsString(
            'grid-column: 1 / -1',
            $ambil('.ad-konten-alat-kotak > .ad-alat-baris__simpul')
        );
    }

    /**
     * Di ponsel, "Terapkan" dan "Hapus filter" harus sebaris.
     *
     * Dua-duanya form yang berbeda, dan sengaja tidak digabung: kalau satu
     * form, tombol Hapus filter ikut mengirim nilai filter yang sedang aktif
     * sehingga tidak menghapus apa pun. Karena itu di ponsel keduanya tidak
     * bisa berbagi sel grid selama masing-masing masih jadi kotak — display:
     * contents yang melarotten keduanya, lalu masing-masing tombolnya
     * mengambil tiga dari enam kolom.
     *
     * Yang dijaga hanya aturan ponsel. Aturan desktop-nya sudah dikunci
     * test sebelumnya dan tidak boleh ikut berubah oleh yang ini.
     */
    public function test_tombol_terapkan_dan_hapus_filter_sebaris_di_ponsel(): void
    {
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        // File ini punya beberapa blok "@media (max-width: 767px)", jadi yang
        // dicari adalah blok yang benar-benar mengatur baris alat Konten —
        // bukan blok pertama yang kebetulan memuat lebar yang sama.
        preg_match_all(
            '/@media \(max-width: 767px\)\s*\{((?:[^{}]|\{[^{}]*\})*)\}/',
            $css,
            $blok
        );

        $ponsel = '';

        foreach ($blok[1] as $isi) {
            if (str_contains($isi, '.ad-konten-alat-kotak')) {
                $ponsel = $isi;

                break;
            }
        }

        $this->assertNotSame('', $ponsel, 'Aturan ponsel untuk baris alat tidak ada di admin.css.');

        // Enam kolom: tiga untuk tiap tombol, jadi keduanya berdampingan dan
        // sama lebar.
        $this->assertStringContainsString('grid-template-columns: repeat(6, minmax(0, 1fr))', $ponsel);
        $this->assertStringContainsString('grid-column: span 3', $ponsel);

        /*
         *(display: contents) yang membuat kedua tombol bisa jadi sel grid
         * yang sama. Tanpa itu, masing-masing form tetap jadi kotak penuh di
         * barisnya sendiri dan "Hapus filter" turun ke baris keempat.
         */
        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat,\s*\.ad-konten-alat__aksi\s*\{\s*display: contents;/',
            $ponsel
        );

        // Kotak cari tetap penuh, tiga select tetap berbagi satu baris.
        $this->assertStringContainsString('grid-column: span 6', $ponsel);
        $this->assertStringContainsString('grid-column: span 2', $ponsel);
    }

    /**
     * Warna di Konten Pembelajaran: yang berwarna, yang putih, dan yang
     * sepadan.
     *
     * Dua kartu aksi berwarna dan berbeda: hijau untuk materi, ungu untuk
     * kuis. Dua permukaan lain sengaja dibiarkan tenang — kotak daftar
     * lavender karena isinya deretan baris, dan baris alat (cari + filter)
     * putih karena isinya kontrol yang sudah berwarna sendiri lewat isiannya.
     * Menaruh baris alat di atas kartu berwarna membuat halaman ini
     * berlapis-lapis warna tanpa menambah informasi apa pun.
     *
     * Yang dijaga bukan nameof warnanya, tapi tiga sifatnya: tidak
     * ada yang berubah diam-diam, tidak ada dua permukaan berdekatan yang
     * sama, dan tombolnya sepadan dengan kartunya — tombol ungu di atas
     * kartu hijau justru menghilangkan pembedaan yang dibawa warnanya.
     */
    public function test_warna_konten_bersesadan_dan_tidak_bertumpuk(): void
    {
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        $ambil = function (string $selector) use ($css): string {
            /*
             * Spasi di selector diganti \s+ karena di admin.css satu daftar
             * selector boleh ditulis melintasi baris — dan preg_quote membuat
             * pola itu tidak bisa mencocokkan baris baru sama sekali.
             */
            $pola = '/'.str_replace(' ', '\s+', preg_quote($selector, '/')).'\s*\{([^}]*)\}/';

            preg_match($pola, $css, $cocok);

            $this->assertNotEmpty($cocok, "Aturan untuk {$selector} tidak ada di admin.css.");

            return $cocok[1];
        };

        $warna = function (string $selector) use ($ambil): string {
            preg_match('/background-color:\s*(var\([^)]*\))/', $ambil($selector), $cocok);

            $this->assertNotEmpty($cocok, "Aturan untuk {$selector} tidak punya background-color.");

            return $cocok[1];
        };

        // Hijau untuk materi, ungu untuk kuis: dua kartu tidak boleh sama.
        $this->assertSame('var(--ad-cucian-sukses)', $warna('.ad-konten-aksi__kartu--materi'));
        $this->assertSame('var(--ad-cucian-ungu)', $warna('.ad-konten-aksi__kartu--kuis'));

        // Kotak daftar lavender: paling tenang, supaya judul barisnya terbaca.
        // Bentuk kartu aksinya ada di test terpisah.
        $this->assertSame('var(--ad-permukaan-lavender)', $warna('.ad-konten-kotak'));

        /*
         * Baris alat putih. Dinyatakan eksplisit, dan gradasi .ad-kartu harus
         * tetap dimatikan: gradasi itu digambar DI ATAS background-color, jadi
         * background-color saja tidak cukup menentukan apa yang benar-benar
         * terlihat.
         */
        $this->assertSame('var(--ad-permukaan)', $warna('.ad-konten-alat-kotak'));
        $this->assertStringContainsString('background-image: none', $ambil('.ad-konten-alat-kotak'));

        /*
         * Menu aksi menayang di atas kartu, jadi isinya harus lebih terang
         * dari kartu di bawahnya — bukan sewarnanya. Kalau ikut mengambil
         * warna kartu, popover-nya hilang di dalam kartu.
         */
        $this->assertSame('var(--ad-permukaan)', $warna('.ad-konten-menu__isi'));
    }

    /*
     * Kartu aksi ("Tambah Materi" / "Tambah Kuis") sengaja mendatar, bukan
     * potret seperti kartu-kartu daftar di bawahnya.
     *
     * Yang dijaga bukan hanya gayanya, tapi juga bahwa kelas potret yang pernah
     * dipakai di sini benar-benar tidak lagi menempel. Kalau .ad-kartu-daftar
     * masih ada di kartu aksi, bentuknya jadi setengah-setengah: ada blok
     * gambar 16:9 tapi kartu tetap mendatar — dan test warna di sebelahnya
     * tetap lolos karena warnanya tidak berubah.
     */
    public function test_kartu_aksi_mendatar_dan_tidak_pakai_kartu_potret(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->baseResponse->getContent();

        // Mendatar: ikon di kiri, isi di kanan, tombol di dalam isi.
        $this->assertSame(2, substr_count($halaman, 'class="ad-konten-aksi__ikon"'));
        $this->assertSame(2, substr_count($halaman, 'class="ad-konten-aksi__isi"'));
        $this->assertSame(2, substr_count($halaman, 'class="ad-konten-aksi__judul"'));

        // Tidak ada sisa kelas potret.
        $this->assertStringNotContainsString('ad-kartu-daftar', $halaman);

        // .ad-konten-aksi__kartu harus benar-benar mendatar di CSS.
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        preg_match('/\.ad-konten-aksi__kartu\s*\{([^}]*)\}/', $css, $cocok);

        $this->assertNotEmpty($cocok, 'Aturan .ad-konten-aksi__kartu tidak ada di admin.css.');
        $this->assertStringContainsString('display: flex', $cocok[1]);
        $this->assertStringContainsString('align-items: flex-start', $cocok[1]);

        /*
         * Tombolnya menempel ke kiri isinya dan tidak melebar. Kalau ikut
         * melebar, kartu jadi punya satu elemen besar di kiri yang tidak ada
         * gunanya, dan di layar sempit tombol sebesar itu jadi sulit ditekan.
         */
        preg_match('/\.ad-konten-aksi__tombol\s*\{([^}]*)\}/', $css, $cocok);

        $this->assertNotEmpty($cocok, 'Aturan .ad-konten-aksi__tombol tidak ada di admin.css.');
        $this->assertStringContainsString('align-self: flex-start', $cocok[1]);
        $this->assertStringNotContainsString('width: 100%', $cocok[1]);
    }

    /**
     * Tombol "Tambah Materi" hijau, "Tambah Kuis" ungu.
     *
     * Tombolnya sepadan dengan kartunya. Dua tombol ungu di atas kartu hijau
     * dan kartu ungu menghilangkan pembedaan yang dibawa warna kartu, jadi
     * tombolnya ikut membedakan — dan karena memakai varian .ad-tombol yang
     * sudah ada, warnanya ikut mode gelap tanpa aturan baru.
     */
    public function test_tombol_tambah_materi_hijau_dan_tambah_kuis_ungu(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->baseResponse->getContent();

        // Dicocokkan ke jalur URL, bukan ke nama route: yang benar-benar ada di
        // HTML adalah hasil route(), yaitu "/admin/konten/materi/tambah".
        preg_match('/<a[^>]*admin\/konten\/materi\/tambah[^>]*class="([^"]*)"/', $halaman, $materi);
        preg_match('/<a[^>]*admin\/konten\/quiz\/tambah[^>]*class="([^"]*)"/', $halaman, $kuis);

        $this->assertNotEmpty($materi, 'Tombol Tambah Materi tidak ada di halaman.');
        $this->assertNotEmpty($kuis, 'Tombol Tambah Kuis tidak ada di halaman.');

        $this->assertStringContainsString('ad-tombol--sukses', $materi[1]);
        $this->assertStringContainsString('ad-tombol--utama', $kuis[1]);

        // Varian hijau dan ungu harus benar-benar beda, kalau tidak tukar di
        // view cuma kosmetik.
        $this->assertStringNotContainsString('ad-tombol--utama', $materi[1]);

        // Varian yang dipakai harus benar-benar ada di CSS, bukan class yang
        // tidak di_style sehingga tombolnya jadi tanpa warna.
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        foreach (['.ad-tombol--sukses' => 'var(--ad-sukses)', '.ad-tombol--utama' => 'var(--ad-teks-ungu-terang)'] as $varian => $warna) {
            preg_match('/'.preg_quote($varian, '/').'\s*\{([^}]*)\}/', $css, $cocok);

            $this->assertNotEmpty($cocok, "Aturan untuk {$varian} tidak ada di admin.css.");
            $this->assertStringContainsString("background-color: {$warna}", $cocok[1]);
        }
    }

    public function test_placeholder_pencarian_ikut_tab(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Cari materi...', false);

        $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Cari kuis...', false);
    }

    public function test_urutan_az_dan_za_tersedia(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('A–Z', false)
            ->assertSee('Z–A', false);
    }

    public function test_kelas_tujuan_sudah_dihapus_dari_form_materi_dan_quiz(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();

        foreach ([
            route('admin.konten.materi.tambah'),
            route('admin.konten.quiz.tambah'),
        ] as $alamat) {
            $isi = $this->actingAs($admin)->get($alamat)->assertOk()->baseResponse->getContent();

            $this->assertStringNotContainsString('name="kelas"', $isi);
            $this->assertStringNotContainsString('Kelas Tujuan', $isi);
        }

        /*
         * Field-nya tidak lagi ada di form Request mana pun, jadi request lama
         * yang masih mengirim "kelas" harus tetap bisa disimpan — bukan ditolak
         * karena tidak dikenal, dan bukan diam-diam disimpan ke kolom yang
         * sudah tidak ada.
         */
        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'kelas' => 'RPL 2',
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.tambah.store'), $this->dataQuiz($pelajaran, [
                'kelas' => 'RPL 3',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertFalse(
            Schema::hasColumn('tb_materi', 'kelas'),
            'Kolom kelas harus sudah dihapus dari tb_materi.'
        );

        $this->assertFalse(
            Schema::hasColumn('tb_quiz', 'kelas'),
            'Kolom kelas harus sudah dihapus dari tb_quiz.'
        );
    }

    public function test_tab_quiz_menampilkan_daftar_quiz(): void
    {
        $admin = $this->buatAdmin();

        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz HTML Dasar');
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi HTML Dasar');

        $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Quiz HTML Dasar')
            ->assertDontSee('Materi HTML Dasar');
    }

    public function test_menu_konten_pembelajaran_tampil_di_sidebar_admin(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Konten Pembelajaran')
            ->assertSee(route('admin.konten'), false);
    }

    public function test_non_admin_tidak_bisa_membuka_halaman_konten_pembelajaran(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get(route('admin.konten'))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.konten'))->assertRedirect(route('login'));
    }

    /* ================= Halaman form ================= */

    public function test_halaman_tambah_materi_mengikuti_form_pemilik_dan_tanpa_tahapan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten.materi.tambah'))
            ->assertOk()
            ->assertSee('Tambah Materi')
            ->assertSee('Simpan Draft')
            ->assertSee('Publish Sekarang')
            ->baseResponse->getContent();

        /*
         * Form admin memakai komponen yang sama persis dengan form pemilik
         * (x-materi.informasi, .bab, .editor) dan JavaScript yang sama
         * (resources/js/materi-tambah.js), jadi isian dan editornya bukan
         * versi lain. Yang tidak ada lagi adalah tahap: form ini satu halaman
         * panjang, sama seperti form pemilik.
         */
        $this->assertStringContainsString('data-tambah-materi', $halaman);
        $this->assertStringContainsString('data-editor', $halaman);
        $this->assertStringContainsString('data-bab-list', $halaman);
        $this->assertStringContainsString('name="isi"', $halaman);

        /*
         * Baris tombolnya kartu biasa di akhir form, sama seperti baris tombol
         * di form Quiz milik admin: bukan baris lengket yang menyala dan mati
         * saat form digulir. Kalau data-action-bar dan penandanya muncul lagi
         * di sini, form ini akan kembali bergantung pada menggulir halaman
         * sebelum tombol simpannya terlihat.
         */
        $this->assertStringContainsString('panel-aksi', $halaman);
        $this->assertStringNotContainsString('data-action-bar', $halaman);

        // Tidak ada wizard: panel tahap dan steppernya sudah dihapus.
        $this->assertStringNotContainsString('data-konten-tahap', $halaman);
        $this->assertStringNotContainsString('data-konten-stepper', $halaman);
        $this->assertStringNotContainsString('data-konten-lanjut', $halaman);

        // Tidak ada saklar pengajuan persetujuan di area admin.
        $this->assertStringNotContainsString('data-publikasikan', $halaman);

        /*
         * Isian form admin harus sama persis dengan form pemilik — termasuk
         * tidak adanya field yang dulu hanya ada di area admin. Kalau kelas
         * tujuan muncul lagi di sini, form admin dan form pemilik akan
         * berbeda isi dan pemeriksaan keduanya bisa menyimpang.
         */
        $this->assertStringNotContainsString('name="kelas"', $halaman);
        $this->assertStringNotContainsString('Kelas Tujuan', $halaman);
    }

    public function test_halaman_tambah_quiz_memakai_tiga_tahap_seperti_form_pemilik(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten.quiz.tambah'))
            ->assertOk()
            ->assertSee('Tambah Quiz')
            ->assertSee('Buat Soal')
            ->assertSee('Pengaturan')
            ->assertSee('Simpan Draft')
            ->assertSee('Publish Sekarang')
            ->baseResponse->getContent();

        // Builder soal milik form pemilik ikut dipakai.
        $this->assertStringContainsString('data-builder-daftar', $halaman);
        $this->assertStringContainsString('data-builder-input', $halaman);

        /*
         * Tiga panel langkah, sama seperti form Quiz milik pengguna, dan
         * langkah Pengaturan ikut membawa durasi serta saklar jawaban.
         */
        $this->assertSame(3, substr_count($halaman, 'data-wizard-panel="'));

        $this->assertStringContainsString('name="durasi"', $halaman);

        /*
         * Dua isian milik alur pemilik tidak dirender di sini: blok pengajuan
         * dan pilihan cara publikasi beserta kolom kode aksesnya. Konten dari
         * area admin selalu terbit untuk semua pengguna, jadi tidak ada kode
         * yang perlu dibagikan; field "visibilitas" tetap dikirim sebagai
         * "public" supaya aturan validasinya sama dengan form pemilik.
         */
        $this->assertStringNotContainsString('data-wizard-approval-area', $halaman);
        $this->assertStringNotContainsString('name="publikasikan"', $halaman);
        $this->assertStringNotContainsString('data-wizard-kode-area', $halaman);
        $this->assertStringNotContainsString('name="kode_akses"', $halaman);
        $this->assertStringNotContainsString('Gunakan Kode', $halaman);
        $this->assertStringContainsString('name="visibilitas" value="public"', $halaman);

        // Field "aksi" dan tombol terbitan milik ruang kerja admin.
        $this->assertStringContainsString('data-konten-aksi', $halaman);
        $this->assertStringContainsString('data-wizard-terbit', $halaman);
        $this->assertStringNotContainsString('data-wizard-simpan', $halaman);

        /*
         * Isian form admin harus sama persis dengan form Quiz milik pengguna:
         * tidak ada field admin saja. Slot komponen langkah 1 pun tidak lagi
         * dipakai, karena satu-satunya isian yang pernah masuk ke sana sudah
         * dihapus.
         */
        $this->assertStringNotContainsString('name="kelas"', $halaman);
        $this->assertStringNotContainsString('Kelas Tujuan', $halaman);
    }

    public function test_halaman_edit_membuka_konten_yang_sudah_ada(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Disunting');
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Disunting');

        $this->actingAs($admin)
            ->get(route('admin.konten.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('Edit Materi')
            ->assertSee('Materi Disunting');

        $this->actingAs($admin)
            ->get(route('admin.konten.quiz.edit', $quiz))
            ->assertOk()
            ->assertSee('Edit Quiz')
            ->assertSee('Quiz Disunting');
    }

    public function test_halaman_tidak_ditemukan_bila_konten_tidak_ada(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.konten.materi.edit', 'tidak-ada'))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.konten.quiz.edit', 999))
            ->assertNotFound();
    }

    public function test_daftar_hanya_menampilkan_karya_admin_yang_sedang_login(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdmin('admin2@example.com');
        $pengguna = $this->buatPengguna();

        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Admin Sendiri');
        $this->buatMateri($adminLain, Materi::STATUS_DRAFT, 'Materi Admin Lain');
        $this->buatMateri($pengguna, Materi::STATUS_DRAFT, 'Materi Pengguna');
        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Admin Sendiri');
        $this->buatQuiz($adminLain, Quiz::STATUS_DRAFT, 'Quiz Admin Lain');
        $this->buatQuiz($pengguna, Quiz::STATUS_DRAFT, 'Quiz Pengguna');

        $halamanMateri = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();
        $halamanMateri->assertSee('Materi Admin Sendiri')
            ->assertDontSee('Materi Admin Lain')
            ->assertDontSee('Materi Pengguna');

        $halamanQuiz = $this->actingAs($admin)->get(route('admin.konten', ['tab' => 'quiz']))->assertOk();
        $halamanQuiz->assertSee('Quiz Admin Sendiri')
            ->assertDontSee('Quiz Admin Lain')
            ->assertDontSee('Quiz Pengguna');
    }

    public function test_jumlah_di_tab_hanya_menghitung_karya_admin_yang_sedang_login(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdmin('admin2@example.com');
        $pengguna = $this->buatPengguna();

        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Admin Sendiri');
        $this->buatMateri($adminLain, Materi::STATUS_DRAFT, 'Materi Admin Lain');
        $this->buatMateri($pengguna, Materi::STATUS_DRAFT, 'Materi Pengguna');

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('1 Materi');
    }

    public function test_karya_pengguna_tidak_bisa_dibuka_di_konten_pembelajaran(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();

        $materi = $this->buatMateri($pengguna, Materi::STATUS_DRAFT, 'Materi Pengguna');
        $quiz = $this->buatQuiz($pengguna, Quiz::STATUS_DRAFT, 'Quiz Pengguna');

        $this->actingAs($admin)
            ->get(route('admin.konten.materi.edit', $materi->slug))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.konten.quiz.edit', $quiz))
            ->assertForbidden();
    }

    public function test_karya_pengguna_tidak_bisa_diubah_diterbitkan_atau_dihapus(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $materi = $this->buatMateri($pengguna, Materi::STATUS_DRAFT, 'Materi Pengguna');
        $quiz = $this->buatQuiz($pengguna, Quiz::STATUS_DRAFT, 'Quiz Pengguna');

        $this->actingAs($admin)
            ->put(route('admin.konten.materi.update', $materi->slug), $this->dataMateri($pelajaran, ['nama' => 'Direbut Admin']))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.publish', $quiz))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.duplikat', $materi->slug))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.duplikat', $quiz))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.konten.materi.destroy', $materi->slug))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.konten.quiz.destroy', $quiz))
            ->assertForbidden();

        $this->assertSame(Materi::STATUS_DRAFT, $materi->fresh()->status);
        $this->assertSame(Quiz::STATUS_DRAFT, $quiz->fresh()->status);
    }

    public function test_karya_admin_lain_tidak_bisa_diubah_oleh_admin(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdmin('admin2@example.com');

        $materi = $this->buatMateri($adminLain, Materi::STATUS_DRAFT, 'Materi Admin Lain');
        $quiz = $this->buatQuiz($adminLain, Quiz::STATUS_DRAFT, 'Quiz Admin Lain');

        $this->actingAs($admin)
            ->get(route('admin.konten.materi.edit', $materi->slug))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.publish', $quiz))
            ->assertForbidden();
    }

    public function test_isian_tidak_lengkap_menolak_penyimpanan_dengan_pesan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), [
                'pelajaran_id' => null,
                'nama' => '',
                'isi' => 'pendek',
                'tingkat_kesulitan' => 'Mudah',
                'aksi' => 'publish',
            ])
            ->assertSessionHasErrors(['pelajaran_id', 'nama', 'isi']);

        $this->assertSame(0, Materi::query()->count());
        $this->assertSame(0, $this->notifikasiPengguna()->count());
    }

    /* ================= Materi ================= */

    public function test_admin_menyimpan_materi_sebagai_draft_dan_tidak_tayang(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.konten', ['tab' => 'materi']));

        $materi = Materi::query()->sole();

        $this->assertSame(Materi::STATUS_DRAFT, $materi->status);
        $this->assertNull($materi->dipublish_pada);
        $this->assertSame($admin->getKey(), (int) $materi->dibuat_oleh);
        $this->assertSame(0, $this->notifikasiPengguna()->count(), 'Draft tidak boleh mengirim notifikasi ke pengguna.');

        $this->actingAs($pengguna)
            ->get(route('user.materi'))
            ->assertOk()
            ->assertDontSee('Materi Dari Form');
    }

    public function test_admin_menerbitkan_materi_langsung_tanpa_persetujuan(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'nama' => 'Pengenalan HTML',
                'aksi' => 'publish',
            ]))
            ->assertSessionHasNoErrors();

        $materi = Materi::query()->sole();

        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->status);
        $this->assertNotNull($materi->dipublish_pada);

        // Tidak ada status antara draft dan published.
        $this->assertNotContains($materi->status, [Materi::STATUS_PENDING, Materi::STATUS_REJECTED]);

        $this->actingAs($pengguna)
            ->get(route('user.materi'))
            ->assertOk()
            ->assertSee('Pengenalan HTML');

        $notifikasi = $this->notifikasiPengguna()->sole();

        $this->assertSame('Materi baru tersedia', $notifikasi->judul);
        $this->assertStringContainsString('Pengenalan HTML', $notifikasi->pesan);
        $this->assertSame(Notifikasi::KONTEN_MATERI, $notifikasi->konten_tipe);
        $this->assertSame((int) $materi->getKey(), (int) $notifikasi->konten_id);
        $this->assertSame((int) $pengguna->getKey(), (int) $notifikasi->pengguna_id);
        $this->assertNull($notifikasi->dibaca_pada);
    }

    public function test_penerbitan_materi_dari_daftar_mengirim_notifikasi(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Terbit');

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.publish', $materi->slug))
            ->assertRedirect(route('admin.konten', ['tab' => 'materi']))
            ->assertSessionHas('sukses', 'Berhasil dipublikasikan');

        $this->assertSame(Materi::STATUS_PUBLISHED, $materi->refresh()->status);
        $this->assertSame(1, $this->notifikasiPengguna()->count());

        $this->actingAs($pengguna)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk();
    }

    public function test_membatalkan_publikasi_menarik_materi_dari_halaman_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $materi = $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Cabut');
        $materi->terbitkan();

        $this->actingAs($pengguna)->get(route('user.materi'))->assertOk()->assertSee('Materi Cabut');

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.publish', $materi->slug))
            ->assertRedirect();

        $materi->refresh();

        $this->assertSame(Materi::STATUS_DRAFT, $materi->status);
        $this->assertNull($materi->dipublish_pada);

        $this->actingAs($pengguna)
            ->get(route('user.materi'))
            ->assertOk()
            ->assertDontSee('Materi Cabut');

        // Membatalkan terbit tidak mengirim apa pun: tidak ada yang berubah
        // bagi pengguna.
        $this->assertSame(0, $this->notifikasiPengguna()->count());
    }

    public function test_materi_duplikat_berasal_dan_tidak_menyalin_thumbnail(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Asli');
        $materi->terbitkan();
        $materi->forceFill(['thumbnail' => 'thumbnails/asli.jpg'])->save();

        $this->actingAs($admin)
            ->from(route('admin.konten'))
            ->post(route('admin.konten.materi.duplikat', $materi->slug))
            ->assertRedirect();

        $salinan = Materi::query()->where('slug', '!=', $materi->slug)->sole();

        $this->assertSame('Materi Asli (Salinan)', $salinan->nama);
        $this->assertSame(Materi::STATUS_DRAFT, $salinan->status);
        $this->assertSame($materi->isi, $salinan->isi);
        $this->assertNull($salinan->thumbnail, 'Berkas thumbnail tidak boleh dipakai dua baris.');
        $this->assertNotSame($materi->slug, $salinan->slug);
    }

    public function test_admin_mengubah_materi_tanpa_mengubah_statusnya(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Lama');
        $materi->terbitkan();

        $this->actingAs($admin)
            ->put(route('admin.konten.materi.update', $materi->slug), $this->dataMateri($pelajaran, [
                'nama' => 'Materi Baru',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.konten', ['tab' => 'materi']));

        $materi->refresh();

        $this->assertSame('Materi Baru', $materi->nama);
        $this->assertSame(
            Materi::STATUS_PUBLISHED,
            $materi->status,
            'Menyunting isi materi tidak boleh menariknya dari halaman pengguna.'
        );
    }

    public function test_admin_menghapus_materi_dan_thumbnailnya(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Buang');

        $this->actingAs($admin)
            ->delete(route('admin.konten.materi.destroy', $materi->slug))
            ->assertRedirect(route('admin.konten', ['tab' => 'materi']));

        $this->assertSame(0, Materi::query()->count());
    }

    /* ================= Quiz ================= */

    public function test_admin_menyimpan_quiz_sebagai_draft_dan_tidak_tayang(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.tambah.store'), $this->dataQuiz($pelajaran))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.konten', ['tab' => 'quiz']));

        $quiz = Quiz::query()->sole();

        $this->assertSame(Quiz::STATUS_DRAFT, $quiz->status);
        $this->assertNull($quiz->dipublish_pada);
        $this->assertSame(15, $quiz->durasi);
        $this->assertSame(Quiz::VISIBILITAS_PUBLIK, $quiz->visibilitas);
        $this->assertNull($quiz->kode_akses, 'Konten yang tayang untuk semua tidak menyimpan kode akses.');
        $this->assertSame(1, $quiz->jumlahSoal());
        $this->assertSame(0, $this->notifikasiPengguna()->count());

        $this->actingAs($pengguna)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertDontSee('Quiz Dari Form');
    }

    public function test_admin_menerbitkan_quiz_lalu_muncul_di_halaman_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.tambah.store'), $this->dataQuiz($pelajaran, [
                'judul' => 'Kuis HTML & CSS',
                'aksi' => 'publish',
            ]))
            ->assertSessionHasNoErrors();

        $quiz = Quiz::query()->sole();

        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
        $this->assertNotNull($quiz->dipublish_pada);

        $this->actingAs($pengguna)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertSee('Kuis HTML');

        $notifikasi = $this->notifikasiPengguna()->sole();

        $this->assertSame('Kuis baru tersedia', $notifikasi->judul);
        $this->assertStringContainsString('Kuis HTML', $notifikasi->pesan);
        $this->assertSame(Notifikasi::KONTEN_QUIZ, $notifikasi->konten_tipe);
    }

    public function test_penerbitan_quiz_dari_daftar_mengirim_notifikasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPengguna();
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Kuis Siang');
        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Kuis Malam')->soal()->create([
            'pertanyaan' => 'Apa itu CSS?',
            'pilihan_a' => 'Cascading Style Sheet',
            'pilihan_b' => 'Bahasa',
            'pilihan_c' => '',
            'pilihan_d' => '',
            'jawaban_benar' => 'A',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.publish', $quiz))
            ->assertRedirect(route('admin.konten', ['tab' => 'quiz']))
            ->assertSessionHas('sukses', 'Berhasil dipublikasikan');

        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->refresh()->status);
        $this->assertSame(1, $this->notifikasiPengguna()->count());
    }

    /**
     * Dari menu ini tidak ada kode yang perlu dibagikan, jadi quiz yang
     * dibuat lewat form Konten Pembelajaran selalu tayang untuk semua pengguna
     * — termasuk kalau kiriman luar form masih menyebut cara publikasi lain.
     */
    public function test_quiz_dari_form_admin_selalu_disimpan_sebagai_publik(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        /*
         * Field "visibilitas" dan "kode_akses" sudah tidak dirender di form ini,
         * jadi tidak ada yang bisa mengisinya lewat tombol. Kiriman seperti di
         * bawah ini tetap harus berakhir sebagai quiz publik, bukan quiz privat
         * yang tidak ada kodenya.
         */
        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.tambah.store'), $this->dataQuiz($pelajaran, [
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'K7F3P9',
                'aksi' => 'publish',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.konten', ['tab' => 'quiz']));

        $quiz = Quiz::query()->sole();

        $this->assertSame(Quiz::VISIBILITAS_PUBLIK, $quiz->visibilitas);
        $this->assertNull($quiz->kode_akses);
        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
        $this->assertSame(1, $this->notifikasiPengguna()->count());

        $this->actingAs($pengguna)
            ->get(route('user.quiz'))
            ->assertOk()
            ->assertSee('Quiz Dari Form');
    }

    /**
     * Quiz yang dibuat sebelum pilihan cara publikasi dihapus masih bisa
     * private di baris. Satu kali disunting lewat form ini, isiannya ikut
     * dikembalikan ke "public" supaya tidak menggantung sebagai quiz yang
     * tidak bisa dibagikan.
     */
    public function test_quiz_lama_yang_memakai_kode_kembali_publik_setelah_disunting(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Lama');
        $quiz->forceFill([
            'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
            'kode_akses' => 'LAMA99',
        ])->save();
        $quiz->terbitkan();

        $this->actingAs($admin)
            ->put(route('admin.konten.quiz.update', $quiz), $this->dataQuiz($pelajaran))
            ->assertSessionHasNoErrors();

        $quiz->refresh();

        $this->assertSame(Quiz::VISIBILITAS_PUBLIK, $quiz->visibilitas);
        $this->assertNull($quiz->kode_akses);
        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
    }

    /**
     * Penjaga terakhir untuk quiz mode kode yang belum sempat disunting:
     * menerbitkannya dari daftar akan menayangkannya ke semua orang, karena
     * halaman Quiz tidak memeriksa cara aksesnya.
     */
    public function test_terbitan_quiz_lama_yang_memakai_kode_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPengguna();
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Kode');
        $quiz->forceFill(['visibilitas' => Quiz::VISIBILITAS_PRIVAT, 'kode_akses' => 'KODE1'])->save();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.publish', $quiz))
            ->assertRedirect(route('admin.konten', ['tab' => 'quiz']))
            ->assertSessionHas('sukses', 'Quiz ini masih memakai kode, jadi tidak bisa diterbitkan.');

        $this->assertSame(Quiz::STATUS_DRAFT, $quiz->refresh()->status);
        $this->assertSame(0, $this->notifikasiPengguna()->count());
    }

    public function test_quiz_duplikat_menyalin_soalnya_dan_tetap_draft(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Kuis Asli');
        $quiz->terbitkan();

        $quiz->soal()->create([
            'pertanyaan' => 'Apa itu HTML?',
            'tipe' => Soal::TIPE_PILIHAN_GANDA,
            'pilihan_a' => 'Bahasa',
            'pilihan_b' => 'Markup',
            'pilihan_c' => '',
            'pilihan_d' => '',
            'jawaban_benar' => 'B',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);

        $baris = IsianSoalQuiz::baris($quiz->soal()->terurut()->get());

        $this->assertNotEmpty($baris);

        $this->actingAs($admin)
            ->from(route('admin.konten', ['tab' => 'quiz']))
            ->post(route('admin.konten.quiz.duplikat', $quiz))
            ->assertRedirect();

        $salinan = Quiz::query()->where('slug', '!=', $quiz->slug)->sole();

        $this->assertSame('Kuis Asli (Salinan)', $salinan->judul);
        $this->assertSame(Quiz::STATUS_DRAFT, $salinan->status);
        $this->assertSame(1, $salinan->jumlahSoal());
    }

    public function test_admin_mengubah_quiz_tanpa_mengubah_statusnya(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Lama');
        $quiz->terbitkan();

        $quiz->soal()->create([
            'pertanyaan' => 'Soal lama?',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => '',
            'pilihan_d' => '',
            'jawaban_benar' => 'A',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.konten.quiz.update', $quiz), $this->dataQuiz($pelajaran, [
                'judul' => 'Quiz Baru',
            ]))
            ->assertSessionHasNoErrors();

        $quiz->refresh();

        $this->assertSame('Quiz Baru', $quiz->judul);
        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
    }

    public function test_admin_menghapus_quiz_beserta_soalnya(): void
    {
        $admin = $this->buatAdmin();
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Buang');
        $quiz->soal()->create([
            'pertanyaan' => 'Soal?',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => '',
            'pilihan_d' => '',
            'jawaban_benar' => 'A',
            'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.konten.quiz.destroy', $quiz))
            ->assertRedirect(route('admin.konten', ['tab' => 'quiz']));

        $this->assertSame(0, Quiz::query()->count());
        $this->assertSame(0, Soal::query()->count());
    }

    /* ================= Notifikasi pengguna ================= */

    public function test_notifikasi_menaut_ke_halaman_konten_yang_benar(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'nama' => 'Pengenalan HTML',
                'aksi' => 'publish',
            ]));

        $halaman = $this->actingAs($pengguna)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Materi baru tersedia')
            ->assertSee('Materi', false)
            ->baseResponse->getContent();

        $materi = Materi::query()->sole();

        $this->assertStringContainsString(route('user.materi.detail', $materi->slug), $halaman);
    }

    public function test_notifikasi_quiz_menaut_ke_halaman_detail_quiz(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.quiz.tambah.store'), $this->dataQuiz($pelajaran, [
                'judul' => 'Kuis HTML',
                'aksi' => 'publish',
            ]));

        $quiz = Quiz::query()->sole();

        $halaman = $this->actingAs($pengguna)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Kuis baru tersedia')
            ->baseResponse->getContent();

        $this->assertStringContainsString(route('user.quiz.detail', $quiz), $halaman);
    }

    public function test_notifikasi_yang_sudah_dibaca_tidak_menyalakan_titik_lonceng(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'aksi' => 'publish',
            ]));

        $notifikasi = $this->notifikasiPengguna()->sole();

        // Sotnya masih menyala selama ada notifikasi yang belum dibaca.
        $this->actingAs($pengguna)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('notif__titik', false);

        $this->actingAs($pengguna)
            ->post(route('user.notifikasi.baca', $notifikasi))
            ->assertRedirect();

        $this->assertNotNull($notifikasi->refresh()->dibaca_pada);

        /*
         * Barisnya tetap ada di daftar — notifikasi yang sudah dibaca boleh
         * dibaca ulang — tapi tidak lagi ditandai belum dibaca, dan titiknya
         * di lonceng ikut hilang karena tidak ada sisa yang belum dibaca.
         */
        $this->actingAs($pengguna)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Materi baru tersedia')
            ->assertDontSee('notif__titik', false)
            ->assertDontSee('is-belum', false);
    }

    public function test_pengguna_tidak_bisa_menandai_notifikasi_milik_orang_lain(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'aksi' => 'publish',
            ]));

        $notifikasi = Notifikasi::query()
            ->where('pengguna_id', $pemilik->getKey())
            ->sole();

        $this->actingAs($orangLain)
            ->post(route('user.notifikasi.baca', $notifikasi))
            ->assertForbidden();

        $this->assertNull($notifikasi->refresh()->dibaca_pada);
    }

    public function test_admin_yang_menerbitkan_tidak_mendapat_notifikasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'aksi' => 'publish',
            ]));

        $this->assertSame(1, $this->notifikasiPengguna()->count());
        $this->assertSame(0, $this->notifikasiPengguna()->where('pengguna_id', $admin->getKey())->count());
    }
}
