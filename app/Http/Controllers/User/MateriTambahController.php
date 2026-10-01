<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MateriIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\BerkasMateri;
use App\Support\NotifikasiAdmin;
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
        $data = $request->isian();
        $diajukan = $request->boolean('publikasikan');

        /*
         * Materi langsung dimiliki pengguna yang sedang login, bukan dari
         * field request, supaya isian "dibuat_oleh" palsu tidak bisa dipakai
         * untuk membuat konten atas nama orang lain.
         *
         * Tombol publikasi tidak berarti materi langsung tayang: statusnya
         * "pending" supaya materi masuk daftar tunggu admin. Yang benar-benar
         * tayang hanya materi yang sudah disetujui admin.
         */
        $materi = new Materi([
            ...$data,
            ...BerkasMateri::simpan($request),
            'dibuat_oleh' => $request->user()->getKey(),
            'slug' => $this->slugUnik($data['nama']),
            'status' => $diajukan ? Materi::STATUS_PENDING : Materi::STATUS_DRAFT,
        ]);

        $materi->save();

        // Kabar untuk admin bahwa antrean Verifikasi bertambah. Yang dibaca
        // hanya saklar "Konten menunggu ditinjau" di Pengaturan admin;
        // keputusan tetap diambil di halaman Verifikasi seperti biasa, tidak
        // ada alur persetujuan baru yang dibuka dari sini.
        if ($diajukan) {
            NotifikasiAdmin::kontenMenunggu($materi);
        }

        return redirect()
            ->route('user.karya-saya', ['tab' => 'materi'])
            ->with('sukses', $diajukan
                ? 'Materi "'.$materi->nama.'" tersimpan dan menunggu persetujuan admin.'
                : 'Materi "'.$materi->nama.'" disimpan sebagai draft.');
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
