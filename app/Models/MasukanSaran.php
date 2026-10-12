<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Masukan dan saran yang dikirim pengguna dari halaman "Masukan & Saran".
 *
 * Tabelnya "tb_masukan_saran" dan foreign key-nya "pengguna_id", ditulis
 * langsung karena tebakan default untuk model bernama User adalah "user_id"
 * — sama seperti relasi Preferensi dan PengerjaanQuiz di User.
 *
 * Pengirimnya hanya menulis baris ini; yang membacanya kembali adalah admin
 * lewat halaman "Masukan & Saran" di area admin. Di sana admin juga
 * memperbarui kolom "status" menandai kiriman mana yang masih baru, sedang
 * diproses, atau sudah selesai ditangani.
 */
#[Fillable(['pengguna_id', 'judul', 'detail', 'lampiran', 'status'])]
class MasukanSaran extends Model
{
    /**
     * Status tindak lanjut: belum ditangani.
     */
    public const STATUS_BARU = 'baru';

    /**
     * Status tindak lanjut: sedang ditangani.
     */
    public const STATUS_DIPROSES = 'diproses';

    /**
     * Status tindak lanjut: sudah selesai ditangani.
     */
    public const STATUS_SELESAI = 'selesai';

    /**
     * Semua status yang sah, urut dari yang paling baru dibuat.
     *
     * Dipakai controller untuk membatasi nilai yang diterima dan untuk
     * menyusun tab filter, jadi tidak ada nilai status yang bisa masuk akal
     * tapi tidak ada di sini.
     *
     * @var array<int, string>
     */
    public const STATUS = [self::STATUS_BARU, self::STATUS_DIPROSES, self::STATUS_SELESAI];

    /**
     * Nama tabel tidak mengikuti default Laravel ("masukan_sarans").
     */
    protected $table = 'tb_masukan_saran';

    /**
     * Label status untuk tampilan, mis. "Sudah Ditangani".
     */
    public static function labelStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_DIPROSES => 'Diproses',
            self::STATUS_SELESAI => 'Sudah Ditangani',
            default => 'Baru',
        };
    }

    /**
     * Nada warna lencana status, mengikuti varian x-admin.lencana.
     */
    public static function nadaStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_DIPROSES => 'info',
            self::STATUS_SELESAI => 'sukses',
            default => 'abu',
        };
    }

    /**
     * Kiriman yang belum ditindaklanjuti admin.
     *
     * Inilah "belum dibaca": selama admin belum mengubah statusnya dari
     * "baru", kiriman itu masih ikut menghitung. Dipakai badge sidebar admin
     * supaya jumlahnya dihitung dari satu tempat, sama seperti
     * Materi::scopeMenunggu() untuk badge "Verifikasi".
     */
    public function scopeBaru(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BARU);
    }

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
