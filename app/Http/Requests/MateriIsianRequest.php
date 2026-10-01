<?php

namespace App\Http\Requests;

use App\Models\Materi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Isian form materi: dipakai oleh halaman tambah materi dan form edit materi
 * milik sendiri, jadi aturannya hanya ditulis satu kali di sini.
 *
 * Field "publikasikan" berarti "pemilik menekan tombol publikasi", bukan
 * "materi ini harus tayang". Nilainya belum tentu menghasilkan materi yang
 * tayang: controller menerjemahkannya jadi permintaan persetujuan, dan baru
 * admin yang bisa menyetujuinya.
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

            /*
             * Kelas tujuan, diisi dari form Konten Pembelajaran di area admin.
             *
             * Nullable dan tidak required: form milik pengguna tidak punya
             * field ini sama sekali, jadi materi yang dibuat pengguna tetap
             * boleh terbit tanpa kelas. Karena field-nya tidak dikirim form
             * itu, validated() juga tidak mengembalikannya dan kolomnya tidak
             * ikut tersentuh — konten yang sudah punya kelas pun tidak akan
             * kehilangan kelasnya karena diedit dari sisi pengguna.
             */
            'kelas' => ['nullable', 'string', 'max:60'],

            // Tombol publikasi di form tambah dan form edit.
            'publikasikan' => ['nullable', 'boolean'],

            /*
             * Catatan pendukung hanya diminta saat materi yang sudah pernah
             * ditolak diajukan ulang, supaya admin punya alasan menilai
             * apakah perbaikannya sudah cukup.
             */
            'catatan_pengajuan' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn (): bool => $this->diajukanUlang()),
            ],

            // Thumbnail: maksimal 100MB per berkas, disimpan ke disk
            // publik sebagai path di kolom tb_materi.
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:102400'],
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
            'kelas.max' => 'Nama kelas maksimal 60 karakter.',
            'catatan_pengajuan.required' => 'Tuliskan catatan pendukung supaya admin tahu apa yang sudah diperbaiki.',
            'catatan_pengajuan.max' => 'Catatan pengajuan maksimal 500 karakter.',
            'thumbnail.file' => 'Thumbnail harus berupa file gambar.',
            'thumbnail.mimes' => 'Format thumbnail harus JPG, PNG, atau WEBP.',
            'thumbnail.max' => 'Ukuran thumbnail maksimal 100MB.',
        ];
    }

    /**
     * Isian materi siap disimpan, tanpa field yang hanya controlling form.
     *
     * @return array<string, mixed>
     */
    public function isian(): array
    {
        $isian = $this->validated();

        unset($isian['publikasikan']);

        return $isian;
    }

    /**
     * Materi yang sedang diedit, diambil dari route.
     *
     * Hanya dipakai untuk tahu apakah materi ini pernah ditolak, supaya
     * catatan pengajuan jadi wajib atau tidak. Halaman tambah materi tidak
     * punya materi, jadi selalu mengembalikan null di sana.
     */
    private function materi(): ?Materi
    {
        $slug = $this->route('materi');

        return is_string($slug)
            ? Materi::query()->where('slug', $slug)->first()
            : null;
    }

    /**
     * Permintaan ini adalah pengajuan ulang: materi yang sudah pernah
     * ditolak, dan kali ini pemiliknya menekan tombol publikasi.
     */
    private function diajukanUlang(): bool
    {
        return $this->boolean('publikasikan') && (bool) $this->materi()?->perluCatatanPengajuan();
    }
}
