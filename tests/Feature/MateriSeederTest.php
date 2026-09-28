<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Support\BabMateri;
use App\Support\IsiMateri;
use Database\Seeders\MateriSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data contoh halaman Materi.
 *
 * Seeder ini yang membentuk tiga kartu di halaman detail: kartu informasi,
 * kartu Daftar Isi, dan kartu isi bab. Kartu Daftar Isi hanya muncul kalau
 * isinya terpecah jadi beberapa seksi, jadi jumlah bab tiap materi wajib
 * diperiksa di sini. Semua test memakai SQLite in-memory.
 */
class MateriSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, Materi>
     */
    private array $daftar = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MateriSeeder::class);

        $this->daftar = Materi::query()->get()->all();
    }

    public function test_setiap_materi_seeder_terpecah_jadi_tiga_bab_atau_lebih(): void
    {
        foreach ($this->daftar as $materi) {
            $this->assertGreaterThanOrEqual(
                3,
                count(IsiMateri::seksi($materi->isi, $materi->nama)),
                "Materi '{$materi->nama}' kurang dari tiga seksi, jadi kartu Daftar Isi tidak muncul.",
            );
        }
    }

    public function test_daftar_bab_di_form_sama_dengan_daftar_isi_halaman_detail(): void
    {
        foreach ($this->daftar as $materi) {
            $this->assertSame(
                array_column(BabMateri::dariIsi($materi->isi), 'title'),
                array_column(IsiMateri::seksi($materi->isi, $materi->nama), 'judul'),
                "Materi '{$materi->nama}': daftar bab di form berbeda dengan Daftar Isi.",
            );
        }
    }

    public function test_hanya_bagian_latihan_terakhir_yang_ditandai_sebagai_latihan(): void
    {
        foreach ($this->daftar as $materi) {
            $seksi = IsiMateri::seksi($materi->isi, $materi->nama);
            $latihan = array_keys(array_filter($seksi, fn (array $s): bool => $s['latihan']));

            $this->assertSame(
                [array_key_last($seksi)],
                $latihan,
                "Materi '{$materi->nama}': bagian latihan tidak hanya yang terakhir.",
            );
        }
    }

    public function test_setiap_materi_seeder_punya_gambar_dan_jumlah_bab_yang_konsisten(): void
    {
        foreach ($this->daftar as $materi) {
            $this->assertNotNull($materi->thumbnail, "Materi '{$materi->nama}' tidak punya gambar.");
            $this->assertSame(
                count(IsiMateri::seksi($materi->isi, $materi->nama)),
                $materi->jumlahBab(),
                "Materi '{$materi->nama}': jumlah bab di kartu tidak sama dengan Daftar Isi.",
            );
        }
    }

    public function test_halaman_detail_materi_seeder_menampilkan_daftar_isi_dan_banyak_bab(): void
    {
        $materi = Materi::query()->where('nama', 'HTML Dasar')->firstOrFail();

        $halaman = $this->actingAs($materi->pembuat)
            ->get(route('user.materi.detail', $materi->slug))
            ->assertOk()
            ->assertSee('Daftar Isi')
            ->assertSee('Pengenalan HTML')
            ->assertSee('https://images.unsplash.com/', false)
            ->baseResponse->getContent();

        $this->assertGreaterThanOrEqual(3, preg_match_all('/data-bab="/', $halaman));
    }
}
