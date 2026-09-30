<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Pengguna": daftar semua akun yang memakai KelasKita.
 *
 * Halaman ini murni membaca. Tidak ada aksi yang mengubah akun: tidak
 * ada tombol edit, tidak ada ubah peran, tidak ada management role.
 * Satu-satunya alasan halaman ini ada adalah admin perlu tahu siapa
 * yang sedang memakai platform dan kapan mereka bergabung.
 *
 * Management peran sengaja tidak dibuat. Saat ini hanya ada dua peran
 * (ADMIN dan USER) dan hanya ada satu admin, jadi sistem permission
 * tidak akan pernah punya sesuatu yang harus dikelola.
 */
class PenggunaController extends Controller
{
    /**
     * Pengguna per halaman.
     */
    private const PER_HALAMAN = 15;

    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));
        $status = $this->statusTerpilih($request->query('status'));

        $daftar = User::query()
            ->when($kataKunci !== '', function ($query): void {
                // Pencarian hanya membaca dua kolom yang tampil di
                // tabel: nama dan email. Tidak ada kolom lain yang perlu
                // ikut dicari.
                $query->where(function ($isi): void {
                    $isi->where('nama', 'like', '%'.$kataKunci.'%')
                        ->orWhere('email', 'like', '%'.$kataKunci.'%');
                });
            })
            ->when($status !== 'semua', fn ($query) => $query->where('aktif', $status === 'aktif'))
            ->withCount(['materi', 'quiz'])
            ->latest()
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        $jumlahPengguna = (int) User::query()->count();

        /*
         * Rata-rata karya dihitung dari total seluruh isi platform,
         * bukan dari $daftar->total(): angka itu hanya menghitung baris
         * di halaman yang sedang dibuka, jadi akan berbedabeda
         * tergantung halaman mana yang sedang dibaca admin.
         */
        $totalKarya = (int) Materi::query()->count() + (int) Quiz::query()->count();

        return view('admin.pengguna', [
            'daftar' => $daftar,
            'kataKunci' => $kataKunci,
            'statusAktif' => $status,
            'jumlahPengguna' => $jumlahPengguna,
            'jumlahAktif' => (int) User::query()->where('aktif', true)->count(),
            'jumlahAdmin' => (int) User::query()->where('peran', User::PERAN_ADMIN)->count(),
            'totalKarya' => $totalKarya,
            'rataKarya' => $jumlahPengguna > 0 ? round($totalKarya / $jumlahPengguna, 1) : 0.0,
        ]);
    }

    /**
     * Status dari query string, dipaksa ke salah satu yang nyata.
     *
     * Tanpa penjaga ini ?status=ngawur akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya dibatasi di sini.
     */
    private function statusTerpilih(mixed $nilai): string
    {
        return in_array((string) $nilai, ['semua', 'aktif', 'nonaktif'], true)
            ? (string) $nilai
            : 'semua';
    }
}
