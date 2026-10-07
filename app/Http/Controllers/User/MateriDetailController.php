<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\SimpananMateri;
use App\Support\AktivitasHarian;
use App\Support\DaftarMateri;
use App\Support\DetailMateri;
use App\Support\MateriDibaca;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MateriDetailController extends Controller
{
    /**
     * Halaman baca satu materi.
     *
     * Materi dicari lewat slug supaya URL enak dibaca dan tidak menebak
     * urutan id. Materi yang belum disetujui admin (draft, menunggu, atau
     * ditolak) ikut otomatis tidak bisa dibuka.
     */
    public function __invoke(Request $request, string $materi): View
    {
        $item = Materi::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->where('slug', $materi)
            ->firstOrFail();

        /*
         * Pengali tampilan. Dihitung di sini (bukan lewat route terpisah)
         * supaya satu buka halaman selalu satu tambahan, dan increment()
         * sekaligus memperbarui nilai di memori sehingga kepala materi
         * langsung menampilkan angka terbarunya tanpa query kedua.
         */
        $item->increment('jumlah_dilihat');

        /*
         * Membaca materi adalah satu dari dua kegiatan yang menyalakan
         * streak, jadi setiap pembukaan halaman baca dicatat di sini.
         * Satu hari punya paling banyak satu baris per jenis, jadi membuka
         * materi yang sama berulang kali tidak menambah apa pun.
         */
        AktivitasHarian::bacaMateri($request->user());

        /*
         * Yang kedua: materi ini masuk daftar "sudah dibaca" milik pengguna.
         *
         * Berbeda dari streak di atas, yang ini mengingat materi mana, bukan
         * cuma hari apa — itulah yang membuat kartu "Progress Belajar" bisa
         * menghitung berapa materi yang benar-benar sudah diselesaikan. Satu
         * baris per pasangan pengguna + materi, jadi membuka ulang halaman
         * yang sama tidak menambah apa pun.
         */
        MateriDibaca::catat($request->user(), $item);

        /*
         * Status tombol Simpan. Satu query untuk pasangan pengguna +
         * materi; kalau pengguna belum login, halaman ini memang tidak
         * bisa dibuka (middleware auth di route).
         */
        $tersimpan = SimpananMateri::query()
            ->where('pengguna_id', $request->user()->getKey())
            ->where('materi_id', $item->getKey())
            ->exists();

        /*
         * Saran baca: utamakan materi lain dari kategori yang sama. Karena
         * tiap kategori bisa saja hanya punya satu materi, sisanya dilengkapi
         * dari materi terbaru supaya daftar tidak kosong.
         */
        $sesuaiKategori = Materi::query()
            ->terbit()
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
                    ->terbit()
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
            // Halaman detail memakai array polos supaya komponen tampilan
            // tidak terikat Eloquent. Isi materinya dipecah jadi seksi +
            // blok di dalam petikan().
            'detail' => DetailMateri::petikan($item, $tersimpan),
            // Saran baca dirender lewat komponen kartu yang sama dengan
            // halaman daftar, jadi cukup array polos.
            'terkini' => DaftarMateri::petakan($terkini),
            'semuaSatuKategori' => (bool) $item->pelajaran
                && $terkini->every(fn (Materi $saran) => $saran->pelajaran_id === $item->pelajaran_id),
        ]);
    }
}
