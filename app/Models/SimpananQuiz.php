<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quiz yang disimpan pengguna lewat tombol "Simpan" (tabel
 * tb_simpanan_quiz).
 *
 * Satu baris = satu pasangan pengguna + quiz, dijaga unique constraint
 * supaya pengguna tidak pernah menyimpan quiz yang sama dua kali.
 * Kembaran dari SimpananMateri supaya kedua ruang bookmark memakai
 * aturan yang sama.
 */
#[Fillable(['pengguna_id', 'quiz_id'])]
class SimpananQuiz extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("simpanan_quizzes").
     */
    protected $table = 'tb_simpanan_quiz';

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}
