<?php

namespace App\Http\Requests;

use App\Models\Materi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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
            'pelajaran_id' => ['required', $this->rulePelajaran()],
            'nama' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:220'],
            'isi' => ['required', 'string', 'min:20'],
            'tingkat_kesulitan' => ['required', Rule::in(['Mudah', 'Sedang', 'Sulit'])],

            /*
             * Isian pendukung yang ditampilkan di form tapi sebelumnya tidak
             * pernah tersimpan: batasnya mengikuti maxlength di form (40 dan
             * 500), dan keduanya tidak diwajibkan supaya pengajuan lewat
             * berkas yang tidak mengisinya tetap diterima.
             */
            'estimasi_waktu' => ['nullable', 'string', 'max:40'],
            'tips' => ['nullable', 'string', 'max:500'],

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
            'estimasi_waktu.max' => 'Estimasi waktu belajar maksimal 40 karakter.',
            'tips.max' => 'Tips / catatan maksimal 500 karakter.',
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
     * Aturan kategori materi.
     *
     * Kategori yang sudah tidak aktif tetap diterima selama nilainya persis
     * sama dengan yang sudah tersimpan. Menonaktifkan kategori di Pengaturan
     * tidak boleh membuat materi terbit kehilangan rumahnya lalu berhenti
     * bisa disimpan; kategori lain tetap harus kategori yang aktif.
     */
    private function rulePelajaran(): Exists
    {
        $tersimpan = $this->materi()?->pelajaran_id;

        $tetapSama = $tersimpan !== null
            && (string) $tersimpan === (string) $this->input('pelajaran_id');

        $aturan = Rule::exists('tb_pelajaran', 'id');

        return $tetapSama ? $aturan : $aturan->where('aktif', true);
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
