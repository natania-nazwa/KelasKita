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
            ->assertSee('Kelola materi dan quiz untuk mendukung proses pembelajaran di Kelas Kita.')
            ->assertSee('Tambah Materi')
            ->assertSee('Buat materi pembelajaran untuk peserta didik.')
            ->assertSee('Tambah Quiz')
            ->assertSee('Buat quiz untuk menguji pemahaman peserta didik.')
            ->baseResponse->getContent();

        /*
         * Dua kartu aksi harus berdampingan dengan lebar sama di desktop,
         * dan susunannya vertikal di mobile. Tanpa ini, "Tambah Materi" dan
         * "Tambah Quiz" bisa tetap muncul tapi tidak berdampingan — dan itu
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
     * Query daftar gagal: kepala, kartu aksi, tab, dan tombol "Coba Lagi"
     * tetap tampil, bukan halaman 500.
     *
     * Controller menutup query daftar dan mengembalikan state "gagal",
     * supaya kegagalan tidak pernah berubah jadi daftar kosong yang
     * terlihat seperti "belum ada konten". Cabang ini dulu melempar
     * "Undefined variable $request" karena view memakai $request yang tidak
     * pernah Laravel salin ke data view — kartu errornya justru yang gagal
     * dirender, dan tombol "Coba Lagi" tidak pernah bisa dipakai.
     */
    public function test_daftar_gagal_dimuat_tetap_menampilkan_tombol_coba_lagi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Penyebab Gagal');

        /*
         * Tabel soal dibuang. Ini satu-satunya tabel yang hanya disentuh oleh
         * query daftar tab Quiz (withCount soal), jadi yang melempar memang
         * blok try — kepala, jumlah materi, dan jumlah quiz tetap berhasil
         * dihitung seperti biasa.
         *
         * Satu quiz dibuat lebih dulu karena paginate() melewatkan query
         * daftar sama sekali saat total baris 0. Tanpa baris ini kegagalan
         * tidak pernah terjadi dan halaman jatuh ke state "Belum ada quiz".
         */
        Schema::dropIfExists('tb_soal');

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk()
            ->baseResponse->getContent();

        $this->assertStringContainsString('Konten Pembelajaran', $halaman);
        $this->assertStringContainsString('Gagal memuat konten', $halaman);
        $this->assertStringContainsString('Coba Lagi', $halaman);
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

    public function test_daftar_tidak_menampilkan_ringkasan_jumlah_atau_pengingat_tanggal(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        /*
         * Baris "Menampilkan N materi dari M" dan "Terakhir diperbarui sesuai
         * tanggal di setiap kartu" dicabut. Keduanya bukan kontrol dan bukan
         * informasi baru: jumlah kartu sudah terbaca dari kartu-kartu di
         * bawahnya, dan tanggal tiap konten tercetak di baris informasinya.
         */
        $halaman->assertDontSee('Menampilkan', false)
            ->assertDontSee('Terakhir diperbarui sesuai tanggal', false)
            ->assertDontSee('ad-konten-info', false);
    }

    public function test_menu_tiga_tik_berdiri_di_sebelah_tanggal(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $html = $this->actingAs($admin)->get(route('admin.konten'))->assertOk()->getContent();

        /*
         * Menu tiga titik pindah ke ujung baris informasi, tepat di sebelah
         * kanan tanggal. Kaki kartu sekarang hanya "Lihat" dan "Edit", jadi
         * urutan kemunculannya di HTML juga berubah: menu harus muncul
         * sebelum kaki kartu.
         */
        $this->assertStringContainsString('kartu-konten__akhir', $html);

        $menu = strpos($html, 'data-konten-menu');
        $kaki = strpos($html, 'karya-aksi"');

        $this->assertNotFalse($menu, 'Menu tiga titik tidak ada di kartu.');
        $this->assertNotFalse($kaki, 'Kaki kartu tidak ada.');
        $this->assertLessThan($kaki, $menu);
    }

    /*
     * Kartu Konten Pembelajaran tidak boleh memotong menu tiga titiknya.
     * .kartu-materi memakai overflow: hidden untuk membulatkan sudut
     * thumbnail, dan itulah yang membuat popover menu terpotong tepat di tepi
     * kartu: tombolnya kelihatan, tapi isinya tidak pernah terlihat.
     */
    public function test_kartu_konten_tidak_memotong_menu_tiga_titik(): void
    {
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/app.css')));

        preg_match('/\.kartu-konten\s*\{([^}]*)\}/', $css, $cocok);

        $this->assertNotEmpty($cocok, 'Aturan .kartu-konten tidak ada di app.css.');
        $this->assertStringContainsString('overflow: visible', $cocok[1]);
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
         *
         * "Lihat" memakai route di dalam /admin/konten, bukan admin.materi.show
         * atau admin.quiz.show: dari sana penanda aktif sidebar ikut pakai
         * admin.konten*, jadi membaca karya sendiri tidak memindahkan menu
         * yang menyala ke Materi atau Quiz.
         */
        $halamanMateri->assertSee(route('admin.konten.materi.show', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.edit', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.duplikat', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.publish', $materi->slug), false)
            ->assertDontSee(route('admin.materi.show', $materi->slug), false);

        $halamanQuiz->assertSee(route('admin.konten.quiz.show', $quiz), false)
            ->assertSee(route('admin.konten.quiz.edit', $quiz), false)
            ->assertSee(route('admin.konten.quiz.duplikat', $quiz), false)
            ->assertSee(route('admin.konten.quiz.publish', $quiz), false)
            ->assertDontSee(route('admin.quiz.show', $quiz), false);
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

    /**
     * Dialog konfirmasi tidak boleh punya form sendiri.
     *
     * Yang mengirim formnya adalah resources/js/konten-publish.js: ia membuat
     * form sendiri di runtime, mengisi action-nya dari data-konten-aksi, lalu
     * mengirimkannya dari tombol konfirmasi.
     *
     * Pernah ada form kedua di dalam dialog ini, dan tombol konfirmasinya
     * menunjuk form itu lewat atribut form="...". Form tanpa action dikirim
     * ke URL halaman yang sedang dibuka, jadi di /admin/konten kliknya jadi
     * POST ke route yang hanya menerima GET: 405, dan admin tidak pernah
     * sampai ke penerbitan. Halaman form punya POST sendiri, jadi di sana
     * bentuk kegagalannya lebih diam-diam: kiriman kedua tanpa isian yang
     * membatalkan kiriman pertama.
     *
     * Dua sisi yang diperiksa: tidak ada form tanpa action di halaman-halaman
     * yang memakai dialog ini, dan tombol konfirmasinya bukan tombol submit.
     *
     * Daftar konten dan form Quiz admin adalah dua halaman yang masih memakai
     * dialog ini. Form Materi tidak, karena tidak lagi punya pemicu terbitan
     * (lihat test_form_materi_tidak_punya_aksi_terbitkan).
     */
    public function test_dialog_publish_tidak_mirim_form_tanpa_action(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = [
            'daftar' => $this->actingAs($admin)->get(route('admin.konten')),
            'tambah quiz' => $this->actingAs($admin)->get(route('admin.konten.quiz.tambah')),
        ];

        foreach ($halaman as $nama => $respons) {
            $respons->assertOk();
            $isi = $respons->baseResponse->getContent();

            preg_match_all('/<form\b[^>]*>/i', $isi, $cocok);

            foreach ($cocok[0] as $tag) {
                $this->assertStringContainsString(
                    'action=',
                    $tag,
                    "Halaman $nama punya form tanpa action, jadi form itu dikirim ke URL halaman ini."
                );
            }

            $this->assertSame(
                1,
                preg_match('/<button[^>]*type="button"[^>]*data-konten-publish-konfirmasi/', $isi),
                "Tombol konfirmasi dialog di halaman $nama harus type=\"button\", bukan submit."
            );

            $this->assertStringNotContainsString(
                'form="form-konten-publish"',
                $isi,
                "Tombol konfirmasi dialog di halaman $nama masih menunjuk form milik Blade."
            );
        }
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
         * Tiap select dibungkus pembeda kelasnya sendiri. Susunan barisnya di
         * ponsel sekarang bergantung pada ketiga nama itu: kategori dan urutan
         * harus berbagi satu baris dan status mendapat baris sendiri, dan itu
         * hanya bisa terjadi lewat .ad-konten-alat__field--kategori / --urut /
         * --status. Kalau salah satu berubah nama, aturan CSSnya diam-diam
         * tidak lagi berlaku dan ketiganya kembali jadi tiga kolom sempit
         * dalam satu baris — tanpa error apa pun.
         */
        foreach (['kategori', 'status', 'urut'] as $varian) {
            $halaman->assertSee('ad-konten-alat__field--'.$varian, false);
        }

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
     * Di ponsel, baris alat ditumpuk dalam empat baris.
     *
     * Dua-duanya form yang berbeda, dan sengaja tidak digabung: kalau satu
     * form, tombol Hapus filter ikut mengirim nilai filter yang sedang aktif
     * sehingga tidak menghapus apa pun. Karena itu di ponsel keduanya tidak
     * bisa berbagi sel grid selama masing-masing masih jadi kotak — display:
     * contents yang melarotten keduanya, lalu masing-masing tombolnya
     * mengambil tiga dari enam kolom.
     *
     * Bentuknya: cari penuh; kategori dan urutan setengah-setengah; status
     * penuh; kedua tombol setengah-setengah.
     *
     * Yang membuat kategori dan urutan bisa berbagi baris padahal urutan
     * elemennya kategori, status, urutan adalah "order" — CSS hanya bisa
     * menyusun ulang lewat itu, dan memindahkan elemen di markup akan mengubah
     * desktop juga, tempat ketiganya memang sebaris. Test ini mengunci seluruh
     * rangkaian order itu: kalau satu hilang, barisnya kembali ke tiga select
     * yang berbagi satu baris dan tiap select cuma dapat seperenam lebar.
     *
     * Yang dijaga hanya aturan ponsel. Aturan desktop-nya sudah dikunci
     * test sebelumnya dan tidak boleh ikut berubah oleh yang ini.
     */
    public function test_baris_alat_di_ponsel_berempat_baris_kategori_dan_urut_sebaris(): void
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

        // Enam kolom: tiga untuk tiap kolom setengah, jadi tombolnya berdampingan
        // dan sama lebar.
        $this->assertStringContainsString('grid-template-columns: repeat(6, minmax(0, 1fr))', $ponsel);
        $this->assertStringContainsString('grid-column: span 3', $ponsel);

        /*
         * display: contents yang membuat kedua tombol bisa jadi sel grid
         * yang sama. Tanpa itu, masing-masing form tetap jadi kotak penuh di
         * barisnya sendiri dan "Hapus filter" turun ke baris keempat.
         */
        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat,\s*\.ad-konten-alat__aksi\s*\{\s*display: contents;/',
            $ponsel
        );

        // Kotak cari dan status sama-sama penuh; kategori dan urutan masing-masing
        // setengah, jadi mereka berbagi satu baris.
        $this->assertStringContainsString('grid-column: span 6', $ponsel);

        foreach (['--kategori', '--urut'] as $varian) {
            $this->assertMatchesRegularExpression(
                '/\.ad-konten-alat__field'.preg_quote($varian, '/').'\s*\{[^}]*grid-column: span 3;/',
                $ponsel,
                "Select $varian harus setengah baris."
            );
        }

        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat__field--status\s*\{[^}]*grid-column: span 6;/',
            $ponsel,
            'Status harus mendapat baris sendiri.'
        );

        /*
         * Urutan visualnya: cari, kategori, urutan, status, terapkan, hapus
         * filter. Angka-angka ini yang membuat kategori dan urutan mendahului
         * status di ponsel; tanpa itu ketiganya kembali berbagi satu baris.
         */
        preg_match_all('/order: (\d+);/', $ponsel, $urutan);
        $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], $urutan[1], 'Urutan baris alat di ponsel tidak lagi berurutan.');

        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat__field--kategori\s*\{[^}]*order: 2;/',
            $ponsel
        );

        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat__field--urut\s*\{[^}]*order: 3;/',
            $ponsel
        );

        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat__field--status\s*\{[^}]*order: 4;/',
            $ponsel
        );

        /*
         * Baris simpul filter aktif ikut jadi sel grid dan order-nya harus paling
         * akhir. Tanpa itu ia mendahului kotak cari: order 0 selalu lebih dulu
         * dari order 1, jadi baris ringkasan filter akan naik ke paling atas
         * setiap kali ada filter aktif.
         */
        $this->assertMatchesRegularExpression(
            '/\.ad-konten-alat-kotak > \.ad-alat-baris__simpul\s*\{[^}]*order: 7;/',
            $ponsel,
            'Baris simpul filter aktif harus tetap paling bawah di ponsel.'
        );
    }

    /**
     * Kaki kartu Konten Pembelajaran membagi rata sesuai jumlah tombolnya.
     *
     * Yang dijaga bukan hanya "Lihat dan Edit sama lebar" — itu sudah terjadi
     * dengan grid dua kolom — tapi juga bahwa kolom kosong tidak pernah muncul.
     * Kalau jumlah kolomnya ditulis tetap dua sementara kartunya hanya merender
     * satu tombol, separuh baris itu jadi ruang mati yang terbaca sebagai
     * ruang putih di samping tombol.
     *
     * Karena itu jumlah kolomnya ikut jumlah anak: grid-auto-flow: column
     * membuat satu tombol mengisi satu kolom penuh, dan dua tombol membagi rata.
     */
    public function test_kaki_kartu_konten_membagi_rata_tanpa_kolom_kosong(): void
    {
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/app.css')));

        preg_match('/\.kartu-konten \.karya-aksi\s*\{([^}]*)\}/', $css, $cocok);

        $this->assertNotEmpty($cocok, 'Aturan kaki kartu Konten tidak ada di app.css.');

        $kaki = $cocok[1];

        $this->assertStringContainsString('grid-auto-flow: column', $kaki);
        $this->assertStringContainsString('grid-auto-columns: minmax(0, 1fr)', $kaki);

        // Jumlah kolom yang dipatok akan menyisakan kolom kosong saat hanya ada
        // satu tombol.
        $this->assertStringNotContainsString('grid-template-columns', $kaki);

        // Setiap tombol tetap mengisi selnya penuh, jadi tidak ada ruang putih
        // di dalam kolomnya sendiri.
        preg_match('/\.kartu-konten \.karya-aksi__tombol\s*\{([^}]*)\}/', $css, $tombol);
        $this->assertStringContainsString('width: 100%', $tombol[1] ?? '');
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
            ->assertSee('Cari quiz...', false);
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

    /*
     * =============================================================
     * PRATINJAU: ISI MATERI DITARIK DARI SERVER
     * =============================================================
     * Pratinjau tidak lagi merakit isi materinya sendiri di JavaScript. Badannya
     * diambil dari endpoint yang memakai pemecah yang sama dengan halaman
     * detail, jadi Daftar Isi, kartu seksi, dan blok kode yang diwarnai di
     * pratinjau benar-benar keluaran komponen yang sama. Yang diuji di bawah
     * adalah sifat yang paling mudah hilang kalau prosesnya dipecah lagi:
     * pratinjau harus sama dengan halaman detail, dan harus menolak isian yang
     * route-nya sendiri akan menolak.
     */

    public function test_pratinjau_mengembalikan_daftar_isi_dan_kartu_seksi(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();

        $isi = "# Pendahuluan\n\nSelamat datang.\n\n# Isi Materi\n\nParagraf kedua.";

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.pratinjau'), [
                'nama' => 'Materi Pratinjau',
                'isi' => $isi,
                'pelajaran_id' => $pelajaran->id,
                'tingkat_kesulitan' => 'Mudah',
            ])
            ->assertOk()
            ->assertSee('Daftar Isi', false)
            ->assertSee('Pendahuluan')
            ->assertSee('Isi Materi')
            ->assertSee('data-bab-nav', false);
    }

    public function test_pratinjau_menampilkan_blok_kode_dengan_penanda_bahasa(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.pratinjau'), [
                'nama' => 'Materi Kode',
                'isi' => "Intro\n\n```php\n<?php echo 1;\n```",
            ])
            ->assertOk()
            ->assertSee('kode-blok', false)
            ->assertSee('data-bahasa="php"', false);
    }

    public function test_pratinjau_sama_dengan_halaman_detail_materi(): void
    {
        $admin = $this->buatAdmin();
        $isi = "# Pendahuluan\n\nSelamat datang.\n\n```php\n<?php echo 1;\n```\n\n# Isi Materi\n\nParagraf kedua.";

        $materi = $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Sama');
        $materi->update(['isi' => $isi]);

        $pratinjau = $this->actingAs($admin)
            ->post(route('admin.konten.materi.pratinjau'), ['nama' => 'Materi Sama', 'isi' => $isi])
            ->assertOk()
            ->getContent();

        $detail = $this->actingAs($admin)
            ->get(route('admin.materi.show', $materi->slug))
            ->assertOk()
            ->getContent();

        /*
         * Yang dibandingkan adalah badan isinya saja, bukan seluruh halaman.
         * Halaman detail memakai layout admin dan punya baris "Kembali ke
         * Materi" yang memang tidak ada di pratinjau, jadi membandingkan
         * keduanya secara utuh akan selalu gagal karena itu.
         */
        $badan = static function (string $html): string {
            $mulai = strpos($html, 'data-bab-wadah');
            $selesai = strpos($html, '</div>', strrpos($html, 'data-bab-nav')) ?: strlen($html);

            return preg_replace('/\s+/', ' ', substr($html, $mulai, $selesai - $mulai));
        };

        $this->assertNotSame(false, strpos($detail, 'data-bab-wadah'));
        $this->assertSame($badan($detail), $badan($pratinjau));
    }

    public function test_pratinjau_menolak_isi_yang_terlalu_besar(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.pratinjau'), [
                'nama' => 'Materi Raksasa',
                'isi' => str_repeat('a', 512 * 1024 + 1),
            ])
            ->assertStatus(422);
    }

    public function test_pratinjau_menolak_tamu(): void
    {
        $this->post(route('admin.konten.materi.pratinjau'), [
            'nama' => 'Materi',
            'isi' => 'Isi.',
        ])->assertRedirect(route('login'));
    }

    /*
 * =============================================================
 * HALAMAN DETAIL: SAMA DENGAN MENU MATERI
 * =============================================================
 * Kartu Konten Pembelajaran punya URL sendiri (/admin/konten/materi/{slug})
 * supaya menu sidebar yang menyala tetap Konten Pembelajaran. Yang tidak
 * boleh berbeda adalah isi halamannya: form, pemecah, dan view-nya sama.
 * Test di bawah membandingkan keduanya byte per byte setelah sidebar
 * dibuang — kalau ada view atau controller yang mulai ditulis ulang di
 * salah satu route, test ini yang menangkapnya lebih dulu.
 *
 * Ada satu perbedaan yang sengaja dikecualikan: tujuan tombol "Kembali ke
 * Materi". Halaman konten harus kembali ke /admin/konten?tab=materi dan
 * halaman katalog ke /admin/materi, jadi tautannya ditukar dulu sebelum
 * keduanya dibandingkan — sisanya wajib identik.
 */
    public function test_detail_konten_identik_dengan_detail_materi(): void
    {
        $admin = $this->buatAdmin();

        $materi = $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Sama');
        $materi->update([
            'isi' => "# Pendahuluan\n\nSelamat datang.\n\n# Isi Materi\n\nParagraf kedua.",
        ]);

        $tanpaSidebar = static function (string $html): string {
            $mulai = strpos($html, '<main');
            $selesai = strrpos($html, '</main>');

            return preg_replace('/\s+/', ' ', substr($html, $mulai, $selesai - $mulai));
        };

        $konten = $tanpaSidebar(
            $this->actingAs($admin)->get(route('admin.konten.materi.show', $materi->slug))->assertOk()->getContent()
        );

        $katalog = $tanpaSidebar(
            $this->actingAs($admin)->get(route('admin.materi.show', $materi->slug))->assertOk()->getContent()
        );

        $tujuanKonten = route('admin.konten', ['tab' => 'materi']);
        $tujuanKatalog = route('admin.materi');

        $this->assertStringContainsString('href="'.$tujuanKonten.'"', $konten);
        $this->assertStringNotContainsString('href="'.$tujuanKatalog.'"', $konten);

        $this->assertStringContainsString('href="'.$tujuanKatalog.'"', $katalog);
        $this->assertStringNotContainsString('href="'.$tujuanKonten.'"', $katalog);

        $this->assertSame(
            $katalog,
            str_replace($tujuanKonten, $tujuanKatalog, $konten)
        );
    }

    /*
     * =============================================================
     * DAFTAR ISI: MATERI BUATAN FORM JUGA PUNYA PENANDA SEKSI
     * =============================================================
     * Form Tambah Materi menyusun babnya jadi "Bab 1: Judul", bukan "# Judul"
     * (lihat susunIsi di resources/js/materi-tambah.js). App\Support\BabMateri
     * membaca keduanya — itu sebabnya kartu bisa menulis "2 Bab" — tetapi
     * pemecah halaman detail (App\Support\IsiMateri) dulu hanya mengenal "#".
     *
     * Akibatnya materi yang dibuat lewat form selalu jatuh jadi satu seksi:
     * Daftar Isi tidak pernah muncul dan seluruh babnya menumpuk di satu kartu,
     * padahal jumlah babnya lebih dari satu. Materi buatan admin adalah materi
     * yang paling sering dibuka dari menu Konten Pembelajaran, jadi gejalanya
     * terasa sebagai "Daftar Isi-nya mana?".
     */
    public function test_daftar_isi_muncul_untuk_materi_buatan_form(): void
    {
        $admin = $this->buatAdmin();

        $materi = $this->buatMateri($admin, Materi::STATUS_PUBLISHED, 'Materi Buatan Form');
        $materi->update([
            'isi' => "Bab 1: Pendahuluan\n\nIsi bab pertama.\n\nBab 2: Latihan Dasar\n\nIsi bab kedua.",
        ]);

        $this->actingAs($admin)
            ->get(route('admin.konten.materi.show', $materi->slug))
            ->assertOk()
            ->assertSee('Daftar Isi', false)
            ->assertSee('1. Pendahuluan')
            ->assertSee('2. Latihan Dasar')
            ->assertSee('id="pendahuluan"', false)
            ->assertSee('data-bab-nav', false)
            // Nomor bab tidak ikut jadi judul, jadi tidak ada "Bab 1:" dobel.
            ->assertDontSee('Bab 1: Pendahuluan');
    }

    /*
         * =============================================================
         * TANGGAL DI KIRI, MENU TITIK TIGA KE ATAS
         * =============================================================
         * Dua hal kecil yang kalau dibalik lagi akan langsung terlihat salah di
         * layar: tanggal harus mengalir di baris informasi seperti butir lain
         * (yang didorong ke kanan hanya menu tiga titik), dan isi menu harus
         * keluar ke atas supaya tidak tertutup kartu di baris berikutnya.
         */
    public function test_tanggal_di_kartu_konten_mengalir_di_baris_informasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $html = $this->actingAs($admin)->get(route('admin.konten'))->assertOk()->getContent();

        $tanggal = strpos($html, 'karya-info__butir karya-info__butir--sepuh');
        $akhir = strpos($html, 'kartu-konten__akhir');

        $this->assertNotFalse($tanggal, 'Baris tanggal tidak ada di kartu.');
        $this->assertNotFalse($akhir, 'Wadah menu tiga titik tidak ada di kartu.');
        $this->assertLessThan($akhir, $tanggal, 'Tanggal harus tampil sebelum menu tiga titik.');
    }

    public function test_menu_tiga_titik_keluar_ke_atas(): void
    {
        $css = $this->tanpaKomentar(file_get_contents(resource_path('css/admin.css')));

        foreach (['.ad-titik__menu', '.ad-konten-menu__isi'] as $kelas) {
            preg_match('/'.preg_quote($kelas, '/').'\s*\{([^}]*)\}/', $css, $cocok);

            $this->assertNotEmpty($cocok, "Aturan {$kelas} tidak ada di admin.css.");
            $this->assertStringContainsString('bottom: calc(100%', $cocok[1]);
            $this->assertStringNotContainsString('top: calc(100%', $cocok[1]);
        }
    }

    public function test_tombol_tambah_bab_tidak_menuliskan_tanda_plus_dua_kali(): void
    {
        /*
         * Tombolnya sudah punya ikon plus di sebelah kiri, jadi "+" di dalam
         * teks labelnya membuat "+ +Tambah Bab" terbaca di layar. Yang diperiksa
         * hanya isi <span> labelnya — kalimat petunjuk di bawah daftar tetap
         * boleh menulis "+ Tambah Bab" karena di situ yang ditulis adalah teks
         * yang harus diklik, bukan nama tombolnya.
         */
        $html = $this->actingAs($this->buatAdmin())
            ->get(route('admin.konten.materi.tambah'))
            ->assertOk()
            ->getContent();

        preg_match('/<span data-bab-tambah-label>(.*?)<\/span>/s', $html, $cocok);

        $this->assertNotEmpty($cocok, 'Label tombol tambah bab tidak ada di form.');
        $this->assertSame('Tambah Bab', trim($cocok[1]));
    }

    /*
 * =============================================================
 * DESKRIPSI KARTU: TIDAK BOLEH JATUH KE ISI MATERI
 * =============================================================
 * Form Tambah Materi tidak punya isian deskripsi, jadi materi yang dibuat dari
 * sini hampir selalu deskripsinya kosong. Kalau kartu memakai
 * Materi::ringkasan() — yang sengaja jatuh ke isi materi kalau deskripsi
 * kosong — yang tampil di bawah judul adalah 120 karakter pertama isi materi
 * beserta penanda babnya: "Bab 1: Pendahuluan …". Itu bukan ringkasan, dan
 * admin membacanya sebagai kartu yang rusak.
     */
    public function test_kartu_tidak_menampilkan_isi_materi_sebagai_deskripsi(): void
    {
        $admin = $this->buatAdmin();

        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Tanpa Deskripsi');
        $materi->update([
            'deskripsi' => null,
            'isi' => "Bab 1: Pendahuluan\n\ndfdfsdf\n\nBab 2: Bab Baru\n\nafdafra",
        ]);

        $html = $this->actingAs($admin)->get(route('admin.konten'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Bab 1: Pendahuluan', $html);
        $this->assertStringNotContainsString('kartu-materi__deskripsi', $html);

        // Judul dan informasinya tetap tampil; hanya deskripsi yang hilang.
        $this->assertStringContainsString('Materi Tanpa Deskripsi', $html);
        $this->assertStringContainsString('2 Bab', $html);
    }

    public function test_kartu_tetap_menampilkan_deskripsi_yang_ada(): void
    {
        $admin = $this->buatAdmin();

        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Berdeskripsi')->update([
            'deskripsi' => 'Ringkasan singkat yang ditulis pengarangnya.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Ringkasan singkat yang ditulis pengarangnya.');
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

    /**
     * Navigasi bawah admin punya enam menu, dan urutannya tetap.
     *
     * Di <= 767px sidebar disembunyikan, jadi <nav class="ad-bawah"> ini
     * satu-satunya cara pindah halaman. Dulu isinya lima menu; "Materi"
     * sengaja ditahan dan hanya terbuka lewat tombol "Lihat Semua" di
     * dashboard. Sekarang Materi ikut masuk dan harus muncul tepat setelah
     * "Verifikasi".
     *
     * Dua sisi yang diuji:
     *
     *   - Urutannya. "Materi" disaring dari $menuUtama, jadi posisinya
     *     berutut dari sidebar dan bisa bergeser diam-diam kalau urutan
     *     $menuUtama diubah.
     *   - Jumlah kolom CSS-nya sama dengan jumlah tautan. Kalau <ul> berisi
     *     enam <a> sementara grid-nya masih repeat(5, ...), kolom keenam
     *     turun ke baris kedua dan bar navigasi menutupi isi halaman.
     */
    public function test_navigasi_bawah_admin_punya_enam_menu_dengan_urutan_yang_tepat(): void
    {
        $admin = $this->buatAdmin();

        $html = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->baseResponse->getContent();

        $awal = strpos($html, 'aria-label="Navigasi utama admin"');
        $this->assertNotFalse($awal, 'Navigasi bawah admin tidak ada di halaman.');

        $nav = substr($html, $awal, strpos($html, '</nav>', $awal) - $awal);

        $tujuan = [
            route('admin.dashboard'),
            route('admin.konten'),
            route('admin.verifikasi'),
            route('admin.materi'),
            route('admin.quiz'),
            route('admin.pengguna'),
        ];

        $this->assertSame(6, substr_count($nav, '<a href'), 'Navigasi bawah admin harus berisi tepat enam tautan.');

        $sebelumnya = -1;

        foreach ($tujuan as $satu) {
            $posisi = strpos($nav, 'href="'.$satu.'"');

            $this->assertNotFalse($posisi, "Menu $satu tidak ada di navigasi bawah.");
            $this->assertGreaterThan(
                $sebelumnya,
                $posisi,
                "Menu $satu ada di tempat yang salah pada navigasi bawah."
            );

            $sebelumnya = $posisi;
        }

        // "Materi" tepat setelah "Verifikasi", bukan di ujung mana pun.
        $this->assertGreaterThan(
            strpos($nav, 'href="'.route('admin.verifikasi').'"'),
            strpos($nav, 'href="'.route('admin.materi').'"'),
            'Materi harus muncul setelah Verifikasi.'
        );

        $css = file_get_contents(resource_path('css/admin.css'));

        $this->assertStringContainsString(
            'grid-template-columns: repeat(6, minmax(0, 1fr))',
            $css,
            'Jumlah kolom navigasi bawah harus enam, sama dengan jumlah tautannya.'
        );

        /*
         * "Pengaturan" tetap di luar baris bawah: ia punya banyak halaman
         * anak dan sudah terbuka lewat ikon roda di header ponsel. Kalau ikut
         * masuk, barisnya jadi tujuh dan tidak muat di 320px.
         */
        $this->assertStringNotContainsString('href="'.route('admin.pengaturan').'"', $nav);
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
            ->assertDontSee('Publish Sekarang')
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

    /**
     * Form materi admin tidak punya aksi terbitkan sama sekali.
     *
     * Menerbitkan materi dilakukan dari daftar Konten Pembelajaran lewat aksi
     * Publish / Batalkan Publikasi di menu tiga titik, jadi form Tambah dan
     * Edit tidak lagi punya tombol ber-name="aksi" value="publish". Yang
     * tersisa hanya "Simpan Draft".
     *
     * Dua hal yang diperiksa, dan keduanya perlu datang berpasangan:
     *
     *   - tidak ada pemicu data-konten-publish. Kalau pemicunya muncul lagi,
     *     dialog konfirmasi ikut hidup dan kita kembali ke jalur yang dulu
     *     salah: tombol di dalam form yang konfirmasinya memanggil wizard
     *     yang tidak ada di halaman ini (data-konten-kirim), sehingga form
     *     tidak pernah terkirim.
     *   - tidak ada dialog terbitan. resources/js/konten-publish.js berhenti
     *     sendiri begitu pemicunya tidak ada, jadi dialog yang dirender di sini
     *     hanya markup display:none yang tidak pernah bisa dibuka — persis
     *     markup yang dulu jadi sumber form tanpa action dan 405.
     */
    public function test_form_materi_tidak_punya_aksi_terbitkan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Tanpa Publish');
        $this->buatPelajaran();

        $halaman = [
            'tambah' => $this->actingAs($admin)->get(route('admin.konten.materi.tambah')),
            'edit' => $this->actingAs($admin)->get(route('admin.konten.materi.edit', $materi->slug)),
        ];

        foreach ($halaman as $nama => $respons) {
            $respons->assertOk();
            $isi = $respons->baseResponse->getContent();

            $this->assertStringNotContainsString('data-konten-publish', $isi, "Halaman $nama tidak punya pemicu terbitan.");
            $this->assertStringNotContainsString('data-konten-kirim', $isi, "Halaman $nama tidak punya wizard.");
            $this->assertStringNotContainsString('data-konten-publish-dialog', $isi, "Halaman $nama tidak perlu dialog terbitan.");

            /*
             * Yang tersisa satu tombol kirim, dan itu tombol draft. Field
             * "aksi" tetap ikut terkirim karena namanya ada pada tombolnya:
             * tanpa itu server tidak tahu materi ini harus jadi draft.
             */
            $this->assertSame(
                1,
                preg_match('/<button(?=[^>]*type="submit")(?=[^>]*name="aksi")(?=[^>]*value="draft")[^>]*>/', $isi),
                "Halaman $nama harus punya tepat satu tombol kirim, yaitu Simpan Draft."
            );

            $this->assertSame(
                0,
                preg_match('/<button(?=[^>]*type="submit")(?=[^>]*value="publish")[^>]*>/', $isi),
                "Tombol terbitan tidak boleh ada di halaman $nama."
            );
        }
    }

    /**
     * Dialog hapus bab harus berada di dalam akar form yang sama.
     *
     * resources/js/materi-tambah.js hanya mencari elemen di dalam elemen
     * [data-tambah-materi] PERTAMA di halaman. Kalau dialog dibungkus akar
     * terpisah, dialognya tidak ditemukan dan modul langsung menghapus bab
     * tanpa konfirmasi (lihat cabang !dialog di bukaDialogHapus), padahal
     * form pemiliknya selalu bertanya dulu.
     *
     * Yang diperiksa dua hal: akarnya cuma satu, dan dialognya ada setelah
     * akar itu dibuka. Keduanya berpasangan — akar tunggal tanpa dialog
     * sama saja mematikan konfirmasinya.
     */
    public function test_dialog_hapus_bab_berada_di_dalam_akar_form(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Dialog Hapus');

        $halaman = [
            'tambah' => $this->actingAs($admin)
                ->get(route('admin.konten.materi.tambah'))
                ->assertOk()
                ->baseResponse->getContent(),
            'edit' => $this->actingAs($admin)
                ->get(route('admin.konten.materi.edit', $materi->slug))
                ->assertOk()
                ->baseResponse->getContent(),
        ];

        foreach ($halaman as $nama => $isiHalaman) {
            $this->assertSame(
                1,
                substr_count($isiHalaman, 'data-tambah-materi'),
                "Halaman $nama harus punya tepat satu akar form."
            );

            $akar = strpos($isiHalaman, 'data-tambah-materi');
            $dialog = strpos($isiHalaman, 'data-dialog-hapus role="dialog"');

            $this->assertNotFalse($akar, "Akar form halaman $nama tidak ditemukan.");
            $this->assertNotFalse($dialog, "Halaman $nama tidak punya dialog hapus bab.");
            $this->assertGreaterThan(
                $akar,
                $dialog,
                "Dialog hapus bab halaman $nama berada di luar akar form."
            );
        }
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

    public function test_form_edit_konten_mempertahankan_kategori_yang_sudah_tidak_aktif(): void
    {
        /*
         * Menu Konten memakai form yang sama dengan form edit Materi, tapi
         * kategorinya diambil lewat controller sendiri. Materi yang
         * kategorinya baru dinonaktifkan lewat Pengaturan harus tetap tampil
         * terpilih di sana — kalau tidak, pilihannya hilang dari dropdown dan
         * nilainya diam-diam berpindah ke kategori pertama begitu Simpan
         * ditekan.
         */
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Kategori Nonaktif');

        $materi->pelajaran->update(['aktif' => false]);

        $html = $this->actingAs($admin)
            ->get(route('admin.konten.materi.edit', $materi->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            preg_match('/<option value="'.$materi->pelajaran_id.'"[^>]*>/', $html, $opsi),
            'Kategori materi tidak ikut masuk ke dropdown edit Konten.',
        );
        $this->assertStringContainsString('selected', $opsi[0]);
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

    public function test_membuka_lonceng_menandai_semua_notifikasi_sudah_dibaca(): void
    {
        $admin = $this->buatAdmin();
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        // Dua terbitan supaya ada lebih dari satu baris belum dibaca: satu
        // klik pada satu baris tidak akan membuat semuanya terbaca.
        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'aksi' => 'publish',
            ]));

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'nama' => 'Materi Kedua',
                'aksi' => 'publish',
            ]));

        $this->assertSame(2, Notifikasi::query()->where('pengguna_id', $pengguna->getKey())->count());

        // Panel lonceng mengirim ini lewat fetch, jadi jawabannya yang
        // dipakai JavaScript untuk menyembunyikan titiknya.
        $this->actingAs($pengguna)
            ->postJson(route('user.notifikasi.baca-semua'))
            ->assertOk()
            ->assertJson(['terbaca' => true, 'sisa' => 0]);

        $this->assertSame(
            0,
            Notifikasi::query()
                ->where('pengguna_id', $pengguna->getKey())
                ->belumDibaca()
                ->count()
        );

        // Titiknya hilang karena tidak ada sisa yang belum dibaca, dan
        // barisnya tetap ada di daftar karena notifikasi boleh dibaca ulang.
        $this->actingAs($pengguna)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Materi baru tersedia')
            ->assertDontSee('notif__titik', false)
            ->assertDontSee('is-belum', false);
    }

    public function test_membuka_lonceng_tidak_menandai_notifikasi_milik_orang_lain(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.tambah.store'), $this->dataMateri($pelajaran, [
                'aksi' => 'publish',
            ]));

        /*
         * Rute ini tidak punya parameter notifikasi, jadi yang perlu dijaga
         * bukan "baris mana yang boleh disentuh" melainkan "baris siapa saja
         * yang boleh disentuh": notifikasi pengguna lain harus tetap belum
         * dibaca karena hanya satu tabel yang dipakai dua lonceng.
         */
        $this->actingAs($orangLain)
            ->post(route('user.notifikasi.baca-semua'))
            ->assertRedirect();

        $this->assertSame(
            1,
            Notifikasi::query()
                ->whereIn('pengguna_id', User::query()->where('peran', User::PERAN_USER)->whereKeyNot($orangLain->getKey())->pluck('id'))
                ->belumDibaca()
                ->count()
        );
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
