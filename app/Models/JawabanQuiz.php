<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban satu soal dalam satu pengerjaan (tabel tb_jawaban_quiz).
 *
 * Satu baris menampung lima tipe soal, dan tiap tipe menaruh isinya di
 * kolom yang berbeda supaya tidak ada yang dipaksa masuk ke kolom yang
 * salah bentuknya:
 *
 *   - multiple_choice, dropdown
 *       satu huruf di jawaban_dipilih, mis. "A".
 *   - multiple_select
 *       lebih dari satu huruf, digabung tanpa pemisah supaya muat di
 *       varchar(16) walau sepuluh pilihan semuanya dicentang: "ACE".
 *   - short_answer, paragraph
 *       jawaban teks peserta ada di jawaban_teks, dan jawaban_dipilih
 *       dibiarkan kosong. Kuncinya tidak ikut disimpan di sini supaya
 *       lembar jawaban tidak berubah begitu kunci soal diedit.
 *
 * jawaban_benar ikut disimpan di sini (meski bisa diambil lagi dari
 * tb_soal) supaya lembar jawaban tidak berubah walaupun soalnya diedit
 * setelah peserta menjawab. Untuk tipe multiple_select kolom varchar(1)
 * ini hanya muat satu huruf, jadi isinya huruf benar yang pertama.
 */
#[Fillable([
    'pengerjaan_quiz_id',
    'soal_id',
    'jawaban_dipilih',
    'jawaban_teks',
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

    /**
     * Huruf yang dicentang peserta, untuk tipe yang memakai pilihan.
     *
     * Hasilnya sudah terurut dan tanpa duplikat, jadi perbandingan dengan
     * kunci jawaban bisa dilakukan sebagai himpunan, bukan sebagai string
     * yang urutannya ikut berpengaruh.
     *
     * @return array<int, string>
     */
    public function hurufDipilih(): array
    {
        $huruf = preg_split('//u', (string) $this->jawaban_dipilih, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $unik = array_values(array_unique(array_filter(
            $huruf,
            fn ($satu) => in_array($satu, Soal::hurufTersedia(), true)
        )));

        sort($unik);

        return $unik;
    }

    /**
     * Jawaban teks peserta, untuk tipe short_answer dan paragraph.
     */
    public function teks(): string
    {
        return (string) $this->jawaban_teks;
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
