<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfilIsianRequest;
use App\Support\BerkasProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Profil Admin" di dalam area Pengaturan admin.
 *
 * Isian dan validasinya sama persis dengan halaman Profil milik pengguna
 * (User\ProfilController): form request-nya ProfilIsianRequest, tempat
 * fotonya App\Support\BerkasProfil, dan selalu bekerja pada $request->user()
 * sehingga tidak ada cara lewat URL untuk mengedit akun orang lain.
 *
 * Yang membedakan hanya dua hal: halaman ini memakai design system admin, dan
 * setelah simpan kembali ke /admin/pengaturan, bukan ke /user/profil.
 */
class ProfilAdminController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.pengaturan.profil', [
            'admin' => $request->user(),
        ]);
    }

    /**
     * Simpan nama, email, dan foto profil admin yang sedang login.
     */
    public function update(ProfilIsianRequest $request): RedirectResponse
    {
        $admin = $request->user();
        $data = $request->safe()->except('foto_profil');
        $fotoBaru = BerkasProfil::simpan($request);

        /*
         * Foto lama hanya dibuang setelah ada foto baru yang benar-benar
         * tersimpan. Kalau unggahannya ditolak oleh validasi, request tidak akan
         * sampai ke sini, jadi tidak ada jalan untuk kehilangan foto lama
         * tanpa menggantinya.
         */
        if (isset($fotoBaru['foto_profil'])) {
            BerkasProfil::hapus($admin->foto_profil);
        }

        $admin->fill([...$data, ...$fotoBaru])->save();

        return redirect()
            ->route('admin.pengaturan')
            ->with('sukses', 'Profil berhasil diperbarui.')
            ->with('suksesDetail', 'Nama, email, dan foto profil yang dipakai di seluruh area admin sudah ikut berubah.');
    }

    /**
     * Hapus foto profil admin.
     *
     * Namanya, email-nya, dan akunnya tetap utuh; yang hilang hanya berkas
     * fotonya, dan avatar kembali memakai inisial.
     */
    public function destroyFoto(Request $request): RedirectResponse
    {
        $admin = $request->user();

        abort_if(blank($admin->foto_profil), 404);

        BerkasProfil::hapus($admin->foto_profil);

        $admin->fill(['foto_profil' => null])->save();

        return redirect()
            ->route('admin.pengaturan')
            ->with('sukses', 'Foto profil berhasil dihapus.');
    }
}
