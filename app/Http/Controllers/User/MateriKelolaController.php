<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MateriIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\BabMateri;
use App\Support\BerkasMateri;
use App\Support\NotifikasiAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mengelola satu materi milik sendiri: membuka form edit, menyimpan
 * perubahan, dan menghapus.
 *
 * Form edit memakai halaman "Tambah Materi" yang sama (view
 * user.materi-tambah), hanya isian dan tujuan simpan yang berbeda, jadi
 * tidak ada dua form untuk hal yang sama.
 *
 * Semua tiga action dijaga hal yang sama: materinya harus benar-benar milik
 * pengguna yang sedang login, kalau tidak jawabannya 403. Dicari dari slug
 * supaya URL-nya enak dibaca, sama seperti halaman detail materi.
 */
class MateriKelolaController extends Controller
{
    public function edit(Request $request, string $materi): View
    {
        $item = $this->materiMilik($request, $materi);

        return view('user.materi-tambah', [
            'kategori' => Pelajaran::query()->aktif()->urutKatalog()->get(),
            'materi' => $item,
            // Daftar bab dipecah lagi dari isi tersimpan supaya editor bisa
            // dibuka dengan bab yang sama seperti waktu materi dibuat.
            'bab' => BabMateri::dariIsi($item->isi),
        ]);
    }

    public function update(MateriIsianRequest $request, string $materi): RedirectResponse
    {
        $item = $this->materiMilik($request, $materi);
        $data = $request->isian();
        $berkasBaru = BerkasMateri::simpan($request);

        /*
         * Tombol Hapus pada thumbnail dikirim sebagai thumbnail_hapus.
         * Berkas barunya menang kalau pengguna juga mengunggah pengganti,
         * jadi bendera ini hanya berarti kalau tidak ada berkas baru.
         */
        $buangThumbnail = $request->boolean('thumbnail_hapus')
            && ! isset($berkasBaru['thumbnail']);

        /*
         * Berkas lama ditangkap sebelum fill(): begitu diisi, kolomnya sudah
         * menunjuk berkas baru, jadi berkas lama yang justru harus dibuang
         * bisa terlewat dan menumpuk di disk.
         */
        $berkasLama = $item->thumbnail;

        $item->fill([
            ...$data,
            ...$berkasBaru,
            ...($buangThumbnail ? ['thumbnail' => null] : []),
        ])->save();

        // Berkas yang diganti harus dihapus supaya storage tidak menumpuk
        // berkas yang sudah tidak dirujuk materi mana pun.
        if (isset($berkasBaru['thumbnail'])) {
            BerkasMateri::hapus($berkasLama);
        }

        if ($buangThumbnail) {
            BerkasMateri::hapus($berkasLama);
        }

        /*
         * Status tidak pernah diubah diam-diam oleh pemilik. Satu-satunya
         * jalan masuk ke daftar tunggu admin adalah tombol publikasi, dan
         * tombol itu hanya muncul kalau materinya masih boleh diajukan
         * (draft, ditolak di bawah batas pengajuan, atau sudah terbit lalu
         * direvisi).
         *
         * Materi yang sudah terbit pun ikut diturunkan ke daftar tunggu,
         * bukan tetap tayang. Kalau tidak, perubahannya bisa langsung
         * menjangkau pengguna tanpa pernah ditinjau admin.
         */
        $pernahTerbit = $item->pernahTerbit();
        $dikirim = $request->boolean('publikasikan') && $item->bolehDiajukan();

        if ($dikirim) {
            $item->ajukanPersetujuan();

            // Kabar untuk admin bahwa antrean Verifikasi bertambah. Yang
            // dibaca hanya saklar "Konten menunggu ditinjau" di Pengaturan
            // admin; keputusan tetap diambil di halaman Verifikasi seperti
            // biasa, tidak ada alur persetujuan baru yang dibuka di sini.
            NotifikasiAdmin::kontenMenunggu($item);
        }

        return redirect()
            ->route('user.karya-saya', ['tab' => 'materi'])
            ->with('sukses', $this->pesanSimpan($item, $dikirim, $pernahTerbit));
    }

    /**
     * Pesan setelah materi edit tersimpan.
     *
     * Diberi kasus tersendiri karena akibatnya berbeda: materi yang tayang
     * lalu ditarik kembali ke daftar tunggu hilang dari halaman publik, jadi
     * pemiliknya perlu tahu supaya tidak mengira materinya masih dibaca.
     */
    private function pesanSimpan(Materi $item, bool $dikirim, bool $pernahTerbit): string
    {
        if (! $dikirim) {
            return 'Materi "'.$item->nama.'" berhasil diperbarui.';
        }

        return $pernahTerbit
            ? 'Materi "'.$item->nama.'" diperbarui dan dikirim ulang ke admin. Materi ini berhenti tayang sampai disetujui lagi.'
            : 'Materi "'.$item->nama.'" diperbarui dan dikirim ke admin untuk ditinjau.';
    }

    public function destroy(Request $request, string $materi): RedirectResponse
    {
        $item = $this->materiMilik($request, $materi);
        $nama = $item->nama;

        BerkasMateri::hapus($item->thumbnail);
        $item->delete();

        return redirect()
            ->route('user.karya-saya', ['tab' => 'materi'])
            ->with('sukses', 'Materi "'.$nama.'" berhasil dihapus.');
    }

    /**
     * Cari materi dari slug, lalu pastikan itu milik pengguna yang sedang
     * login: tidak ada = 404, milik orang lain = 403.
     */
    private function materiMilik(Request $request, string $slug): Materi
    {
        $item = Materi::query()->where('slug', $slug)->firstOrFail();

        abort_unless($item->dimilikiOleh($request->user()?->getKey()), 403);

        return $item;
    }
}
