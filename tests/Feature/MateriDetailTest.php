<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman detail materi: kepala materi, Daftar Isi, isi seksi, blok kode,
 * dan kartu latihan.
 *
 * Semua test memakai SQLite in-memory (lihat phpunit.xml), jadi data di sini
 * tidak menyentuh database Supabase sungguhan.
 */
class MateriDetailTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Natania',
            'email' => 'natania@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatMateri(string $isi, string $nama = 'HTML Dasar'): Materi
    {
        $pelajaran = Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'aktif' => true,
        ]);

        return Materi::create([
            'pelajaran_id' => $pelajaran->id,
            'dibuat_oleh' => $this->buatPengguna()->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value(),
            'deskripsi' => 'Materi dasar HTML untuk membuat struktur halaman web.',
            'isi' => $isi,
            'tingkat_kesulitan' => 'Mudah',
            'aktif' => true,
        ]);
    }

    public function test_kepala_materi_menampilkan_kategori_judul_dan_informasi(): void
    {
        $materi = $this->buatMateri('Isi materi.');

        $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertSee('HTML Dasar')
            ->assertSee('Pemrograman')
            ->assertSee('Materi dasar HTML untuk membuat struktur halaman web.')
            ->assertSee('Natania')
            ->assertSee('menit baca')
            ->assertSee('Kembali');
    }

    public function test_daftar_isi_dan_seksi_tampil_bila_isi_memiliki_penanda(): void
    {
        $materi = $this->buatMateri(<<<'ISI'
        # Pengenalan HTML

        Isi pengenalan.

        # Membuat Link

        Isi tautan.
        ISI);

        $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertSee('Daftar Isi')
            ->assertSee('1. Pengenalan HTML')
            ->assertSee('2. Membuat Link')
            ->assertSee('href="#pengenalan-html"', false)
            ->assertSee('id="membuat-link"', false);
    }

    public function test_daftar_isi_disembunyikan_bila_materi_cuma_satu_seksi(): void
    {
        $materi = $this->buatMateri("Paragraf pertama.\n\nParagraf kedua.");

        $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertDontSee('Daftar Isi')
            ->assertSee('Paragraf pertama.');
    }

    public function test_blok_kode_dirender_beserta_tombol_salin(): void
    {
        $materi = $this->buatMateri(<<<'ISI'
        # Pengenalan HTML

        ## Contoh kode HTML:

        ```html
        <h1>Halo, Dunia!</h1>
        ```

        # Latihan Soal

        Sudah memahami materi ini?
        ISI);

        $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertSee('Contoh kode HTML:')
            ->assertSee('data-salin-kode', false)
            ->assertSee('Copy')
            // Kode sudah di-escape, jadi tag HTML-nya tampil sebagai teks
            // di dalam <span> pewarna, bukan sebagai elemen.
            ->assertSee('&lt;h1&gt;', false)
            ->assertSee('kode-tag', false);
    }

    public function test_seksi_latihan_dirender_sebagai_kartu(): void
    {
        $materi = $this->buatMateri(<<<'ISI'
        # Pengenalan HTML

        Isi pengenalan.

        # Latihan Soal

        Sudah memahami materi HTML Dasar?
        ISI);

        $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertSee('Sudah memahami materi HTML Dasar?')
            ->assertSee('Mulai Latihan')
            ->assertSee(route('user.quiz'), false);
    }

    public function test_isi_materi_bermarkup_tidak_dijalankan_sebagai_html(): void
    {
        $materi = $this->buatMateri('# XSS'."\n\n".'<script>alert("x")</script>');

        $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_halaman_detail_membutuhkan_login(): void
    {
        $materi = $this->buatMateri('Isi materi.');

        $this->get(route('user.materi.detail', $materi->slug))->assertRedirect('/login');
    }
}
