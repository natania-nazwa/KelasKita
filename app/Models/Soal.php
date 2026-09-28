<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu soal pilihan ganda milik sebuah quiz (tabel tb_soal).
 *
 * Kolom pilihan_a sampai pilihan_d menyimpan teks jawabannya, sedangkan
 * jawaban_benar hanya menyimpan huruf yang benar (A / B / C / D).
 */
#[Fillable([
    'quiz_id',
    'pertanyaan',
    'pilihan_a',
    'pilihan_b',
    'pilihan_c',
    'pilihan_d',
    'jawaban_benar',
    'pembahasan',
    'urutan',
    'tingkat_kesulitan',
    'aktif',
])]
class Soal extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("soals").
     */
    protected $table = 'tb_soal';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Soal yang aman ditampilkan ke user.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Urutan tampilan soal di halaman mengerjakan quiz.
     */
    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('id');
    }

    /**
     * Keempat pilihan jawaban dalam bentuk ['A' => ..., 'B' => ...],
     * supaya komponen tampilan cukup melakukan satu perulangan.
     *
     * @return array<string, string>
     */
    public function pilihan(): array
    {
        return [
            'A' => (string) $this->pilihan_a,
            'B' => (string) $this->pilihan_b,
            'C' => (string) $this->pilihan_c,
            'D' => (string) $this->pilihan_d,
        ];
    }
}
