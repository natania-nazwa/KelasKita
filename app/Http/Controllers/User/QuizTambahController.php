<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizIsianRequest;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\SimpanSoalQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Form tambah quiz milik sendiri.
 *
 * Tombol "Buat Quiz" ada di halaman "Karya Saya" (menu khusus yang mengelola
 * konten pribadi), dan form ini yang dibuka tombol tersebut.
 *
 * *_soal dikirim sebagai array angka (soal[0][pertanyaan], soal[0][pilihan_a],
 * dst.) supaya satu form bisa memuat banyak soal sekaligus. Aturannya ada di
 * App\Http\Requests\QuizIsianRequest.
 */
class QuizTambahController extends Controller
{
    public function create(): View
    {
        return view('user.quiz-tambah', [
            'kategori' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
        ]);
    }

    public function store(QuizIsianRequest $request): RedirectResponse
    {
        $data = $request->isian();

        /*
         * Quiz baru langsung disimpan sebagai "pending": pemilik tidak boleh
         * memublikasikan sendiri, admin yang memutuskan. Status ini juga
         * membuat quiz tetap terlihat di "Karya Saya" walau belum tayang.
         */
        $quiz = Quiz::create([
            'pelajaran_id' => $data['pelajaran_id'],
            'dibuat_oleh' => $request->user()->getKey(),
            'judul' => $data['judul'],
            'slug' => $this->slugUnik($data['judul']),
            'deskripsi' => $data['deskripsi'] ?? null,
            'durasi' => $data['durasi'] ?? null,
            'visibilitas' => $data['visibilitas'],
            'status' => Quiz::STATUS_PENDING,
            'kode_akses' => $data['kode_akses'] ?? null,
        ]);

        SimpanSoalQuiz::ganti($quiz, $data['soal']);

        return redirect()
            ->route('user.karya-saya', ['tab' => 'quiz'])
            ->with('sukses', 'Quiz "'.$quiz->judul.'" tersimpan dan menunggu persetujuan admin.');
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
