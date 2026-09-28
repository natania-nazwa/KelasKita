<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TolakMateriRequest;
use App\Models\Materi;
use App\Support\TinjauanMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keputusan admin atas satu materi: menyetujui atau menolak.
 *
 * Dua action ini hanya boleh dijalankan untuk materi yang benar-benar sedang
 * menunggu. Materi draft tidak bisa disetujui (belum pernah diajukan), dan
 * materi yang sudah terbit atau ditolak tidak bisa diputuskan lagi dari
 * halaman ini: kalau tetap dipaksakan, jawabannya 404 supaya angka status
 * di halaman review tidak pernah berbeda dengan isi tabelnya.
 *
 * Keduanya mengembalikan admin ke tab status yang sedang dibuka, supaya
 * menolak satu materi tidak membuat admin kehilangan tempatnya.
 */
class MateriTinjauController extends Controller
{
    public function setujui(Request $request, string $materi): RedirectResponse
    {
        $item = $this->materiMenunggu($materi);

        $item->setujui();

        return $this->kembaliKeTinjauan(
            $request,
            'Materi "'.$item->nama.'" disetujui dan sekarang tayang untuk semua pengguna.'
        );
    }

    public function tolak(TolakMateriRequest $request, string $materi): RedirectResponse
    {
        $item = $this->materiMenunggu($materi);

        $item->tolak($request->string('alasan')->trim()->value());

        return $this->kembaliKeTinjauan(
            $request,
            'Materi "'.$item->nama.'" ditolak. Alasannya sudah dikirim ke pemiliknya.'
        );
    }

    /**
     * Cari materi dari slug, dan pastikan statusnya "menunggu".
     *
     * Tidak ada = 404. Status lain juga 404, bukan 403: bukan soal hak akses,
     * tapi soal materi ini memang tidak sedang diputuskan.
     */
    private function materiMenunggu(string $slug): Materi
    {
        $item = Materi::query()->where('slug', $slug)->firstOrFail();

        abort_unless($item->status === Materi::STATUS_PENDING, 404);

        return $item;
    }

    /**
     * Kembali ke tab status yang tadi dibuka, atau ke daftar tunggu kalau
     * form ditolak dari tab lain.
     */
    private function kembaliKeTinjauan(Request $request, string $pesan): RedirectResponse
    {
        $status = (string) $request->input('status', Materi::STATUS_PENDING);

        $params = array_key_exists($status, TinjauanMateri::pilihanStatus())
            ? ['status' => $status]
            : [];

        return redirect()
            ->route('admin.materi', $params)
            ->with('sukses', $pesan);
    }
}
