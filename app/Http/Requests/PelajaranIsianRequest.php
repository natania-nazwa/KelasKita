<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Isian form tambah / ubah mata pelajaran, dari halaman "Kelola Mata
 * Pelajaran" di area admin.
 *
 * Yang divalidasi di sini adalah baris tb_pelajaran: nama, kode (slug), dan
 * status aktif. Materi dan quiz yang sudah memakai pelajaran itu tidak ikut
 * disentuh oleh form ini; untuk menyembunyikan sebuah mata pelajaran dari
 * pilihan, admin menonaktifkannya, bukan menghapus barisnya.
 *
 *Slug unik dijaga karena kolomnya punya batasan unik di database dan dipakai
 * sebagai URL filter di halaman Materi, Quiz, dan jadwal.
 */
class PelajaranIsianRequest extends FormRequest
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
        $id = $this->route('pelajaran')?->getKey();
        $baru = $this->isMethod('POST');

        return [
            'nama' => ['required', 'string', 'max:255'],
            /*
             * Slug wajib diisi hanya saat menambah. Saat mengubah, kolomnya
             * sengaja tidak ada di form: slug sudah dipakai di URL filter, jadi
             * membiarkannya ikut nama akan mematikan tautan lama tanpa
             * disengaja. Baris yang slug-nya kosong di form ubah berarti
             "jangan sentuh slug".
             */
            'slug' => [
                $baru ? 'required' : 'nullable',
                'string',
                'max:255',
                /*
                 * Bentuk slug sama persis dengan yang boleh ditulis model lain
                 * di project ini: huruf kecil, angka, tanda hubung. Tanpa ini
                 * slug bisa berisi spasi, yang menjadi URL yang merepotkan dan
                 * tidak cocok dengan filter yang sudah ada.
                 */
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tb_pelajaran', 'slug')->ignore($id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama mata pelajaran wajib diisi.',
            'nama.max' => 'Nama mata pelajaran maksimal 255 karakter.',
            'slug.required' => 'Kode mata pelajaran wajib diisi.',
            'slug.regex' => 'Kode mata pelajaran hanya boleh huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Kode mata pelajaran ini sudah dipakai.',
            'deskripsi.max' => 'Deskripsi maksimal 1000 karakter.',
            'aktif.required' => 'Status mata pelajaran wajib diisi.',
            'aktif.boolean' => 'Status mata pelajaran tidak valid.',
        ];
    }

    /**
     * Nilai siap tulis, sudah berupa boolean untuk kolom aktif.
     *
     * Slug yang tidak dikirim berarti "jangan ubah slug yang sekarang", bukan
     * "kosongkan slug". Karena itu isian kosong tidak ikut dikembalikan.
     *
     * @return array<string, mixed>
     */
    public function nilai(): array
    {
        $nilai = [
            'nama' => (string) $this->input('nama'),
            'deskripsi' => filled($this->input('deskripsi'))
                ? (string) $this->input('deskripsi')
                : null,
            'aktif' => $this->boolean('aktif'),
        ];

        if (filled($this->input('slug'))) {
            $nilai['slug'] = (string) $this->input('slug');
        }

        return $nilai;
    }
}
