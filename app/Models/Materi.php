<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Materi belajar (tabel tb_materi).
 *
 * Setiap materi selalu punya satu mata pelajaran (kategori) dari
 * tb_pelajaran, dan boleh dibuat oleh admin atau user.
 */
#[Fillable([
    'pelajaran_id',
    'dibuat_oleh',
    'nama',
    'slug',
    'deskripsi',
    'isi',
    'tingkat_kesulitan',
    'aktif',
])]
class Materi extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("materials").
     */
    protected $table = 'tb_materi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function pelajaran(): BelongsTo
    {
        return $this->belongsTo(Pelajaran::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Materi yang aman ditampilkan ke user.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Pencarian materi di halaman Materi.
     *
     * Mencari di nama, deskripsi, isi materi, dan nama mata pelajarannya.
     * Karakter % dan _ dari user di-escape supaya tidak jadi wildcard.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): Builder
    {
        $kataKunci = trim((string) $kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        /*
         * PostgreSQL membuat LIKE tidak peduli huruf besar-kecil. Tanpa
         * ILIKE, mengetik "katakana" tidak akan menemukan judul "Katakana".
         */
        $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pola, $operator) {
            $query->where('nama', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhere('isi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola));
        });
    }

    /**
     * Filter kategori berdasarkan slug mata pelajaran.
     */
    public function scopeKategori(Builder $query, ?string $slug): Builder
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return $query;
        }

        return $query->whereHas(
            'pelajaran',
            fn (Builder $pelajaran) => $pelajaran->where('slug', $slug)
        );
    }

    /**
     * Cuplikan materi untuk ditampilkan di kartu.
     */
    public function ringkasan(int $jumlah = 110): string
    {
        return Str::limit(
            trim((string) ($this->ringkasan_teks ?? $this->deskripsi ?? $this->isi)),
            $jumlah
        );
    }

    public function getRingkasanTeksAttribute(): ?string
    {
        if (filled($this->deskripsi)) {
            return $this->deskripsi;
        }

        return filled($this->isi) ? Str::limit(strip_tags($this->isi), 160) : null;
    }

    /**
     * Perkiraan waktu baca dalam menit untuk ditampilkan di kartu.
     *
     * Dihitung dari jumlah kata isi materi dengan kecepatan baca sekitar
     * 200 kata per menit, minimal 1 menit supaya tidak pernah "0 menit".
     */
    public function waktuBaca(): int
    {
        $jumlahKata = str(strip_tags((string) $this->isi))
            ->squish()
            ->explode(' ')
            ->filter()
            ->count();

        return max(1, (int) ceil($jumlahKata / 200));
    }

    public function getWaktuBacaMenitAttribute(): int
    {
        return $this->waktuBaca();
    }
}
