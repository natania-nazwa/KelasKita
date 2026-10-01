<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Isian form pengaturan notifikasi admin.
 *
 * Saklarnya dikirim sebagai kolom HTML, jadi checkbox yang tidak dicentang
 * tidak ikut terkirim sama sekali. Karena itu setiap saklar di form memakai
 * field tersembunyi dengan nilai "0" tepat sebelumnya: yang dicentang mengirim
 * "1", yang tidak dicentang mengirim "0". Tanpa field tersembunyi itu,
 * mematikan saklar akan berarti field-nya hilang dan nilainya tidak pernah
 * berubah.
 *
 * Aturan "required" dipakai, bukan "nullable", supaya form yang kehilangan
 * field dianggap salah dan tidak diam-diam menyimpan nilai bawaan.
 */
class PreferensiNotifikasiRequest extends FormRequest
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
            'notifikasi_konten_terbit' => ['required', 'boolean'],
            'notifikasi_konten_draft' => ['required', 'boolean'],
            'notifikasi_aktivitas_kuis' => ['required', 'boolean'],
            'notifikasi_aktivitas_konten' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notifikasi_konten_terbit.required' => 'Pengaturan notifikasi konten terbit wajib diisi.',
            'notifikasi_konten_terbit.boolean' => 'Nilai pengaturan notifikasi konten terbit tidak valid.',
            'notifikasi_konten_draft.required' => 'Pengaturan notifikasi konten draft wajib diisi.',
            'notifikasi_konten_draft.boolean' => 'Nilai pengaturan notifikasi konten draft tidak valid.',
            'notifikasi_aktivitas_kuis.required' => 'Pengaturan notifikasi hasil kuis wajib diisi.',
            'notifikasi_aktivitas_kuis.boolean' => 'Nilai pengaturan notifikasi hasil kuis tidak valid.',
            'notifikasi_aktivitas_konten.required' => 'Pengaturan notifikasi konten menunggu wajib diisi.',
            'notifikasi_aktivitas_konten.boolean' => 'Nilai pengaturan notifikasi konten menunggu tidak valid.',
        ];
    }

    /**
     * Nilai siap tulis untuk kolom preferensi, sudah berupa boolean.
     *
     * Hanya empat kolom notifikasi yang ikut, bukan kolom tema maupun kolom
     * publikasi: form ini tidak boleh bisa mengubah hal yang tidak ada di
     * dalamnya.
     *
     * @return array<string, bool>
     */
    public function nilai(): array
    {
        return [
            'notifikasi_konten_terbit' => $this->boolean('notifikasi_konten_terbit'),
            'notifikasi_konten_draft' => $this->boolean('notifikasi_konten_draft'),
            'notifikasi_aktivitas_kuis' => $this->boolean('notifikasi_aktivitas_kuis'),
            'notifikasi_aktivitas_konten' => $this->boolean('notifikasi_aktivitas_konten'),
        ];
    }
}
