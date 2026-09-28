<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz (tabel tb_quiz).
 *
 * Setiap quiz selalu punya satu mata pelajaran (kategori) dari tb_pelajaran
 * dan satu pembuat dari tb_pengguna. Soal-soalnya berada di tb_soal.
 *
 * Status quiz mengikuti alur persetujuan admin:
 *   draft     = baru dibuat, belum diajukan
 *   pending   = menunggu persetujuan admin
 *   published = sudah disetujui, tampil untuk semua pengguna
 *   rejected  = ditolak admin (lihat catatan_admin)
 */
#[Fillable([
    'dibuat_oleh',
    'pelajaran_id',
    'judul',
    'slug',
    'deskripsi',
    'durasi',
    'visibilitas',
    'status',
    'kode_akses',
    'catatan_admin',
    'dipublish_pada',
    'thumbnail',
])]
class Quiz extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const VISIBILITAS_PUBLIK = 'public';

    public const VISIBILITAS_PRIVAT = 'private';

    /**
     * Nama tabel tidak mengikuti default Laravel ("quizzes").
     */
    protected $table = 'tb_quiz';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dipublish_pada' => 'datetime',
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

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    /**
     * Quiz yang sudah disetujui admin, jadi aman tampil untuk semua pengguna.
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Quiz milik satu pengguna. Dipakai halaman "Karya Saya" supaya quiz
     * milik orang lain tidak pernah ikut tampil, dan supaya quiz yang masih
     * draft atau ditolak tetap bisa dikelola pemiliknya.
     */
    public function scopeMilik(Builder $query, ?int $idPembuat): Builder
    {
        return $query->where('dibuat_oleh', $idPembuat);
    }

    /**
     * Satu-satunya sumber kebenaran untuk "¿ini karya saya?".
     *
     * Dipakai halaman detail, form edit, dan hapus supaya satu tempat yang
     * menentukan apakah pengguna yang sedang login berhak mengelola quiz ini.
     */
    public function dimilikiOleh(?int $idPengguna): bool
    {
        return $idPengguna !== null && (int) $this->dibuat_oleh === $idPengguna;
    }

    /**
     * Pencarian quiz di halaman Quiz.
     *
     * Mencari di judul, deskripsi, dan nama mata pelajarannya. Karakter %
     * dan _ dari user di-escape supaya tidak jadi wildcard.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): Builder
    {
        $kataKunci = trim((string) $kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        /*
         * PostgreSQL membuat LIKE tidak peduli huruf besar-kecil. Tanpa
         * ILIKE, mengetik "html" tidak akan menemukan judul "HTML & CSS Dasar".
         */
        $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pola, $operator) {
            $query->where('judul', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola));
        });
    }

    /**
     * Jumlah soal aktif milik quiz ini.
     *
     * Dipakai untuk badge "10 Soal" pada kartu. Nilai hasil withCount()
     * disimpan di atribut "jumlah_soal_termuat" supaya query daftar tidak
     * perlu satu query tambahan per kartu.
     */
    public function jumlahSoal(): int
    {
        return (int) ($this->jumlah_soal_termuat ?? $this->soal()->aktif()->count());
    }

    /**
     * Label status untuk ditampilkan pada halaman detail dan kartu
     * "Karya Saya", supaya user tahu quiznya sudah tayang atau masih
     * menunggu admin.
     */
    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_PUBLISHED => 'Dipublikasikan',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Draft',
        };
    }
}
