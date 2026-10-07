<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu kegiatan belajar pada satu hari (tabel tb_aktivitas_harian).
 *
 * Yang menulis baris di sini hanya App\Support\AktivitasHarian, dan hanya
 * dua pemanggilnya: membuka halaman baca materi, dan menyimpan jawaban soal.
 * Karena itu tabel ini tidak perlu mencatat jam, perangkat, atau halaman mana
 * yang dibuka — yang dibutuhkan cuma satu fakta per hari: "hari ini ia belajar".
 *
 * Satu baris per pengguna + tanggal + jenis, dijaga unique constraint di
 * database. Membaca materi yang sama sepuluh kali dalam sehari tetap satu
 * baris, jadi membuka halaman baca tidak pernah memakai tempat.
 */
#[Fillable([
    'pengguna_id',
    'tanggal',
    'jenis',
])]
class AktivitasHarian extends Model
{
    /** Membuka halaman baca materi. */
    public const JENIS_BACA_MATERI = 'baca_materi';

    /** Menyimpan jawaban soal quiz. */
    public const JENIS_KERJAKAN_QUIZ = 'kerjakan_quiz';

    /**
     * Nama tabel tidak mengikuti default Laravel ("aktivitas_harians").
     */
    protected $table = 'tb_aktivitas_harian';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    /**
     * Hanya baris milik satu pengguna, untuk menghitung streak-nya.
     */
    public function scopeMilik(Builder $query, ?int $idPengguna): Builder
    {
        return $query->when(
            $idPengguna !== null,
            fn (Builder $q) => $q->where('pengguna_id', $idPengguna)
        );
    }

    /**
     * Hanya baris yang tanggalnya sudah lewat, untuk memangkas baris lama.
     *
     * Batasnya dikirim sebagai Carbon, bukan teks Y-m-d, supaya bentuk
     * nilainya sama persis dengan yang ditulis kolom tanggal (lihat
     * catat() di App\Support\AktivitasHarian untuk alasannya).
     */
    public function scopeKedaluwarsa(Builder $query, Carbon $batas): Builder
    {
        return $query->where('tanggal', '<', $batas->copy()->startOfDay());
    }
}
