<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas lampiran masukan dan saran di disk publik.
 *
 * Bentuknya sengaja meniru App\Support\BerkasProfil dan BerkasMateri supaya
 * nama folder dan tempat simpan hanya tertulis di satu tempat. Bedanya, field
 * ini benar-benar opsional: "tidak ada lampiran" adalah keadaan yang sah,
 * jadi simpan() mengembalikan null alih-alih array kosong.
 */
final class BerkasMasukan
{
    /**
     * Folder tempat lampiran disimpan di disk publik.
     */
    public const FOLDER = 'masukan-saran';

    /**
     * Simpan lampiran yang diunggah dan kembalikan path-nya siap disimpan
     * ke kolom lampiran, atau null kalau tidak ada berkas yang diunggah.
     */
    public static function simpan(Request $request): ?string
    {
        if (! $request->hasFile('lampiran')) {
            return null;
        }

        return $request->file('lampiran')->store(self::FOLDER, 'public');
    }

    /**
     * Hapus berkas dari disk publik.
     *
     * Aman untuk path kosong dan untuk berkas yang sudah tidak ada.
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
