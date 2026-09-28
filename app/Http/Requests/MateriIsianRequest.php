<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Isian form materi: dipakai oleh halaman tambah materi dan form edit materi
 * milik sendiri, jadi aturannya hanya ditulis satu kali di sini.
 *
 * Kelolaan (apakah materi ini boleh diubah pemiliknya) tidak dicek di sini:
 * materi dicari dari route, jadi pemeriksaannya dilakukan di controller yang
 * sama-sama sudah memegang model-nya.
 */
class MateriIsianRequest extends FormRequest
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
            'pelajaran_id' => ['required', Rule::exists('tb_pelajaran', 'id')->where('aktif', true)],
            'nama' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:220'],
            'isi' => ['required', 'string', 'min:20'],
            'tingkat_kesulitan' => ['required', Rule::in(['Mudah', 'Sedang', 'Sulit'])],

            // Thumbnail & audio: maksimal 100MB per berkas, disimpan
            // ke disk publik sebagai path di kolom tb_materi.
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:102400'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg', 'max:102400'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pelajaran_id.required' => 'Pilih kategori materi terlebih dahulu.',
            'pelajaran_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'nama.required' => 'Judul materi wajib diisi.',
            'isi.required' => 'Isi materi wajib diisi.',
            'isi.min' => 'Isi materi minimal 20 karakter.',
            'tingkat_kesulitan.in' => 'Tingkat kesulitan tidak dikenal.',
            'thumbnail.file' => 'Thumbnail harus berupa file gambar.',
            'thumbnail.mimes' => 'Format thumbnail harus JPG, PNG, atau WEBP.',
            'thumbnail.max' => 'Ukuran thumbnail maksimal 100MB.',
            'audio.file' => 'Audio harus berupa file audio.',
            'audio.mimes' => 'Format audio harus MP3, WAV, M4A, atau OGG.',
            'audio.max' => 'Ukuran audio maksimal 100MB.',
        ];
    }
}
