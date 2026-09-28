<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Support\DaftarQuiz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Quiz: seluruh quiz yang sudah tayang di aplikasi.
 *
 * Quiz milik siapa pun ikut tampil di sini, termasuk milik pengguna yang
 * sedang login. Pengelolaan quiz milik sendiri (buat, ubah, hapus) ada di
 * halaman "Karya Saya", bukan di sini.
 *
 * Searching dilakukan di server lewat query string (?q=), jadi filter tetap
 * jalan walau JavaScript dimatikan. Script di resources/js/app.js cuma
 * menambahinya: submit otomatis saat mengetik dan skeleton selagi halaman
 * berikutnya dimuat.
 */
class QuizController extends Controller
{
    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));

        $quiz = $this->daftarQuiz()
            ->cari($kataKunci)
            ->latest()
            ->paginate(DaftarQuiz::perHalaman())
            ->withQueryString();

        return view('user.quiz', [
            'quiz' => $quiz,
            'daftar' => DaftarQuiz::petakan($quiz->items(), $request->user()?->getKey()),
            'kataKunci' => $kataKunci,
            'alasanKosong' => $kataKunci !== '' ? 'cari' : 'kosong',
        ]);
    }

    /**
     * Query dasar daftar quiz: hanya yang sudah disetujui admin, jadi aman
     * tampil untuk semua pengguna. Quiz yang masih draft milik pengguna
     * sendiri dikelola lewat "Karya Saya".
     */
    private function daftarQuiz(): Builder
    {
        return Quiz::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->where('aktif', true)]);
    }
}
