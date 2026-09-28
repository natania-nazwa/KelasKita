<?php

namespace Tests\Feature;

use App\Support\BerkasMateri;
use Tests\TestCase;

/**
 * URL publik thumbnail & audio materi.
 *
 * Kolom thumbnail menyimpan dua bentuk nilai: path unggahan di disk publik
 * (mis. "thumbnails/abc.jpg") dan URL penuh untuk data contoh. Keduanya
 * harus menghasilkan alamat yang benar, dan path unggahan tidak boleh
 * kehilangan nama foldernya.
 */
class BerkasMateriTest extends TestCase
{
    public function test_nilai_kosong_menghasilkan_null(): void
    {
        $this->assertNull(BerkasMateri::url(null));
        $this->assertNull(BerkasMateri::url(''));
    }

    public function test_url_penuh_dipakai_apa_adanya(): void
    {
        $url = 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1120';

        $this->assertSame($url, BerkasMateri::url($url));
    }

    public function test_path_unggahan_mempertahankan_nama_folder(): void
    {
        $this->assertSame(asset('storage/thumbnails/abc.jpg'), BerkasMateri::url('thumbnails/abc.jpg'));
        $this->assertSame(asset('storage/audio/abc.mp3'), BerkasMateri::url('audio/abc.mp3'));
    }
}
