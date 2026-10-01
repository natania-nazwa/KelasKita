<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preferensi satu pengguna (tabel tb_preferensi).
 *
 * Berisi dua kelompok hal yang memang satu akun: preferensi tampilan dan
 * perilaku admin yang hanya dibaca halaman Pengaturan serta pemanggil yang
 * membuat notifikasi admin.
 *
 * Tabelnya terpisah dari tb_pengguna, bukan menambah kolom di sana, karena
 * yang disimpan di sini bukan bagian dari identitas akun. Mengganti nama atau
 * email tidak boleh ikut mengubah atau menghapus preferensinya.
 *
 * Baris preferensi tidak dibuat saat model User dimuat, melainkan justru
 * ketika pertama kali dibutuhkan lewat ambil(). Jadi membaca profil pengguna
 * tidak pernah menyisakan baris untuk pengguna yang tidak pernah membuka
 * Pengaturan.
 */
#[Fillable([
    // Wajib: ambil() membuat baris lewat firstOrCreate(['pengguna_id' => …]).
    // Foreign key yang tidak ada di daftar ini akan dibuang diam-diam, dan
    // insert-nya jadi gagal dengan "NOT NULL constraint failed".
    'pengguna_id',
    'tema',
    'notifikasi_konten_terbit',
    'notifikasi_konten_draft',
    'notifikasi_aktivitas_kuis',
    'notifikasi_aktivitas_konten',
    'konfirmasi_publikasi',
    'status_konten_default',
])]
class Preferensi extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("preferensis").
     */
    protected $table = 'tb_preferensi';

    public const TEMA_TERANG = 'terang';

    public const TEMA_GELAP = 'gelap';

    /**
     * Status konten yang boleh dipakai sebagai default konten baru.
     *
     * Hanya dua, dan tidak ada status "menunggu persetujuan" di antara
     * keduanya: aplikasi ini punya satu admin pengelola, jadi admin boleh
     * menerbitkan karyanya sendiri secara langsung.
     */
    public const STATUS_KONTEN = ['draft', 'published'];

    /**
     * Get the attributes that should be cast.
     *
     * Saklar notifikasi dan konfirmasi disimpan sebagai boolean sungguhan,
     * bukan "1"/"0", supaya form yang memakainya tidak perlu tahu bentuknya.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notifikasi_konten_terbit' => 'boolean',
            'notifikasi_konten_draft' => 'boolean',
            'notifikasi_aktivitas_kuis' => 'boolean',
            'notifikasi_aktivitas_konten' => 'boolean',
            'konfirmasi_publikasi' => 'boolean',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    /**
     * Ambil preferensi satu pengguna, membuatnya kalau belum ada.
     *
     * Satu-satunya jalan masuk ke tabel ini.
     *
     * Dua pemanggil yang datang bersamaan tidak akan bentrok: keduanya
     * meminta baris dengan pengguna_id yang sama, dan hanya satu yang berhasil
     * menulis. Yang kalah akan membaca baris yang baru saja dibuat.
     */
    public static function ambil(?User $pengguna): ?self
    {
        if ($pengguna === null) {
            return null;
        }

        return self::query()->firstOrCreate(
            ['pengguna_id' => $pengguna->getKey()],
            self::bawaan(),
        );
    }

    /**
     * Isi baris preferensi tanpa pengguna_id, untuk firstOrCreate() dan
     * update().
     *
     * Nilai setiap isian sama persis dengan default kolom di migrasi, jadi
     * pengguna yang belum pernah membuka Pengaturan tetap punya perilaku yang
     * masuk akal: tema terang, notifikasi menyala, konfirmasi publish menyala,
     * dan konten baru disimpan sebagai draft.
     *
     * @return array<string, mixed>
     */
    public static function bawaan(): array
    {
        return [
            'tema' => self::TEMA_TERANG,
            'notifikasi_konten_terbit' => true,
            'notifikasi_konten_draft' => true,
            'notifikasi_aktivitas_kuis' => true,
            'notifikasi_aktivitas_konten' => true,
            'konfirmasi_publikasi' => true,
            'status_konten_default' => 'draft',
        ];
    }

    /**
     * Tema yang aman untuk ditampilkan: yang tersimpan kalau dikenal, kalau
     * tidak terang.
     */
    public function temaAman(): string
    {
        return $this->tema === self::TEMA_GELAP
            ? self::TEMA_GELAP
            : self::TEMA_TERANG;
    }

    /**
     * Simpan tema dan kembalikan nilai yang benar-benar diterima.
     *
     * Tema yang tidak dikenal jatuh ke terang, supaya data yang rusak tidak
     * membuat seluruh area admin kehilangan warna.
     */
    public function simpanTema(?string $tema): string
    {
        $tema = $tema === self::TEMA_GELAP ? self::TEMA_GELAP : self::TEMA_TERANG;

        $this->forceFill(['tema' => $tema])->save();

        return $tema;
    }

    /**
     * Simpan status bawaan konten baru.
     *
     * Hanya "draft" dan "published" yang diterima; nilai lain diabaikan dan
     * baris tidak disentuh, jadi form yang mengirim isian aneh tidak bisa
     * mengarang status yang tidak dikenal seluruh aplikasi.
     */
    public function simpanStatusKonten(?string $status): string
    {
        $status = in_array($status, self::STATUS_KONTEN, true) ? $status : 'draft';

        $this->forceFill(['status_konten_default' => $status])->save();

        return $status;
    }

    /**
     * Saklar notifikasi untuk suatu jenis aktif?
     *
     * Nama kolom harus salah satu dari daftar yang diizinkan, dicek di sini
     * dan bukan lewat akses properti bebas, supaya salah ketik di pemanggil
     * tidak diam-diam membaca kolom lain.
     */
    public function notifikasiAktif(string $kolom): bool
    {
        $diizinkan = [
            'notifikasi_konten_terbit',
            'notifikasi_konten_draft',
            'notifikasi_aktivitas_kuis',
            'notifikasi_aktivitas_konten',
        ];

        return in_array($kolom, $diizinkan, true) && (bool) $this->{$kolom};
    }
}
