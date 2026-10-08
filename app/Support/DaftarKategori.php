<?php

namespace App\Support;

use App\Models\Pelajaran;
use Illuminate\Support\Collection;

/**
 * Daftar kategori untuk filter Materi dan Quiz.
 *
 * Halaman Materi dan Quiz milik pengguna maupun admin punya bentuk filter yang
 * sama persis: daftar kategori lengkap dengan jumlah konten di dalamnya,
 * dengan urutan mengikuti daftar resmi mata pelajaran. Logikanya ditulis satu
 * kali di sini supaya keempat halaman itu tidak bisa berbeda isi atau urutan
 * hanya karena disalin terpisah.
 *
 * Berbeda dari versi sebelumnya, kategori dengan jumlah 0 tidak lagi
 * disembunyikan. Jadi lewat dropdown selalu punya sebelas pilihan yang sama,
 * dan memilih kategori yang isinya belum ada memberi tahu lewat daftar yang
 * kosong, bukan lewat opsi yang hilang dari pilihan.
 */
class DaftarKategori
{
    /**
     * Daftar kategori lengkap dengan jumlah konten, urut sesuai katalog.
     *
     * @param  Collection<int|string, int>|array<int|string, int>  $jumlah  jumlah konten per id pelajaran, hasil dari pluck('jumlah', 'pelajaran_id')
     * @return Collection<int, array<string, mixed>>
     */
    public static function filter(Collection|array $jumlah, bool $aktifSaja = true): Collection
    {
        $query = Pelajaran::query()->urutKatalog();

        if ($aktifSaja) {
            $query->aktif();
        }

        return $query
            ->get()
            ->map(fn (Pelajaran $pelajaran) => [
                ...Pelajaran::warna($pelajaran->slug, $pelajaran->nama),
                'jumlah' => (int) ($jumlah[$pelajaran->id] ?? 0),
            ])
            ->values();
    }
}
