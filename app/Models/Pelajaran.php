<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mata pelajaran / kategori materi (tabel tb_pelajaran).
 *
 * Satu baris di tabel ini dipakai sebagai filter kategori di halaman Materi,
 * mis. Bahasa Jepang, Bahasa Indonesia, Matematika, IPA, IPS, PPKN, dan PPLG.
 */
#[Fillable(['nama', 'slug', 'deskripsi', 'ikon', 'aktif'])]
class Pelajaran extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("lessons").
     */
    protected $table = 'tb_pelajaran';

    /**
     * Daftar mata pelajaran baku yang dipakai sebagai warna, ikon, dan
     * urutan filter di halaman Materi.
     *
     * Data ini bukan pengganti baris di database, melainkan "warna dasar"
     * supaya tampilan tetap konsisten walaupun tabel tb_pelajaran masih
     * kosong atau ada pelajaran baru yang ditambahkan admin.
     */
    public const KATALOG = [
        [
            'nama' => 'Bahasa Indonesia',
            'slug' => 'bahasa-indonesia',
            'ikon' => 'A',
            'warna' => '#f492d3',
            'warna_gelap' => '#d94da8',
        ],
        [
            'nama' => 'Bahasa Inggris',
            'slug' => 'bahasa-inggris',
            'ikon' => 'En',
            'warna' => '#7cc0f7',
            'warna_gelap' => '#2f8fdc',
        ],
        [
            'nama' => 'Bahasa Jepang',
            'slug' => 'bahasa-jepang',
            'ikon' => '日',
            'warna' => '#f78299',
            'warna_gelap' => '#e23a5e',
        ],
        [
            'nama' => 'Matematika',
            'slug' => 'matematika',
            'ikon' => '∑',
            'warna' => '#8b80e6',
            'warna_gelap' => '#5a4cc9',
        ],
        [
            'nama' => 'IPA',
            'slug' => 'ipa',
            'ikon' => '🔬',
            'warna' => '#5fd6ae',
            'warna_gelap' => '#14a97e',
        ],
        [
            'nama' => 'IPS',
            'slug' => 'ips',
            'ikon' => '🌏',
            'warna' => '#f6cd6b',
            'warna_gelap' => '#c99213',
        ],
        [
            'nama' => 'PPKN',
            'slug' => 'ppkn',
            'ikon' => '🇮🇩',
            'warna' => '#f9a86b',
            'warna_gelap' => '#e0722a',
        ],
        [
            'nama' => 'PPLG',
            'slug' => 'pplg',
            'ikon' => '💻',
            'warna' => '#4fd0e0',
            'warna_gelap' => '#0e9bb0',
        ],
    ];

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    /**
     * Hanya pelajaran aktif yang boleh muncul sebagai filter.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Template warna + ikon untuk satu mata pelajaran.
     *
     * Mengembalikan array ['nama', 'slug', 'ikon', 'warna', 'warna_gelap'].
     */
    public static function warna(string $slug, ?string $nama = null): array
    {
        foreach (self::KATALOG as $item) {
            if ($item['slug'] === $slug) {
                return $nama ? [...$item, 'nama' => $nama] : $item;
            }
        }

        // Pelajaran di luar katalog (mis. yang dibuat admin) tetap dapat
        // warna, hanya memakai warna utama brand.
        return [
            'nama' => $nama ?? str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'ikon' => '📘',
            'warna' => '#a78bfa',
            'warna_gelap' => '#6c4de6',
        ];
    }
}
