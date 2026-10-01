<?php

namespace App\Http\Requests;

use App\Models\Preferensi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Isian form pengaturan publikasi.
 *
 * Dua isian, keduanya soal perilaku admin saat mengelola kontennya sendiri:
 *
 *   - status_konten_default = status yang dipakai kalau admin tidak memilih
 *     status secara eksplisit. Untuk KelasKita bawaannya "draft", supaya ada
 *     kesempatan memeriksa isi sebelum tayang.
 *   - konfirmasi_publikasi   = tampilkan dialog konfirmasi sebelum publish.
 *     Saklarnya memakai field tersembunyi dengan nilai "0", karena checkbox
 *     yang tidak dicentang tidak pernah terkirim.
 *
 * Hanya "draft" dan "published" yang diterima. Tidak ada status "menunggu",
 * tidak ada "ditolak", tidak ada persetujuan: aplikasi ini punya satu admin
 * pengelola, jadi admin menerbitkan karyanya sendiri secara langsung.
 */
class PreferensiPublikasiRequest extends FormRequest
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
            'status_konten_default' => ['required', 'string', Rule::in(Preferensi::STATUS_KONTEN)],
            'konfirmasi_publikasi' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_konten_default.required' => 'Status default konten baru wajib dipilih.',
            'status_konten_default.in' => 'Status default konten harus Draft atau Published.',
            'konfirmasi_publikasi.required' => 'Pengaturan konfirmasi publish wajib diisi.',
            'konfirmasi_publikasi.boolean' => 'Nilai pengaturan konfirmasi publish tidak valid.',
        ];
    }

    /**
     * Nilai siap tulis untuk kolom preferensi.
     *
     * @return array<string, bool|string>
     */
    public function nilai(): array
    {
        return [
            'status_konten_default' => (string) $this->input('status_konten_default'),
            'konfirmasi_publikasi' => $this->boolean('konfirmasi_publikasi'),
        ];
    }
}
