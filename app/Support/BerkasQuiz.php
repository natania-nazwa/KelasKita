<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas lampiran quiz (thumbnail) pada disk publik.
 *
 * Pemisahan dari App\Support\BerkasMateri disengaja: folder simpan dan
 * aturan jenis berkasnya berbeda. Dua kelasnya tetap memakai pola yang
 * sama supaya cara membaca kolom thumbnail di view juga sama.
 */
final class BerkasQuiz
{
    /** Folder tujuan di disk publik. */
    public const FOLDER = 'thumbnails-quiz';

    /**
     * Simpan berkas yang diunggah dan kembalikan path-nya siap disimpan ke
     * kolom quiz.
     *
     * Field yang tidak diunggah sengaja tidak ikut dikembalikan, sehingga
     * thumbnail lama tetap utuh saat form disimpan tanpa berkas baru.
     *
     * @return array<string, string>
     */
    public static function simpan(Request $request): array
    {
        if (! $request->hasFile('thumbnail')) {
            return [];
        }

        return [
            'thumbnail' => $request->file('thumbnail')->store(self::FOLDER, 'public'),
        ];
    }

    /**
     * Hapus berkas dari disk publik.
     *
     * Aman untuk path kosong (quiz yang memang tidak punya thumbnail) dan
     * untuk berkas yang sudah tidak ada. Path di kolom bisa berupa URL
     * penuh (gambar contoh dari seeder), jadi berkas seperti itu dilewati.
     */
    public static function hapus(?string $path): void
    {
        if (blank($path) || preg_match('#^https?://#i', $path) === 1) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * URL publik dari isi kolom thumbnail.
     *
     * Kolom itu menyimpan dua bentuk nilai yang berbeda:
     *   - path relatif di disk publik, mis. "thumbnails-quiz/abc.jpg",
     *     hasil unggahan form. Path lengkapnya dipakai, jangan dipotong
     *     basename() supaya nama foldernya tidak hilang;
     *   - URL penuh, mis. gambar contoh pada data seeder, dipakai apa
     *     adanya.
     *
     * Aman dipanggil dengan nilai kosong (quiz tanpa thumbnail).
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
}
