<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User dan admin berada di tabel yang sama (tb_pengguna).
 * Pembeda keduanya hanya kolom "peran".
 */
#[Fillable(['nama', 'email', 'kata_sandi', 'peran', 'aktif'])]
#[Hidden(['kata_sandi', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const PERAN_USER = 'user';

    public const PERAN_ADMIN = 'admin';

    /**
     * Nama tabel tidak mengikuti default Laravel ("users").
     */
    protected $table = 'tb_pengguna';

    /**
     * Kolom password yang dipakai Auth::attempt() saat login.
     * Default Laravel adalah "password", sedangkan tabel kita "kata_sandi".
     */
    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'kata_sandi' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Satu-satunya sumber kebenaran: nilai kolom "peran" di database.
     */
    public function isAdmin(): bool
    {
        return $this->peran === self::PERAN_ADMIN;
    }

    public function isAktif(): bool
    {
        return (bool) $this->aktif;
    }

    /**
     * Inisial untuk avatar. Maksimal dua huruf supaya bulatannya tetap
     * proporsional: "Natania" jadi "N", "Siti Aminah" jadi "SA".
     */
    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim((string) $this->nama)) ?: [];
        $kata = array_values(array_filter($kata, 'strlen'));

        if ($kata === []) {
            return '?';
        }

        if (count($kata) === 1) {
            return mb_strtoupper(mb_substr($kata[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($kata[0], 0, 1).mb_substr($kata[count($kata) - 1], 0, 1));
    }

    /**
     * Warna avatar diturunkan dari nama, jadi user yang sama selalu punya
     * warna yang sama di seluruh aplikasi tanpa perlu kolom avatar.
     *
     * @return array{warna: string, warna_gelap: string}
     */
    public function warnaAvatar(): array
    {
        $palet = [
            ['#8b7bf0', '#5b46cf'],
            ['#f78299', '#d63a63'],
            ['#4fd0e0', '#0e8ba0'],
            ['#5fd6ae', '#12946f'],
            ['#f6cd6b', '#b8830c'],
            ['#7cc0f7', '#2b81cf'],
            ['#f492d3', '#cc3f9c'],
            ['#f9a86b', '#cf671c'],
        ];

        [$warna, $warnaGelap] = $palet[abs(crc32((string) $this->nama)) % count($palet)];

        return [
            'warna' => $warna,
            'warna_gelap' => $warnaGelap,
        ];
    }
}
