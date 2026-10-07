<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\HapusAkunRequest;
use App\Http\Requests\KataSandiRequest;
use App\Http\Requests\ProfilIsianRequest;
use App\Support\BerkasProfil;
use App\Support\BerkasQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Hapus akun beserta seluruh isinya, lalu keluarkan pengguna.
     *
     * Password sudah diperiksa HapusAkunRequest, jadi sampai baris ini
     * account belum ada yang tersentuh: kode di bawahnya baru jalan kalau
     * orang yang sedang login memang pemilik akun itu dan tahu passwordnya.
     *
     * Sesuai relasi tabel di database, menghapus baris tb_pengguna sudah
     * membersihkan Riwayat Aktivitas, preferensi, notifikasi, bookmark,
     * pengerjaan dan sesi quiz miliknya (ON DELETE CASCADE). Dua hal tidak
     * ikut hilang, dan itu disengaja:
     *
     *   - Materi miliknya tetap ada dengan pembuatnya jadi kosong
     *     (ON DELETE SET NULL), supaya karya yang sudah tayang tidak ikut
     *     hilang bersama akunnya.
     *   - Materi tidak punya relasi database ke user, jadi isinya tidak
     *     tersentuh sama sekali.
     *
     * Berkas di disk publik tidak dihapus oleh cascade database, jadi kedua
     * daftar path diambil lebih dulu dan berkasnya dihapus setelah baris
     * akun benar-benar hilang — urutan yang sama seperti hapus quiz di
     * User\QuizKelolaController::destroy().
     */
    public function hapus(HapusAkunRequest $request): RedirectResponse
    {
        $pengguna = $request->user();

        $foto = $pengguna->foto_profil;
        $thumbnail = $pengguna->quiz()->pluck('thumbnail')->all();

        // Logout sebelum delete: sesi tidak boleh tetap membawa akun yang
        // sudah tidak ada lagi, dan memvalidasi ulang session tidak butuh
        // baris pengguna.
        Auth::guard('web')->logout();

        $pengguna->delete();

        BerkasProfil::hapus($foto);

        foreach ($thumbnail as $berkas) {
            BerkasQuiz::hapus($berkas);
        }

        // invalidate() membuang seluruh isi session lama termasuk tokennya,
        // jadi tidak ada sisa yang bisa dipakai setelah akun dihapus. Flash
        // pesan ditulis sesudahnya, supaya tetap terbawa ke halaman tujuan.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('sukses', 'Akunmu sudah dihapus. Terima kasih sudah belajar di KelasKita.');
    }
}
