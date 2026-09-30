<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizIsianRequest;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\BerkasQuiz;
use App\Support\KodeQuiz;
use App\Support\SimpanSoalQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Edit dan hapus satu quiz dari area admin.
 *
 * Route yang sudah ada (user.quiz.edit / user.quiz.destroy) milik pemilik
 * quiz dan menolak admin dengan 403, jadi admin tidak punya jalan untuk
 * mengubah maupun menghapus karya orang lain. Controller ini mengisi
 * kekosongan itu, dan sengaja tidak memeriksa apakah quiz yang dihapus
 * dibuat admin atau pengguna: yang diurus di sini adalah konten yang sudah
 * tayang, dan quiz-nya memang harus bisa ditarik admin kapan saja.
 *
 * Edit berbeda. Hanya quiz yang dibuat admin sendiri yang boleh diubah:
 * karya pengguna boleh dibaca dan dihapus dari sini, tapi soal dan
 * pembahasannya milik penulisnya. Aturan yang sama sudah dipakai saat
 * memetakan daftar (App\Support\DaftarQuizAdmin::petikan), supaya tombol
 * Edit tidak pernah muncul untuk sesuatu yang pasti ditolak 403.
 *
 * Form edit memakai komponen wizard yang sama dengan halaman Tambah Quiz
 * milik pengguna (x-quiz.wizard-informasi, .wizard-soal, .wizard-pengaturan)
 * dan JavaScript yang sama, jadi isian dan builder soalnya tidak mungkin
 * menyimpang dari form yang dipakai pemilik. Yang berbeda hanya judul,
 * penjelas, tujuan simpan, dan tiga hal yang memang milik alur pemilik:
 * tujuan tombol Batal, teks tombol Draft, dan blok "Ajukan Persetujuan"
 * yang disembunyikan lewat prop $admin.
 */
class QuizKelolaController extends Controller
{
    public function edit(Request $request, Quiz $quiz): View
    {
        $this->pastikanMilik($request, $quiz);

        $quiz->loadMissing(['pelajaran', 'pembuat']);

        return view('admin.quiz-edit', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
            'quiz' => $quiz,
            // Semua soal, bukan hanya yang aktif: kalau ada soal yang
            // dinonaktifkan, form edit harus tetap bisa melihat dan
            // mengembalikannya, bukan diam-diam menghapusnya.
            'soal' => $quiz->soal()->terurut()->get(),
            // Quiz tanpa kode (mis. quiz publik yang belum pernah punya
            // kode) tetap membuka form dengan kode acak, supaya admin bisa
            // memakai tombol "Generate Kode" tanpa mengetik dari nol.
            'kodeAwal' => $quiz->kode_akses ?? KodeQuiz::unik(),
        ]);
    }

    /**
     * Simpan perubahan admin.
     *
     * Status quiz tidak pernah ikut turun ke "menunggu" di sini. Aturan itu
     * milik pemilik: revisi pemilik harus diinjau ulang supaya perubahannya
     * tidak langsung menjangkau pengguna tanpa pemeriksaan. Admin justru
     * pihak yang menyetujui konten tersebut, jadi quiz-nya tetap tayang
     * setelah diperbarui — termasuk tanggal terbitnya, yang sengaja
     * dibiarkan apa adanya supaya urutan "Terbaru" di daftar tidak ikut
     * berubah karena suntingan admin.
     */
    public function update(QuizIsianRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->pastikanMilik($request, $quiz);

        $data = $request->isian();

        /*
         * Thumbnail lama ditangkap sebelum save(): begitu save() selesai,
         * getOriginal() sudah berisi nilai baru, jadi berkas yang justru
         * baru disimpan bisa ikut terhapus dan gambarnya tampil rusak di mana
         * pun quiz ini ditampilkan.
         */
        $thumbnailLama = $quiz->thumbnail;
        $thumbnailBaru = BerkasQuiz::simpan($request)['thumbnail'] ?? null;

        /*
         * Tombol Hapus pada thumbnail dikirim sebagai thumbnail_hapus. Kalau
         * admin juga mengunggah berkas baru, berkas barulah yang menang —
         * JavaScript sudah menurunkan bendera itu begitu gambar dipilih, tapi
         * di sini dua-duanya dicek lagi.
         */
        $buangThumbnail = $request->boolean('thumbnail_hapus') && $thumbnailBaru === null;

        /*
         * Quiz yang tayang lalu diganti jadi mode kode ditarik dari halaman
         * Quiz. Mode kode hanya bisa dibuka lewat kodenya, jadi
         * membiarkannya tetap tayang akan menayangkan quiz privat ke semua
         * orang. Quiz::tarikDariDaftar() sudah tidak melakukan apa-apa kalau
         * statusnya belum published, jadi tidak perlu dicek ulang di sini.
         */
        $menjadiKode = $data['visibilitas'] === Quiz::VISIBILITAS_PRIVAT;

        $quiz->fill([
            'pelajaran_id' => $data['pelajaran_id'],
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => $data['visibilitas'],
            'tampilkan_jawaban' => $data['tampilkan_jawaban'],
            'kode_akses' => $data['kode_akses'] ?? null,
            'catatan_pengajuan' => $data['catatan_pengajuan'] ?? null,
            ...($thumbnailBaru !== null
                ? ['thumbnail' => $thumbnailBaru]
                : ($buangThumbnail ? ['thumbnail' => null] : [])),
        ])->save();

        // Berkas lama dibuang hanya kalau memang ada penggantinya — baik
        // berkas baru maupun permintaan hapus dari admin.
        if ($thumbnailBaru !== null || $buangThumbnail) {
            BerkasQuiz::hapus($thumbnailLama);
        }

        if ($menjadiKode) {
            $quiz->tarikDariDaftar();
        }

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        return redirect()
            ->route('admin.quiz.show', $quiz)
            ->with('sukses', $this->pesanSimpan($quiz, $menjadiKode));
    }

    /**
     * Pesan setelah quiz edit tersimpan.
     *
     * Diberi kasus tersendiri karena akibatnya berbeda: quiz yang tayang lalu
     * ditarik kembali ke draft hilang dari halaman Quiz, jadi admin perlu tahu
     * supaya tidak mengira quiznya masih dibaca.
     */
    private function pesanSimpan(Quiz $quiz, bool $menjadiKode): string
    {
        if ($menjadiKode) {
            return 'Quiz "'.$quiz->judul.'" diperbarui dan sekarang memakai kode '.$quiz->kodeGabung().'. Quiz ini ditarik dari halaman Quiz karena mode kode tidak tayang untuk semua pengguna.';
        }

        return 'Quiz "'.$quiz->judul.'" berhasil diperbarui.';
    }

    /**
     * Hapus quiz beserta soal dan thumbnailnya.
     *
     * tb_soal punya foreign key ke tb_quiz dengan cascadeOnDelete, jadi soal
     * quiz ini ikut terhapus. Baris lain yang menunjuk quiz — nilai,
     * jawaban, sesi, dan simpanan — semuanya ikut cascade dari tb_quiz, jadi
     * tidak ada sisa yang menunjuk quiz yang sudah tidak ada.
     *
     * Berkas thumbnail dihapus setelah kovery dihapus supaya tidak tertinggal
     * sebagai gambar yatim di disk publik.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $judul = $quiz->judul;
        $thumbnail = $quiz->thumbnail;

        // Tetap dihapus eksplisit walau foreign key-nya cascade, supaya
        // perilakunya sama persis dengan User\QuizKelolaController::destroy
        // dan tidak bergantung pada engine database yang dipakai.
        $quiz->soal()->delete();
        $quiz->delete();

        BerkasQuiz::hapus($thumbnail);

        return redirect()
            ->route('admin.quiz')
            ->with('sukses', 'Quiz "'.$judul.'" berhasil dihapus.');
    }

    /**
     * Quiz yang bukan karya admin yang sedang login tidak boleh dibuka untuk
     * diedit. Sama seperti materi, hapus tidak dibatasi: konten yang sudah
     * tayang harus bisa ditarik admin, siapa pun yang membuatnya.
     */
    private function pastikanMilik(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->dimilikiOleh($request->user()?->getKey()), 403);
    }
}
