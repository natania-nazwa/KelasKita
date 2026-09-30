<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Support\DetailMateri;
use Illuminate\View\View;

/**
 * Halaman detail materi di area admin.
 *
 * Isinya bukan pratinjau terpisah: halaman ini merender komponen yang
 * sama persis dengan halaman detail milik pengguna
 * (resources/views/components/materi/detail-*.blade.php), jadi admin
 * membaca judul, deskripsi, thumbnail, daftar bab, isi materi, blok kode,
 * dan navigasi bab dengan tampilan yang sama dengan yang dibaca pengguna.
 *
 * Yang berbeda hanya kerangkanya. Halaman ini memakai layout admin, punya
 * tombol "Kembali ke Materi" dan "Edit Materi" di luar area konten, dan
 * tautan "Kembali"/"Lihat semua"-nya mengarah ke daftar admin — bukan ke
 * daftar pengguna.
 *
 * Tanpa penjaga status, sama seperti sebelumnya: admin boleh membuka
 * materi dari status mana pun lewat URL, dan halaman ini sengaja tidak
 * menambah penghitung jumlah_dilihat karena yang dibuka adalah pemeriksaan
 * admin, bukan pembacaan oleh pengguna.
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
            'detail' => DetailMateri::petikan(
                $item,
                // false: admin tidak punya bookmark, dan tombol Simpan
                // disembunyikan lewat prop $simpan di x-materi.detail-kepala.
                tersimpan: false,
                tautanDaftar: route('admin.materi'),
                tautanLatihan: route('admin.quiz'),
            ),
            'tautanEdit' => route('admin.materi.edit', $item->slug),
        ]);
    }
}
