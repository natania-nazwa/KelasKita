<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas lampiran materi (thumbnail dan audio) pada disk publik.
 *
 * Dipakai form tambah materi, form edit materi, dan saat materi dihapus,
 * supaya aturan nama folder dan penghapusan berkas lama hanya ada di satu
 * tempat.
 */
final class BerkasMateri
{
    /**
     * Simpan berkas yang diunggah dan kembalikan path-nya siap disimpan ke
     * kolom materi.
     *
     * Field yang tidak diunggah sengaja tidak ikut dikembalikan, sehingga
     * thumbnail atau audio lama tetap utuh saat form disimpan tanpa berkas
     * baru.
     *
     * @return array<string, string>
     */
    public static function simpan(Request $request): array
    {
        $tersimpan = [];

        if ($request->hasFile('thumbnail')) {
            $tersimpan['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        if ($request->hasFile('audio')) {
            $tersimpan['audio'] = $request->file('audio')->store('audio', 'public');
        }

        return $tersimpan;
    }

    /**
     * Hapus berkas dari disk publik.
     *
     * Aman untuk path kosong (materi yang memang tidak punya lampiran) dan
     * untuk berkas yang sudah tidak ada.
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
