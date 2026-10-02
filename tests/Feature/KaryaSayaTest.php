<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman "Karya Saya" plus kelola materi & quiz milik sendiri.
 *
 * Yang diuji di sini adalah tiga hal yang jadi inti fitur ini:
 *   1. Karya Saya hanya menampilkan konten milik pengguna yang login.
 *   2. Materi/quiz milik sendiri tetap muncul di halaman Materi/Quiz umum.
 *   3. Edit dan hapus hanya boleh untuk konten sendiri; milik orang lain
 *      ditolak 403, dan konten yang tidak ada ditolak 404.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml) dan disk publik
 * palsu, jadi tidak menyentuh database sungguhan.
 */
class KaryaSayaTest extends TestCase
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

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'ikon' => '</>',
            'aktif' => true,
        ]);
    }

    private function buatMateri(Pelajaran $pelajaran, ?User $pembuat, string $nama): Materi
    {
        return Materi::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $pembuat?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value(),
            'deskripsi' => "Ringkasan $nama",
            'isi' => "Bab 1: Pendahuluan\n\nIsi materi.\n\nBab 2: Lanjutan\n\nIsi bab kedua.",
            'tingkat_kesulitan' => 'Mudah',
            // Default-nya sudah disetujui supaya materinya tayang, sama
            // seperti materi yang sudah lewat peninjauan admin.
            'status' => Materi::STATUS_PUBLISHED,
        ]);
    }

    private function buatQuiz(Pelajaran $pelajaran, ?User $pembuat, string $judul, string $status = Quiz::STATUS_PUBLISHED): Quiz
    {
        $quiz = Quiz::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $pembuat?->getKey(),
            'judul' => $judul,
            'slug' => str($judul)->slug()->value(),
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => $status,
        ]);

        Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Pertanyaan satu',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'jawaban_benar' => 'A',
            'pembahasan' => null,
            'urutan' => 1,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);

        return $quiz;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataSoal(array $tambahan = []): array
    {
        return array_merge([
            'pertanyaan' => 'Apa itu Blade?',
            'pilihan_a' => 'Mesin template',
            'pilihan_b' => 'Database',
            'pilihan_c' => 'CSS',
            'pilihan_d' => 'JavaScript',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Blade adalah mesin template Laravel.',
            'tingkat_kesulitan' => 'Mudah',
        ], $tambahan);
    }

    public function test_halaman_karya_saya_membuka_dengan_judul_dan_subtitle(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get('/user/karya-saya')
            ->assertOk()
            ->assertSee('Karya Saya')
            ->assertSee('Kelola materi dan quiz yang kamu buat sendiri.')
            ->assertSee('Materi Saya')
            ->assertSee('Quiz Saya');
    }

    public function test_halaman_karya_saya_hanya_menampilkan_materi_pribadi(): void
    {
        $budi = $this->buatPengguna();
        $siti = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();
        $this->buatMateri($pelajaran, $budi, 'Materi Budi');
        $this->buatMateri($pelajaran, $siti, 'Materi Siti');

        $this->actingAs($budi)
            ->get('/user/karya-saya')
            ->assertOk()
            ->assertSee('Materi Budi')
            ->assertDontSee('Materi Siti');
    }

    public function test_halaman_karya_saya_hanya_menampilkan_quiz_pribadi(): void
    {
        $budi = $this->buatPengguna();
        $siti = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();
        $this->buatQuiz($pelajaran, $budi, 'Quiz Budi');
        $this->buatQuiz($pelajaran, $siti, 'Quiz Siti');

        $this->actingAs($budi)
            ->get('/user/karya-saya?tab=quiz')
            ->assertOk()
            ->assertSee('Quiz Budi')
            ->assertDontSee('Quiz Siti');
    }

    public function test_karya_saya_menampilkan_materi_yang_belum_terbit_pemiliknya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Materi Disembunyikan');
        $materi->update(['status' => Materi::STATUS_DRAFT]);

        $this->actingAs($user)
            ->get('/user/karya-saya')
            ->assertOk()
            ->assertSee('Materi Disembunyikan')
            ->assertSee('Draft');
    }

    public function test_karya_saya_menampilkan_alasan_penolakan_dan_sisa_pengajuan(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Materi Ditolak');
        $materi->tolak('Bab 3 belum ada contoh kode.');

        $this->actingAs($user)
            ->get('/user/karya-saya')
            ->assertOk()
            ->assertSee('Materi Ditolak')
            ->assertSee('Ditolak')
            ->assertSee('Bab 3 belum ada contoh kode.');
    }

    public function test_kartu_menampilkan_jumlah_bab_dan_tanggal_dibuat(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        $isi = $this->actingAs($user)->get('/user/karya-saya')->getContent();

        $this->assertStringContainsString('2 Bab', $isi);
        $this->assertStringContainsString(
            $materi->created_at->translatedFormat('d M Y'),
            $isi,
        );
    }

    public function test_karya_saya_kosong_menampilkan_tautan_ke_form_yang_sudah_ada(): void
    {
        $user = $this->buatPengguna();
        $this->buatPelajaran();

        $materi = $this->actingAs($user)->get('/user/karya-saya')
            ->assertOk()
            ->assertSee('Belum ada materi yang kamu buat')
            ->assertSee(route('user.materi.tambah'), false);

        $this->actingAs($user)->get('/user/karya-saya?tab=quiz')
            ->assertOk()
            ->assertSee('Belum ada quiz yang kamu buat')
            ->assertSee(route('user.quiz.tambah'), false);
    }

    /*
     * Pratinjau pada form materi milik pengguna dilayani route-nya sendiri,
     * bukan route admin. Yang dijaga di sini bukan hanya(route-nya) masih ada,
     * tapi juga bahwa formnya benar-benar menyebutkannya: tab Preview di form
     * admin dan form pengguna memakai modul JavaScript yang sama, jadi kalau
     * route anggota hilang, pratinjau di form miliknya akan kosong tanpa
     * penjelasan apa pun.
     */
    public function test_pratinjau_form_materi_punya_endpoint_sendiri(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $form = $this->actingAs($user)
            ->get(route('user.materi.tambah'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('user.materi.pratinjau'), $form);

        $this->actingAs($user)
            ->post(route('user.materi.pratinjau'), [
                'nama' => 'Materi Pratinjau',
                'isi' => "# Pendahuluan\n\nSelamat datang.\n\n# Isi Materi\n\nParagraf kedua.",
                'pelajaran_id' => $pelajaran->id,
            ])
            ->assertOk()
            ->assertSee('Daftar Isi', false)
            ->assertSee('Pendahuluan')
            ->assertSee('Isi Materi');
    }

    public function test_pencarian_menyaring_karya_pribadi(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $this->buatMateri($pelajaran, $user, 'Belajar Blade');
        $this->buatMateri($pelajaran, $user, 'Belajar Tailwind');

        $this->actingAs($user)
            ->get('/user/karya-saya?q=Tailwind')
            ->assertOk()
            ->assertSee('Belajar Tailwind')
            ->assertDontSee('Belajar Blade');
    }

    public function test_kartu_materi_berisi_tombol_lihat_edit_dan_dialog_hapus(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        $isi = $this->actingAs($user)->get('/user/karya-saya')->getContent();

        $this->assertStringContainsString(route('user.materi.detail', $materi->slug), $isi);
        $this->assertStringContainsString(route('user.materi.edit', $materi->slug), $isi);
        $this->assertStringContainsString(route('user.materi.destroy', $materi->slug), $isi);

        // Dialog konfirmasi: ada tombol Batal dan Hapus, dan form hapus
        // memakai @method('DELETE') supaya bukan GET.
        $this->assertStringContainsString('Hapus Materi?', $isi);
        $this->assertStringContainsString('Materi ini akan dihapus dan tidak dapat dikembalikan.', $isi);
        $this->assertStringContainsString('name="_method" value="DELETE"', $isi);
        $this->assertStringContainsString('data-konfirmasi-batal', $isi);
    }

    public function test_kartu_quiz_berisi_tombol_lihat_edit_dan_dialog_hapus(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Pemrograman');

        $isi = $this->actingAs($user)->get('/user/karya-saya?tab=quiz')->getContent();

        $this->assertStringContainsString(route('user.quiz.detail', $quiz), $isi);
        $this->assertStringContainsString(route('user.quiz.edit', $quiz), $isi);
        $this->assertStringContainsString(route('user.quiz.destroy', $quiz), $isi);

        $this->assertStringContainsString('Hapus Quiz?', $isi);
        $this->assertStringContainsString('Quiz ini akan dihapus dan tidak dapat dikembalikan.', $isi);
    }

    public function test_kartu_ringkasan_menghitung_seluruh_karya_pengguna(): void
    {
        $user = $this->buatPengguna();
        $orangLain = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();

        $this->buatMateri($pelajaran, $user, 'Materi Budi');
        $this->buatMateri($pelajaran, $user, 'Materi Budi Dua');
        $this->buatMateri($pelajaran, $orangLain, 'Materi Siti');

        $this->buatQuiz($pelajaran, $user, 'Quiz Menunggu', Quiz::STATUS_PENDING);
        $this->buatQuiz($pelajaran, $user, 'Quiz Terbit');
        $this->buatQuiz($pelajaran, $orangLain, 'Quiz Siti', Quiz::STATUS_PENDING);

        // Materi yang statusnya masih pending ikut dihitung sebagai "menunggu"
        // supaya angkanya sama dengan hitungan di halaman admin.
        $materiMenunggu = $this->buatMateri($pelajaran, $user, 'Materi Menunggu');
        $materiMenunggu->update(['status' => Materi::STATUS_PENDING]);

        $halaman = $this->actingAs($user)->get('/user/karya-saya')->assertOk();

        // Tiga materi, dua quiz, dua soal, dan dua yang masih pending.
        // Karya Siti tidak ikut dihitung di nomor mana pun.
        $this->assertSame([
            'materi' => 3,
            'quiz' => 2,
            'soal' => 2,
            'menunggu' => 2,
        ], $halaman->viewData('ringkasan'));

        $halaman->assertSee('Materi Saya')
            ->assertSee('Quiz Saya')
            ->assertSee('Total Soal')
            ->assertSee('Menunggu Persetujuan');
    }

    public function test_daftar_karya_membagi_sembilan_kartu_per_halaman(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        for ($i = 1; $i <= 10; $i++) {
            $this->buatMateri($pelajaran, $user, 'Materi '.$i);
        }

        $halaman = $this->actingAs($user)->get('/user/karya-saya')->assertOk();

        // Sembilan per halaman supaya grid tiga kolom jadi tiga baris penuh.
        $this->assertSame(9, $halaman->viewData('paginasi')->perPage());
        $this->assertCount(9, $halaman->viewData('daftar'));
    }

    public function test_form_edit_materi_membuka_dengan_isi_lama(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        $this->actingAs($user)
            ->get(route('user.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('Edit Materi')
            ->assertSee('value="Belajar Blade"', false)
            // Daftar bab dipecah lagi dari isi tersimpan.
            ->assertSee('Pendahuluan', false)
            ->assertSee('Lanjutan', false);
    }

    public function test_materi_bisa_diubah_oleh_pemiliknya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Belajar Blade Dasar',
                'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
                'tingkat_kesulitan' => 'Sedang',
            ])
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tb_materi', [
            'id' => $materi->getKey(),
            'nama' => 'Belajar Blade Dasar',
            // Slog tidak ikut berubah supaya tautan lama tidak rusak.
            'slug' => $materi->slug,
            'tingkat_kesulitan' => 'Sedang',
        ]);
    }

    public function test_materi_milik_orang_lain_tidak_bisa_diedit_atau_dihapus(): void
    {
        $budi = $this->buatPengguna();
        $siti = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $siti, 'Materi Siti');

        $this->actingAs($budi)
            ->get(route('user.materi.edit', $materi->slug))
            ->assertForbidden();

        $this->actingAs($budi)
            ->put(route('user.materi.update', $materi->slug), [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Materi Sitinya Dibajak',
                'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
                'tingkat_kesulitan' => 'Mudah',
            ])
            ->assertForbidden();

        $this->actingAs($budi)
            ->delete(route('user.materi.destroy', $materi->slug))
            ->assertForbidden();

        $this->assertDatabaseHas('tb_materi', [
            'id' => $materi->getKey(),
            'nama' => 'Materi Siti',
        ]);
    }

    public function test_materi_yang_tidak_ada_menghasilkan_404(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->get('/user/materi/tidak-ada/edit')
            ->assertNotFound();
    }

    public function test_materi_pemilik_dapat_dihapus_beserta_berkasnya(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');
        $materi->update(['thumbnail' => 'thumbnails/contoh.png']);

        Storage::disk('public')->put('thumbnails/contoh.png', 'isi');

        $this->actingAs($user)
            ->delete(route('user.materi.destroy', $materi->slug))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('tb_materi', ['id' => $materi->getKey()]);
        Storage::disk('public')->assertMissing('thumbnails/contoh.png');
    }

    public function test_materi_yang_diedit_tetunya_tampil_di_halaman_materi(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');

        $this->actingAs($user)->put(route('user.materi.update', $materi->slug), [
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Belajar Blade Dasar',
            'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
            'tingkat_kesulitan' => 'Mudah',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get('/user/materi')
            ->assertOk()
            ->assertSee('Belajar Blade Dasar');
    }

    public function test_thumbnail_baru_saat_edit_mengganti_berkas_lama(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');
        $materi->update(['thumbnail' => 'thumbnails/lama.png']);

        Storage::disk('public')->put('thumbnails/lama.png', 'lama');

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Belajar Blade',
                'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
                'tingkat_kesulitan' => 'Mudah',
                'thumbnail' => UploadedFile::fake()->image('baru.png', 320, 180),
            ])
            ->assertSessionHasNoErrors();

        $tersimpan = $materi->fresh()->thumbnail;

        $this->assertStringStartsWith('thumbnails/', $tersimpan);
        $this->assertNotSame('thumbnails/lama.png', $tersimpan);
        Storage::disk('public')->assertMissing('thumbnails/lama.png');
        Storage::disk('public')->assertExists($tersimpan);
    }

    public function test_materi_yang_diedit_tanpa_berkas_baru_mempertahankan_lampiran_lama(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');
        $materi->update(['thumbnail' => 'thumbnails/lama.png']);

        Storage::disk('public')->put('thumbnails/lama.png', 'lama');

        // Kolom berkas dibiarkan kosong: lampiran yang ada tidak boleh hilang
        // hanya karena form disimpan tanpa mengunggah ulang.
        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Belajar Blade',
                'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
                'tingkat_kesulitan' => 'Mudah',
            ])
            ->assertSessionHasNoErrors();

        $setelah = $materi->fresh();

        $this->assertSame('thumbnails/lama.png', $setelah->thumbnail);
        Storage::disk('public')->assertExists('thumbnails/lama.png');
    }

    /**
     * Tombol Hapus pada thumbnail materi harus benar-benar membuang
     * berkasnya: kolom jadi kosong dan gambarnya hilang dari disk, bukan
     * cuma pratinjau di browser.
     */
    public function test_thumbnail_materi_bisa_dihapus_lewat_bendera_hapus(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pelajaran, $user, 'Belajar Blade');
        $materi->update(['thumbnail' => 'thumbnails/lama.png']);

        Storage::disk('public')->put('thumbnails/lama.png', 'lama');

        // Form edit memang menitipkan bendera itu.
        $this->actingAs($user)
            ->get(route('user.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('name="thumbnail_hapus"', false)
            ->assertSee('data-thumbnail-hapus-flag', false);

        $this->actingAs($user)
            ->put(route('user.materi.update', $materi->slug), [
                'pelajaran_id' => $pelajaran->id,
                'nama' => 'Belajar Blade',
                'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
                'tingkat_kesulitan' => 'Mudah',
                'thumbnail_hapus' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($materi->fresh()->thumbnail);
        Storage::disk('public')->assertMissing('thumbnails/lama.png');
    }

    public function test_form_edit_quiz_membuka_dengan_soal_lama(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Pemrograman');

        $this->actingAs($user)
            ->get(route('user.quiz.edit', $quiz))
            ->assertOk()
            ->assertSee('Edit Quiz')
            ->assertSee('value="Quiz Pemrograman"', false)
            ->assertSee('Pertanyaan satu');
    }

    public function test_quiz_bisa_diubah_oleh_pemiliknya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Pemrograman', Quiz::STATUS_PENDING);

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), [
                'pelajaran_id' => $pelajaran->id,
                'judul' => 'Quiz Pemrograman Dasar',
                'deskripsi' => 'Deskripsi baru.',
                'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
                'durasi' => 20,
                'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
                'soal' => [
                    $this->dataSoal(['pertanyaan' => 'Pertanyaan baru']),
                    $this->dataSoal(['pertanyaan' => 'Pertanyaan kedua', 'urutan' => 2]),
                ],
            ])
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tb_quiz', [
            'id' => $quiz->getKey(),
            'judul' => 'Quiz Pemrograman Dasar',
            'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
            'durasi' => 20,
            // Status tidak ikut diubah oleh pemilik.
            'status' => Quiz::STATUS_PENDING,
        ]);

        // Soal lama diganti seluruhnya, dan urutannya dihitung ulang.
        $this->assertDatabaseMissing('tb_soal', ['pertanyaan' => 'Pertanyaan satu']);
        $this->assertDatabaseHas('tb_soal', ['pertanyaan' => 'Pertanyaan baru', 'urutan' => 1]);
        $this->assertDatabaseHas('tb_soal', ['pertanyaan' => 'Pertanyaan kedua', 'urutan' => 2]);
    }

    /**
     * Kode akses hanya boleh huruf dan angka. Tanda hubung dan garis bawah
     * ditolak karena App\Support\KodeQuiz::normalisasi() membuangnya sebelum
     * kode dibandingkan, jadi kode yang memakainya tidak akan pernah bisa
     * diketik peserta untuk masuk.
     */
    public function test_kode_akses_dengan_tanda_hubung_ditolak(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Privat');
        $quiz->update(['visibilitas' => Quiz::VISIBILITAS_PRIVAT, 'kode_akses' => 'LAMA123']);

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), [
                'pelajaran_id' => $pelajaran->id,
                'judul' => 'Quiz Privat',
                'deskripsi' => 'Deskripsi quiz privat.',
                'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                'kode_akses' => 'LAMA-123',
                'soal' => [$this->dataSoal()],
            ])
            ->assertSessionHasErrors(['kode_akses' => 'Kode akses hanya boleh memakai huruf dan angka.']);
    }

    public function test_quiz_privat_menyimpan_kode_aksesnya_saat_diubah(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Privat');
        $quiz->update(['visibilitas' => Quiz::VISIBILITAS_PRIVAT, 'kode_akses' => 'LAMA123']);

        $this->actingAs($user)
            ->put(route('user.quiz.update', $quiz), [
                'pelajaran_id' => $pelajaran->id,
                'judul' => 'Quiz Privat',
                'deskripsi' => 'Deskripsi quiz privat.',
                'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                // Kode yang sama masih boleh dipakai saat mengedit quiz ini.
                'kode_akses' => 'LAMA123',
                'soal' => [$this->dataSoal()],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_quiz', [
            'id' => $quiz->getKey(),
            'kode_akses' => 'LAMA123',
        ]);
    }

    public function test_quiz_milik_orang_lain_tidak_bisa_diedit_atau_dihapus(): void
    {
        $budi = $this->buatPengguna();
        $siti = $this->buatPengguna(['nama' => 'Siti', 'email' => 'siti@example.com']);
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $siti, 'Quiz Siti');

        $this->actingAs($budi)
            ->get(route('user.quiz.edit', $quiz))
            ->assertForbidden();

        $this->actingAs($budi)
            ->put(route('user.quiz.update', $quiz), [
                'pelajaran_id' => $pelajaran->id,
                'judul' => 'Quiz Sitinya Dibajak',
                'deskripsi' => 'Deskripsi.',
                'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
                'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
                'soal' => [$this->dataSoal()],
            ])
            ->assertForbidden();

        $this->actingAs($budi)
            ->delete(route('user.quiz.destroy', $quiz))
            ->assertForbidden();

        $this->assertDatabaseHas('tb_quiz', ['id' => $quiz->getKey(), 'judul' => 'Quiz Siti']);
    }

    public function test_quiz_pemilik_dapat_dihapus_beserta_soalnya(): void
    {
        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $quiz = $this->buatQuiz($pelajaran, $user, 'Quiz Pemrograman');

        $this->actingAs($user)
            ->delete(route('user.quiz.destroy', $quiz))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'quiz']))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('tb_quiz', ['id' => $quiz->getKey()]);
        $this->assertDatabaseMissing('tb_soal', ['quiz_id' => $quiz->getKey()]);
    }

    public function test_halaman_karya_saya_membutuhkan_login(): void
    {
        $this->get('/user/karya-saya')->assertRedirect('/login');
        $this->put('/user/materi/contoh')->assertRedirect('/login');
        $this->delete('/user/quiz/1')->assertRedirect('/login');
    }
}
