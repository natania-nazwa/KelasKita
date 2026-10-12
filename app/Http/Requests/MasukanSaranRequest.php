<?php

namespace App\Http\Requests;

use App\Support\BerkasMasukan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Isian form "Masukan & Saran": satu-satunya jalur yang menulis baris ke
 * tb_masukan_saran.
 *
 * Judul dan detail wajib diisi karena masukan tanpa keduanya tidak bisa
 * ditindaklanjuti. Lampiran opsional dan boleh berjenis gambar atau PDF,
 * jadi lampiran itu memang bukti/tangkapan layar, bukan sekadar gambar.
 *
 * Pemilik baris tidak pernah dibaca dari form: ia diisi controller dari
 * sesi login (lihat User\MasukanSaranController), supaya isian "pengguna_id"
 * palsu tidak bisa dipakai mengirim masukan atas nama orang lain.
 */
class MasukanSaranRequest extends FormRequest
{
    /**
     * Batas ukuran lampiran: 5MB, ditulis dalam KB karena itu satuan
     * yang dipakai rule max untuk berkas.
     */
    public const MAKSIMAL_LAMPIRAN = 5120;

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
            'judul' => ['required', 'string', 'max:150'],
            'detail' => ['required', 'string', 'max:500'],
            'lampiran' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:'.self::MAKSIMAL_LAMPIRAN,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Tulis dulu judul masukan atau saranmu.',
            'judul.max' => 'Judul maksimal 150 karakter.',
            'detail.required' => 'Tulis dulu isi masukan atau saranmu.',
            'detail.max' => 'Isi masukan atau saran maksimal 500 karakter.',
            'lampiran.file' => 'Lampiran harus berupa berkas.',
            'lampiran.mimes' => 'Lampiran harus berformat JPG, PNG, atau PDF.',
            'lampiran.max' => 'Ukuran lampiran maksimal 5MB.',
        ];
    }

    /**
     * Isian masukan siap disimpan.
     *
     * Tepi judul dan detail dirapikan supaya spasi tak sengaja di ujung
     * tidak ikut tersimpan. Lampiran disimpan lewat App\Support\BerkasMasukan
     * dan selalu ikut dikembalikan — null kalau tidak ada berkas — karena
     * kolomnya memang satu-satunya penanda keberadaan lampiran.
     *
     * @return array<string, mixed>
     */
    public function isian(): array
    {
        return [
            'judul' => trim($this->string('judul')->toString()),
            'detail' => trim($this->string('detail')->toString()),
            'lampiran' => BerkasMasukan::simpan($this),
        ];
    }
}
