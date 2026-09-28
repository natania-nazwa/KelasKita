<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalIsianRequest;
use App\Models\Jadwal;
use App\Support\DaftarJadwal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mengelola satu jadwal milik sendiri: membuka form edit, menyimpan
 * perubahan, dan menghapus.
 *
 * Form edit memakai halaman "Tambah Jadwal" yang sama (view
 * user.jadwal-tambah), hanya isian dan tujuan simpan yang berbeda, jadi tidak
 * ada dua form untuk hal yang sama.
 *
 * Semua tiga action dijaga hal yang sama: jadwalnya harus benar-benar milik
 * pengguna yang sedang login, kalau tidak jawabannya 403.
 */
class JadwalKelolaController extends Controller
{
    public function edit(Request $request, Jadwal $jadwal): View
    {
        $item = $this->jadwalMilik($request, $jadwal);

        return view('user.jadwal-tambah', [
            'jadwal' => $item,
            'hariAktif' => $item->hari,
        ]);
    }

    public function update(JadwalIsianRequest $request, Jadwal $jadwal): RedirectResponse
    {
        $item = $this->jadwalMilik($request, $jadwal);
        $item->fill($request->isian())->save();

        return redirect()
            ->route('user.jadwal', ['tanggal' => DaftarJadwal::tanggalDekat($item->hari)])
            ->with('sukses', 'Jadwal "'.$item->judul.'" berhasil diperbarui.');
    }

    public function destroy(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $item = $this->jadwalMilik($request, $jadwal);
        $judul = $item->judul;
        $tanggal = DaftarJadwal::tanggalDekat($item->hari);

        $item->delete();

        return redirect()
            ->route('user.jadwal', array_filter(['tanggal' => $tanggal]))
            ->with('sukses', 'Jadwal "'.$judul.'" berhasil dihapus.');
    }

    /**
     * Pastikan jadwal yang ditangani benar milik pengguna yang sedang login.
     */
    private function jadwalMilik(Request $request, Jadwal $jadwal): Jadwal
    {
        abort_unless($jadwal->dimilikiOleh($request->user()?->getKey()), 403);

        return $jadwal;
    }
}
