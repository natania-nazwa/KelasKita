<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Mengubah materi atau quiz menjadi kartu daftar untuk halaman
 * "Konten Pembelajaran" di area admin.
 *
 * Satu kelas untuk dua jenis konten, karena kartunya memang sama: thumbnail,
 * judul, keterangan singkat, kategori, status, tanggal, dan tautan aksi.
 * Bedanya hanya kata "Materi"/"Quiz", satuan jumlah, dan apa yang dihitung di keterangan
 * (jumlah bab untuk materi, jumlah soal untuk quiz) — jadi dipisah-pisah
 * hanya akan membuat dua daftar yang nyaris sama.
 *
 * Berbeda dengan App\Support\DaftarMateriAdmin dan DaftarQuizAdmin, yang
 * hanya memetakan konten yang sudah tayang: di sini admin melihat semua
 * status, karena itulah gunanya halaman ini.
 *
 * Kelas ini tidak memilih konten mana yang boleh tampil. Pola "hanya karya
 * sendiri" diputuskan di pemanggil lewat Model::scopeMilik, sama seperti di
 * halaman "Karya Saya", supaya satu tempat yang memutuskan batas daftar dan
 * satu tempat yang memetakan barisnya.
 */
final class DaftarKonten
{
    /** Jumlah kartu per halaman. */
    public const PER_HALAMAN = 12;

    /**
     * Petakan sekumpulan model (materi atau quiz) ke bentuk array yang
     * dipakai kartu daftar.
     *
     * Bentuk array per kartu sengaja mengikuti kartu "Karya Saya" milik
     * pengguna (App\Support\DaftarMateri dan DaftarQuiz) sebanyak mungkin:
     * judul, deskripsi, thumbnail, jumlah, menit, tanggal, kategori, dan
     * status. Kartu di "Konten Pembelajaran" memakai kelas CSS yang sama
     * persis dengan kartu pengguna, jadi satu konten punya tampilan yang sama
     * di kedua tempat tanpa dua set gaya yang bisa menyimpang.
     *
     * Yang ditambahkan: `jenis` (materi atau quiz) supaya satu komponen
     * kartu bisa melayani keduanya, dan `ringkasan` yang dipakai sebagai
     * keterangan singkat pada dialog hapus.
     *
     * Bentuk array per kartu:
     *   id, jenis, judul, deskripsi, jumlah, satuan, menit, ringkasan,
     *   thumbnail, terbit, tanggal_label, status, warna_status,
     *   status_label, status_ringkas,
     *   kategori => [nama, ikon, warna, warna_gelap],
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

            /*
             * Hanya kolom deskripsi, tidak lewat Materi::ringkasan().
             *
             * ringkasan() sengaja jatuh ke isi materi kalau deskripsinya
             * kosong, supaya materi lama yang tidak pernah punya deskripsi
             * tetap punya sesuatu untuk ditampilkan. Di daftar ini itu
             * justru salah: form Tambah Materi tidak punya isian deskripsi
             * sama sekali, jadi setiap materi yang dibuat dari Konten
             * Pembelajaran akan memamerkan 120 karakter pertama isi
             * materinya sendiri — termasuk penanda babnya ("Bab 1: …").
             * Itu bukan ringkasan, dan admin membacanya sebagai kalau
             * kartu ini rusak.
             *
             * Kalau deskripsinya memang kosong, kartu tidak menampilkannya
             * (lihat components/admin/konten-kartu). Materi tanpa deskripsi
             * tetap terbaca dari judul, jumlah bab, durasi, dan tanggalnya.
             */
            'deskripsi' => $item->deskripsi,
            'jumlah' => $materi ? $item->jumlahBab() : $item->jumlahSoal(),
            'satuan' => $materi ? 'Bab' : 'Soal',
            'menit' => $materi ? $item->waktuBaca() : (int) $item->durasi,
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
            'warna_status' => $item->warnaStatus(),
            'status_label' => $item->labelStatus(),
            'status_ringkas' => self::statusRingkas((string) $item->status),
            'tautan' => self::tautan($item),
        ];
    }

    /**
     * Label status pendek untuk lencana di baris daftar.
     *
     * Berbeda dengan labelStatus() yang dipakai halaman detail dan form
     * ("Dipublikasikan"), lencana di baris perlu dua kata supaya muat
     * berdampingan dengan tanggal tanpa membuat baris membungkus. Hanya dua
     * status yang bisa muncul di sini: daftar ini manage karya admin sendiri,
     * yang statusnya cuma draft atau published.
     */
    private static function statusRingkas(string $status): string
    {
        return $status === Materi::STATUS_PUBLISHED ? 'Published' : 'Draft';
    }

    /**
     * Tautan aksi untuk satu konten.
     *
     * Semuanya memakai route milik "Konten Pembelajaran", termasuk "lihat".
     * Daftar ini mengelola seluruh konten, bukan hanya yang sudah tayang,
     * jadi jalurnya tidak boleh sama dengan halaman "Materi" dan "Quiz"
     * yang tetap mengelola katalog konten published saja.
     *
     * "Lihat" punya route-nya sendiri di dalam /admin/konten, meskipun
     * controller, view, dan datanya sama persis dengan admin.materi.show dan
     * admin.quiz.show. Yang dibedakan hanya nama route-nya, karena penanda
     * aktif di sidebar memakai admin.konten*: lewat route katalog, menekan
     * "Lihat" di sini akan memindahkan menu yang menyala dari Konten
     * Pembelajaran ke Materi atau Quiz — persis yang tidak boleh terjadi
     * saat admin masih membaca karyanya sendiri.
     *
     * @return array{lihat: string, edit: string, hapus: string, publish: string, duplikat: string}
     */
    private static function tautan(Materi|Quiz $item): array
    {
        if ($item instanceof Materi) {
            return [
                'lihat' => route('admin.konten.materi.show', $item->slug),
                'edit' => route('admin.konten.materi.edit', $item->slug),
                'hapus' => route('admin.konten.materi.destroy', $item->slug),
                'publish' => route('admin.konten.materi.publish', $item->slug),
                'duplikat' => route('admin.konten.materi.duplikat', $item->slug),
            ];
        }

        return [
            'lihat' => route('admin.konten.quiz.show', $item->getKey()),
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

        while (self::slugDipakai($tabel, $slug, $abaikan)) {
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
