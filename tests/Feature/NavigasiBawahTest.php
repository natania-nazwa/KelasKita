<?php

namespace Tests\Feature;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kepala halaman dan navigasi bawah untuk layar kecil.
 *
 * Sebelum perubahan ini ada dua baris kepala yang tumpang tindih di mobile
 * (top bar desktop yang ikut tampil, lalu strip chip horizontal) dan tidak
 * ada navigasi bawah sama sekali. Sekarang ada satu kepala per ukuran
 * layar: top bar hanya ≥1024px, header mobile hanya <1024px, dan navigasi
 * bawah tetap hanya <1024px dengan tepat lima tujuan.
 *
 * Yang diuji di sini adalah kontrak strukturnya — bukan warna atau jarak,
 * yang hanya bisa dibuktikan lewat mata — supaya perubahan tata letak
 * berikutnya tidak diam-diam mengembalikan tumpang tindih atau
 * menambahkan baris keenam yang pasti terpotong di layar 320px.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di
 * sini tidak menyentuh database sungguhan.
 */
class NavigasiBawahTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi pemrograman',
            'aktif' => true,
        ]);
    }

    private function buatQuiz(User $pembuat): Quiz
    {
        return Quiz::create([
            'pelajaran_id' => $this->buatPelajaran()->getKey(),
            'dibuat_oleh' => $pembuat->getKey(),
            'judul' => 'Quiz Pecahan',
            'slug' => 'quiz-pecahan',
            'deskripsi' => 'Deskripsi quiz.',
            'durasi' => 10,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }

    /**
     * Ambil navigasi bawah dari halaman.
     *
     * Dipotong dari markup, bukan dicari teksnya di seluruh halaman:
     * "Jadwal" misalnya juga muncul di panel "Jadwal Hari Ini" milik
     * dashboard, jadi pencarian biasa tidak akan bisa membuktikan bahwa
     * menu itu masuk atau keluar dari navigasi bawah.
     */
    private function navBawah(string $html): string
    {
        preg_match('/<nav aria-label="Navigasi utama".*?<\/nav>/s', $html, $cocok);

        return $cocok[0] ?? '';
    }

    /**
     * Ambil header mobile dari halaman (aturannya sama dengan navBawah).
     */
    private function headerMobile(string $html): string
    {
        preg_match('/<header class="latar-atas sticky top-0 z-30 text-dark lg:hidden">.*?<\/header>/s', $html, $cocok);

        return $cocok[0] ?? '';
    }

    public function test_navigasi_bawah_tampil_di_dashboard_dengan_lima_tujuan(): void
    {
        $html = $this->actingAs($this->buatPengguna())
            ->get(route('user.dashboard'))
            ->assertOk()
            ->getContent();

        $nav = $this->navBawah($html);

        $this->assertNotSame('', $nav, 'Navigasi bawah tidak ada di dashboard.');

        $this->assertSame(5, substr_count($nav, '<a href'), 'Navigasi bawah harus berisi tepat lima tautan.');

        foreach (['Beranda', 'Materi', 'Quiz', 'Karya Saya', 'Profil'] as $label) {
            $this->assertStringContainsString($label, $nav, "Label \"$label\" tidak ada di navigasi bawah.");
        }

        // Menu yang tidak muat jadi tujuan keenam sengaja dipindah ke
        // tombol "Menu lainnya", bukan ditambahkan sebagai baris keenam.
        foreach (['user.jadwal', 'user.hasil', 'user.simpanan'] as $tujuan) {
            $this->assertStringNotContainsString(
                route($tujuan),
                $nav,
                "Halaman $tujuan tidak boleh ada di navigasi bawah; ia di menu lainnya.",
            );
        }
    }

    public function test_menu_aktif_di_navigasi_bawah_memakai_arahan_halaman(): void
    {
        $html = $this->actingAs($this->buatPengguna())
            ->get(route('user.materi'))
            ->assertOk()
            ->getContent();

        $nav = $this->navBawah($html);

        $this->assertSame(1, substr_count($nav, 'aria-current="page"'), 'Harus ada tepat satu menu yang ditandai aktif.');

        // Penandanya harus menempel pada item Materi, bukan pada item
        // pertama yang kebetulan juga cocok dengan pola pencarian.
        preg_match('/<a[^>]*'.preg_quote(route('user.materi'), '/').'.*?<\/a>/s', $nav, $item);

        $this->assertNotSame([], $item, 'Item Materi tidak ada di navigasi bawah.');
        $this->assertStringContainsString('aria-current="page"', $item[0]);
    }

    public function test_menu_lainnya_menyimpan_jadwal_hasil_dan_simpan(): void
    {
        $html = $this->actingAs($this->buatPengguna())
            ->get(route('user.dashboard'))
            ->assertOk()
            ->getContent();

        $header = $this->headerMobile($html);

        $this->assertNotSame('', $header, 'Header mobile tidak ada di dashboard.');

        foreach (['user.jadwal', 'user.hasil', 'user.simpanan'] as $tujuan) {
            $this->assertStringContainsString(
                route($tujuan),
                $header,
                "Halaman $tujuan harus tetap terjangkau lewat tombol Menu lainnya.",
            );
        }
    }

    /**
     * Menu Hasil memakai wildcard pada polanya, jadi tautannya di "Menu
     * lainnya" tetap ditandai aktif bukan cuma di /user/hasil, tapi juga
     * di halamannya yang diturunkan (/user/hasil/quiz/{quiz}).
     *
     * Penting karena Hasil tidak ada di navigasi bawah: tanpa penanda itu
     * peserta yang sedang membuka daftar per quiz tidak punya cara tahu
     * sedang berada di halaman mana.
     */
    public function test_menu_hasil_di_menu_lainnya_terus_aktif_di_halaman_turunan(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna);

        foreach ([route('user.hasil'), route('user.hasil.daftar', $quiz)] as $tujuan) {
            $html = $this->actingAs($pengguna)
                ->get($tujuan)
                ->assertOk()
                ->getContent();

            // Dipotong dari header mobile supaya class aktif pada sidebar
            // desktop tidak ikut terhitung sebagai bukti.
            preg_match(
                '/<a[^>]*'.preg_quote(route('user.hasil'), '/').'.*?<\/a>/s',
                $this->headerMobile($html),
                $item,
            );

            $this->assertNotSame([], $item, "Tautan Hasil tidak ada di Menu lainnya pada $tujuan.");
            $this->assertStringContainsString(
                'bg-lavender text-primary-dark',
                $item[0],
                "Tautan Hasil tidak ditandai aktif pada $tujuan.",
            );
        }
    }

    public function test_kolom_cari_global_pindah_ke_baris_kedua_header_mobile(): void
    {
        $html = $this->actingAs($this->buatPengguna())
            ->get(route('user.materi'))
            ->assertOk()
            ->getContent();

        // Kolom cari lama (top bar) hanya untuk desktop sekarang.
        $this->assertStringContainsString('id="cari-topbar"', $html);

        $header = $this->headerMobile($html);

        $this->assertStringContainsString('id="cari-mobile"', $header, 'Header mobile kehilangan kolom pencarian.');
        $this->assertStringContainsString('aria-label="Notifikasi"', $header, 'Header mobile kehilangan lonceng.');
    }

    public function test_navigasi_bawah_tidak_tampil_di_halaman_fokus(): void
    {
        $pengguna = $this->buatPengguna();

        $halamanFokus = [
            route('user.materi.tambah'),
            route('user.sesi.gabung'),
        ];

        foreach ($halamanFokus as $tujuan) {
            $html = $this->actingAs($pengguna)->get($tujuan)->assertOk()->getContent();

            $this->assertSame(
                '',
                $this->navBawah($html),
                "Navigasi bawah tidak boleh tampil di $tujuan — ada elemen yang sudah menempel di kaki halaman.",
            );
        }
    }

    public function test_navigasi_bawah_tidak_tampil_di_halaman_mengerjakan_soal(): void
    {
        $pengguna = $this->buatPengguna();
        $quiz = $this->buatQuiz($pengguna);

        Soal::create([
            'quiz_id' => $quiz->getKey(),
            'pertanyaan' => 'Pertanyaan nomor 1',
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Pembahasan singkat.',
            'urutan' => 1,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);

        // Mulai dulu lewat tujuannya sendiri: tanpa baris pengerjaan,
        // URL soal langsung akan dialihkan.
        $this->actingAs($pengguna)->get(route('user.quiz.mulai', $quiz));

        $html = $this->actingAs($pengguna)
            ->get(route('user.judulsoal.soal', [$quiz->slug, 1]))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            '',
            $this->navBawah($html),
            'Navigasi bawah menutupi navigasi soal dan timer yang sedang berjalan.',
        );
    }

    public function test_top_bar_desktop_tidak_disentuh_oleh_header_mobile(): void
    {
        $respons = $this->actingAs($this->buatPengguna())->get(route('user.dashboard'))->assertOk();

        // Top bar tetap dirender (hidden lg:block lewat CSS), jadi id-nya
        // masih dipakai oleh halaman Simpan sebagai patokan.
        $respons->assertSee('data-app-topbar', false)
            ->assertSee('id="cari-topbar"', false);

        // Dan ia tidak lagi punya bentuk mobile: header mobile memakai
        // kelas sendiri, keduanya tidak pernah dirender bersamaan.
        $respons->assertSee('class="latar-atas sticky top-0 z-30 hidden lg:block"', false);
        $respons->assertSee('class="latar-atas sticky top-0 z-30 text-dark lg:hidden"', false);
    }
}
