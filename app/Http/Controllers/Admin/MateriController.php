<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use App\Support\DaftarMateriAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman "Materi": mengelola materi yang sudah dipublikasikan.
 *
 * Batasnya sengaja tegas: daftar di sini hanya materi berstatus "published",
 * karena materi yang masih menunggu keputusan milik halaman Verifikasi dan
 * yang ditolak atau masih draft milik "Karya Saya" pemiliknya. Halaman ini
 * karena itu tidak punya setujui/tolak, dan tidak pernah menampilkan materi
 * yang belum tayang sebagai materi yang sudah tayang.
 *
 * Yang bisa dilakukan di sini: mencari, menyaring, membuka detail, mengubah,
 * dan menghapus. Tidak ada tab status, karena dengan daftar yang sudah
 * published-only tab itu hanya akan mengulang daftar yang sama.
 *
 * Tombol Edit pada kartu hanya muncul untuk materi yang dibuat admin yang
 * sedang login; materi buatan pengguna lain bisa dibaca dan dihapus, tapi
 * isinya bukan hak admin untuk diubah. Aturan yang sama ditegakkan lagi di
 * Admin\MateriKelolaController, jadi menyembunyikan tombolnya bukan satu-
 * satunya penjaga.
 */
class MateriController extends Controller
{
    /**
     * Urutan daftar yang bisa dipilih, dan Closure pengurutannya.
     *
     * Hanya dua, dan keduanya dibaca dari tanggal terbit: urutan daftar di
     * sini sama dengan urutan kemunculan materi di halaman pengguna, jadi
     * tidak ada angka yang hanya ada di area admin.
     *
     * Key dipakai sebagai nilai query string, jadi nilainya tidak boleh
     * berubah tanpa sengaja: tautan lama yang memakai "urut=..." akan ikut
     * ke nilai yang sama, dan nilai yang tidak dikenal jatuh ke "terbaru".
     *
     * Sengaja method, bukan const: Closure adalah kode yang baru jalan saat
     * aplikasi dijalankan, sedangkan nilai const harus bisa dihitung saat
     * file dikompilasi. Menulis Closure di dalam const membuat PHP 8.5
     * menolak seluruh file dengan "Constant expression contains invalid
     * operations", dan karena kelas ini ikut termuat saat boot, satu baris
     * itu bisa menjatuhkan seluruh aplikasi.
     *
     * @return array<string, \Closure(Builder): Builder>
     */
    private static function urutan(): array
    {
        return [
            'terbaru' => fn (Builder $query) => $query->orderByDesc('dipublish_pada')->orderByDesc('created_at'),
            'terlama' => fn (Builder $query) => $query->orderBy('dipublish_pada')->orderBy('created_at'),
        ];
    }

    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q'));
        $kategori = trim((string) $request->query('kategori'));
        $pembuat = trim((string) $request->query('pembuat'));
        $urut = $this->urutanTerpilih($request->query('urut'));

        $daftar = $this->daftarMateri($kataKunci, $kategori, $pembuat, $urut);

        return view('admin.materi', [
            'daftar' => DaftarMateriAdmin::petikan($daftar->items(), $request->user()?->getKey()),
            'paginasi' => $daftar,
            'kataKunci' => $kataKunci,
            'kategoriAktif' => $kategori,
            'daftarKategori' => $this->daftarKategori(),
            'daftarPembuat' => $this->daftarPembuat(),
            'pembuatAktif' => $pembuat,
            'urutAktif' => $urut,
            'pilihanUrut' => $this->pilihanUrut(),
            'totalMateri' => Materi::query()->terbit()->count(),
        ]);
    }

    /**
     * Materi sesuai kata kunci, kategori, pembuat, dan urutan.
     *
     * Pencarian dibungkus satu grup WHERE supaya tidak bertabrakan dengan
     * filter yang dipasang sebelumnya: tanpa grup itu, mengetik kata kunci
     * akan membuat "atau" ikut meloloskan materi dari kategori lain.
     */
    private function daftarMateri(string $kataKunci, string $kategori, string $pembuat, string $urut): LengthAwarePaginator
    {
        $query = Materi::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->when($kategori !== '', fn (Builder $query) => $query->kategori($kategori))
            ->when($pembuat !== '', fn (Builder $query) => $query->where('dibuat_oleh', $pembuat))
            ->when($kataKunci !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $this->kriteriaPencarian($query, $kataKunci)));

        (self::urutan()[$urut])($query);

        return $query->paginate(DaftarMateriAdmin::perHalaman())->withQueryString();
    }

    /**
     * Kata kunci dicocokkan ke nama, deskripsi, isi, nama pelajaran, dan
     * identitas pembuat, supaya admin bisa menemukan materi lewat judul,
     * lewat isi, lewat kategori, maupun lewat siapa yang membuatnya.
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
     * Kategori yang punya materi published, lengkap dengan jumlahnya.
     *
     * Hanya kategori yang benar-benar berisi materi yang ditawarkan, supaya
     * memilih salah satunya tidak pernah menghasilkan daftar kosong karena
     * alasan yang tidak terlihat. Urutannya mengikuti katalog Pelajaran,
     * lalu abjad, supaya urutannya tidak ikut berubah setiap kali
     * dipaginasi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function daftarKategori(): Collection
    {
        $jumlah = Materi::query()
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
            ->map(fn (Pelajaran $pelajaran) => [
                ...Pelajaran::warna($pelajaran->slug, $pelajaran->nama),
                'jumlah' => (int) ($jumlah[$pelajaran->id] ?? 0),
            ])
            ->filter(fn (array $item) => $item['jumlah'] > 0)
            ->sortBy(fn (array $item) => [$urutanKatalog[$item['slug']] ?? 99, $item['nama']])
            ->values();
    }

    /**
     * Pembuat yang punya materi published, untuk dropdown filter.
     *
     * Hanya diambil dari materi yang sudah terbit supaya pilihan ini tidak
     * pernah berisi nama yang menyaring daftar menjadi kosong.
     *
     * @return Collection<int, array{id: int, nama: string}>
     */
    private function daftarPembuat(): Collection
    {
        $adaMateri = Materi::query()
            ->terbit()
            ->whereNotNull('dibuat_oleh')
            ->select('dibuat_oleh')
            ->distinct()
            ->pluck('dibuat_oleh')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($adaMateri === []) {
            return new Collection;
        }

        return User::query()
            ->whereKey($adaMateri)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (User $pengguna) => ['id' => (int) $pengguna->getKey(), 'nama' => $pengguna->nama]);
    }

    /**
     * @return array<string, string>
     */
    private function pilihanUrut(): array
    {
        return [
            'terbaru' => 'Terbaru',
            'terlama' => 'Terlama',
        ];
    }

    /**
     * Urutan dari query string; nilai tak dikenal jatuh ke "terbaru".
     *
     * Tanpa penjaga ini "?urut=ngawur" akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya dipetikan ke urutan bawaan.
     */
    private function urutanTerpilih(mixed $nilai): string
    {
        return array_key_exists((string) $nilai, self::urutan())
            ? (string) $nilai
            : 'terbaru';
    }
}
