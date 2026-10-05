<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MateriIsianRequest;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Preferensi;
use App\Support\BabMateri;
use App\Support\BerkasMateri;
use App\Support\DaftarKonten;
use App\Support\NotifikasiAdmin;
use App\Support\NotifikasiKonten;
use App\Support\PratinjauMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Tambah, ubah, hapus, duplikat, terbitkan, dan batalkan terbitkan materi
 * milik admin sendiri, dari menu "Konten Pembelajaran".
 *
 * Berbeda dari Admin\MateriKelolaController yang sudah ada, dan perbedaannya
 * disengaja:
 *
 *   - route-nya sendiri, di bawah /admin/konten, supaya tidak menyenggol
 *     halaman "Materi" yang tetap mengelola kumpulan konten published;
 *   - memakai pembatas "hanya milik admin", sama seperti
 *     Admin\MateriKelolaController dan halaman "Karya Saya" milik pengguna.
 *     Daftar di Konten Pembelajaran hanya berisi karya admin yang sedang
 *     login, jadi form, terbitkan, dan hapus di sini juga hanya untuk karya
 *     itu. Materi buatan pengguna tetap managing lewat Verifikasi dan lewat
 *     katalog Materi;
 *   - statusnya hanya "draft" dan "published". Tidak ada pending, tidak ada
 *     rejected, tidak ada tombol setujui: admin yang menerbitkan karyanya
 *     sendiri tidak menunggu keputusan siapa pun.
 *
 * Isian, daftar bab, dan editornya bukan ditulis ulang di sini. Form ini
 * memakai komponen yang sama dengan halaman Tambah Materi milik pengguna
 * (x-materi.informasi, .bab, .editor) dan JavaScript yang sama
 * (resources/js/materi-tambah.js), jadi admin menulis materi dengan alat
 * yang persis sama seperti pemiliknya. Membuat form terpisah hanya akan
 * membuat pemeriksaannya, toolbar-nya, dan aturan gambar thumbnail-nya
 * menyimpang pada satu versi.
 */
class KontenMateriController extends Controller
{
    public function create(): View
    {
        return view('admin.konten-materi', [
            'kategori' => Pelajaran::untukForm(),
            'materi' => null,
            'bab' => null,
        ]);
    }

    /**
     * Simpan materi baru.
     *
     * Statusnya ditentukan oleh tombol yang ditekan, bukan oleh saklar
     * terpisah: "Simpan Draft" menyimpan sebagai draft, "Publish Sekarang"
     * langsung menerbitkannya. Keduanya menyimpan isi yang sama persis.
     *
     * Kalau form datang tanpa field "aksi" sama sekali, statusnya diambil dari
     * preferensi admin (App\Models\Preferensi::status_konten_default). Bawaannya
     * "draft", jadi perilaku saat ini tidak berubah; admin yang mengubahnya di
     * Pengaturan tetap bisa membuat konten baru yang langsung tayang tanpa
     * menekan tombol Publish Sekarang.
     */
    public function store(MateriIsianRequest $request): RedirectResponse
    {
        $data = $request->isian();
        $terbit = $this->mauTerbit($request);
        $admin = $request->user();

        $materi = new Materi([
            ...$data,
            ...BerkasMateri::simpan($request),
            'dibuat_oleh' => $admin->getKey(),
            'slug' => DaftarKonten::slugUnik($data['nama'], Materi::class),
            'status' => $request->has('aksi')
                ? Materi::STATUS_DRAFT
                : Preferensi::ambil($admin)->status_konten_default,
        ]);

        $materi->save();

        if ($terbit) {
            $this->terbitkan($request, $materi);
        } else {
            NotifikasiAdmin::kontenDisimpanDraft($materi, $admin);
        }

        return redirect()
            ->route('admin.konten', ['tab' => 'materi'])
            ->with('sukses', $this->pesanSimpan($terbit))
            ->with('suksesDetail', $this->detailSimpan($terbit));
    }

    public function edit(Request $request, string $materi): View
    {
        $item = $this->cariMilik($request, $materi);

        return view('admin.konten-materi', [
            'kategori' => Pelajaran::untukForm($item->pelajaran_id),
            'materi' => $item,
            // Daftar bab dipecah lagi dari isi tersimpan, supaya editor dibuka
            // dengan bab yang sama seperti waktu materi dibuat.
            'bab' => BabMateri::dariIsi($item->isi),
        ]);
    }

    /**
     * Pratinjau materi yang sedang disusun.
     *
     * Menerima dua isian yang sudah disusun JavaScript (judul dan isi gabung
     * seluruh bab) dan mengembalikan fragment HTML — Daftar Isi plus kartu
     * seksi — dari view yang sama dengan halaman detail. Yang dikirim balik
     * bukan halaman utuh karena pemanggilnya hanya menukar isi satu wadah di
     * dalam form.
     *
     * Endpoint ini yang membuat pratinjau bisa sama persis dengan halaman
     * detail, termasuk blok kode yang diwarnai dan Daftar Isi. Menyalin
     * aturan pemecahan isi ke JavaScript hanya akan menghasilkan versi kedua
     * yang pasti menyimpang begitu salah satu sisi berubah; merakit model dan
     * memanggil pemecah yang sama membuat keduanya tidak mungkin berbeda.
     */
    public function pratinjau(Request $request): Response
    {
        $nama = (string) $request->input('nama', '');
        $isi = (string) $request->input('isi', '');

        if (PratinjauMateri::melebihiBatas($nama, $isi)) {
            return response('', 422);
        }

        return response()->view('admin.materi-detail-isi', [
            'detail' => PratinjauMateri::detail(
                $nama,
                $isi,
                tautanDaftar: route('admin.konten', ['tab' => 'materi']),
                tautanLatihan: route('admin.quiz'),
                tingkatKesulitan: $request->input('tingkat_kesulitan'),
                pelajaran: Pelajaran::query()->find($request->input('pelajaran_id')),
                pembuat: $request->user(),
            ),
        ]);
    }

    /**
     * Simpan perubahan admin.
     *
     * Status lama tidak ikut berubah di sini. Mengubah isi materi yang sudah
     * tayang tidak menariknya dari halaman pengguna, dan tidak menerbitkannya
     * kalau sebelumnya masih draft: status hanya berubah lewat tombol
     * terbitkan atau batalkan terbitkan di daftar.
     */
    public function update(MateriIsianRequest $request, string $materi): RedirectResponse
    {
        $item = $this->cariMilik($request, $materi);

        /*
         * Tombol Hapus pada thumbnail dikirim sebagai thumbnail_hapus. Berkas
         * barunya menang kalau admin juga mengunggah pengganti, jadi bendera
         * ini hanya berarti kalau tidak ada berkas baru.
         */
        $berkasBaru = BerkasMateri::simpan($request);
        $buangThumbnail = $request->boolean('thumbnail_hapus') && ! isset($berkasBaru['thumbnail']);

        /*
         * Berkas lama ditangkap sebelum fill(): begitu diisi, kolomnya sudah
         * menunjuk berkas baru, jadi berkas lama yang justru harus dibuang
         * bisa terlewat dan menumpuk di disk.
         */
        $berkasLama = $item->thumbnail;

        $item->fill([
            ...$request->isian(),
            ...$berkasBaru,
            ...($buangThumbnail ? ['thumbnail' => null] : []),
        ])->save();

        if (isset($berkasBaru['thumbnail']) || $buangThumbnail) {
            BerkasMateri::hapus($berkasLama);
        }

        // Materi yang masih draft ikut memberi tahu lewat saklar "Konten
        // disimpan sebagai draft". Materi yang sudah tayang tidak, karena
        // statusnya tidak berubah karena diedit.
        if ($item->status === Materi::STATUS_DRAFT) {
            NotifikasiAdmin::kontenDisimpanDraft($item, $request->user());
        }

        return redirect()
            ->route('admin.konten', ['tab' => 'materi'])
            ->with('sukses', 'Materi "'.$item->nama.'" berhasil diperbarui.')
            ->with('suksesDetail', 'Perubahan isinya langsung dipakai materi yang sudah tayang.');
    }

    /**
     * Terbitkan atau batalkan terbitkan.
     *
     * Satu aksi untuk dua arah, jadi tombolnya cukup satu dan klik ganda tidak
     * pernah menghasilkan dua perubahan. Menerbitkan mengisi tanggal terbit
     * dan mengirim notifikasi ke pengguna; membatalkan terbitkan tidak
     * mengirim apa pun, karena tidak ada perubahan yang perlu diberitahukan.
     */
    public function publish(Request $request, string $materi): RedirectResponse
    {
        $item = $this->cariMilik($request, $materi);

        if ($item->status === Materi::STATUS_PUBLISHED) {
            $item->tarikDariDaftar();

            return redirect()
                ->route('admin.konten', ['tab' => 'materi'])
                ->with('sukses', 'Publikasi materi dibatalkan.')
                ->with('suksesDetail', 'Materi "'.$item->nama.'" kembali menjadi draft dan tidak muncul di halaman Materi.');
        }

        $this->terbitkan($request, $item);

        return redirect()
            ->route('admin.konten', ['tab' => 'materi'])
            ->with('sukses', 'Berhasil dipublikasikan')
            ->with('suksesDetail', 'Materi berhasil dipublikasikan dan tersedia untuk pengguna.');
    }

    /**
     * Salin materi jadi materi baru berstatus draft.
     *
     * Duplikat selalu draft, tidak pernah langsung terbit: admin yang membuat
     * salinan masih harus memeriksa isinya sebelum menayangkannya.
     *
     * Thumbnail tidak ikut disalin. Kolomnya menyimpan path berkas di disk,
     * jadi menyalinnya membuat dua baris menunjuk berkas yang sama — dan
     * menghapus salah satunya akan ikut menghapus gambar milik yang lain.
     * Materi hasil duplikat diisi thumbnail sendiri seperti materi baru.
     */
    public function duplikat(Request $request, string $materi): RedirectResponse
    {
        $item = $this->cariMilik($request, $materi);
        $judul = $item->nama.' (Salinan)';

        $salinan = new Materi([
            'pelajaran_id' => $item->pelajaran_id,
            'dibuat_oleh' => $item->dibuat_oleh,
            'nama' => $judul,
            'slug' => DaftarKonten::slugUnik($judul, Materi::class),
            'deskripsi' => $item->deskripsi,
            'isi' => $item->isi,
            'tingkat_kesulitan' => $item->tingkat_kesulitan,
            'estimasi_waktu' => $item->estimasi_waktu,
            'tips' => $item->tips,
            'status' => Materi::STATUS_DRAFT,
        ]);

        $salinan->save();

        return redirect()
            ->route('admin.konten.materi.edit', $salinan->slug)
            ->with('sukses', 'Materi diduplikat sebagai draft.')
            ->with('suksesDetail', 'Periksa isinya, lalu terbitkan kalau sudah siap.');
    }

    /**
     * Hapus materi beserta thumbnailnya.
     *
     * Baris simpanan pengguna (tb_simpanan_materi) ikut terhapus karena
     * foreign key-nya cascadeOnDelete, jadi tidak ada sisa bookmark yang
     * menunjuk materi yang sudah tidak ada.
     */
    public function destroy(Request $request, string $materi): RedirectResponse
    {
        $item = $this->cariMilik($request, $materi);
        $nama = $item->nama;

        BerkasMateri::hapus($item->thumbnail);
        $item->delete();

        return redirect()
            ->route('admin.konten', ['tab' => 'materi'])
            ->with('sukses', 'Materi "'.$nama.'" berhasil dihapus.')
            ->with('suksesDetail', 'Materi ini tidak lagi bisa dibuka dari halaman Materi.');
    }

    /**
     * Terbitkan materi lalu kirim notifikasi ke pengguna.
     */
    private function terbitkan(Request $request, Materi $materi): void
    {
        $materi->terbitkan();

        NotifikasiKonten::kirim($materi, $request->user()?->getKey());

        if ($request->user() !== null) {
            NotifikasiAdmin::kontenDiterbitkan($materi, $request->user());
        }
    }

    /**
     * Admin menekan tombol "Publish Sekarang", bukan "Simpan Draft".
     *
     * Yang diperiksa hanya field "aksi" milik form admin, jadi form milik
     * pengguna yang punya saklar bernama lain tidak ikut terpengaruh.
     */
    private function mauTerbit(MateriIsianRequest $request): bool
    {
        return $request->input('aksi') === 'publish';
    }

    private function pesanSimpan(bool $terbit): string
    {
        return $terbit ? 'Berhasil dipublikasikan' : 'Draft berhasil disimpan';
    }

    private function detailSimpan(bool $terbit): string
    {
        return $terbit
            ? 'Materi berhasil dipublikasikan dan tersedia untuk pengguna.'
            : 'Konten dapat dilanjutkan dan dipublikasikan kapan saja.';
    }

    /**
     * Cari materi dari slug, lalu pastikan itu karya admin yang sedang login.
     *
     * Status tidak diperiksa di sini: admin boleh membuka form apa pun yang
     * ada di daftarnya, termasuk yang masih draft. Yang diperiksa adalah
     * pemiliknya, supaya daftar dan kemampuannya tidak berbeda — kalau
     * Someone mengetik URL materi orang lain, jawabannya 403, bukan form
     * milik orang lain yang terbuka.
     */
    private function cariMilik(Request $request, string $slug): Materi
    {
        $item = Materi::query()
            ->with(['pelajaran', 'pembuat'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($item->dimilikiOleh($request->user()?->getKey()), 403);

        return $item;
    }
}
