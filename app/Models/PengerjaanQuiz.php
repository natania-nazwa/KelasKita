<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu sesi pengerjaan quiz oleh seorang pengguna (tabel tb_pengerjaan_quiz).
 *
 * Tabel ini sudah ada sebelumnya, jadi fitur lobby memakainya apa adanya:
 * yang ditambahkan hanya kolom sesi_id, supaya pengerjaan tahu ia
 * bagian dari sesi mana yang sedang dijalankan. Jawaban tiap soal tetap
 * disimpan di tb_jawaban_quiz.
 */
#[Fillable([
    'sesi_id',
    'pengguna_id',
    'quiz_id',
    'jumlah_soal',
    'jumlah_dijawab',
    'jumlah_benar',
    'jumlah_salah',
    'nilai',
    'dimulai_pada',
    'selesai_pada',
])]
class PengerjaanQuiz extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("pengerjaan_quizzes").
     */
    protected $table = 'tb_pengerjaan_quiz';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_soal' => 'integer',
            'jumlah_dijawab' => 'integer',
            'jumlah_benar' => 'integer',
            'jumlah_salah' => 'integer',
            'nilai' => 'integer',
            'dimulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
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

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanQuiz::class, 'pengerjaan_quiz_id');
    }

    public function sudahSelesai(): bool
    {
        return $this->selesai_pada !== null;
    }

    /**
     * Nilai dalam persen 0-100, dihitung ulang dari jawaban yang tersimpan.
     *
     * Dihitung ulang (bukan ditambahkan) supaya jawaban yang diubah peserta
     * tidak membuat angka dobel.
     */
    public function hitungUlang(): void
    {
        $jawaban = $this->jawaban()->get();

        $benar = $jawaban->where('benar', true)->count();

        $this->jumlah_dijawab = $jawaban->count();
        $this->jumlah_benar = $benar;
        $this->jumlah_salah = $this->jumlah_dijawab - $benar;
        $this->nilai = $this->jumlah_soal > 0
            ? (int) round($benar / $this->jumlah_soal * 100)
            : 0;

        $this->save();
    }
}
