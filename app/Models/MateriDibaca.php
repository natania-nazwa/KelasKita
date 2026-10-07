<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Materi yang sudah dibuka dan dibaca pengguna (tabel tb_materi_dibaca).
 *
 * Kembaran dari SimpananMateri, tapi kalau tabel itu mencatat keputusan
 * pengguna sebelum membaca (bookmark), tabel ini mencatat fakta setelahnya:
 * halamannya benar-benar dibuka. Karena itu isinya dipakai untuk menghitung
 * progress belajar, sedangkan SimpananMateri tidak pernah boleh dipakai untuk
 * itu: orang yang menyimpan 20 materi tanpa membacaranya tidak akan terlihat
 * sebagai 20 materi selesai.
 *
 * Satu baris = satu pasangan pengguna + materi, dijaga unique constraint.
 * Membuka materi yang sama berkali-kali tetap satu baris, jadi halaman materi
 * tidak pernah menambah baris setiap kali direfresh.
 */
#[Fillable(['pengguna_id', 'materi_id'])]
class MateriDibaca extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("materi_dibacas").
     */
    protected $table = 'tb_materi_dibaca';

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }

    /**
     * Hanya baris milik satu pengguna.
     *
     * Dipakai bareng filter lain di pemanggilnya, jadi ditulis sebagai scope
     * supaya nama dan bentuknya sama dengan scope milik di model lain.
     */
    public function scopeMilik(Builder $query, ?int $idPengguna): Builder
    {
        return $query->when(
            $idPengguna !== null,
            fn (Builder $q) => $q->where('pengguna_id', $idPengguna)
        );
    }
}
