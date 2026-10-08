<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;

/**
 * Kartu kategori di section "Kategori Materi" landing page.
 *
 * Landing page menampilkan sepuluh mata pelajaran teratas, bukan seluruh
 * daftar: sepuluh kartu sudah memenuhi dua baris penuh di grid lima kolom,
 * dan filter Materi, Quiz, serta form tambah konten tetap menampilkan
 * seluruh daftar resmi.
 *
 * Datanya dibaca dari Pelajaran::KATALOG, bukan hardcoded di Blade. Sebelumnya
 * sepuluh kartu ditulis manual di view, lengkap dengan jumlah materi yang
 * selalu sama-sama "5 Materi" padahal isinya berbeda-beda, dan lima di
 * antaranya bukan mata pelajaran yang bisa dipilih di halaman mana pun. Satu
 * sumber data membuat kartu landing page, filter, dan form tidak mungkin
 * lagi menampilkan nama yang berbeda.
 *
 * Jumlah materi dihitung dari materi yang sudah terbit saja, sama persis
 * dengan yang dihitung filter halaman Materi. Materi draft tidak dihitung,
 * supaya angka di kartu tidak menjanjikan isi yang belum tayang.
 */
class DaftarKategoriLanding
{
    /**
     * Berapa banyak kartu yang ditampilkan landing page.
     */
    public const JUMLAH_KARTU = 10;

    /**
     * Kartu kategori untuk landing page, urut dari teratas KATALOG.
     *
     * Pelajaran yang sudah dinonaktifkan lewat Pengaturan admin ikut
     * disembunyikan, supaya kartu landing page tidak menawarkan kategori yang
     * tidak bisa dipilih di form tambah materi maupun quiz.
     *
     * @return array<int, array{nama: string, slug: string, ikon: string, warna: string, warna_gelap: string, jumlah_materi: int}>
     */
    public static function kartu(): array
    {
        $nonaktif = Pelajaran::query()
            ->where('aktif', false)
            ->pluck('slug')
            ->all();

        $jumlahMateri = Materi::query()
            ->terbit()
            ->whereNotNull('pelajaran_id')
            ->selectRaw('pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('pelajaran_id')
            ->pluck('jumlah', 'pelajaran_id');

        $idPelajaran = Pelajaran::query()
            ->pluck('id', 'slug');

        $kartu = [];

        foreach (Pelajaran::landing(self::JUMLAH_KARTU) as $pelajaran) {
            if (in_array($pelajaran['slug'], $nonaktif, true)) {
                continue;
            }

            $kartu[] = [
                'nama' => $pelajaran['nama'],
                'slug' => $pelajaran['slug'],
                'ikon' => $pelajaran['ikon'],
                'warna' => $pelajaran['warna'],
                'warna_gelap' => $pelajaran['warna_gelap'],
                'jumlah_materi' => (int) ($jumlahMateri[$idPelajaran[$pelajaran['slug']] ?? null] ?? 0),
            ];
        }

        return $kartu;
    }
}
