<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $admin = User::create(['nama' => 'Admin', 'email' => 'a@e.com', 'kata_sandi' => 'rahasia123', 'peran' => User::PERAN_ADMIN])->refresh();
        $u = User::create(['nama' => 'Budi', 'email' => 'b@e.com', 'kata_sandi' => 'rahasia123'])->refresh();
        $p = Pelajaran::firstOrCreate(['slug' => 'matematika'], ['nama' => 'Matematika', 'deskripsi' => 'd', 'aktif' => true]);
        $m = Materi::create(['pelajaran_id' => $p->id, 'dibuat_oleh' => $u->id, 'nama' => 'Materi Salah', 'slug' => 'materi-salah-1', 'deskripsi' => 'd', 'isi' => 'isi cukup panjang', 'status' => Materi::STATUS_PENDING]);

        $c = $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('admin.verifikasi'))
            ->post(route('admin.materi.tolak', $m->slug), ['kembali' => 'admin.verifikasi', 'alasan' => str_repeat('x', 501)])
            ->assertStatus(200)
            ->getContent();

        $this->assertSame(1, substr_count($c, 'Belum bisa diputuskan'), 'belum');
        $this->assertSame(1, substr_count($c, 'maksimal 500'), 'maksimal');
        $this->assertSame(1, substr_count($c, str_repeat('x', 501)), 'isiawal');
    }
}
