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

        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('Konten Pembelajaran')
            ->assertSee('Kelola materi dan kuis untuk mendukung proses pembelajaran di Kelas Kita.')
            ->assertSee('Tambah Materi')
            ->assertSee('Buat materi pembelajaran untuk peserta didik.')
            ->assertSee('Tambah Quiz')
            ->assertSee('Buat quiz untuk menguji pemahaman peserta didik.');
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
            ->assertSee('Dipublikasikan');
    }

    public function test_daftar_memakai_kartu_yang_sama_dengan_karya_saya(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $this->buatQuiz($admin, Quiz::STATUS_DRAFT, 'Quiz Mentah');

        $grid = 'grid grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 xl:grid-cols-3';

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();
        $halaman->assertSee($grid, false)
            ->assertSee('kartu-materi karya-kartu', false);

        // Grid sama persis dengan halaman Karya Saya. Kelas kartunya sama, tapi
        // kartu admin menambah kartu-konten: di situ peregangan tombol
        // "Lihat" dimatikan karena jumlahnya lima tombol, bukan tiga.
        $karyaSaya = $this->actingAs($admin)
            ->get(route('user.karya-saya'))
            ->assertOk();

        $karyaSaya->assertSee($grid, false)
            ->assertSee('kartu-materi karya-kartu group min-w-0', false)
            ->assertDontSee('kartu-konten', false);
    }

    public function test_kartu_memakai_kelas_status_dan_aksi_karya_saya(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Sudah Tayang');

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        // Kelas status, info, dan aksi semuanya milik kartu Karya Saya.
        $halaman->assertSee('karya-status karya-status--', false)
            ->assertSee('karya-pojok', false)
            ->assertSee('kartu-materi__lencana', false)
            ->assertSee('karya-info__butir', false)
            ->assertSee('karya-aksi__tombol', false);
    }

    public function test_kartu_aksi_admin_menambah_publish_dan_duplikat(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');
        $quiz = $this->buatQuiz($admin, Quiz::STATUS_PUBLISHED, 'Quiz Sudah Tayang');

        // Kartu admin menandai dirinya supaya CSS bisa mematikan peregangan
        // tombol "Lihat": lima tombol tidak bisa membagi ruang rata seperti
        // tiga tombol di kartu Karya Saya, jadi tanpa ini "Lihat" melebar jadi
        // oval panjang.
        $this->actingAs($admin)
            ->get(route('admin.konten'))
            ->assertOk()
            ->assertSee('kartu-materi karya-kartu kartu-konten group min-w-0', false);

        // Draft: tombolnya "Publish".
        $halamanMateri = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        $halamanMateri->assertSee('Duplikat')
            ->assertSee('Publish')
            ->assertSee(route('admin.konten.materi.duplikat', $materi->slug), false)
            ->assertSee(route('admin.konten.materi.publish', $materi->slug), false);

        // Sudah terbit: tombol yang sama jadi "Batalkan".
        $halamanQuiz = $this->actingAs($admin)
            ->get(route('admin.konten', ['tab' => 'quiz']))
            ->assertOk();

        $halamanQuiz->assertSee('Duplikat')
            ->assertSee('Batalkan')
            ->assertSee(route('admin.konten.quiz.duplikat', $quiz->getKey()), false)
            ->assertSee(route('admin.konten.quiz.publish', $quiz->getKey()), false);
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

    public function test_seluruh_kontrol_filter_dalam_satu_baris_lurus(): void
    {
        $admin = $this->buatAdmin();
        $this->buatMateri($admin, Materi::STATUS_DRAFT, 'Materi Mentah');

        $halaman = $this->actingAs($admin)->get(route('admin.konten'))->assertOk();

        // Kotak alatnya satu baris tidak membungkus, dan seluruh kontrolnya
        // anak langsung dari satu wadah flex yang sama.
        $halaman->assertSee('ad-alat-kotak ad-alat-kotak--konten', false)
            ->assertSee('class="ad-alat-zeile"', false)
            ->assertSee('class="ad-alat-zeile__hapus"', false);

        // Tidak ada lagi pembungkus .ad-alat-baris yang bikin controls turun
        // ke baris kedua.
        $halaman->assertDontSee('class="ad-alat-baris"', false)
            ->assertDontSee('ad-alat-baris__aksi', false);

        // Kedua form tetap satu baris: "Terapkan" di form yang membawa semua
        // field, "Hapus filter" di form sendiri yang hanya membawa tab.
        $halaman->assertSee('<input type="hidden" name="tab" value="materi">', false);
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

    public function test_halaman_tambah_materi_memakai_dua_tahap_dan_komponen_pemilik(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten.materi.tambah'))
            ->assertOk()
            ->assertSee('Tambah Materi')
            ->assertSee('Informasi Dasar')
            ->assertSee('Isi Materi')
            ->assertSee('Simpan Draft')
            ->assertSee('Publish Sekarang')
            ->baseResponse->getContent();

        // Komponen yang dipakai form milik pengguna ikut terender, jadi isian
        // dan editornya bukan versi lain.
        $this->assertStringContainsString('data-tambah-materi', $halaman);
        $this->assertStringContainsString('data-editor', $halaman);
        $this->assertStringContainsString('data-bab-list', $halaman);
        $this->assertStringContainsString('name="isi"', $halaman);

        // Tidak ada saklar pengajuan persetujuan di area admin.
        $this->assertStringNotContainsString('data-publikasikan', $halaman);
    }

    public function test_halaman_tambah_quiz_memakai_dua_tahap_tanpa_langkah_pengaturan(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPelajaran();

        $halaman = $this->actingAs($admin)
            ->get(route('admin.konten.quiz.tambah'))
            ->assertOk()
            ->assertSee('Tambah Quiz')
            ->assertSee('Buat Soal')
            ->assertSee('Simpan Draft')
            ->assertSee('Publish Sekarang')
            ->baseResponse->getContent();

        // Builder soal milik form pemilik ikut dipakai.
        $this->assertStringContainsString('data-builder-daftar', $halaman);
        $this->assertStringContainsString('data-builder-input', $halaman);

        // Hanya ada dua panel langkah, dan tidak ada blok pengaturan.
        $this->assertSame(2, substr_count($halaman, 'data-wizard-panel="'));
        $this->assertSame(1, substr_count($halaman, 'data-langkah-total="2"'));
        $this->assertStringNotContainsString('data-wizard-approval-area', $halaman);
        $this->assertStringNotContainsString('data-wizard-kode-area', $halaman);

        // Visibilitas terkunci ke public supaya kode akses tidak pernah diisi.
        $this->assertStringContainsString('name="visibilitas" value="public"', $halaman);
        $this->assertStringContainsString('data-konten-aksi', $halaman);
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
