<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MateriIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\BerkasMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Form tambah materi milik sendiri.
 *
 * Tombol "Tambah Materi" ada di halaman "Karya Saya" (menu khusus yang
 * managing konten pribadi), dan form ini yang dibuka tombol tersebut.
 */
class MateriTambahController extends Controller
{
    public function create(): View
    {
        return view('user.materi-tambah', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
        ]);
    }

    public function store(MateriIsianRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /*
         * Materi langsung dimiliki pengguna yang sedang login, bukan dari
         * field request, supaya isian "dibuat_oleh" palsu tidak bisa dipakai
         * untuk membuat konten atas nama orang lain.
         */
        $materi = new Materi([
            ...$data,
            ...BerkasMateri::simpan($request),
            'dibuat_oleh' => $request->user()->getKey(),
            'slug' => $this->slugUnik($data['nama']),
            'aktif' => true,
        ]);

        $materi->save();

        return redirect()
            ->route('user.karya-saya', ['tab' => 'materi'])
            ->with('sukses', 'Materi "'.$materi->nama.'" berhasil ditambahkan.');
    }

    /**
     * Slug dari judul, diberi akhiran angka bila slug-nya sudah dipakai
     * materi lain.
     */
    private function slugUnik(string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'materi';
        $slug = $dasar;
        $urutan = 2;

        while (Materi::query()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.$urutan;
            $urutan++;
        }

        return $slug;
    }
}
