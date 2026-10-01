<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Mengubah materi atau quiz menjadi baris daftar untuk halaman
 * "Konten Pembelajaran" di area admin.
 *
 * Satu kelas untuk dua jenis konten, karena barisnya memang sama: thumbnail,
 * judul, keterangan singkat, kategori, status, tanggal, dan tautan aksi.
 * Bedanya hanya kata "Materi"/"Quiz" dan apa yang dihitung di keterangan
 * (jumlah bab untuk materi, jumlah soal untuk quiz) — jadi dipisah-pisah
 * hanya akan membuat dua daftar yang nyaris sama.
 *
 * Berbeda dengan App\Support\DaftarMateriAdmin dan DaftarQuizAdmin, yang
 * hanya memetakan konten yang sudah tayang: di sini admin melihat semua
 * status, karena itulah gunanya halaman ini.
 */
final class DaftarKonten
{
    /** Jumlah baris per halaman. */
    public const PER_HALAMAN = 12;

    /**
     * Petakan sekumpulan model (materi atau quiz) ke bentuk array yang
     * dipakai baris daftar.
     *
     * Bentuk array per baris:
     *   id, jenis, judul, ringkasan, meta, thumbnail, jumlah,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   terbit, tanggal_label, status, status_label, warna_status,
     *   tautan => [lihat, edit, hapus, publish, duplikat]
     *
     * @param  iterable<int, Materi|Quiz>  $konten
     * @return array<int, array<string, mixed>>
     */
    public static function petikan(iterable $konten): array
    {
        $hasil = [];

        foreach ($konten as $item) {
            $hasil[] = self::baris($item);
        }

        return $hasil;
    }

    /**
     * @return array<string, mixed>
     */
    private static function baris(Materi|Quiz $item): array
    {
        $materi = $item instanceof Materi;

        $pelajaran = $item->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');

        /*
         * Tanggal yang ditampilkan adalah tanggal terbit kalau kontennya sudah
         * tayang, dan tanggal pembuatannya kalau masih draft. Karena itu nilai
         * ini tidak pernah kosong.
         */
        $terbit = $item->dipublish_pada ?? $item->created_at;

        return [
            'id' => $item->getKey(),
            'jenis' => $materi ? 'materi' : 'quiz',
            'judul' => $materi ? $item->nama : $item->judul,
            'ringkasan' => $materi
                ? 'Materi • '.$item->jumlahBab().' bab'
                : 'Quiz • '.$item->jumlahSoal().' soal',
            'thumbnail' => $materi
                ? BerkasMateri::url($item->thumbnail)
                : BerkasQuiz::url($item->thumbnail),
            'kategori' => [
                'nama' => $kategori['nama'],
                'ikon' => $kategori['ikon'],
                'warna' => $kategori['warna'],
                'warna_gelap' => $kategori['warna_gelap'],
            ],
            'terbit' => $terbit,
            'tanggal_label' => DetailMateri::tanggal($terbit),
            'status' => (string) $item->status,
            'status_label' => $item->labelStatus(),
            'warna_status' => $item->warnaStatus(),
            'tautan' => self::tautan($item),
        ];
    }

    /**
     * Tautan aksi untuk satu konten.
     *
     * Semuanya memakai route milik "Konten Pembelajaran", bukan route admin
     * yang sudah ada: daftar ini mengelola seluruh konten, bukan hanya yang
     * sudah tayang, jadi jalurnya tidak boleh sama dengan halaman "Materi" dan
     * "Quiz" yang tetap manages konten published saja.
     *
     * @return array{lihat: string, edit: string, hapus: string, publish: string, duplikat: string}
     */
    private static function tautan(Materi|Quiz $item): array
    {
        if ($item instanceof Materi) {
            return [
                'lihat' => route('admin.konten.materi.lihat', $item->slug),
                'edit' => route('admin.konten.materi.edit', $item->slug),
                'hapus' => route('admin.konten.materi.destroy', $item->slug),
                'publish' => route('admin.konten.materi.publish', $item->slug),
                'duplikat' => route('admin.konten.materi.duplikat', $item->slug),
            ];
        }

        return [
            'lihat' => route('admin.konten.quiz.lihat', $item->getKey()),
            'edit' => route('admin.konten.quiz.edit', $item->getKey()),
            'hapus' => route('admin.konten.quiz.destroy', $item->getKey()),
            'publish' => route('admin.konten.quiz.publish', $item->getKey()),
            'duplikat' => route('admin.konten.quiz.duplikat', $item->getKey()),
        ];
    }

    /**
     * Slug unik dari sebuah judul, dengan akhiran angka bila slug dasar sudah
     * dipakai konten lain.
     *
     * Dipakai oleh form tambah dan aksi duplikat. Satu tempat supaya
     * "pengenalan-html", "pengenalan-html-2", dan seterusnya hanya punya satu
     * aturan, dan materi maupun quiz tidak punya versi berbeda.
     */
    public static function slugUnik(string $judul, string $tabel, ?Model $abaikan = null): string
    {
        $dasar = Str::slug($judul) ?: 'konten';
        $slug = $dasar;
        $urutan = 2;

        while (static::slugDipakai($tabel, $slug, $abaikan)) {
            $slug = $dasar.'-'.$urutan;
            $urutan++;
        }

        return $slug;
    }

    /**
     * @param  class-string  $tabel
     */
    private static function slugDipakai(string $tabel, string $slug, ?Model $abaikan): bool
    {
        return $tabel::query()
            ->where('slug', $slug)
            ->when(
                $abaikan?->getKey() !== null,
                fn ($query) => $query->whereKeyNot($abaikan->getKey())
            )
            ->exists();
    }
}
