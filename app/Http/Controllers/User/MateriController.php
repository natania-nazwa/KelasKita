<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Pelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MateriController extends Controller
{
    /**
     * Jumlah materi per halaman.
     */
    private const PER_HALAMAN = 9;

    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $materi = Materi::query()
            ->aktif()
            ->with(['pelajaran', 'pembuat'])
            ->cari($kataKunci)
            ->kategori($kategori)
            ->latest()
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        return view('user.materi', [
            'materi' => $materi,
            'totalMateri' => Materi::query()->aktif()->count(),
            'kataKunci' => $kataKunci,
            'kategoriAktif' => $kategori,
            'kategori' => $this->daftarKategori(),
        ]);
    }

    /**
     * Daftar kategori untuk dropdown filter.
     *
     * Hanya kategori yang benar-benar punya materi aktif yang ditampilkan,
     * supaya user tidak memilih kategori kosong. Urutan mengikuti
     * Pelajaran::KATALOG agar tampilan konsisten, dan warnanya diambil dari
     * katalog tersebut supaya kartu dan filter memakai warna yang sama.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function daftarKategori(): Collection
    {
        $jumlahPerPelajaran = Materi::query()
            ->aktif()
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
