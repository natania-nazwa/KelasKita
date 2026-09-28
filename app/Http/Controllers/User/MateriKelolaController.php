<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MateriIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\BabMateri;
use App\Support\BerkasMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mengelola satu materi milik sendiri: membuka form edit, menyimpan
 * perubahan, dan menghapus.
 *
 * Form edit memakai halaman "Tambah Materi" yang sama (view
 * user.materi-tambah), hanya isian dan tujuan simpan yang berbeda, jadi
 * tidak ada dua form untuk hal yang sama.
 *
 * Semua tiga action dijaga hal yang sama: materinya harus benar-benar milik
 * pengguna yang sedang login, kalau tidak jawabannya 403. Dicari dari slug
 * supaya URL-nya enak dibaca, sama seperti halaman detail materi.
 */
class MateriKelolaController extends Controller
{
    public function edit(Request $request, string $materi): View
    {
        $item = $this->materiMilik($request, $materi);

        return view('user.materi-tambah', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
            'materi' => $item,
            // Daftar bab dipecah lagi dari isi tersimpan supaya editor bisa
            // dibuka dengan bab yang sama seperti waktu materi dibuat.
            'bab' => BabMateri::dariIsi($item->isi),
        ]);
    }

    public function update(MateriIsianRequest $request, string $materi): RedirectResponse
    {
        $item = $this->materiMilik($request, $materi);
        $data = $request->validated();
        $berkasBaru = BerkasMateri::simpan($request);

        // Berkas yang diganti harus dihapus supaya storage tidak menumpuk
        // berkas yang sudah tidak dirujuk materi mana pun.
        foreach (['thumbnail', 'audio'] as $kolom) {
            if (isset($berkasBaru[$kolom])) {
                BerkasMateri::hapus($item->{$kolom});
            }
        }

        $item->fill([...$data, ...$berkasBaru])->save();

        return redirect()
            ->route('user.karya-saya', ['tab' => 'materi'])
            ->with('sukses', 'Materi "'.$item->nama.'" berhasil diperbarui.');
    }

    public function destroy(Request $request, string $materi): RedirectResponse
    {
        $item = $this->materiMilik($request, $materi);
        $nama = $item->nama;

        BerkasMateri::hapus($item->thumbnail, $item->audio);
        $item->delete();

        return redirect()
            ->route('user.karya-saya', ['tab' => 'materi'])
            ->with('sukses', 'Materi "'.$nama.'" berhasil dihapus.');
    }

    /**
     * Cari materi dari slug, lalu pastikan itu milik pengguna yang sedang
     * login: tidak ada = 404, milik orang lain = 403.
     */
    private function materiMilik(Request $request, string $slug): Materi
    {
        $item = Materi::query()->where('slug', $slug)->firstOrFail();

        abort_unless($item->dimilikiOleh($request->user()?->getKey()), 403);

        return $item;
    }
}
