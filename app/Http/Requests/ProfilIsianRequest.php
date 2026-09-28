<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Isian form edit profil: nama, email, dan foto profil.
 *
 * Satu form untuk dua kemungkinan sumber isian: tombol "Simpan Perubahan"
 * pada dialog edit profil, dan tombol "Ganti Foto" di dalam dialog itu
 * sendiri. Karena keduanya mengirim field yang sama, validasinya juga
 * sama dan tidak ditulis dua kali.
 */
class ProfilIsianRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                /*
                 * Email unik di database. Dir loosened dari akun sendiri,
                 * jadi menyimpan form tanpa mengubah email tidak dianggap
                 * bentrok dengan email yang sekarang dipakai.
                 */
                Rule::unique('tb_pengguna', 'email')->ignore($this->user()->getKey()),
            ],
            'foto_profil' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.max' => 'Nama lengkap maksimal 120 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'foto_profil.file' => 'Foto profil harus berupa file gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, PNG, atau WEBP.',
            'foto_profil.max' => 'Ukuran foto profil maksimal 2MB.',
        ];
    }
}
