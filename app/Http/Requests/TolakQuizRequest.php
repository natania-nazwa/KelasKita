<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alasan admin saat menolak quiz.
 *
 * Hanya satu field, tapi aturannya tetap ditulis di satu tempat: alasan ini
 * dibaca kembali oleh pemilik di "Karya Saya", dan jadi dasar peninjauan
 * berikutnya kalau quiz itu diajukan ulang dengan catatan pendukung.
 */
class TolakQuizRequest extends FormRequest
{
    /** Panjang maksimal alasan, sama dengan textarea di halaman peninjauan. */
    public const MAKSIMAL_ALASAN = 500;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'max:'.self::MAKSIMAL_ALASAN],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Tuliskan alasan penolakan supaya pemilik tahu apa yang harus diperbaiki.',
            'alasan.max' => 'Alasan penolakan maksimal '.self::MAKSIMAL_ALASAN.' karakter.',
        ];
    }
}
