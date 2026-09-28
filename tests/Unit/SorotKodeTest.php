<?php

namespace Tests\Unit;

use App\Support\SorotKode;
use PHPUnit\Framework\TestCase;

/**
 * Pewarna sintaks blok kode.
 *
 * Fungsi ini mengubah kode yang ditulis pengajar menjadi <span> berwarna.
 * Karena teksnya datang dari user, hasil sorotan harus selalu sudah
 * ter-escape: kode tidak boleh pernah berubah jadi markup yang dieksekusi.
 */
class SorotKodeTest extends TestCase
{
    public function test_kode_mentah_diescape(): void
    {
        $hasil = SorotKode::sorot('<h1>Halo</h1>', 'html');

        // Tag HTML dari kode pengajar tidak boleh keluar sebagai markup,
        // meski sudah dibungkus <span> pewarna.
        $this->assertStringNotContainsString('<h1>', $hasil);
        $this->assertStringNotContainsString('</h1>', $hasil);
        $this->assertStringContainsString('&lt;h1&gt;', $hasil);
    }

    public function test_tag_dibungkus_span_pewarna(): void
    {
        $hasil = SorotKode::sorot('<p class="x">Halo</p>', 'html');

        $this->assertStringContainsString('<span class="kode-tag">', $hasil);
        $this->assertStringContainsString('class="kode-atribut"', $hasil);
    }

    public function test_komentar_dan_string_diberi_warna(): void
    {
        $css = SorotKode::sorot('/* cat */`na { content: "halo"; }', 'css');

        $this->assertStringContainsString('kode-komentar', $css);
        $this->assertStringContainsString('kode-string', $css);
    }

    public function test_bahasa_tidak_dikenal_tetap_aman(): void
    {
        $hasil = SorotKode::sorot('var a = <b>;', null);

        $this->assertStringNotContainsString('<b>', $hasil);
        $this->assertStringContainsString('&lt;b&gt;', $hasil);
    }

    public function test_teks_kosong_tidak_menghasilkan_tag(): void
    {
        $this->assertSame('', SorotKode::sorot('', 'html'));
    }
}
