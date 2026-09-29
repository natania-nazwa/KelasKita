<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tambah, ubah, dan hapus jadwal sendiri dari halaman /user/jadwal.
 *
 * Tiga hal yang diuji di sini dan tidak bisa relied on di halaman daftar:
 *
 *  1. Kepemilikan. Jadwal milik orang lain tidak boleh bisa dibuka, diubah,
 *     atau dihapus, dan halaman daftar tidak boleh menampilkannya.
 *  2. Validasi. Jam yang bertumpuk di hari yang sama harus ditolak, dan jam
 *     yang bersentuhan (08:00-09:30 lalu 09:30-10:30) tetap boleh.
 *  3. Pergantian sumber data. Selama tabel kosong, halaman memakai jadwal
 *     contoh. Begitu ada satu baris milik pengguna, contoh itu hilang dan
 *     yang tampil hanya miliknya.
 */
class JadwalKelolaTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $nama = 'Natania'): User
    {
        return User::create([
            'nama' => $nama,
            'email' => str($nama)->slug()->value().'@example.com',
            'kata_sandi' => 'rahasia123',
        ])->refresh();
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function buatJadwal(User $pemilik, array $ubah = []): Jadwal
    {
        return Jadwal::create([
            'dibuat_oleh' => $pemilik->getKey(),
            'hari' => 1,
            'mulai' => '08:00:00',
            'selesai' => '09:30:00',
            'pelajaran' => 'matematika',
            'judul' => 'Matematika',
            'kelas' => 'Kelas 11 RPL 2',
            'ruang' => 'Ruang Kelas 3B',
            ...$ubah,
        ])->refresh();
    }

    /**
     * Isian form yang valid.
     *
     * "guru" sengaja tidak ada di sini: aplikasinya sudah tidak mengelola nama
     * guru, dan kalau ada yang mengirim field itu, isiannya harus diabaikan
     * (dicek di test_jadwal_guru_akan_diabaikan).
     *
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:30',
            'pelajaran' => 'matematika',
            'judul' => 'Matematika',
            'kelas' => 'Kelas 11 RPL 2',
            'ruang' => 'Ruang Kelas 3B',
            ...$ubah,
        ];
    }

    /**
     * Tanggal terdekat yang jatuh pada hari = 1 (Senin), dipakai untuk
     * mengecek tujuan redirect setelah menambah, mengubah, dan menghapus.
     *
     * Dihitung sendiri, bukan lewat DaftarJadwal, supaya test benar-benar
     * memeriksa hasil redirect dan bukan hanya mengulang implementasi.
     */
    private function tanggalSenin(): string
    {
        $tanggal = now()->startOfDay();

        for ($i = 0; $i < 7; $i++) {
            if ((int) $tanggal->dayOfWeek === Carbon::MONDAY) {
                return $tanggal->toDateString();
            }

            $tanggal = $tanggal->copy()->addDay();
        }

        return now()->startOfDay()->toDateString();
    }

    public function test_tamu_tidak_bisa_membuka_form_tambah(): void
    {
        $this->get('/user/jadwal/tambah')->assertRedirect(route('login'));
    }

    public function test_form_tambah_terbuka_dengan_hari_yang_sedang_dibuka(): void
    {
        $respons = $this->actingAs($this->buatPengguna())
            ->get('/user/jadwal/tambah?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('Tambah Jadwal')
            ->assertSee('Simpan Jadwal');

        // Hari dari halaman jadwal harus terpilih di dropdown, jadi tombol
        // dari panel daftar langsung membuka form untuk hari itu.
        $respons->assertSee('<option value="1" selected', false);
    }

    public function test_jadwal_baru_tersimpan_dan_muncul_di_halaman(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post('/user/jadwal/tambah', $this->isian(['judul' => 'Praktikum Proyek', 'pelajaran' => 'pjkr']))
            ->assertRedirect(route('user.jadwal', ['tanggal' => $this->tanggalSenin()]));

        $this->assertDatabaseHas('tb_jadwal', [
            'dibuat_oleh' => $user->getKey(),
            'hari' => 1,
            'pelajaran' => 'pjkr',
            'judul' => 'Praktikum Proyek',
        ]);

        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('Praktikum Proyek');
    }

    public function test_jadwal_baru_langsung_menggantikan_jadwal_contoh(): void
    {
        $user = $this->buatPengguna();

        // Sebelum ada baris, halaman masih memakai jadwal contoh.
        $this->actingAs($user)
            ->get('/user/jadwal')
            ->assertOk()
            ->assertSee('jadwal contoh', false);

        $this->buatJadwal($user, ['judul' => 'Jadwal Milikku']);

        // Setelah ada satu baris, contoh hilang dan yang tampil hanya
        // jadwal sendiri.
        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('Jadwal Milikku')
            ->assertDontSee('jadwal contoh', false);
    }

    public function test_isian_kosong_ditolak(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah')
            ->assertSessionHasErrors(['hari', 'mulai', 'selesai', 'pelajaran', 'judul']);

        $this->assertDatabaseCount('tb_jadwal', 0);
    }

    public function test_jam_yang_bertumpuk_ditolak(): void
    {
        $user = $this->buatPengguna();
        $this->buatJadwal($user, ['mulai' => '08:00:00', 'selesai' => '09:30:00']);

        $this->actingAs($user)
            ->post('/user/jadwal/tambah', $this->isian([
                'mulai' => '09:00',
                'selesai' => '10:30',
                'judul' => 'Matematika Lagi',
            ]))
            ->assertSessionHasErrors('mulai');

        $this->assertDatabaseCount('tb_jadwal', 1);
    }

    public function test_jam_yang_bersentuhan_boleh_berdampingan(): void
    {
        $user = $this->buatPengguna();
        $this->buatJadwal($user, ['mulai' => '08:00:00', 'selesai' => '09:30:00']);

        // Pelajaran berikutnya mulai tepat waktu pelajaran pertama selesai.
        $this->actingAs($user)
            ->post('/user/jadwal/tambah', $this->isian([
                'mulai' => '09:30',
                'selesai' => '11:00',
                'judul' => 'IPA',
                'pelajaran' => 'ipa',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tb_jadwal', 2);
    }

    public function test_jam_yang_bertumpuk_di_hari_lain_boleh(): void
    {
        $user = $this->buatPengguna();
        $this->buatJadwal($user, ['hari' => 1]);

        $this->actingAs($user)
            ->post('/user/jadwal/tambah', $this->isian(['hari' => 2, 'judul' => 'Matematika Selasa']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tb_jadwal', 2);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian(['mulai' => '10:00', 'selesai' => '09:00']))
            ->assertSessionHasErrors('selesai');
    }

    public function test_mata_pelajaran_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian(['pelajaran' => 'pelajaran-hantu']))
            ->assertSessionHasErrors('pelajaran');
    }

    public function test_jadwal_milik_sendiri_dapat_diubah(): void
    {
        $user = $this->buatPengguna();
        $jadwal = $this->buatJadwal($user);

        $this->actingAs($user)
            ->put('/user/jadwal/'.$jadwal->getKey(), $this->isian([
                'judul' => 'Matematika Peminatan',
                'ruang' => 'Lab Komputer 1',
            ]))
            ->assertRedirect(route('user.jadwal', ['tanggal' => $this->tanggalSenin()]));

        $this->assertSame('Matematika Peminatan', $jadwal->refresh()->judul);
        $this->assertSame('Lab Komputer 1', $jadwal->ruang);
    }

    public function test_jadwal_guru_akan_diabaikan(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post('/user/jadwal/tambah', $this->isian([
                'guru' => 'Pak Rahmat',
                'judul' => 'Tanpa Guru',
            ]))
            ->assertSessionHasNoErrors();

        // Field guru tidak lagi ada di form maupun di isian(), jadi yang
        // tersimpan adalah nilai default kolomnya, bukan nilai dari request.
        $this->assertDatabaseHas('tb_jadwal', ['judul' => 'Tanpa Guru']);
        $this->assertDatabaseMissing('tb_jadwal', ['guru' => 'Pak Rahmat']);

        // Namanya juga tidak boleh muncul di halaman.
        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertDontSee('Pak Rahmat');
    }

    public function test_mengubah_jadwal_tidak_ditolak_oleh_jam_miliknya_sendiri(): void
    {
        $user = $this->buatPengguna();
        $jadwal = $this->buatJadwal($user);

        // Simpan ulang dengan isian yang sama persis: jam miliknya sendiri
        // tidak boleh dianggap bentrok dengan dirinya sendiri.
        $this->actingAs($user)
            ->put('/user/jadwal/'.$jadwal->getKey(), $this->isian(['judul' => 'Matematika']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Matematika', $jadwal->refresh()->judul);
    }

    public function test_jadwal_milik_sendiri_dapat_dihapus(): void
    {
        $user = $this->buatPengguna();
        $jadwal = $this->buatJadwal($user);

        $this->actingAs($user)
            ->delete('/user/jadwal/'.$jadwal->getKey())
            ->assertRedirect(route('user.jadwal', ['tanggal' => $this->tanggalSenin()]));

        $this->assertDatabaseCount('tb_jadwal', 0);
    }

    public function test_jadwal_orang_lain_tidak_bisa_diubah_atau_dihapus(): void
    {
        $saya = $this->buatPengguna('Natania');
        $orangLain = $this->buatPengguna('Keyla');
        $jadwal = $this->buatJadwal($orangLain, ['judul' => 'Jadwal Keyla']);

        $this->actingAs($saya)
            ->get('/user/jadwal/'.$jadwal->getKey().'/edit')
            ->assertForbidden();

        $this->actingAs($saya)
            ->put('/user/jadwal/'.$jadwal->getKey(), $this->isian(['judul' => 'Dibajak']))
            ->assertForbidden();

        $this->actingAs($saya)
            ->delete('/user/jadwal/'.$jadwal->getKey())
            ->assertForbidden();

        $this->assertSame('Jadwal Keyla', $jadwal->refresh()->judul);
    }

    public function test_jadwal_orang_lain_tidak_muncul_di_halaman(): void
    {
        $saya = $this->buatPengguna('Natania');
        $orangLain = $this->buatPengguna('Keyla');

        $this->buatJadwal($saya, ['judul' => 'Jadwal Natania']);
        $this->buatJadwal($orangLain, ['judul' => 'Jadwal Keyla']);

        $this->actingAs($saya)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('Jadwal Natania')
            ->assertDontSee('Jadwal Keyla');
    }

    public function test_tombol_aksi_hanya_muncul_untuk_jadwal_yang_bisa_diubah(): void
    {
        $user = $this->buatPengguna();

        // Jadwal contoh tidak punya baris di database, jadi tidak boleh
        // punya tombol edit atau hapus.
        $contoh = $this->actingAs($user)->get('/user/jadwal')->assertOk();
        $contoh->assertDontSee(route('user.jadwal.edit', 1), false);

        $jadwal = $this->buatJadwal($user, ['judul' => 'Jadwal Sendiri']);

        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee(route('user.jadwal.edit', $jadwal->getKey()), false)
            ->assertSee(route('user.jadwal.destroy', $jadwal->getKey()), false);
    }

    public function test_dibuat_oleh_tidak_bisa_dipalsukan_dari_request(): void
    {
        $saya = $this->buatPengguna('Natania');
        $orangLain = $this->buatPengguna('Keyla');

        $this->actingAs($saya)
            ->post('/user/jadwal/tambah', $this->isian([
                'dibuat_oleh' => $orangLain->getKey(),
                'judul' => 'Milik Saya',
            ]));

        // Field "dibuat_oleh" di luar form, jadi harus diabaikan dan
        // jadwal tetap milik pengguna yang sedang login.
        $this->assertDatabaseHas('tb_jadwal', [
            'dibuat_oleh' => $saya->getKey(),
            'judul' => 'Milik Saya',
        ]);
    }

    public function test_pr_bersama_tenggatnya_tersimpan(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post('/user/jadwal/tambah', $this->isian([
                'judul' => 'Bahasa Indonesia',
                'pelajaran' => 'bahasa-indonesia',
                'pr' => 'PR halaman 45-50',
                'pr_dikumpulkan' => now()->addDays(3)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_jadwal', [
            'judul' => 'Bahasa Indonesia',
            'pr' => 'PR halaman 45-50',
        ]);

        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('PR halaman 45-50')
            ->assertSee('3 hari lagi');
    }

    public function test_jadwal_tanpa_pr_tetap_bisa_disimpan(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian(['judul' => 'PPKN']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_jadwal', ['judul' => 'PPKN', 'pr' => null]);
    }

    public function test_pr_tanpa_tenggat_ditolak(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian(['pr' => 'PR halaman 45-50']))
            ->assertSessionHasErrors('pr_dikumpulkan');

        $this->assertDatabaseCount('tb_jadwal', 0);
    }

    public function test_tenggat_tanpa_pr_ditolak(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian([
                'pr_dikumpulkan' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('pr');
    }

    public function test_pr_yang_sudah_lewat_ditandai_terlambat(): void
    {
        $user = $this->buatPengguna();
        $tanggal = $this->tanggalSenin();

        $this->buatJadwal($user, [
            'hari' => 1,
            'mulai' => '19:00:00',
            'selesai' => '20:00:00',
            'judul' => 'Matematika Malam',
            'pr' => 'PR halaman 10',
            'pr_dikumpulkan' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$tanggal)
            ->assertOk()
            ->assertSee('PR halaman 10')
            ->assertSee('Terlambat 2 hari');
    }

    public function test_pr_bisa_dicari(): void
    {
        $user = $this->buatPengguna();

        $this->buatJadwal($user, [
            'judul' => 'Bahasa Indonesia',
            'pr' => 'PR halaman 45-50',
        ]);

        $this->buatJadwal($user, [
            'mulai' => '10:00:00',
            'selesai' => '11:30:00',
            'judul' => 'IPA',
            'pelajaran' => 'ipa',
        ]);

        $respons = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin().'&q=halaman%2045')
            ->assertOk()
            ->assertSee('PR halaman 45-50');

        // Pelajaran yang PR-nya tidak cocok tidak boleh ikut muncul.
        $respons->assertDontSee('>IPA<', false);
    }

    public function test_pr_tidak_bocor_ke_pengguna_lain(): void
    {
        $saya = $this->buatPengguna('Natania');
        $orangLain = $this->buatPengguna('Keyla');

        $this->buatJadwal($orangLain, [
            'judul' => 'Jadwal Keyla',
            'pr' => 'PR Rahasia Keyla',
        ]);

        $this->actingAs($saya)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertDontSee('PR Rahasia Keyla', false);
    }

    public function test_mengubah_jadwal_bisa_menghapus_pr(): void
    {
        $user = $this->buatPengguna();
        $jadwal = $this->buatJadwal($user, [
            'pr' => 'PR halaman 45-50',
            'pr_dikumpulkan' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($user)
            ->put('/user/jadwal/'.$jadwal->getKey(), $this->isian(['judul' => 'Tanpa PR']))
            ->assertSessionHasNoErrors();

        $this->assertNull($jadwal->refresh()->pr);
        $this->assertNull($jadwal->pr_dikumpulkan);
    }

    public function test_field_opsional_yang_kosong_disimpan_sebagai_null(): void
    {
        $user = $this->buatPengguna();

        $this->actingAs($user)
            ->post('/user/jadwal/tambah', [
                'hari' => 1,
                'mulai' => '08:00',
                'selesai' => '09:30',
                'pelajaran' => 'ppkn',
                'judul' => 'PPKN',
                // Kelas, ruang, dan PR sengaja tidak dikirim sama sekali.
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_jadwal', [
            'judul' => 'PPKN',
            'kelas' => null,
            'ruang' => null,
            'pr' => null,
            'pr_dikumpulkan' => null,
        ]);
    }

    public function test_field_kosong_tidak_muncul_di_halaman(): void
    {
        $user = $this->buatPengguna();

        $this->buatJadwal($user, [
            'judul' => 'PPKN Saja',
            'pelajaran' => 'ppkn',
            'kelas' => null,
            'ruang' => null,
            'pr' => null,
        ]);

        $respons = $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('PPKN Saja');

        // Yang kosong tidak boleh muncul sebagai baris kosong: tidak ada
        // label "Kelas" maupun "Ruang" yang tidak followed nilai.
        $html = $respons->getContent();

        $this->assertStringNotContainsString('data-jadwal-meta="Kelas"', $html);
        $this->assertStringNotContainsString('data-jadwal-meta="Ruang"', $html);

        // Durasi tetap ada, karena dihitung dari jam dan bukan dari isian.
        $this->assertStringContainsString('data-jadwal-meta="Durasi"', $html);
    }

    public function test_field_yang_diisi_muncul_di_halaman(): void
    {
        $user = $this->buatPengguna();

        $this->buatJadwal($user, [
            'judul' => 'Bahasa Indonesia',
            'pelajaran' => 'bahasa-indonesia',
            'kelas' => 'Kelas 12 RPL 1',
            'ruang' => 'Ruang Baca',
        ]);

        $this->actingAs($user)
            ->get('/user/jadwal?tanggal='.$this->tanggalSenin())
            ->assertOk()
            ->assertSee('data-jadwal-meta="Kelas"', false)
            ->assertSee('Kelas 12 RPL 1')
            ->assertSee('data-jadwal-meta="Ruang"', false)
            ->assertSee('Ruang Baca');
    }

    public function test_isian_yang_cuma_spesi_dianggap_kosong(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian([
                'kelas' => '   ',
                'ruang' => '  ',
                'pr' => '  ',
                'pr_dikumpulkan' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tb_jadwal', ['kelas' => null, 'ruang' => null, 'pr' => null]);
    }

    public function test_pr_kosong_tapi_tenggat_terisi_ditolak(): void
    {
        $this->actingAs($this->buatPengguna())
            ->post('/user/jadwal/tambah', $this->isian([
                'pr' => '   ',
                'pr_dikumpulkan' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('pr');

        $this->assertDatabaseCount('tb_jadwal', 0);
    }
}
