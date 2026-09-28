<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\KataSandiRequest;
use App\Http\Requests\ProfilIsianRequest;
use App\Support\BerkasProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Profil: melihat, mengubah, dan mengelola data akun sendiri.
 *
 * Setiap action selalu bekerja pada $request->user(), tidak pernah pada id
 * dari parameter route. Jadi tidak ada cara lewat URL untuk mengedit akun
 * orang lain, dan halaman ini otomatis ikut memakai data terbaru begitu
 * nama atau foto profil berubah.
 *
 * Kolom peran, kata_sandi, dan status aktif tidak bisa diubah dari sini:
 * peran dan aktif hanya oleh admin, kata_sandi lewat action ubahKataSandi()
 * yang memeriksa password lama lebih dulu.
 */
class ProfilController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('user.profil', [
            'pengguna' => $request->user(),
        ]);
    }

    /**
     * Simpan nama, email, dan foto profil.
     *
     * Field yang tidak ikut dikirim (mis. tidak ada file baru) tidak
     * menimpa nilai lama, jadi mengoreksi email saja tidak membuat foto
     * profil ikut hilang.
     */
    public function update(ProfilIsianRequest $request): RedirectResponse
    {
        $pengguna = $request->user();
        $data = $request->safe()->except('foto_profil');
        $fotoBaru = BerkasProfil::simpan($request);

        // Foto yang diganti harus dihapus supaya storage tidak menumpuk
        // berkas yang tidak dirujuk pengguna mana pun.
        if (isset($fotoBaru['foto_profil'])) {
            BerkasProfil::hapus($pengguna->foto_profil);
        }

        $pengguna->fill([...$data, ...$fotoBaru])->save();

        return redirect()
            ->route('user.profil')
            ->with('sukses', 'Profil berhasil diperbarui.');
    }

    /**
     * Hapus foto profil.
     *
     * Berkas dihapus dari disk lalu kolom dikosongkan. Karena kolomnya jadi
     * kosong, User::fotoProfilUrl() mengembalikan null dan semua tempat yang
     * menampilkan avatar (header, kartu materi, kartu quiz) otomatis kembali
     * memakai inisial nama depan.
     */
    public function hapusFoto(Request $request): RedirectResponse
    {
        $pengguna = $request->user();

        abort_if(blank($pengguna->foto_profil), 404);

        BerkasProfil::hapus($pengguna->foto_profil);

        $pengguna->fill(['foto_profil' => null])->save();

        return redirect()
            ->route('user.profil')
            ->with('sukses', 'Foto profil berhasil dihapus.');
    }

    /**
     * Ganti password akun sendiri.
     *
     * Password lama sudah diperiksa oleh KataSandiRequest, jadi di sini
     * hanya menyalin password baru. Hash-nya dibuat oleh cast "hashed" pada
     * kolom kata_sandi, jadi tidak ada kode yang menyentuh teks sandi polos
     * dan tidak perlu memanggil Hash::make() di sini.
     */
    public function ubahKataSandi(KataSandiRequest $request): RedirectResponse
    {
        $request->user()->fill([
            'kata_sandi' => $request->string('kata_sandi_baru')->value(),
        ])->save();

        return redirect()
            ->route('user.profil')
            ->with('sukses', 'Password berhasil diubah.');
    }
}
