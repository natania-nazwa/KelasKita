<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Soal yang ditandai "ragu" oleh peserta (tabel tb_soal_ragu).
 *
 * Satu baris = satu soal yang ingin peserta tinjau lagi. Tidak ada isinya
 * selain dua foreign key: yang ditandai adalah soal, bukan jawabannya, dan
 * jawabannya sendiri tetap hidup di tb_jawaban_quiz.
 *
 * Kuncinya adalah pengerjaan, bukan pengguna dan quiz: satu orang boleh
 * mengerjakan quiz yang sama beberapa kali, jadi tanda ragu dari percobaan
 * pertama tidak boleh ikut muncul di percobaan kedua.
 *
 * Sifatnya bebas dari penilaian: menandai ragu tidak menambah, mengurangi,
 * atau mengubah apa pun yang tersimpan di tb_jawaban_quiz.
 */
#[Fillable(['pengerjaan_quiz_id', 'soal_id'])]
class SoalRagu extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("soal_ragus").
     */
    protected $table = 'tb_soal_ragu';

    public function pengerjaan(): BelongsTo
    {
        return $this->belongsTo(PengerjaanQuiz::class, 'pengerjaan_quiz_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
