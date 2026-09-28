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
     * URL publik dari isi kolom thumbnail/audio.
     *
     * Kolom itu menyimpan dua bentuk nilai yang berbeda:
     *   - path relatif di disk publik, mis. "thumbnails/abc.jpg", hasil
     *     unggahan form. Path lengkapnya dipakai, jangan dipotong
     *     basename() supaya nama foldernya tidak hilang;
     *   - URL penuh, mis. gambar contoh pada data seeder, dipakai apa
     *     adanya.
     *
     * Aman dipanggil dengan nilai kosong.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
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
