<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isian pendukung form materi: estimasi waktu belajar dan tips/catatan.
 *
 * Kedua isian sudah lama tampil di form (x-materi.informasi) dan ikut
 * terkirim tiap kali tombol simpan ditekan, tapi sebelumnya tidak pernah
 * sampai ke database — tidak ada kolom, tidak ada aturan validasi, tidak ada
 * controller yang membacanya. Test di sini menjaga supaya isian benar-benar
 * tersimpan, kembali tampil saat form dibuka lagi, ikut terbawa saat materi
 * diduplikat, dan ditolak kalau melewati batas yang ditulis di form.
 */
class MateriIsianPendukungTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private function buatPengguna(array $atribut = []): User
    {
        return User::create(array_merge([
            'nama' => 'Budi',
            'email' => 'budi@example.com',
            'kata_sandi' => 'rahasia123',
        ], $atribut))->refresh();
    }

    private function buatAdmin(): User
    {
        return $this->buatPengguna([
            'nama' => 'Admin',
            'email' => 'admin@example.com',
            'peran' => User::PERAN_ADMIN,
        ]);
    }

    private function buatPelajaran(): Pelajaran
    {
        return Pelajaran::firstOrCreate(['slug' => 'pemrograman'], [
            'nama' => 'Pemrograman',
            'deskripsi' => 'Deskripsi Pemrograman',
            'aktif' => true,
        ]);
    }

    private function buatMateri(?User $pemilik, string $nama): Materi
    {
        $this->urutan++;

        return Materi::create([
            'pelajaran_id' => $this->buatPelajaran()->id,
            'dibuat_oleh' => $pemilik?->getKey(),
            'nama' => $nama,
            'slug' => str($nama)->slug()->value().'-'.$this->urutan,
            'deskripsi' => 'Ringkasan materi.',
            'isi' => 'Isi materi yang cukup panjang untuk memenuhi batas minimal validasi.',
            'tingkat_kesulitan' => 'Mudah',
            'status' => Materi::STATUS_PUBLISHED,
            'dipublish_pada' => now()->toDateTimeString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Pelajaran $pelajaran, array $tambahan = []): array
    {
        return array_merge([
            'pelajaran_id' => $pelajaran->id,
            'nama' => 'Judul Materi',
            'isi' => 'Isi materi yang cukup panjang untuk memenuhi batas minimal validasi.',
            'tingkat_kesulitan' => 'Mudah',
        ], $tambahan);
    }

    /*
     * =============================================================
     * TERSIMPAN DAN KEMBALI TAMPIL
     * =============================================================
     * Sebelum kolomnya ada, estimasi selalu kembali ke "10 menit" dan tips
     * selalu kembali kosong begitu halaman dimuat ulang.
     */

    public function test_edit_menyimpan_estimasi_dan_tips_lalu_menampilkan_kembali(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($admin, 'Materi Edit');

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'estimasi_waktu' => '25 menit',
                'tips' => 'Fokus pada bagian rekursi sebelum lanjut ke dynamic programming.',
            ]))
            ->assertRedirect(route('admin.materi.show', $materi->slug));

        $materi->refresh();

        $this->assertSame('25 menit', $materi->estimasi_waktu);
        $this->assertSame(
            'Fokus pada bagian rekursi sebelum lanjut ke dynamic programming.',
            $materi->tips,
        );

        $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('value="25 menit"', false)
            ->assertSee('Fokus pada bagian rekursi sebelum lanjut ke dynamic programming.');
    }

    public function test_form_tambah_menyimpan_estimasi_dan_tips(): void
    {
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();

        $this->actingAs($pengguna)
            ->post(route('user.materi.tambah.store'), $this->dataForm($pelajaran, [
                'nama' => 'Materi Baru',
                'estimasi_waktu' => '5 menit',
                'tips' => 'Kerjakan latihan di akhir bab.',
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']));

        $materi = Materi::query()->where('nama', 'Materi Baru')->firstOrFail();

        $this->assertSame('5 menit', $materi->estimasi_waktu);
        $this->assertSame('Kerjakan latihan di akhir bab.', $materi->tips);
    }

    public function test_edit_milik_sendiri_menyimpan_estimasi_dan_tips(): void
    {
        $pengguna = $this->buatPengguna();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($pengguna, 'Materi Milik Sendiri');

        $this->actingAs($pengguna)
            ->put(route('user.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'nama' => 'Materi Milik Sendiri',
                'estimasi_waktu' => '15 menit',
                'tips' => 'Baca contoh soal sebelum ujian.',
            ]))
            ->assertRedirect(route('user.karya-saya', ['tab' => 'materi']));

        $materi->refresh();

        $this->assertSame('15 menit', $materi->estimasi_waktu);
        $this->assertSame('Baca contoh soal sebelum ujian.', $materi->tips);
    }

    public function test_materi_lama_tanpa_estimasi_tetap_menampilkan_isian_bawaan(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, 'Materi Lama');

        $this->assertNull($materi->estimasi_waktu);

        $this->actingAs($admin)
            ->get(route('admin.materi.edit', $materi->slug))
            ->assertOk()
            ->assertSee('value="10 menit"', false);
    }

    public function test_duplikat_mewarisi_estimasi_dan_tips(): void
    {
        $admin = $this->buatAdmin();
        $materi = $this->buatMateri($admin, 'Materi Asal');
        $materi->fill([
            'estimasi_waktu' => '30 menit',
            'tips' => 'Catatan yang dibawa oleh materi asal.',
        ])->save();

        $this->actingAs($admin)
            ->post(route('admin.konten.materi.duplikat', $materi->slug))
            ->assertRedirect();

        $salinan = Materi::query()->where('nama', 'Materi Asal (Salinan)')->firstOrFail();

        $this->assertSame('30 menit', $salinan->estimasi_waktu);
        $this->assertSame('Catatan yang dibawa oleh materi asal.', $salinan->tips);
    }

    /*
     * =============================================================
     * BATAS YANG DITULIS DI FORM
     * =============================================================
     * Form menulis maxlength 40 dan 500; server harus memegang batas yang
     * sama, bukan menerima apa pun yang dikirim.
     */

    public function test_estimasi_yang_kepanjangan_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($admin, 'Materi Batas Estimasi');

        $this->actingAs($admin)
            ->from(route('admin.materi.edit', $materi->slug))
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'estimasi_waktu' => str_repeat('a', 41),
            ]))
            ->assertSessionHasErrors('estimasi_waktu');

        $this->assertNull($materi->refresh()->estimasi_waktu);
    }

    public function test_tips_yang_kepanjangan_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($admin, 'Materi Batas Tips');

        $this->actingAs($admin)
            ->from(route('admin.materi.edit', $materi->slug))
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'tips' => str_repeat('a', 501),
            ]))
            ->assertSessionHasErrors('tips');

        $this->assertNull($materi->refresh()->tips);
    }

    public function test_estimasi_dan_tips_boleh_dikosongkan(): void
    {
        $admin = $this->buatAdmin();
        $pelajaran = $this->buatPelajaran();
        $materi = $this->buatMateri($admin, 'Materi Tanpa Estimasi');

        $this->actingAs($admin)
            ->put(route('admin.materi.update', $materi->slug), $this->dataForm($pelajaran, [
                'estimasi_waktu' => '',
                'tips' => '',
            ]))
            ->assertSessionHasNoErrors();

        $materi->refresh();

        $this->assertNull($materi->estimasi_waktu);
        $this->assertNull($materi->tips);
    }
}
