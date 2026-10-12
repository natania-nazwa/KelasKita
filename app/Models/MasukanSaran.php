<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Masukan dan saran yang dikirim pengguna dari halaman "Masukan & Saran".
 *
 * Tabelnya "tb_masukan_saran" dan foreign key-nya "pengguna_id", ditulis
 * langsung karena tebakan default untuk model bernama User adalah "user_id"
 * — sama seperti relasi Preferensi dan PengerjaanQuiz di User.
 *
 * Baris ini hanya ditulis, tidak pernah dibaca kembali di dalam aplikasi.
 * Halamannya sengaja tanpa daftar dan riwayat, jadi modelnya pun tidak punya
 * scope atau accessor tambahan.
 */
#[Fillable(['pengguna_id', 'judul', 'detail', 'lampiran'])]
class MasukanSaran extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("masukan_sarans").
     */
    protected $table = 'tb_masukan_saran';

    /**
     * Pengguna yang mengirim masukan ini.
     *
     * Foreign key-nya ditulis langsung sebagai "pengguna_id"; tanpa itu,
     * tebakan default untuk model bernama User adalah "user_id" dan relasi
     * ini gagal dengan "column ... user_id does not exist".
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
