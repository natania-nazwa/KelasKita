<?php

namespace App\Models;

use App\Support\DaftarJadwal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu jam pelajaran milik satu pengguna (tabel tb_jadwal).
 *
 * Jadwal disimpan per pengguna, bukan global: setiap orang menyusun
 * jadwalnya sendiri lewat tombol "Tambah Jadwal" di halaman /user/jadwal, dan
 * halaman itu hanya menampilkan jadwal miliknya sendiri. Kepemilikan
 * ditegakkan di query lewat scopeMilik(), bukan dengan menyembunyikan tombol
 * edit di view.
 *
 * Kolom "mulai" dan "selesai" sengaja TIDAK diberi cast tanggal. Isinya jam
 * saja, dan membacanya sebagai tanggal membuat jam bisa bergeser karena
 * zona waktu. Yang dipakai aplikasi cuma potongan "HH:MM" dari string itu,
 * jadi dibiarkan apa adanya oleh database.
 *
 * Kolom "pr" (pekerjaan rumah) dan "pr_dikumpulkan" menyimpan tugas yang
 * menempel pada jam pelajaran itu. Keduanya boleh kosong: jadwal tanpa PR
 * adalah hal yang normal, bukan data yang belum lengkap.
 *
 * Kolom "guru" sengaja tidak ada di $fillable. Aplikasinya sudah tidak
 * mengelola nama guru sama sekali, tapi kolomnya di database dibiarkan
 * supaya data lama tidak ikut hilang. Baris baru tetap bisa disimpan karena
 * kolom itu punya nilai default di level database.
 */
#[Fillable([
    'dibuat_oleh', 'hari', 'mulai', 'selesai', 'pelajaran', 'judul',
    'kelas', 'ruang', 'pr', 'pr_dikumpulkan',
])]
class Jadwal extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("jadwals").
     */
    protected $table = 'tb_jadwal';

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'pr_dikumpulkan' => 'date',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Hanya jadwal milik satu pengguna. Dipakai di semua query halaman
     * jadwal supaya baris pengguna lain tidak pernah masuk ke markup.
     */
    public function scopeMilik(Builder $query, ?int $idPengguna): Builder
    {
        return $query->where('dibuat_oleh', $idPengguna);
    }

    /**
     * Urut dari hari paling awal (Minggu) lalu jam paling pagi, supaya daftar
     * di halaman tidak perlu diurutkan ulang di PHP.
     */
    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('hari')->orderBy('mulai');
    }

    /**
     * Jam mulai dalam bentuk "HH:MM".
     */
    public function jamMulai(): string
    {
        return $this->potongJam($this->mulai);
    }

    /**
     * Jam selesai dalam bentuk "HH:MM".
     */
    public function jamSelesai(): string
    {
        return $this->potongJam($this->selesai);
    }

    /**
     * Lama pelajaran dalam menit.
     */
    public function durasiMenit(): int
    {
        [$jamMulai, $menitMulai] = array_map('intval', explode(':', $this->jamMulai()));
        [$jamSelesai, $menitSelesai] = array_map('intval', explode(':', $this->jamSelesai()));

        return ($jamSelesai * 60 + $menitSelesai) - ($jamMulai * 60 + $menitMulai);
    }

    /**
     * Nama hari jadwalnya, mis. "Senin".
     */
    public function namaHari(): string
    {
        return DaftarJadwal::namaHariPenuh(Carbon::createFromDate(2026, 1, 4)->addDays((int) $this->hari));
    }

    /**
     * Apakah jadwal ini punya tugas/PR.
     */
    public function punyaPr(): bool
    {
        return filled($this->pr);
    }

    /**
     * Apakah jadwal ini benar milik pengguna yang sedang login.
     */
    public function dimilikiOleh(?int $idPengguna): bool
    {
        return $this->dibuat_oleh !== null && (int) $this->dibuat_oleh === (int) $idPengguna;
    }

    /**
     * Driver database bisa menyimpan jam sebagai "08:00" atau "08:00:00".
     * Yang dipakai aplikasi selalu lima karakter pertama: "HH:MM".
     */
    private function potongJam(mixed $nilai): string
    {
        return substr((string) $nilai, 0, 5);
    }
}
