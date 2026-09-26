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
}
