<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unggahan thumbnail & audio pada form tambah materi.
 *
 * File disimpan ke disk publik dan path-nya dicatat di kolom
 * thumbnail/audio tabel tb_materi. Semua test memakai SQLite
 * in-memory dan disk publik palsu, jadi tidak menyentuh data
 * Supabase sungguhan.
 */
class MateriUnggahFileTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(): User
    {
        return User::create([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::create([
            'nama' => 'Pemrograman',
            'slug' => 'pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'aktif' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Materi Berlampirkan',
            'isi' => 'Isi materi yang definitely lebih dari dua puluh karakter.',
            'tingkat_kesulitan' => 'Mudah',
        ], $tambahan);
    }

    private function audioWav(): string
    {
        // Kepala RIFF/WAVE minimum; cukup sebagai konten berkas audio.
        return 'RIFF'.pack('V', 36).'WAVEfmt '.pack('V', 16)
            .pack('v', 1).pack('v', 1).pack('V', 8000).pack('V', 8000)
            .pack('v', 1).pack('v', 8).'data'.pack('V', 0);
    }

    public function test_thumbnail_dan_audio_tersimpan_ke_disk_publik(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/materi/tambah', $this->dataForm($pelajaran, [
                'thumbnail' => UploadedFile::fake()->image('thumbnail.png', 320, 180),
                'audio' => UploadedFile::fake()->createWithContent('narasi.wav', $this->audioWav()),
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']))
            ->assertSessionHasNoErrors();

        $materi = Materi::query()->where('nama', 'Materi Berlampirkan')->firstOrFail();

        $this->assertStringStartsWith('thumbnails/', $materi->thumbnail);
        $this->assertStringStartsWith('audio/', $materi->audio);

        Storage::disk('public')->assertExists($materi->thumbnail);
        Storage::disk('public')->assertExists($materi->audio);
    }

    public function test_thumbnail_dengan_format_tidak_valid_ditolak(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/materi/tambah', $this->dataForm($pelajaran, [
                'thumbnail' => UploadedFile::fake()->create('berkas.exe', 10),
            ]))
            ->assertSessionHasErrors([
                'thumbnail' => 'Format thumbnail harus JPG, PNG, atau WEBP.',
            ]);

        $this->assertDatabaseCount('tb_materi', 0);
    }

    public function test_audio_dengan_format_tidak_valid_ditolak(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/materi/tambah', $this->dataForm($pelajaran, [
                'audio' => UploadedFile::fake()->create('narasi.txt', 10),
            ]))
            ->assertSessionHasErrors([
                'audio' => 'Format audio harus MP3, WAV, M4A, atau OGG.',
            ]);

        $this->assertDatabaseCount('tb_materi', 0);
    }

    public function test_berkas_lebih_dari_100mb_ditolak(): void
    {
        Storage::fake('public');

        $user = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($user)
            ->post('/user/materi/tambah', $this->dataForm($pelajaran, [
                'thumbnail' => UploadedFile::fake()->create('raksasa.png', 102401),
            ]))
            ->assertSessionHasErrors([
                'thumbnail' => 'Ukuran thumbnail maksimal 100MB.',
            ]);

        $this->assertDatabaseCount('tb_materi', 0);
    }
}
