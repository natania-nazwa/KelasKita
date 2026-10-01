<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PelajaranIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Kelola Mata Pelajaran" di area Pengaturan admin.
 *
 * Mengelola baris di tabel tb_pelajaran yang sudah ada, bukan tabel baru.
 * Tabel ini sudah dipakai sebagai filter kategori di halaman Materi, Quiz, dan
 * jadwal, dan sudah punya kolom "aktif", jadi menonaktifkan sebuah mata pelajaran
 * cukup untuk menghilangkannya dari pilihan tanpa merusak materi dan quiz yang
 * sudah memakainya.
 *
 * Aturan menghapus versus menonaktifkan:
 *
 *   - kalau belum dipakai materi atau quiz sama sekali, boleh dihapus;
 *   - kalau sudah dipakai, hapus ditolak dan admin diarahkan menonaktifkan.
 *     Baris yang sudah jadi bagian isi materi atau quiz tidak boleh hilang hanya
 *     karena admin menata daftar filter.
 *
 * Slug yang sudah dipakai tidak ikut berubah saat nama diedit. Slug dipakai di URL
 * filter, jadi ikut berubahnya tanpa disengaja akan membuat tautan lama mati;
 * admin yang memang ingin slug baru mengetiknya sendiri lewat form ubah.
 */
class PelajaranKelolaController extends Controller
{
    /**
     * Daftar mata pelajaran beserta jumlah materi dan quiz yang memakainya.
     *
     * Dua aggregate dihitung satu kali per baris, bukan satu query per baris di
     * view, supaya menambah satu mata pelajaran tidak menambah query.
     */
    public function index(): View
    {
        $daftar = Pelajaran::query()
            ->withCount(['materi', 'quiz'])
            ->orderBy('nama')
            ->get();

        return view('admin.pengaturan.pelajaran', [
            'daftar' => $daftar,
            'jumlahAktif' => $daftar->where('aktif', true)->count(),
            'jumlahNonaktif' => $daftar->where('aktif', false)->count(),
        ]);
    }

    /**
     * Simpan mata pelajaran baru.
     */
    public function store(PelajaranIsianRequest $request): RedirectResponse
    {
        Pelajaran::query()->create($request->nilai());

        return redirect()
            ->route('admin.pengaturan.pelajaran')
            ->with('sukses', 'Mata pelajaran berhasil ditambahkan.')
            ->with('suksesDetail', 'Mata pelajaran ini langsung muncul sebagai pilihan saat membuat materi atau quiz.');
    }

    /**
     * Simpan perubahan satu mata pelajaran.
     *
     * Form ubah sengaja tidak punya kolom slug, jadi kolom itu tidak ikut
     * terkirim dan baris mempertahankan slug lamanya. Admin yang memang ingin
     * slug baru mengetiknya lewat kolom Kode pada form tambah, bukan lewat
     * form ubah, supaya tidak ada tautan filter yang mati tanpa disadari.
     */
    public function update(PelajaranIsianRequest $request, Pelajaran $pelajaran): RedirectResponse
    {
        $pelajaran->fill($request->nilai())->save();

        return redirect()
            ->route('admin.pengaturan.pelajaran')
            ->with('sukses', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Hapus satu mata pelajaran, hanya kalau belum dipakai.
     */
    public function destroy(Request $request, Pelajaran $pelajaran): RedirectResponse
    {
        $dipakai = $this->dipakai($pelajaran);

        if ($dipakai > 0) {
            return redirect()
                ->route('admin.pengaturan.pelajaran')
                ->with('galat', 'Mata pelajaran "'.$pelajaran->nama.'" masih dipakai oleh '.$dipakai.' konten, jadi tidak bisa dihapus.')
                ->with('catatan', 'Nonaktifkan saja supaya materinya tetap utuh dan pilihan untuk konten baru tidak menampilkannya.');
        }

        $nama = $pelajaran->nama;
        $pelajaran->delete();

        return redirect()
            ->route('admin.pengaturan.pelajaran')
            ->with('sukses', 'Mata pelajaran "'.$nama.'" berhasil dihapus.');
    }

    /**
     * Total materi dan quiz yang memakai mata pelajaran ini.
     */
    private function dipakai(Pelajaran $pelajaran): int
    {
        return (int) Materi::query()->where('pelajaran_id', $pelajaran->getKey())->count()
            + (int) Quiz::query()->where('pelajaran_id', $pelajaran->getKey())->count();
    }
}
