<?php

namespace Tests\Unit;

use App\Support\IsiMateri;
use PHPUnit\Framework\TestCase;

/**
 * Pemecah isi materi jadi seksi + blok.
 *
 * Isi materi ditulis pengajar sebagai teks polos, jadi parser inilah yang
 * menentukan halaman detail punya berapa seksi, blok kode apa saja, dan
 * mana yang jadi kartu latihan.
 */
class IsiMateriTest extends TestCase
{
    public function test_isi_tanpa_penanda_jadi_satu_seksi(): void
    {
        $seksi = IsiMateri::seksi("Paragraf pertama.\n\nParagraf kedua.", 'Materi Singkat');

        $this->assertCount(1, $seksi);
        $this->assertSame('Materi Singkat', $seksi[0]['judul']);
        $this->assertSame('materi-singkat', $seksi[0]['slug']);
        $this->assertCount(2, $seksi[0]['blok']);
        $this->assertSame('paragraf', $seksi[0]['blok'][0]['tipe']);
    }

    public function test_isi_kosong_tetap_menghasilkan_satu_seksi(): void
    {
        $seksi = IsiMateri::seksi(null, 'Materi Kosong');

        $this->assertCount(1, $seksi);
        $this->assertSame([], $seksi[0]['blok']);
    }

    public function test_penanda_seksi_dipakai_untuk_daftar_isi(): void
    {
        $seksi = IsiMateri::seksi(<<<'ISI'
        # Pengenalan HTML

        Isi pengenalan.

        # Struktur Dasar HTML

        Isi struktur.
        ISI);

        $this->assertCount(2, $seksi);
        $this->assertSame(['pengenalan-html', 'struktur-dasar-html'], array_column($seksi, 'slug'));
        $this->assertSame([1, 2], array_column($seksi, 'nomor'));
        $this->assertSame('Pengenalan HTML', $seksi[0]['judul']);
    }

    public function test_slug_judul_yang_sama_dibedakan_urutannya(): void
    {
        $seksi = IsiMateri::seksi("# Link\n\nSatu.\n\n# Link\n\nDua.");

        $this->assertSame(['link', 'link-2'], array_column($seksi, 'slug'));
    }

    public function test_blok_kode_menyimpan_bahasa_dan_kode_mentah(): void
    {
        $seksi = IsiMateri::seksi(<<<'ISI'
        # Tag

        ```html
        <h1>Halo</h1>
        ```
        ISI);

        $kode = $seksi[0]['blok'][0];

        $this->assertSame('kode', $kode['tipe']);
        $this->assertSame('html', $kode['bahasa']);
        $this->assertSame('<h1>Halo</h1>', $kode['kode']);
        $this->assertStringContainsString('kode-tag', $kode['sorot']);
    }

    public function test_blok_kode_tanpa_bahasa_dipakai_teks(): void
    {
        $seksi = IsiMateri::seksi("# Tag\n\n```\nvar a = 1;\n```");

        $this->assertSame('teks', $seksi[0]['blok'][0]['bahasa']);
    }

    public function test_daftar_butir_berurutan_menggabung_satu_blok(): void
    {
        $seksi = IsiMateri::seksi("# Tag\n\n- `h1` untuk judul\n- `p` untuk paragraf\n- `a` untuk tautan");

        $daftar = $seksi[0]['blok'][0];

        $this->assertSame('daftar', $daftar['tipe']);
        $this->assertCount(3, $daftar['butir']);
        $this->assertStringContainsString('<code>h1</code>', $daftar['butir'][0]);
    }

    public function test_subjudul_dan_catatan_dikenali(): void
    {
        $seksi = IsiMateri::seksi("# Tag\n\n## Contoh\n\n> Browser membaca tag.\n");

        $tipe = array_column($seksi[0]['blok'], 'tipe');

        $this->assertSame(['sub', 'catatan'], $tipe);
        $this->assertSame('Contoh', $seksi[0]['blok'][0]['judul']);
    }

    public function test_seksi_latihan_ditandai_agar_dirender_sebagai_kartu(): void
    {
        $seksi = IsiMateri::seksi("# Pengenalan\n\nIsi.\n\n# Latihan Soal\n\nSudah paham?");

        $this->assertFalse($seksi[0]['latihan']);
        $this->assertTrue($seksi[1]['latihan']);
    }

    public function test_teks_bermarkup_diescape_dulu_sebelum_diformat(): void
    {
        $seksi = IsiMateri::seksi('# XSS'."\n\n".'<script>alert("x")</script> **tebal** `kode`');

        $html = $seksi[0]['blok'][0]['html'];

        // Tag dari pengguna harus jadi teks, bukan elemen.
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('<strong>tebal</strong>', $html);
        $this->assertStringContainsString('<code>kode</code>', $html);
    }

    public function test_seksi_tanpa_isi_di_akhir_dibuang(): void
    {
        $seksi = IsiMateri::seksi("# Pengenalan\n\nIsi pengenalan.\n\n# Judul Kosong");

        $this->assertCount(1, $seksi);
        $this->assertSame('Pengenalan', $seksi[0]['judul']);
    }
}
