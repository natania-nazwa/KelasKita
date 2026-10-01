<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi milik satu pengguna (tabel tb_notifikasi).
 *
 * Yang menulisnya hanya admin, lewat App\Support\NotifikasiKonten, setiap
 * kali ia menerbitkan materi atau quiz dari menu "Konten Pembelajaran".
 * Tidak ada notifikasi lain di aplikasi, jadi satu tabel ini adalah satu-
 * satunya sistem notifikasi — bukan tambahan kedua.
 *
 * Yang disimpan di sini bukan salinan isi konten, tapi koordinatnya: jenis
 * notifikasi, judul, pesan, dan tautan ke konten asalnya. Isi materi atau
 * quiz yang berubah karena diedit admin tidak perlu ikut diperbarui di sini,
 * karena yang dibaca pembaca tetap kontennya, bukan salinannya.
 */
#[Fillable([
    'pengguna_id',
    'jenis',
    'judul',
    'pesan',
    'konten_id',
    'konten_tipe',
    'dibaca_pada',
])]
class Notifikasi extends Model
{
    /** Materi baru diterbitkan admin. */
    public const JENIS_MATERI_BARU = 'materi_baru';

    /** Quiz baru diterbitkan admin. */
    public const JENIS_QUIZ_BARU = 'quiz_baru';

    /** Dua jenis yang pernah ada, untuk dropdown dan validasi. */
    public const JENIS_TERSEDIA = [
        self::JENIS_MATERI_BARU,
        self::JENIS_QUIZ_BARU,
    ];

    /** Nilai kolom konten_tipe untuk materi. */
    public const KONTEN_MATERI = 'materi';

    /** Nilai kolom konten_tipe untuk quiz. */
    public const KONTEN_QUIZ = 'quiz';

    /**
     * Nama tabel tidak mengikuti default Laravel ("notifications").
     *
     * Sengaja tabel sendiri, bukan tabel "notifications" bawaan Laravel:
     * nama kolomnya dibuat eksplisit (judul, pesan, konten) supaya lonceng
     * di topbar bisa merakit tampilan dari kolom biasa, bukan dari isi JSON
     * milik kelas notifikasi yang tidak ada di aplikasi ini.
     */
    protected $table = 'tb_notifikasi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    /**
     * Hanya notifikasi yang belum dibaca, untuk titik di lonceng.
     */
    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_pada');
    }

    /**
     * Notifikasi sudah dibaca atau belum.
     */
    public function sudahDibaca(): bool
    {
        return $this->dibaca_pada !== null;
    }

    /**
     * Tandai notifikasi ini sudah dibaca.
     *
     * Sudah dibaca tidak diubah lagi: notifikasi yang dibuka dua kali tidak
     * boleh memindahkan tanggal bacanya, karena urutan di daftar ditentukan
     * oleh kapan notifikasi itu dibuat, bukan kapan terakhir dibuka.
     */
    public function tandaiDibaca(): void
    {
        if ($this->dibaca_pada !== null) {
            return;
        }

        $this->forceFill(['dibaca_pada' => now()])->save();
    }

    /**
     * Model konten yang jadi sumber pesan, atau null kalau kontennya sudah
     * dihapus.
     *
     * Dipakai supaya lonceng notifikasi bisa menulis tautan yang benar tanpa
     * menebak nama route-nya.
     */
    public function konten(): ?Model
    {
        return match ($this->konten_tipe) {
            self::KONTEN_MATERI => Materi::query()->find($this->konten_id),
            self::KONTEN_QUIZ => Quiz::query()->find($this->konten_id),
            default => null,
        };
    }
}
