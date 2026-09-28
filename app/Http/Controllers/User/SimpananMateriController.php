<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\SimpananMateri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Penyimpanan materi milik pengguna yang sedang login.
 *
 * Dua pintu dipakai halaman Materi dan Materi Detail lewat fetch:
 *   POST /user/materi-detail/{materi}/simpan  menambah / melepas simpanan
 *   GET  /user/simpanan/materi                daftar slug yang disimpan
 *
 * Daftar itulah yang dipakai resources/js/app.js untuk menampilkan status
 * tombol "Simpan" di semua kartu materi dalam satu pembacaan, jadi tidak
 * ada pertanyaan per kartu.
 */
class SimpananMateriController extends Controller
{
    /**
     * Balik status simpan satu materi untuk pengguna yang login.
     */
    public function toggle(Request $request, string $materi): JsonResponse
    {
        $item = Materi::query()
            ->terbit()
            ->where('slug', $materi)
            ->firstOrFail();

        $pasangan = [
            'pengguna_id' => $request->user()->getKey(),
            'materi_id' => $item->getKey(),
        ];

        // delete() menghasilkan jumlah baris yang hilang: 0 berarti memang
        // belum tersimpan, jadi barisnya dibuat. Satu langkah sekaligus
        // menangani klik ganda tanpa mengandung race yang menggantung.
        $tersimpan = SimpananMateri::query()->where($pasangan)->delete() === 0;

        if ($tersimpan) {
            SimpananMateri::query()->create($pasangan);
        }

        return response()->json(['tersimpan' => $tersimpan]);
    }

    /**
     * Slug seluruh materi yang disimpan pengguna yang login.
     */
    public function data(Request $request): JsonResponse
    {
        $materiId = SimpananMateri::query()
            ->where('pengguna_id', $request->user()->getKey())
            ->pluck('materi_id');

        $slug = Materi::query()
            ->terbit()
            ->whereIn('id', $materiId)
            ->pluck('slug')
            ->values();

        return response()->json(['slug' => $slug->all()]);
    }
}
