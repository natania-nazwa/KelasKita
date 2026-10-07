<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Isian form konfirmasi hapus akun.
 *
 * Satu aturan saja, tapi memang wajib: password akun ini sendiri harus
 * dikirim dan benar. Menghapus akun menghapus semua karyanya, hasil belajarnya,
 * dan seluruh Riwayat Aktivitas tanpa bisa dibatalkan, jadi satu klik tak
 * sengaja di halaman mana pun tidak boleh cukup untuk menjalankannya.
 *
 * "current_password" aman dipakai di sini: aturan itu mengecek hash lewat
 * User::getAuthPassword(), sehingga ikut mengikuti nama kolom "kata_sandi"
 * milik project ini. Kalau rules ini gagal, FormRequest sudah membatalkan
 * seluruh request sebelum ProfilController::hapus() sempat berjalan, sehingga
 * akun dan berkasnya tidak ada yang tersentuh.
 *
 * Kalau aturan ini gagal, FormRequest sudah membatalkan seluruh request
 * sebelum ProfilController::hapus() sempat jalan, jadi halaman Profil cukup
 * menampilkan @error('kata_sandi') di dalam dialog.
 */
class HapusAkunRequest extends FormRequest
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
            'kata_sandi' => ['required', 'string', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kata_sandi.required' => 'Masukkan password akunmu untuk melanjutkan.',
            'kata_sandi.current_password' => 'Password tidak cocok dengan password akunmu.',
        ];
    }
}
