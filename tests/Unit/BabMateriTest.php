<?php

namespace Tests\Unit;

use App\Support\BabMateri;
use App\Support\IsiMateri;
use PHPUnit\Framework\TestCase;

/**
 * Pemecah isi materi kembali menjadi daftar bab.
 *
 * Form edit dan halaman detail sama-sama membaca kolom tb_materi.isi.
 * Kalau parser ini dan IsiMateri tidak sepakat soal di mana satu bab
 * berakhir, daftar bab di form akan berbeda dengan Daftar Isi yang dilihat
 * pembaca, dan struktur babnya rusak begitu materi disimpan ulang.
 */
class BabMateriTest extends TestCase
{
    public function test_isi_tanpa_penanda_jadi_satu_bab(): void
    {
        $bab = BabMateri::dariIsi("Paragraf pertama.\n\nParagraf kedua.");

        $this->assertCount(1, $bab);
        $this->assertSame('bab-1', $bab[0]['id']);
        $this->assertSame('Pendahuluan', $bab[0]['title']);
    }

    public function test_isi_kosong_tetap_menghasilkan_satu_bab(): void
    {
        $this->assertSame(
            [['id' => 'bab-1', 'title' => 'Pendahuluan', 'content' => '']],
            BabMateri::dariIsi(null),
        );
    }

    public function test_penanda_hash_menjadi_daftar_bab(): void
    {
        $bab = BabMateri::dariIsi(<<<'ISI'
        # Pengenalan HTML

        Isi pengenalan.

        # Struktur Dasar HTML

        Isi struktur.
        ISI);

        $this->assertSame(['Pengenalan HTML', 'Struktur Dasar HTML'], array_column($bab, 'title'));
        $this->assertSame(['bab-1', 'bab-2'], array_column($bab, 'id'));
        $this->assertStringContainsString('Isi pengenalan.', $bab[0]['content']);
    }

    public function test_subjudul_tidak_memecah_bab(): void
    {
        $bab = BabMateri::dariIsi(<<<'ISI'
        # Pengenalan

        Pengantar.

        ## Contoh kode

        Isi contoh.
        ISI);

        $this->assertCount(1, $bab);
        $this->assertSame('Pengenalan', $bab[0]['title']);
        $this->assertStringContainsString('## Contoh kode', $bab[0]['content']);
    }

    public function test_penanda_di_dalam_blok_kode_tidak_memecah_bab(): void
    {
        $bab = BabMateri::dariIsi(<<<'ISI'
        # Deployment

        ```bash
        # salin berkas ke server
        rsync -av . server:/app
        ```
        ISI);

        $this->assertCount(1, $bab);
        $this->assertSame('Deployment', $bab[0]['title']);
        $this->assertStringContainsString('# salin berkas ke server', $bab[0]['content']);
    }

    public function test_format_tulis_form_tetap_terbaca(): void
    {
        $bab = BabMateri::dariIsi("Bab 1: Pendahuluan\n\nIsi satu.\n\nBab 2: Materi\n\nIsi dua.");

        $this->assertSame(['Pendahuluan', 'Materi'], array_column($bab, 'title'));
    }

    public function test_teks_sebelum_penanda_pertama_tetap_jadi_bab_sendiri(): void
    {
        $bab = BabMateri::dariIsi("Kalimat pembuka.\n\n# Bab Pertama\n\nIsi bab.");

        $this->assertSame(['Pendahuluan', 'Bab Pertama'], array_column($bab, 'title'));
    }

    public function test_daftar_bab_sama_dengan_daftar_isi_halaman_detail(): void
    {
        $isi = <<<'ISI'
        # Pengenalan

        Pengantar.

        ## Contoh kode

        Isi contoh.

        # Latihan Soal

        Kerjakan lima soal.
        ISI;

        $this->assertSame(
            array_column(BabMateri::dariIsi($isi), 'title'),
            array_column(IsiMateri::seksi($isi, 'Pengenalan'), 'judul'),
        );
    }
}
