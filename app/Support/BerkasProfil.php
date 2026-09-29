<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas foto profil pengguna di disk publik.
 *
 * Bentuknya sengaja meniru App\Support\BerkasMateri supaya aturan nama
 * folder, tempat simpan, dan penghapusan berkas lama hanya satu tempat.
 * Bedanya hanya field-nya: satu foto profil, bukan thumbnail materi.
 */
final class BerkasProfil
{
    /**
     * Simpan foto profil yang diunggah dan kembalikan path-nya siap
     * disimpan ke kolom foto_profil.
     *
     * Field yang tidak diunggah sengaja tidak ikut dikembalikan, sehingga
     * foto lama tetap utuh saat form profil disimpan tanpa berkas baru.
     *
     * @return array<string, string>
     */
    public static function simpan(Request $request): array
    {
        if (! $request->hasFile('foto_profil')) {
            return [];
        }

        return [
            'foto_profil' => $request->file('foto_profil')->store(
                User::FOLDER_FOTO_PROFIL,
                'public',
            ),
        ];
    }

    /**
     * Hapus berkas dari disk publik.
     *
     * Aman untuk path kosong (pengguna yang memang belum memasang foto)
     * dan untuk berkas yang sudah tidak ada. Berguna juga kalau atribut
     * pada modelnya belum ada, mis. saat tabel belum dimigrasi.
     */
    public static function hapus(?string ...$path): void
    {
        foreach ($path as $berkas) {
            if (filled($berkas)) {
                Storage::disk('public')->delete($berkas);
            }
        }
    }
}
