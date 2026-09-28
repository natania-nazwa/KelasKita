<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Peserta yang sudah mengetik kode sesi dan masuk ke lobby
 * (tabel tb_peserta_quiz).
 *
 * Yang disimpan adalah pengguna_id, bukan nama. Nama peserta diambil dari
 * tb_pengguna saat lobby dibuka, jadi kalau nama peserta diganti di
 * datanya, daftar di lobby ikut berubah tanpa perlu diperbarui di sini.
 *
 * Status peserta mengikuti perjalanannya di sesi ini:
 *   lobby       = sudah masuk lobby, belum mengerjakan soal
 *   mengerjakan = sedang mengerjakan soal
 *   selesai     = sudah menekan "Selesai" dan melihat hasil
 */
#[Fillable(['sesi_id', 'pengguna_id', 'status', 'bergabung_pada'])]
class PesertaQuiz extends Model
{
    /** Sudah masuk lobby, belum mulai mengerjakan soal. */
    public const STATUS_LOBBY = 'lobby';

    /** Sedang mengerjakan soal. */
    public const STATUS_MENGERJAKAN = 'mengerjakan';

    /** Sudah selesai dan melihat hasil. */
    public const STATUS_SELESAI = 'selesai';

    /**
     * Nama tabel tidak mengikuti default Laravel ("peserta_quizzes").
     */
    protected $table = 'tb_peserta_quiz';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bergabung_pada' => 'datetime',
        ];
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiQuiz::class, 'sesi_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
