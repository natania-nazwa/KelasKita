<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kali login yang berhasil (tabel "tb_riwayat_login").
 *
 * Satu baris = satu kali masuk, bukan satu orang. Orang yang login
 * lima kali punya lima baris, dan itulah yang dihitung grafik
 * "Aktivitas Login Mingguan" di dashboard admin.
 *
 * Baris ini hanya dibaca untuk statistik, jadi tidak adaupdated_at
 * yang dipakai: yang penting kapan login-nya terjadi.
 */
#[Fillable(['pengguna_id'])]
class RiwayatLogin extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("riwayat_logins").
     */
    protected $table = 'tb_riwayat_login';

    /**
     * Catat satu login berhasil untuk seorang pengguna.
     *
     * Dipanggil dari AuthenticatedSessionController tepat setelah
     * kredensial diterima, bukan dari halaman yang dibuka setelahnya:
     * kalau dicatat dari halaman tujuan, orang yang langsung menekan
     * "Keluar" tidak akan pernah masuk ke hitungan.
     */
    public static function catat(User $pengguna): self
    {
        return static::query()->create([
            'pengguna_id' => $pengguna->getKey(),
        ]);
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
