<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Support\TinjauanQuiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Tinjau Quiz": satu-satunya tempat admin melihat quiz yang
 * menunggu persetujuan dan memutuskan setujui atau tolak.
 *
 * Halaman ini terbuka otomatis ke tab "Menunggu Persetujuan" supaya yang
 * pertama dilihat admin memang pekerjaan yang perlu dikerjakan, bukan daftar
 * seluruh quiz.
 *
 * Quiz mode kode tidak pernah muncul di sini. Quiz seperti itu tidak tayang
 * untuk semua pengguna, jadi tidak ada yang perlu disetujui admin; yang
 * berbasis kode adalah miliknya sendiri, bukan milik seluruh pengguna.
 */
class QuizController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = $this->statusTerpilih($request->query('status'));
        $kataKunci = trim((string) $request->query('q', ''));

        $daftar = $this->daftarQuiz($status, $kataKunci);

        return view('admin.quiz', [
            'daftar' => $daftar['baris'],
            'paginasi' => $daftar['hal'],
            'statusAktif' => $status,
            'pilihanStatus' => TinjauanQuiz::pilihanStatus(),
            'jumlahStatus' => $this->jumlahStatus(),
            'kataKunci' => $kataKunci,
        ]);
    }

    /**
     * Quiz sesuai status dan pencarian, siap jadi baris tabel.
     *
     * @return array{baris: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarQuiz(string $status, string $kataKunci): array
    {
        $hal = Quiz::query()
            ->where('status', $status)
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal' => fn ($soal) => $soal->aktif()])
            ->cari($kataKunci)
            ->latest()
            ->paginate(TinjauanQuiz::perHalaman())
            ->withQueryString();

        return [
            'baris' => TinjauanQuiz::petakan($hal->items()),
            'hal' => $hal,
        ];
    }

    /**
     * Berapa quiz yang ada di tiap status, untuk angka di tiap tab.
     *
     * Satu query untuk semua status supaya tab tidak butuh N query.
     *
     * @return array<string, int>
     */
    private function jumlahStatus(): array
    {
        $jumlah = Quiz::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect(TinjauanQuiz::pilihanStatus())
            ->map(fn (string $label, string $status): int => (int) ($jumlah[$status] ?? 0))
            ->all();
    }

    /**
     * Status dari query string, diabaikan kalau tidak dikenal.
     *
     * Tanpa penjaga ini ?status=ngawur akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya dipaksa ke salah satu status yang nyata.
     */
    private function statusTerpilih(mixed $nilai): string
    {
        return array_key_exists((string) $nilai, TinjauanQuiz::pilihanStatus())
            ? (string) $nilai
            : Quiz::STATUS_PENDING;
    }
}
