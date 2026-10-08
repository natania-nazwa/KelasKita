<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Soal;
use App\Support\DaftarKategori;
use App\Support\DaftarQuiz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman Quiz: seluruh quiz yang sudah tayang di aplikasi.
 *
 * Quiz milik siapa pun ikut tampil di sini, termasuk milik pengguna yang
 * sedang login. Pengelolaan quiz milik sendiri (buat, ubah, hapus) ada di
 * halaman "Karya Saya", bukan di sini.
 *
 * Searching dilakukan di server lewat query string (?q=) dan penyaringan
 * kategori lewat (?kategori=), jadi filter tetap jalan walau JavaScript
 * dimatikan. Script di resources/js/app.js cuma menambahinya: submit
 * otomatis saat mengetik dan skeleton selagi halaman berikutnya dimuat.
 */
class QuizController extends Controller
{
    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $daftarKategori = $this->daftarKategori();

        $quiz = $this->daftarQuiz()
            ->cari($kataKunci)
            ->kategori($kategori)
            ->latest()
            ->paginate(DaftarQuiz::perHalaman())
            ->withQueryString();

        return view('user.quiz', [
            'quiz' => $quiz,
            'daftar' => DaftarQuiz::petakan($quiz->items(), $request->user()?->getKey()),
            'kataKunci' => $kataKunci,
            'kategori' => $daftarKategori,
            'kategoriAktif' => $kategori,
            'totalQuiz' => $daftarKategori->sum('jumlah'),
            'totalSoal' => $this->totalSoal(),
            'totalKategori' => $this->totalKategori(),
            'alasanKosong' => match (true) {
                $kataKunci !== '' => 'cari',
                $kategori !== '' => 'filter',
                default => 'kosong',
            },
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
            ->withCount(['soal as jumlah_soal_termuat' => fn ($soal) => $soal->aktif()]);
    }

    /**
     * Daftar kategori untuk filter, lengkap dengan jumlah quiz di dalamnya.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function daftarKategori(): Collection
    {
        $jumlahPerPelajaran = Quiz::query()
            ->terbit()
            ->whereNotNull('pelajaran_id')
            ->selectRaw('pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('pelajaran_id')
            ->pluck('jumlah', 'pelajaran_id');

        return DaftarKategori::filter($jumlahPerPelajaran);
    }

    /**
     * Berapa kategori yang benar-benar berisi quiz yang tayang.
     *
     * Angka ini dihitung sendiri, bukan dari jumlah pilihan di filter.
     * Filter menampilkan seluruh daftar resmi, termasuk yang isinya masih
     * kosong, jadi membacanya selalu menghasilkan sebelas dan tidak lagi
     * menjawab "seberapa banyak kategori yang ada di pustaka ini".
     */
    private function totalKategori(): int
    {
        return (int) Quiz::query()
            ->terbit()
            ->whereNotNull('pelajaran_id')
            ->distinct()
            ->count('pelajaran_id');
    }

    /**
     * Total soal aktif dari seluruh quiz yang tayang, dipakai sebagai angka
     * besar di kepala halaman.
     */
    private function totalSoal(): int
    {
        return Soal::query()
            ->aktif()
            ->whereIn('quiz_id', Quiz::query()->terbit()->select('tb_quiz.id'))
            ->count();
    }
}
