<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\DaftarMateriAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Materi": kelola seluruh materi dari semua status.
 *
 * Berbeda dengan halaman Verifikasi, di sini tidak ada keputusan setujui atau
 * tolak. Halaman ini berfungsi sebagai papan untuk memantau semua materi
 * (pending, tayang, ditolak, maupun draft) dengan ringkasan jumlah, filter
 * status dan pelajaran, serta pencarian yang juga menjangkau nama pembuat.
 */
class MateriController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = $this->statusTerpilih($request->query('status'));
        $pelajaran = trim((string) $request->query('pelajaran'));
        $kataKunci = trim((string) $request->query('q'));

        $daftar = $this->daftarMateri($status, $pelajaran, $kataKunci);

        return view('admin.materi', [
            'daftar' => $daftar['baris'],
            'paginasi' => $daftar['hal'],
            'statusAktif' => $status,
            'pilihanStatus' => $this->pilihanStatus(),
            'jumlahStatus' => $this->jumlahStatus(),
            'kataKunci' => $kataKunci,
            'pelajaranAktif' => $pelajaran,
            'daftarPelajaran' => Pelajaran::query()->aktif()->orderBy('nama')->get(),
        ]);
    }

    /**
     * Materi sesuai status, pelajaran, dan kata kunci, siap jadi baris tabel.
     *
     * @return array{baris: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    private function daftarMateri(string $status, string $pelajaran, string $kataKunci): array
    {
        $hal = Materi::query()
            ->with(['pelajaran', 'pembuat'])
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($pelajaran !== '', fn (Builder $query) => $query->kategori($pelajaran))
            ->when($kataKunci !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $this->kriteriaPencarian($query, $kataKunci)))
            ->latest()
            ->paginate(DaftarMateriAdmin::perHalaman())
            ->withQueryString();

        return [
            'baris' => DaftarMateriAdmin::petakan($hal->items()),
            'hal' => $hal,
        ];
    }

    /**
     * Kata kunci dicocokkan ke nama, deskripsi, isi, nama pelajaran, dan
     * identitas pembuat, supaya admin bisa menemukan materi lewat siapa yang
     * membuatnya. Kriteria dibungkus satu grup WHERE supaya tidak bertabrakan
     * dengan filter status dan pelajaran yang dipasang sebelumnya.
     */
    private function kriteriaPencarian(Builder $query, string $kataKunci): Builder
    {
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pola, $operator) {
            $query->where('nama', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhere('isi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola))
                ->orWhereHas('pembuat', fn (Builder $pembuat) => $pembuat->where('nama', $operator, $pola)->orWhere('email', $operator, $pola));
        });
    }

    /**
     * Berapa materi di tiap status, untuk angka di kartu statistik.
     *
     * Satu query untuk semua status supaya empat kartu tidak butuh empat
     * query. Status yang belum punya materi ikut hadir dengan angka nol
     * supaya kartunya selalu tampil.
     *
     * @return array<string, int>
     */
    private function jumlahStatus(): array
    {
        $jumlah = Materi::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect($this->pilihanStatus())
            ->map(fn (string $label, string $status): int => (int) ($jumlah[$status] ?? 0))
            ->all();
    }

    /**
     * Pilihan status untuk dropdown filter, diurutkan dari yang paling
     * butuh perhatian (menunggu) sampai yang paling biasa (draft).
     *
     * @return array<string, string>
     */
    private function pilihanStatus(): array
    {
        return [
            Materi::STATUS_PENDING => 'Menunggu Persetujuan',
            Materi::STATUS_PUBLISHED => 'Dipublikasikan',
            Materi::STATUS_REJECTED => 'Ditolak',
            Materi::STATUS_DRAFT => 'Draft',
        ];
    }

    /**
     * Status dari query string; string kosong berarti semua status.
     *
     * Tanpa penjaga ini ?status=ngawur akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya diabaikan kalau tidak dikenal.
     */
    private function statusTerpilih(mixed $nilai): string
    {
        return array_key_exists((string) $nilai, $this->pilihanStatus())
            ? (string) $nilai
            : '';
    }
}
