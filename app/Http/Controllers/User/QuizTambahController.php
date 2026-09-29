<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizIsianRequest;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\BerkasQuiz;
use App\Support\KodeQuiz;
use App\Support\SimpanSoalQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Form tambah quiz milik sendiri.
 *
 * Tombol "Buat Quiz" ada di halaman "Karya Saya" (menu khusus yang mengelola
 * konten pribadi), dan wizard tiga tahap ini yang dibuka tombol tersebut.
 *
 * Halaman ini juga dipakai untuk mengubah quiz (lihat
 * User\QuizKelolaController), jadi isian awal, tujuan form, dan isi tombol
 * mengikuti $quiz. Form tambah dan form edit sengaja tidak dipisah.
 *
 * *_soal dikirim sebagai array angka (soal[0][pertanyaan], soal[0][pilihan_a],
 * dst.) supaya satu form bisa memuat banyak soal sekaligus. Aturannya ada di
 * App\Http\Requests\QuizIsianRequest, dan urutan barisnya disusun ulang oleh
 * resources/js/quiz-tambah.js setiap kali soal ditambah, dihapus, atau
 * diurutkan ulang.
 *
 * Status awal quiz ditentukan di store(): mode kode langsung draft dan siap
 * dipakai, mode publik baru "pending" kalau pemilik menyalakan saklar
 * "Ajukan Persetujuan".
 */
class QuizTambahController extends Controller
{
    public function create(): View
    {
        return view('user.quiz-tambah', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
            'kodeAwal' => KodeQuiz::unik(),
        ]);
    }

    public function store(QuizIsianRequest $request): RedirectResponse
    {
        $data = $request->isian();

        /*
         * Quiz punya dua jalan masuk yang berbeda.
         *
         * Quiz mode kode tidak pernah tayang untuk semua pengguna, jadi tidak
         * ada yang perlu disetujui dan statusnya tetap draft. Sesi kodenya
         * tetap bisa dibuka pemiliknya kapan saja.
         *
         * Quiz mode publik tayang untuk semua pengguna, jadi tidak boleh
         * diminta lewat tombol: saklar "Ajukan Persetujuan" hanya membuat
         * statusnya jadi "pending" supaya masuk daftar tunggu admin. Yang
         * benar-benar tayang hanya quiz yang sudah disetujui.
         */
        $diajukan = $request->diajukanKeAdmin();

        $quiz = Quiz::create([
            'pelajaran_id' => $data['pelajaran_id'],
            'dibuat_oleh' => $request->user()->getKey(),
            'judul' => $data['judul'],
            'slug' => $this->slugUnik($data['judul']),
            'deskripsi' => $data['deskripsi'],
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => $data['visibilitas'],
            'tampilkan_jawaban' => $data['tampilkan_jawaban'],
            'status' => $diajukan ? Quiz::STATUS_PENDING : Quiz::STATUS_DRAFT,
            'kode_akses' => $data['kode_akses'] ?? null,
            'catatan_pengajuan' => $data['catatan_pengajuan'] ?? null,
            ...BerkasQuiz::simpan($request),
        ]);

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        return redirect()
            ->route('user.karya-saya', ['tab' => 'quiz'])
            ->with('sukses', $this->pesanSimpan($quiz, $diajukan));
    }

    /**
     * Pesan setelah quiz baru tersimpan.
     *
     * Diberi kasus tersendiri karena akibatnya berbeda: quiz yang sudah
     * tayang akan dijumpai semua orang lewat halaman Quiz, sedangkan quiz
     * mode kode hanya bisa dibuka lewat kode yang baru dibuat.
     */
    private function pesanSimpan(Quiz $quiz, bool $diajukan): string
    {
        if ($diajukan) {
            return 'Quiz "'.$quiz->judul.'" tersimpan dan menunggu persetujuan admin.';
        }

        return $quiz->pakaiKode()
            ? 'Quiz "'.$quiz->judul.'" tersimpan. Bagikan kode '.$quiz->kodeGabung().' ke peserta, lalu buka sesinya saat mereka sudah masuk.'
            : 'Quiz "'.$quiz->judul.'" tersimpan sebagai draft.';
    }

    /**
     * Slug dari judul, diberi akhiran angka bila slug-nya sudah dipakai
     * quiz lain.
     */
    private function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul) ?: 'quiz';
        $slug = $dasar;
        $urutan = 2;

        while (Quiz::query()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.$urutan;
            $urutan++;
        }

        return $slug;
    }
}
