<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use Illuminate\View\View;

class MateriDetailController extends Controller
{
    /**
     * Halaman baca satu materi.
     *
     * Materi dicari lewat slug supaya URL enak dibaca dan tidak menebak
     * urutan id. Materi yang tidak aktif (sudah disembunyikan admin) ikut
     * otomatis tidak bisa dibuka.
     */
    public function __invoke(string $materi): View
    {
        $item = Materi::query()
            ->aktif()
            ->with(['pelajaran', 'pembuat'])
            ->where('slug', $materi)
            ->firstOrFail();

        /*
         * Saran baca: utamakan materi lain dari kategori yang sama. Karena
         * tiap kategori bisa saja hanya punya satu materi, sisanya dilengkapi
         * dari materi terbaru supaya daftar tidak kosong.
         */
        $sesuaiKategori = Materi::query()
            ->aktif()
            ->whereKeyNot($item->getKey())
            ->when(
                $item->pelajaran_id,
                fn ($query) => $query->where('pelajaran_id', $item->pelajaran_id)
            )
            ->latest()
            ->limit(3)
            ->get();

        $terkini = $sesuaiKategori->count() < 3
            ? $sesuaiKategori->concat(
                Materi::query()
                    ->aktif()
                    ->whereKeyNot($item->getKey())
                    ->when(
                        $item->pelajaran_id,
                        fn ($query) => $query->where('pelajaran_id', '!=', $item->pelajaran_id)
                    )
                    ->when(
                        $sesuaiKategori->isNotEmpty(),
                        fn ($query) => $query->whereNotIn('id', $sesuaiKategori->modelKeys())
                    )
                    ->latest()
                    ->limit(3 - $sesuaiKategori->count())
                    ->get()
            )
            : $sesuaiKategori;

        return view('user.materi-detail', [
            'materi' => $item,
            'terkini' => $terkini,
        ]);
    }
}
