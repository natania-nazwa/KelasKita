<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizIsianRequest;
use App\Models\Pelajaran;
use App\Models\Preferensi;
use App\Models\Quiz;
use App\Support\BerkasQuiz;
use App\Support\DaftarKonten;
use App\Support\KodeQuiz;
use App\Support\NotifikasiAdmin;
use App\Support\NotifikasiKonten;
use App\Support\SimpanSoalQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tambah, ubah, hapus, duplikat, terbitkan, dan batalkan terbitkan quiz milik
 * admin sendiri, dari menu "Konten Pembelajaran".
 *
 * Berbeda dari Admin\QuizKelolaController yang sudah ada, dan perbedaannya
 * disengaja:
 *
 *   - route-nya sendiri, di bawah /admin/konten, supaya tidak menyenggol
 *     halaman "Quiz" yang tetap mengelola kumpulan konten published;
 *   - memakai pembatas "hanya milik admin", sama seperti
 *     Admin\QuizKelolaController dan halaman "Karya Saya" milik pengguna.
 *     Daftar di Konten Pembelajaran hanya berisi karya admin yang sedang
 *     login, jadi form, terbitkan, dan hapus di sini juga hanya untuk karya
 *     itu. Quiz buatan pengguna tetap managing lewat Verifikasi dan lewat
 *     katalog Quiz;
 *   - memakai tiga tahap wizard yang sama dengan form Quiz milik pengguna,
 *     termasuk langkah Pengaturan. Dua isian milik alur pemilik tidak
 *     dirender di sana: blok "Ajukan Persetujuan" (admin adalah pihak yang
 *     menerbitkan, jadi tidak ada yang perlu diajukan) dan pilihan cara
 *     publikasi beserta kode aksesnya (konten dari area admin selalu terbit
 *     untuk semua pengguna, jadi tidak ada kode yang perlu dibagikan);
 *   - statusnya hanya "draft" dan "published". Tidak ada pending, tidak ada
 *     rejected, tidak ada tombol setujui.
 *
 * Builder soalnya bukan ditulis ulang. Form ini memakai komponen wizard yang
 * sama dengan form Quiz milik pengguna (x-quiz.wizard-informasi, .wizard-soal,
 * .wizard-pengaturan) dan JavaScript yang sama (resources/js/quiz-tambah.js,
 * .quiz-builder.js).
 */
class KontenQuizController extends Controller
{
    public function create(): View
    {
        return view('admin.konten-quiz', [
            'kategori' => Pelajaran::query()->aktif()->urutKatalog()->get(),
            'quiz' => null,
            'soal' => null,
            'kodeAwal' => KodeQuiz::unik(),
        ]);
    }

    /**
     * Simpan quiz baru.
     *
     * Visibilitasnya dipaksa "public" dan dipanggil dari sini, bukan dibaca
     * dari request: konten yang terbit dari halaman ini selalu tayang untuk
     * semua pengguna, jadi tidak ada kode akses yang perlu disimpan. Form-nya
     * juga tidak menawarkannya lagi (lihat x-quiz.wizard-pengaturan), tapi
     * pemaksan di sini tetap perlu supaya isian yang datang dari luar
     * form — atau dari versi form yang lama — tidak bisa membuat quiz privat
     * di ruang kerja yang tidak punya cara membagikannya.
     */
    public function store(QuizIsianRequest $request): RedirectResponse
    {
        $data = $request->isian();
        $terbit = $this->mauTerbit($request);
        $admin = $request->user();

        $quiz = Quiz::create([
            'pelajaran_id' => $data['pelajaran_id'],
            'dibuat_oleh' => $admin->getKey(),
            'judul' => $data['judul'],
            'slug' => DaftarKonten::slugUnik($data['judul'], Quiz::class),
            'deskripsi' => $data['deskripsi'],
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'tampilkan_jawaban' => $data['tampilkan_jawaban'],
            'kode_akses' => null,
            /*
             * Kalau form datang tanpa field "aksi" sama sekali, statusnya
             * diambil dari preferensi admin. Bawaannya "draft", jadi perilaku
             * saat ini tidak berubah; admin yang mengubahnya di Pengaturan
             * tetap bisa membuat quiz baru yang langsung tayang tanpa menekan
             * tombol Publish Sekarang.
             */
            'status' => $request->has('aksi')
                ? Quiz::STATUS_DRAFT
                : Preferensi::ambil($admin)->status_konten_default,
            ...BerkasQuiz::simpan($request),
        ]);

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        if ($terbit) {
            $this->terbitkan($request, $quiz);
        } else {
            NotifikasiAdmin::kontenDisimpanDraft($quiz, $admin);
        }

        return redirect()
            ->route('admin.konten', ['tab' => 'quiz'])
            ->with('sukses', $this->pesanSimpan($terbit))
            ->with('suksesDetail', $this->detailSimpan($terbit));
    }

    public function edit(Request $request, Quiz $quiz): View
    {
        $this->pastikanMilik($request, $quiz);

        return view('admin.konten-quiz', [
            'kategori' => Pelajaran::query()->aktif()->urutKatalog()->get(),
            'quiz' => $quiz,
            // Semua soal, bukan hanya yang aktif: kalau ada soal yang
            // dinonaktifkan, form edit harus tetap bisa melihatnya.
            'soal' => $quiz->soal()->terurut()->get(),
            'kodeAwal' => $quiz->kode_akses ?? KodeQuiz::unik(),
        ]);
    }

    /**
     * Simpan perubahan admin.
     *
     * Status lama tidak ikut berubah di sini, sama seperti pada materi:
     * menyunting isi quiz yang sudah tayang tidak menariknya dari halaman
     * Quiz, dan tidak menerbitkannya kalau sebelumnya masih draft. Status
     * hanya berubah lewat tombol terbitkan atau batalkan terbitkan di daftar.
     *
     * Visibilitas dan kode akses ikut dipaksa ke "public" dan kosong, sama
     * seperti di store(). Tujuannya supaya quiz yang dibuat sebelum pilihan
     * cara publikasi dihapus kembali ikut tayang untuk semua pengguna begitu
     * disunting di sini, bukan menggantung sebagai quiz privat yang tidak
     * ada cara membagikannya.
     */
    public function update(QuizIsianRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $data = $request->isian();

        /*
         * Thumbnail lama ditangkap sebelum save(): begitu save() selesai,
         * getOriginal() sudah berisi nilai baru, jadi berkas yang justru baru
         * disimpan bisa ikut terhapus dan gambarnya tampil rusak di mana pun
         * quiz ini ditampilkan.
         */
        $thumbnailLama = $quiz->thumbnail;
        $thumbnailBaru = BerkasQuiz::simpan($request)['thumbnail'] ?? null;

        $buangThumbnail = $request->boolean('thumbnail_hapus') && $thumbnailBaru === null;

        $quiz->fill([
            'pelajaran_id' => $data['pelajaran_id'],
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'tampilkan_jawaban' => $data['tampilkan_jawaban'],
            'kode_akses' => null,
            ...($thumbnailBaru !== null
                ? ['thumbnail' => $thumbnailBaru]
                : ($buangThumbnail ? ['thumbnail' => null] : [])),
        ])->save();

        if ($thumbnailBaru !== null || $buangThumbnail) {
            BerkasQuiz::hapus($thumbnailLama);
        }

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        // Quiz yang masih draft ikut memberi tahu lewat saklar "Konten disimpan
        // sebagai draft". Quiz yang sudah tayang tidak, karena statusnya tidak
        // berubah karena diedit.
        if ($quiz->status === Quiz::STATUS_DRAFT) {
            NotifikasiAdmin::kontenDisimpanDraft($quiz, $request->user());
        }

        return redirect()
            ->route('admin.konten', ['tab' => 'quiz'])
            ->with('sukses', 'Quiz "'.$quiz->judul.'" berhasil diperbarui.')
            ->with('suksesDetail', $quiz->status === Quiz::STATUS_PUBLISHED
                ? 'Perubahan soalnya langsung dipakai quiz yang sudah tayang.'
                : 'Quiz masih berupa draft. Terbitkan saat sudah siap.');
    }

    /**
     * Terbitkan atau batalkan terbitkan.
     *
     * Satu aksi untuk dua arah, jadi tombolnya cukup satu dan klik ganda tidak
     * pernah menghasilkan dua perubahan.
     */
    public function publish(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        if ($quiz->status === Quiz::STATUS_PUBLISHED) {
            $quiz->tarikDariDaftar();

            return redirect()
                ->route('admin.konten', ['tab' => 'quiz'])
                ->with('sukses', 'Publikasi quiz dibatalkan.')
                ->with('suksesDetail', 'Quiz "'.$quiz->judul.'" kembali menjadi draft dan tidak muncul di halaman Quiz.');
        }

        /*
         * Penjaga untuk baris yang masih memakai kode, yaitu quiz yang dibuat
         * sebelum pilihan cara publikasi dihapus dari form ini. Halaman Quiz
         * menampilkan semua quiz berstatus published tanpa memeriksa cara
         * aksesnya, jadi menerbitkannya akan menayangkan quiz privat ke semua
         * orang. Form di halaman edit sudah mengembalikan baris seperti ini ke
         * "public"; penjaga ini yang menjawabnya kalau admin menekan
         * tombol terbit sebelum sempat menyuntingnya.
         */
        if ($quiz->pakaiKode()) {
            return redirect()
                ->route('admin.konten', ['tab' => 'quiz'])
                ->with('sukses', 'Quiz ini masih memakai kode, jadi tidak bisa diterbitkan.')
                ->with('suksesDetail', 'Buka form quiz ini sekali, lalu terbitkan lagi dari daftar.');
        }

        $this->terbitkan($request, $quiz);

        return redirect()
            ->route('admin.konten', ['tab' => 'quiz'])
            ->with('sukses', 'Berhasil dipublikasikan')
            ->with('suksesDetail', 'Kuis berhasil dipublikasikan dan tersedia untuk pengguna.');
    }

    /**
     * Salin quiz jadi quiz baru berstatus draft, lengkap dengan soalnya.
     *
     * Duplikat selalu draft, tidak pernah langsung terbit.
     *
     * Tiga hal sengaja tidak ikut disalin: thumbnail, karena kolomnya
     * menyimpan path berkas di disk dan menyalinnya membuat dua baris menunjuk
     * berkas yang sama; kode akses, karena kode harus unik dan kode yang
     * sama tidak akan bisa dipakai; dan status, karena terbit atau tidaknya
     * salinan itu keputusan admin sendiri lewat tombolnya.
     *
     * Salinan selalu memakai cara publikasi "public" seperti formnya: dari
     * menu ini tidak ada kode yang perlu dibagikan.
     */
    public function duplikat(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $judul = $quiz->judul.' (Salinan)';

        $salinan = Quiz::create([
            'pelajaran_id' => $quiz->pelajaran_id,
            'dibuat_oleh' => $quiz->dibuat_oleh,
            'judul' => $judul,
            'slug' => DaftarKonten::slugUnik($judul, Quiz::class),
            'deskripsi' => $quiz->deskripsi,
            'durasi' => $quiz->durasi,
            'tingkat_kesulitan' => $quiz->tingkat_kesulitan,
            'tampilkan_jawaban' => $quiz->tampilkan_jawaban,
            'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
            'kode_akses' => null,
            'status' => Quiz::STATUS_DRAFT,
        ]);

        SimpanSoalQuiz::salin($quiz, $salinan);

        return redirect()
            ->route('admin.konten.quiz.edit', $salinan)
            ->with('sukses', 'Quiz diduplikat sebagai draft.')
            ->with('suksesDetail', 'Periksa soalnya, lalu terbitkan kalau sudah siap.');
    }

    /**
     * Hapus quiz beserta soal dan thumbnailnya.
     *
     * tb_soal punya foreign key ke tb_quiz dengan cascadeOnDelete, jadi soal
     * quiz ini ikut terhapus. Baris lain yang menunjuk quiz — nilai, jawaban,
     * sesi, dan simpanan — semuanya ikut cascade dari tb_quiz.
     */
    public function destroy(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $judul = $quiz->judul;
        $thumbnail = $quiz->thumbnail;

        // Tetap dihapus eksplisit walau foreign key-nya cascade, supaya
        // perilakunya sama persis dengan User\QuizKelolaController::destroy
        // dan tidak bergantung pada engine database yang dipakai.
        $quiz->soal()->delete();
        $quiz->delete();

        BerkasQuiz::hapus($thumbnail);

        return redirect()
            ->route('admin.konten', ['tab' => 'quiz'])
            ->with('sukses', 'Quiz "'.$judul.'" berhasil dihapus.')
            ->with('suksesDetail', 'Quiz ini tidak lagi bisa dibuka dari halaman Quiz.');
    }

    /**
     * Pastikan quiz ini karya admin yang sedang login.
     *
     * Daftar di Konten Pembelajaran hanya berisi karya sendiri, jadi kemampuannya
     * juga begitu. Tanpa penjaga ini, mengetik URL quiz orang lain akan membuka
     * form edit, terbit, duplikat, atau hapus untuk karya yang bukan miliknya.
     */
    private function pastikanMilik(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->dimilikiOleh($request->user()?->getKey()), 403);
    }

    /**
     * Terbitkan quiz lalu kirim notifikasi ke pengguna.
     */
    private function terbitkan(Request $request, Quiz $quiz): void
    {
        $quiz->terbitkan();

        NotifikasiKonten::kirim($quiz, $request->user()?->getKey());

        if ($request->user() !== null) {
            NotifikasiAdmin::kontenDiterbitkan($quiz, $request->user());
        }
    }

    /**
     * Admin menekan tombol "Publish Sekarang", bukan "Simpan Draft".
     */
    private function mauTerbit(QuizIsianRequest $request): bool
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
            ? 'Kuis berhasil dipublikasikan dan tersedia untuk pengguna.'
            : 'Konten dapat dilanjutkan dan dipublikasikan kapan saja.';
    }
}
