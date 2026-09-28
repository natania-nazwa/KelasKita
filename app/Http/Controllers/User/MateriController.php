<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Support\DaftarMateri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman Materi: seluruh materi yang tersedia di aplikasi.
 *
 * Materi buatan siapa pun ikut tampil di sini, termasuk milik pengguna yang
 * sedang login. Pengelolaan materi milik sendiri (buat, ubah, hapus) ada di
 * halaman "Karya Saya", bukan di sini.
 */
class MateriController extends Controller
{
    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $materi = $this->daftarMateri()
            ->cari($kataKunci)
            ->kategori($kategori)
            ->paginate(DaftarMateri::perHalaman())
            ->withQueryString();

        return view('user.materi', [
            'materi' => $materi,
            'daftar' => DaftarMateri::petakan($materi->items()),
            'kategori' => $this->daftarKategori(),
            'kataKunci' => $kataKunci,
            'kategoriAktif' => $kategori,
            'totalMateri' => Materi::query()->terbit()->count(),
            'totalPembuat' => $this->totalPembuat(),
        ]);
    }

    /**
     * Jumlah pembuat materi yang berbeda (guru maupun teman), untuk
     * angka ringkas di kepala halaman.
     */
    private function totalPembuat(): int
    {
        return (int) Materi::query()
            ->terbit()
            ->whereNotNull('dibuat_oleh')
            ->distinct()
            ->count('dibuat_oleh');
    }

    /**
     * Query dasar daftar materi: semua materi yang sudah disetujui admin,
     * terbaru dulu. Pencarian dan filter kategori ditambahkan setelahnya
     * di __invoke().
     */
    private function daftarMateri(): Builder
    {
        return Materi::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->latest();
    }

    /**
     * Daftar kategori untuk dropdown filter.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function daftarKategori(): Collection
    {
        $jumlahPerPelajaran = Materi::query()
            ->terbit()
            ->whereNotNull('pelajaran_id')
            ->selectRaw('pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('pelajaran_id')
            ->pluck('jumlah', 'pelajaran_id');

        $urutanKatalog = collect(Pelajaran::KATALOG)
            ->pluck('slug')
            ->mapWithKeys(fn (string $slug, int $index) => [$slug => $index]);

        return Pelajaran::query()
            ->aktif()
            ->orderBy('nama')
            ->get()
            ->map(fn (Pelajaran $pelajaran) => [...Pelajaran::warna($pelajaran->slug, $pelajaran->nama), 'jumlah' => (int) ($jumlahPerPelajaran[$pelajaran->id] ?? 0)])
            ->filter(fn (array $item) => $item['jumlah'] > 0)
            ->sortBy(fn (array $item) => [$urutanKatalog[$item['slug']] ?? 99, $item['nama']])
            ->values();
    }
}
