<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Materi yang disimpan pengguna lewat tombol "Simpan" (tabel
 * tb_simpanan_materi).
 *
 * Satu baris = satu pasangan pengguna + materi, dijaga unique constraint
 * supaya pengguna tidak pernah menyimpan materi yang sama dua kali.
 */
#[Fillable(['pengguna_id', 'materi_id'])]
class SimpananMateri extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("simpanan_materis").
     */
    protected $table = 'tb_simpanan_materi';

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }
}
