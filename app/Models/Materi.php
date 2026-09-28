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
    'thumbnail',
    'audio',
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
     * Materi milik satu pengguna.
     *
     * Dipakai halaman "Karya Saya": filter ini yang membuat materi milik
     * orang lain tidak pernah ikut tampil di sana. Sengaja tanpa scope
     * aktif(), supaya materi milik sendiri yang sedang disembunyikan tetap
     * bisa diedit atau dihapus pemiliknya.
     */
    public function scopeMilik(Builder $query, ?int $idPembuat): Builder
    {
        return $query->where('dibuat_oleh', $idPembuat);
    }

    /**
     * Satu-satunya sumber kebenaran untuk "¿ini karya saya?".
     *
     * Dipakai halaman detail, form edit, dan hapus supaya satu tempat yang
     * menentukan apakah pengguna yang sedang login berhak mengelola materi ini.
     */
    public function dimilikiOleh(?int $idPengguna): bool
    {
        return $idPengguna !== null && (int) $this->dibuat_oleh === $idPengguna;
    }

    /**
     * Label status untuk kartu di "Karya Saya", supaya pemilik tahu
     * materinya masih tayang atau sedang disembunyikan.
     */
    public function labelStatus(): string
    {
        return $this->aktif ? 'Aktif' : 'Nonaktif';
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

    /**
     * Perkiraan jumlah bab untuk ditampilkan di kartu.
     *
     * Bab tidak disimpan sebagai tabel sendiri: form "Tambah Materi" menyusun
     * seluruh bab menjadi satu teks polos di kolom "isi" (lihat
     * resources/js/materi-tambah.js). Dua bentuk penanda itu yang dibaca di
     * sini:
     *
     *   "Bab 2: Mengenal Blade"  -> satu baris per bab, ditulis form
     *   "# Judul Seksi"          -> penanda seksi untuk materi yang diketik manual
     *
     * Materi tanpa penanda apa pun dihitung sebagai satu bab, jadi angkanya
     * tidak pernah nol.
     */
    public function jumlahBab(): int
    {
        $isi = (string) $this->isi;

        $dariForm = preg_match_all('/^\s*Bab\s+\d+\s*:.*$/mu', $isi);

        if ($dariForm > 0) {
            return $dariForm;
        }

        return max(1, preg_match_all('/^\s*#{1,2}\s+\S.*$/mu', $isi));
    }
}
