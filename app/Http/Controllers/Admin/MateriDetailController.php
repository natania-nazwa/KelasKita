<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Support\DetailMateri;
use Illuminate\Http\Request;
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
 * Satu kelas melayani dua route: admin.materi.show dan
 * admin.konten.materi.show. Keduanya sengaja memakai controller, view, dan
 * data yang sama supaya tampilannya identik; yang dibedakan hanya nama
 * route-nya, karena penanda aktif sidebar memakai admin.konten*.
 *
 * Yang berbeda hanya kerangkanya. Halaman ini memakai layout admin, punya
 * tombol "Kembali ke Materi" di luar area konten, dan tautan
 * "Kembali"/"Lihat semua"-nya mengarah ke daftar admin — bukan ke daftar
 * pengguna. Tujuan tombol Kembali itu sendiri mengikuti route yang sedang
 * dibuka (lihat __invoke), supaya admin yang datang dari Konten
 * Pembelajaran kembali ke Konten Pembelajaran, bukan ke menu Materi.
 *
 * Tanpa penjaga status, sama seperti sebelumnya: admin boleh membuka
 * materi dari status mana pun lewat URL, dan halaman ini sengaja tidak
 * menambah penghitung jumlah_dilihat karena yang dibuka adalah pemeriksaan
 * admin, bukan pembacaan oleh pengguna.
 */
class MateriDetailController extends Controller
{
    public function __invoke(Request $request, string $materi): View
    {
        $item = Materi::query()
            ->with(['pelajaran', 'pembuat'])
            ->where('slug', $materi)
            ->firstOrFail();

        /*
         * Tujuan "Kembali ke Materi" mengikuti halaman asal.
         *
         * Tanpa ini, admin yang membuka karyanya dari Konten Pembelajaran
         * akan dilempar ke /admin/materi begitu menekan Kembali — menu yang
         * menyala di sidebar ikut pindah ke Materi, persis yang dipisah oleh
         * pemisahan dua route ini. Pratinjau pada form Tambah/Edit Materi
         * sudah memakai aturan yang sama
         * (Admin\KontenMateriController::pratinjau).
         */
        $tautanDaftar = $request->routeIs('admin.konten.materi.show')
            ? route('admin.konten', ['tab' => 'materi'])
            : route('admin.materi');

        return view('admin.materi-detail', [
            'detail' => DetailMateri::petikan(
                $item,
                // false: admin tidak punya bookmark, dan tombol Simpan
                // disembunyikan lewat prop $simpan di x-materi.detail-kepala.
                tersimpan: false,
                tautanDaftar: $tautanDaftar,
                tautanLatihan: route('admin.quiz'),
            ),
            'tautanEdit' => route('admin.materi.edit', $item->slug),
        ]);
    }
}
