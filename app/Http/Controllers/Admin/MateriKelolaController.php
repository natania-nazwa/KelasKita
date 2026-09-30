<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MateriIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\BabMateri;
use App\Support\BerkasMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Edit dan hapus satu materi dari area admin.
 *
 * Route yang sudah ada (user.materi.edit / user.materi.destroy) milik
 * pemilik materi dan menolak admin dengan 403, jadi admin tidak punya jalan
 * untuk mengubah maupun menghapus karya orang lain. Controller ini mengisi
 * kekosongan itu, dan sengaja tidak memeriksa siapa pemilik materinya: yang
 * diurus di sini adalah konten yang sudah tayang, bukan hak publikasi atas
 * karya tersebut.
 *
 * Form edit memakai komponen form yang sama dengan halaman Tambah Materi
 * milik pengguna (x-materi.informasi, x-materi.bab, x-materi.editor) dan
 * JavaScript yang sama, jadi isian dan editornya tidak mungkin menyimpang
 * dari form yang dipakai pemilik. Yang berbeda hanya judul, penjelas, dan
 * tujuan simpan.
 */
class MateriKelolaController extends Controller
{
    public function edit(Request $request, string $materi): View
    {
        $item = $this->cariMilik($request, $materi);

        return view('admin.materi-edit', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
            'materi' => $item,
            // Daftar bab dipecah lagi dari isi tersimpan supaya editor bisa
            // dibuka dengan bab yang sama seperti waktu materi dibuat.
            'bab' => BabMateri::dariIsi($item->isi),
        ]);
    }

    /**
     * Simpan perubahan admin.
     *
     * Status materi tidak pernah ikut turun ke "menunggu" di sini. Aturan
     * itu milik pemilik: revisi pemilik harus diinjau ulang supaya
     * perubahannya tidak langsung menjangkau pengguna tanpa pemeriksaan.
     * Admin justru pihak yang menyetujui konten tersebut, jadi materinya
     * tetap tayang setelah diperbarui — termasuk tanggal terbitnya, yang
     * sengaja dibiarkan apa adanya supaya urutan "Terbaru diterbitkan" di
     * daftar tidak ikut berubah karena suntingan admin.
     */
    public function update(MateriIsianRequest $request, string $materi): RedirectResponse
    {
        $item = $this->cariMilik($request, $materi);

        /*
         * Tombol Hapus pada thumbnail dikirim sebagai thumbnail_hapus.
         * Berkas barunya menang kalau admin juga mengunggah pengganti, jadi
         * bendera ini hanya berarti kalau tidak ada berkas baru.
         */
        $berkasBaru = BerkasMateri::simpan($request);
        $buangThumbnail = $request->boolean('thumbnail_hapus') && ! isset($berkasBaru['thumbnail']);

        /*
         * Berkas lama ditangkap sebelum fill(): begitu diisi, kolomnya sudah
         * menunjuk berkas baru, jadi berkas lama yang justru harus dibuang
         * bisa terlewat dan menumpuk di disk.
         */
        $berkasLama = $item->thumbnail;

        $item->fill([
            ...$request->isian(),
            ...$berkasBaru,
            ...($buangThumbnail ? ['thumbnail' => null] : []),
        ])->save();

        if (isset($berkasBaru['thumbnail']) || $buangThumbnail) {
            BerkasMateri::hapus($berkasLama);
        }

        return redirect()
            ->route('admin.materi.show', $item->slug)
            ->with('sukses', 'Materi "'.$item->nama.'" berhasil diperbarui.');
    }

    /**
     * Hapus materi beserta thumbnailnya.
     *
     * Baris simpanan pengguna (tb_simpanan_materi) ikut terhapus karena
     * foreign key-nya cascadeOnDelete, jadi tidak ada sisa bookmark yang
     * menunjuk materi yang sudah tidak ada. Tidak ada bab, progress, atau
     * quiz yang berelasi ke materi: bab disimpan sebagai teks di dalam
     * kolom isi, bukan tabel tersendiri.
     */
    public function destroy(string $materi): RedirectResponse
    {
        $item = $this->cari($materi);
        $nama = $item->nama;

        BerkasMateri::hapus($item->thumbnail);
        $item->delete();

        return redirect()
            ->route('admin.materi')
            ->with('sukses', 'Materi "'.$nama.'" berhasil dihapus.');
    }

    /**
     * Cari materi dari slug, tanpa memeriksa status: admin boleh membuka form
     * edit untuk materi yang statusnya apa pun, selama materi itu miliknya
     * sendiri.
     */
    private function cari(string $slug): Materi
    {
        return Materi::query()
            ->with(['pelajaran', 'pembuat'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Cari materi dari slug dan pastikan itu karya admin yang sedang login.
     *
     * Edit dibatasi ke materi milik sendiri: materi buatan pengguna boleh
     * dibaca dan dihapus dari halaman Materi, tapi isinya milik penulisnya.
     * Ini adalah penjaga sesungguhnya; DaftarMateriAdmin::petikan() hanya
     * dipakai untuk tidak menampilkan tombol yang memang ditolak di sini.
     */
    private function cariMilik(Request $request, string $slug): Materi
    {
        $item = $this->cari($slug);

        abort_unless($item->dimilikiOleh($request->user()?->getKey()), 403);

        return $item;
    }
}
