<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Isian form ubah password.
 *
 * Tiga aturan dari spesifikasi form, masing-masing ditulis sebagai aturan
 * Laravel supaya pesannya muncul otomatis di bawah field yang salah:
 *   1. Password lama wajib diisi, dan harus benar-benar password akun ini.
 *   2. Password baru wajib diisi dan minimal 8 karakter.
 *   3. Konfirmasi harus sama persis dengan password baru.
 *
 * "current_password" aman dipakai di sini: aturan itu mengecek hash lewat
 * User::getAuthPassword(), sehingga ikut mengikuti nama kolom "kata_sandi"
 * milik project ini.
 */
class KataSandiRequest extends FormRequest
{
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
            'kata_sandi_lama' => ['required', 'string', 'current_password'],
            'kata_sandi_baru' => ['required', 'string', 'min:8', 'confirmed:kata_sandi_baru_konfirmasi'],
            'kata_sandi_baru_konfirmasi' => ['required', 'string', 'same:kata_sandi_baru'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kata_sandi_lama.required' => 'Password lama wajib diisi.',
            'kata_sandi_lama.current_password' => 'Password lama tidak cocok dengan password akunmu.',
            'kata_sandi_baru.required' => 'Password baru wajib diisi.',
            'kata_sandi_baru.min' => 'Password baru minimal 8 karakter.',
            'kata_sandi_baru.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
            'kata_sandi_baru_konfirmasi.required' => 'Konfirmasi password baru wajib diisi.',
            'kata_sandi_baru_konfirmasi.same' => 'Konfirmasi password tidak sama dengan password baru.',
        ];
    }
}
