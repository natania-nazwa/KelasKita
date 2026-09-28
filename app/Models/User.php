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
#[Fillable(['nama', 'email', 'kata_sandi', 'peran', 'aktif', 'foto_profil'])]
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
     * Folder tempat foto profil disimpan di disk publik.
     *
     * Dipakai User::fotoProfilUrl() dan App\Support\BerkasProfil supaya
     * nama foldernya hanya tertulis di satu tempat.
     */
    public const FOLDER_FOTO_PROFIL = 'foto-profil';

    /**
     * Inisial untuk avatar: satu huruf pertama nama depan.
     *
     * "Natania Nazwa Gisella" jadi "N", "Budi Santoso" jadi "B". Satu huruf
     * dipakai supaya bulatannya proporsional di ukuran avatar mana pun,
     * termasuk yang besar di header profil.
     */
    public function inisial(): string
    {
        $nama = trim((string) $this->nama);

        if ($nama === '') {
            return '?';
        }

        // Satu huruf pertama dari kata pertama, bukan satu karakter pertama
        // dari nama: "  Budi" dan "Budi" harus sama-sama jadi "B".
        $kataPertama = preg_split('/\s+/', $nama)[0] ?? '';

        return $kataPertama === ''
            ? '?'
            : mb_strtoupper(mb_substr($kataPertama, 0, 1));
    }

    /**
     * URL foto profil, atau null kalau pengguna belum memasang foto.
     *
     * Null inilah sinyal untuk memakai avatar inisial. Jangan pernah
     * mengembalikan gambar placeholder: kalau tidak ada foto, pemanggil
     * harus menggambar inisial.
     */
    public function fotoProfilUrl(): ?string
    {
        if (blank($this->foto_profil)) {
            return null;
        }

        return asset('storage/'.$this->foto_profil);
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
