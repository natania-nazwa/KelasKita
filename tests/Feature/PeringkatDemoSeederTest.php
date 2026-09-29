<?php

namespace Tests\Feature;

use App\Models\PengerjaanQuiz;
use App\Models\PesertaQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\User;
use Database\Seeders\PeringkatDemoHapusSeeder;
use Database\Seeders\PeringkatDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data contoh PeringkatDemoSeeder.
 *
 * Seeder ini ada supaya halaman peringkat bisa dilihat tanpa harus mendaftar
 * banyak akun dan menjawab soal satu per satu. Karena itu ia ikut diuji di
 * sini: kalau nanti soalnya, jawabannya, atau urutan pesertanya diubah,
 * halaman yang dibuka setelah seeder dijalankan harus tetap menampilkan apa
 * yang dijanjikan, yaitu tiga besar di podium dan peserta berikutnya berurutan
 * di bawahnya.
 *
 * Test berjalan di SQLite in-memory (lihat phpunit.xml), jadi seeder ini tidak
 * pernah menyentuh database sungguhan. Menjalankannya di .env yang asli tetap
 * dilakukan manual lewat artisan.
 */
class PeringkatDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_sesi_mode_kode_yang_bisa_dibuka_pesertanya(): void
    {
        $this->seed(PeringkatDemoSeeder::class);

        $sesi = SesiQuiz::query()->firstOrFail();

        $this->assertTrue($sesi->sudahSelesai());
        $this->assertSame(7, $sesi->peserta()->count());

        // Setiap peserta harus punya baris di daftar, tidak boleh ada yang
        // hilang: yang belum menjawab pun tetap punya baris.
        $pengguna = User::query()
            ->where('email', 'aulia.demo@kelaskita.test')
            ->firstOrFail();

        $this->actingAs($pengguna)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Peringkat Peserta')
            // Podium: tiga besar.
            ->assertSeeInOrder(['Aulia Rahma', 'Bagas Nugroho', 'Citra Lestari'])
            // Daftar di bawahnya: peringkat 4 sampai 7.
            ->assertSeeInOrder(['Dimas Prayoga', 'Fitri Handayani', 'Gilang Saputra', 'Hana Kusuma'])
            // Peserta yang belum menjawab tetap ada, ditandai, bukan disembunyikan.
            ->assertSee('Belum menjawab');
    }

    /**
     * Nilainya harus berasal dari penilaian yang sama dengan jawaban sungguhan,
     * bukan angka yang ditulis seeder. 10, 9, 8, 7, 6, dan 5 benar dari 10
     * soal berarti 100 sampai 50, jadi urutannya benar-benar turun.
     */
    public function test_nilai_peserta_dihitung_dari_jawabannya(): void
    {
        $this->seed(PeringkatDemoSeeder::class);

        $sesi = SesiQuiz::query()->firstOrFail();

        $nilai = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->pluck('nilai', 'pengguna_id')
            ->map(fn ($nilai) => (int) $nilai)
            ->all();

        $this->assertSame([100, 90, 80, 70, 60, 50], array_values($nilai));

        // Peserta ketujuh sengaja belum menjawab, jadi tidak punya pengerjaan
        // sama sekali — itulah yang membuatnya tampil dengan nilai 0.
        $tanpaPengerjaan = User::query()
            ->where('email', 'hana.demo@kelaskita.test')
            ->firstOrFail();

        $this->assertArrayNotHasKey($tanpaPengerjaan->getKey(), $nilai);
    }

    /**
     * Seeder harus aman dijalankan berulang: kode akses quiz punya batasan
     * unique di database, jadi run kedua akan ditolak kalau tidak dicocokkan.
     */
    public function test_seeder_aman_dijalankan_berulang(): void
    {
        $this->seed(PeringkatDemoSeeder::class);
        $this->seed(PeringkatDemoSeeder::class);

        $sesi = SesiQuiz::query()->firstOrFail();

        $this->assertSame(1, SesiQuiz::query()->count());
        $this->assertSame(7, $sesi->peserta()->count());
    }

    /**
     * Semua halaman yang bisa dibuka dari kartu hasil peserta harus terbuka,
     * bukan cuma halaman peringkat. Kalau salah satunya meledak, orang yang
     * sedang mencoba-coba tampilan demo akan berhenti di error dan mengira
     * fiturnya yang rusak.
     */
    public function test_semua_halaman_yang_bisa_diklik_dari_kartu_hasil_terbuka(): void
    {
        $this->seed(PeringkatDemoSeeder::class);

        $sesi = SesiQuiz::query()->firstOrFail();
        $pengguna = User::query()->where('email', 'aulia.demo@kelaskita.test')->firstOrFail();
        $pengerjaan = PengerjaanQuiz::query()
            ->where('pengguna_id', $pengguna->getKey())
            ->firstOrFail();

        // Kartu hasil: di sinilah tombol "Lihat Peringkat"-nya berada.
        $this->actingAs($pengguna)
            ->get(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]))
            ->assertOk()
            ->assertSee('Lihat Peringkat');

        $this->actingAs($pengguna)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk();

        $this->actingAs($pengguna)
            ->get(route('user.hasil.detail', $pengerjaan->getKey()))
            ->assertOk();

        // Tombol "Kembali ke Hasil" di kaki halaman peringkat.
        $this->actingAs($pengguna)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertRedirect(route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()]));

        // Halaman rekap milik host.
        $host = User::query()->where('email', 'guru.demo@kelaskita.test')->firstOrFail();

        $this->actingAs($host)
            ->get(route('user.sesi.hasil', $sesi))
            ->assertOk()
            ->assertSee('Rekap nilai peserta');

        $this->actingAs($host)
            ->get(route('user.sesi.peringkat', $sesi))
            ->assertOk()
            ->assertSee('Kamu adalah pembuat quiz ini');
    }

    public function test_seeder_penghapusan_membuang_quiz_dan_sesinya(): void
    {
        $this->seed(PeringkatDemoSeeder::class);

        $this->assertSame(1, Quiz::query()->where('kode_akses', 'UTS9A2')->count());

        $this->seed(PeringkatDemoHapusSeeder::class);

        $this->assertSame(0, Quiz::query()->where('kode_akses', 'UTS9A2')->count());
        $this->assertSame(0, SesiQuiz::query()->count());
        $this->assertSame(0, PesertaQuiz::query()->count());
        $this->assertSame(0, PengerjaanQuiz::query()->count());
    }
}
