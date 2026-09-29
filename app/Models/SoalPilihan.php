<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pilihan jawaban milik sebuah soal (tabel tb_soal_pilihan).
 *
 * Dulu pilihan jawaban disimpan sebagai kolom pilihan_a sampai pilihan_f di
 * tb_soal. Sekarang jadi baris, karena tipe multiple_select boleh punya lebih
 * dari satu jawaban benar dan builder mengizinkan pilihan ditambah sampai
 * sepuluh — dua hal yang tidak muat di kolom varchar.
 *
 * Kolom pilihan_a sampai pilihan_f di tb_soal sengaja dibiarkan dan tetap
 * diisi empat pilihan pertama (lihat App\Support\SimpanSoalQuiz), supaya
 * soal lama serta pembaca lama tidak ikut rusak.
 */
#[Fillable([
    'soal_id',
    'huruf',
    'teks',
    'urutan',
    'benar',
])]
class SoalPilihan extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("soal_pilihan" → plural).
     */
    protected $table = 'tb_soal_pilihan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'benar' => 'boolean',
        ];
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
