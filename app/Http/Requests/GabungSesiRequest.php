<?php

namespace App\Http\Requests;

use App\Models\SesiQuiz;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Isian form "Masukkan Kode" di dashboard.
 *
 * Validasi yang paling penting di sini adalah format kode: peserta hanya
 * boleh mengetik huruf dan angka, maksimal sepuluh karakter, karena itulah
 * panjang kolom kode di tb_sesi_quiz. Pesan kesalahannya ditulis dalam
 * bahasa peserta ("Kode quiz tidak ditemukan") supaya enak dibaca.
 *
 * Pemeriksaan apakah sesi-nya ada, sudah dibuka, sudah selesai, atau
 * pemengguna sudah bergabung dilakukan di controller, karena butuh
 * melihat isi database.
 */
class GabungSesiRequest extends FormRequest
{
    /** Panjang maksimum sesuai kolom "kode" di tb_sesi_quiz. */
    public const PANJANG_KODE = 10;

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
            'kode' => ['required', 'string', 'max:'.self::PANJANG_KODE, 'regex:/^[A-Za-z0-9]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode quiz wajib diisi.',
            'kode.max' => 'Kode quiz maksimal '.self::PANJANG_KODE.' karakter.',
            'kode.regex' => 'Kode quiz hanya boleh berisi huruf dan angka.',
        ];
    }

    /**
     * Kode dalam bentuk baku: huruf besar tanpa spasi dan tanda hubung.
     */
    public function kode(): string
    {
        return SesiQuiz::normalisasiKode($this->string('kode')->toString());
    }
}
