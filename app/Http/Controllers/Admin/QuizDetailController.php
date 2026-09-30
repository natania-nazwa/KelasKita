<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Support\DaftarQuiz;
use App\Support\DaftarSoal;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman detail quiz di area admin.
 *
 * Isinya bukan pratinjau terpisah: halaman ini merender komponen yang
 * sama persis dengan halaman detail milik pengguna
 * (resources/views/components/quiz/detail-*.blade.php) dan pemecah yang
 * sama (App\Support\DaftarQuiz), jadi admin membaca judul, deskripsi,
 * thumbnail, metadata, dan daftar soalnya dengan tampilan yang sama dengan
 * yang dibaca pengguna.
 *
 * Yang berbeda hanya kerangkanya. Halaman ini memakai layout admin, punya
 * tombol "Kembali ke Quiz" dan "Edit Quiz" di luar area konten, dan tombol
 * aksi milik pembaca disembunyikan lewat prop $aksi=false — "Mulai Quiz",
 * "Bagikan", dan form buka sesi adalah milik orang yang akan mengerjakan
 * quiz, bukan milik admin yang sedang memeriksa isinya.
 *
 * Tanpa penjaga status, sama seperti halaman detail materi: admin boleh
 * membuka quiz dari status mana pun lewat URL, karena daftar di halaman Quiz
 * memang published-only, sementara Verifikasi dan Karya Saya tidak berubah
 * karena itu.
 */
class QuizDetailController extends Controller
{
    public function __invoke(Request $request, Quiz $quiz): View
    {
        $admin = $request->user();

        $quiz->loadMissing(['pelajaran', 'pembuat']);

        $daftarSoal = $quiz->soal()->aktif()->terurut()->get();

        return view('admin.quiz-detail', [
            'quiz' => $quiz,

            /*
             * Bentuk array yang sama persis dengan halaman detail pengguna,
             * supaya komponen di bawah tidak perlu tahu siapa yang membuka.
             * DaftarSoal juga sama: halaman ini tidak hanya tidak boleh
             * membocorkan kunci jawaban, daftar soal di admin pun tidak perlu
             * menampilkan pembahasannya.
             */
            'kartu' => DaftarQuiz::petakan([$quiz], $admin?->getKey())[0],
            'soal' => DaftarSoal::petakan($daftarSoal),
            'jumlahSoal' => $daftarSoal->count(),
            'tautanEdit' => route('admin.quiz.edit', $quiz),
        ]);
    }
}
