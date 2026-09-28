<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban satu soal dalam satu pengerjaan (tabel tb_jawaban_quiz).
 *
 * jawaban_benar ikut disimpan di sini (meski bisa diambil lagi dari
 * tb_soal) supaya lembar jawaban tidak berubah walaupun soalnya diedit
 * setelah peserta menjawab.
 */
#[Fillable([
    'pengerjaan_quiz_id',
    'soal_id',
    'jawaban_dipilih',
    'jawaban_benar',
    'benar',
    'dijawab_pada',
])]
class JawabanQuiz extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("jawaban_quizzes").
     */
    protected $table = 'tb_jawaban_quiz';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'benar' => 'boolean',
            'dijawab_pada' => 'datetime',
        ];
    }

    public function pengerjaan(): BelongsTo
    {
        return $this->belongsTo(PengerjaanQuiz::class, 'pengerjaan_quiz_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
