<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Support\TinjauanMateri;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Tinjau Materi": satu-satunya tempat admin melihat materi yang
 * menunggu persetujuan dan memutuskan approve atau tolak.
 *
 * Halaman ini terbuka otomatis ke tab "Menunggu Persetujuan" supaya yang
 * pertama dilihat admin memang pekerjaan yang perlu dikerjakan, bukan daftar
 * seluruh materi.
 */
class MateriController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = $this->statusTerpilih($request->query('status'));
        $kataKunci = trim((string) $request->query('q', ''));

        $daftar = $this->daftarMateri($status, $kataKunci);

        return view('admin.materi', [
            'daftar' => $daftar['baris'],
            'paginasi' => $daftar['hal'],
            'statusAktif' => $status,
            'pilihanStatus' => TinjauanMateri::pilihanStatus(),
            'jumlahStatus' => $this->jumlahStatus(),
            'kataKunci' => $kataKunci,
        ]);
    }

    /**
     * Materi sesuai status dan pencarian, siap jadi baris tabel.
     *
     * @return array{baris: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarMateri(string $status, string $kataKunci): array
    {
        $hal = Materi::query()
            ->where('status', $status)
            ->with(['pelajaran', 'pembuat'])
            ->cari($kataKunci)
            ->latest()
            ->paginate(TinjauanMateri::perHalaman())
            ->withQueryString();

        return [
            'baris' => TinjauanMateri::petakan($hal->items()),
            'hal' => $hal,
        ];
    }

    /**
     * Berapa materi yang ada di tiap status, untuk angka di tiap tab.
     *
     * Satu query untuk semua status supaya tab tidak butuh N query.
     *
     * @return array<string, int>
     */
    private function jumlahStatus(): array
    {
        $jumlah = Materi::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect(TinjauanMateri::pilihanStatus())
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
        return array_key_exists((string) $nilai, TinjauanMateri::pilihanStatus())
            ? (string) $nilai
            : Materi::STATUS_PENDING;
    }
}
