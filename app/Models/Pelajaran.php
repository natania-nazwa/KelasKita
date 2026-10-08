<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mata pelajaran / kategori materi (tabel tb_pelajaran).
 *
 * Satu baris di tabel ini dipakai sebagai filter kategori di halaman Materi,
 * Quiz, dan jadwal. Daftar resminya ada di KATALOG dan tabelnya diselaraskan
 * dengan migration, jadi nama pelajaran di seluruh aplikasi hanya satu.
 */
#[Fillable(['nama', 'slug', 'deskripsi', 'ikon', 'aktif'])]
class Pelajaran extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("lessons").
     */
    protected $table = 'tb_pelajaran';

    /**
     * Daftar resmi mata pelajaran, lengkap dengan warna, ikon, dan urutannya.
     *
     * Ini satu-satunya daftar yang dipakai aplikasi: baris di tb_pelajaran
     * diselaraskan dengan daftar ini lewat migration, dan setiap tempat yang
     * menampilkan kategori membacanya lewat warna() atau urutan(). Karena itu
     * nama pelajaran di landing page, filter Materi dan Quiz, form tambah
     * konten, form jadwal, dan Kelola Mata Pelajaran tidak akan berbeda.
     *
     * Urutan array sekaligus urutan tampilannya: landing page memakai enam
     * teratas, filter dan pilihan jadwal memakai seluruh daftarnya.
     */
    public const KATALOG = [
        [
            'nama' => 'PAI',
            'slug' => 'pai',
            'deskripsi' => 'Pendidikan Agama Islam: akidah, fikih, dan akhlak.',
            'ikon' => '🕌',
            'warna' => '#4fd0a8',
            'warna_gelap' => '#14916f',
        ],
        [
            'nama' => 'Bahasa Indonesia',
            'slug' => 'bahasa-indonesia',
            'deskripsi' => 'Tata bahasa, kosakata, dan keterampilan membaca dan menulis.',
            'ikon' => 'A',
            'warna' => '#f492d3',
            'warna_gelap' => '#d94da8',
        ],
        [
            'nama' => 'Bahasa Inggris',
            'slug' => 'bahasa-inggris',
            'deskripsi' => 'Grammar, kosakata, dan kemampuan menulis dan berbicara.',
            'ikon' => 'En',
            'warna' => '#7cc0f7',
            'warna_gelap' => '#2f8fdc',
        ],
        [
            'nama' => 'Bahasa Jepang',
            'slug' => 'bahasa-jepang',
            'deskripsi' => 'Hiragana, katakana, kanji, dan kosakata dasar.',
            'ikon' => '日',
            'warna' => '#f78299',
            'warna_gelap' => '#e23a5e',
        ],
        [
            'nama' => 'Pendidikan Pancasila',
            'slug' => 'pendidikan-pancasila',
            'deskripsi' => 'Nilai kebangsaan, hak dan kewajiban warga negara.',
            'ikon' => '🇮🇩',
            'warna' => '#f9a86b',
            'warna_gelap' => '#e0722a',
        ],
        [
            'nama' => 'PPLG',
            'slug' => 'pplg',
            'deskripsi' => 'Pengembangan Perangkat Lunak dan Gim, dari desain sampai rilis.',
            'ikon' => '💻',
            'warna' => '#4fd0e0',
            'warna_gelap' => '#0e9bb0',
        ],
        [
            'nama' => 'Matematika',
            'slug' => 'matematika',
            'deskripsi' => 'Pecahan, geometri, dan aljebra untuk jenjang dasar.',
            'ikon' => '∑',
            'warna' => '#8b80e6',
            'warna_gelap' => '#5a4cc9',
        ],
        [
            'nama' => 'IPA',
            'slug' => 'ipa',
            'deskripsi' => 'Fisika, astronomi, dan phenomena alam sehari-hari.',
            'ikon' => '🔬',
            'warna' => '#5fd6ae',
            'warna_gelap' => '#14a97e',
        ],
        [
            'nama' => 'IPS',
            'slug' => 'ips',
            'deskripsi' => 'Sejarah, geografi, dan kehidupan sosial masyarakat.',
            'ikon' => '🌏',
            'warna' => '#f6cd6b',
            'warna_gelap' => '#c99213',
        ],
        [
            'nama' => 'Sejarah',
            'slug' => 'sejarah',
            'deskripsi' => 'Peristiwa dan tokoh penting dalam sejarah dunia dan Indonesia.',
            'ikon' => '🏛',
            'warna' => '#b39ef5',
            'warna_gelap' => '#6a45c9',
        ],
        [
            'nama' => 'Database',
            'slug' => 'database',
            'deskripsi' => 'Penyimpanan data terstruktur dan bahasa SQL.',
            'ikon' => '🗄️',
            'warna' => '#3fb8c9',
            'warna_gelap' => '#0e7d8c',
        ],
    ];

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    /**
     * Quiz yang memakai mata pelajaran ini.
     *
     * Sisi sebaliknya dari Quiz::pelajaran(), dan dipakai halaman "Kelola
     * Mata Pelajaran" untuk menghitung berapa konten yang bergantung pada
     * setiap baris sebelum admin menghapusnya.
     */
    public function quiz(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Hanya pelajaran aktif yang boleh muncul sebagai filter.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Urutkan menurut KATALOG, bukan menurut nama.
     *
     * Semua pilihan dan filter kategori memakai urutan ini supaya daftar
     * pelajaran selalu sama, dari mana pun dibaca. Pelajaran di luar katalog
     * (mis. yang ditambahkan admin dari Kelola Mata Pelajaran) tidak punya
     * posisi, jadi diletakkan di belakang dan diurutkan menurut nama.
     */
    public function scopeUrutKatalog(Builder $query): Builder
    {
        $kasus = collect(self::urutan())
            ->map(fn (int $posisi, string $slug) => "WHEN '{$slug}' THEN {$posisi}")
            ->implode(' ');

        return $query
            ->orderByRaw("CASE slug {$kasus} ELSE 99 END")
            ->orderBy('nama');
    }

    /**
     * Peta slug ke posisi di KATALOG, untuk mengurutkan daftar kategori.
     *
     * Dipakai setiap kali kategori ditampilkan urut, supaya urutannya sama di
     * filter Materi, filter Quiz, pilihan jadwal, dan kartu landing page.
     * Pelajaran di luar katalog (mis. yang ditambahkan admin dari Kelola Mata
     * Pelajaran) tidak ada di peta ini, jadi diletakkan di akhir daftar.
     *
     * @return array<string, int>
     */
    public static function urutan(): array
    {
        return collect(self::KATALOG)
            ->pluck('slug')
            ->mapWithKeys(fn (string $slug, int $index) => [$slug => $index])
            ->all();
    }

    /**
     * Enam pelajaran teratas dari KATALOG, untuk kartu kategori di landing page.
     *
     * Landing page tidak butuh seluruh daftar: enam kartu sudah memenuhi
     * barisnya, dan sisanya tetap lengkap di setiap filter. Dipotong dari
     * KATALOG, bukan dari database, supaya kartu tidak ikut berubah-ubah isi
     * setiap kali ada materi baru.
     *
     * @return array<int, array{nama: string, slug: string, ikon: string, warna: string, warna_gelap: string}>
     */
    public static function landing(int $jumlah = 6): array
    {
        return array_slice(self::KATALOG, 0, $jumlah);
    }

    /**
     * Kategori untuk dropdown form tambah dan form edit materi.
     *
     * Daftarnya kategori aktif. Tapi materi yang sedang diedit boleh saja
     * membawa kategori yang sudah dinonaktifkan lewat Pengaturan, dan
     * kategori itu ikut disisipkan ke daftar: kalau tidak, pilihan yang
     * tersimpan hilang dari dropdown, nilainya diam-diam berpindah ke
     * kategori pertama begitu Simpan ditekan, dan validasi menolak
     * penyimpanannya.
     *
     * @return Collection<int, Pelajaran>
     */
    public static function untukForm(?int $pelajaranId = null): Collection
    {
        $kategori = static::query()->aktif()->urutKatalog()->get();

        if ($pelajaranId === null) {
            return $kategori;
        }

        $milikMateri = static::query()->find($pelajaranId);

        if ($milikMateri === null || $kategori->contains('id', $milikMateri->getKey())) {
            return $kategori;
        }

        return $kategori
            ->push($milikMateri)
            ->sortBy('nama')
            ->values();
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
