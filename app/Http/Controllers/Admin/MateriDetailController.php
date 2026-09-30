<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Support\DaftarMateriAdmin;
use Illuminate\View\View;

/**
 * Halaman detail di "Kelola Materi": lihat isi materi selengkapnya tanpa
 * harus membuka materi di sisi pengguna.
 *
 * Satu-satunya halaman admin yang menyasar materi lewat slug. Tidak ada
 * penjaga status di sini karena tugas admin justru meninjau materi dari
 * semua status, termasuk draft yang belum tayang.
 */
class MateriDetailController extends Controller
{
    public function __invoke(string $materi): View
    {
        $item = Materi::query()
            ->with(['pelajaran', 'pembuat'])
            ->where('slug', $materi)
            ->firstOrFail();

        return view('admin.materi-detail', [
            'materi' => DaftarMateriAdmin::petikan($item),
        ]);
    }
}
