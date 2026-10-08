<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Support\DaftarKategori;
use App\Support\DaftarMateriAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

    public function __invoke(Request $request): View|RedirectResponse
    {
        $kataKunci = trim((string) $request->query('q'));
        $kategori = trim((string) $request->query('kategori'));
        $urut = $this->urutanTerpilih($request->query('urut'));

        $daftar = $this->daftarMateri($kataKunci, $kategori, $urut);

        if ($this->diLuarRentang($daftar)) {
            return redirect()->route('admin.materi', array_diff_key($request->query(), ['page' => '']));
        }

        return view('admin.materi', [
            'daftar' => DaftarMateriAdmin::petikan($daftar->items(), $request->user()?->getKey()),
            'paginasi' => $daftar,
            'kataKunci' => $kataKunci,
            'kategoriAktif' => $kategori,
            'daftarKategori' => $this->daftarKategori(),
            'urutAktif' => $urut,
            'pilihanUrut' => $this->pilihanUrut(),
            'totalMateri' => Materi::query()->terbit()->count(),
        ]);
    }

    /**
     * Materi sesuai kata kunci, kategori, dan urutan.
     *
     * Pencarian dibungkus satu grup WHERE supaya tidak bertabrakan dengan
     * filter yang dipasang sebelumnya: tanpa grup itu, mengetik kata kunci
     * akan membuat "atau" ikut meloloskan materi dari kategori lain.
     *
     * Tidak ada filter pembuat. Dulu ada dropdown "Semua pembuat", dan
     * dropdown itu bisa jadi tidak punya satu pun pilihan: materi yang sudah
     * tayang tidak selalu punya dibuat_oleh yang terisi, jadi daftar pembuat
     * yang diambil dari materi yang punya pembuat saja bisa kosong. Nama
     * pembuat tetap bisa dicari lewat kolom cari, jadi tidak ada yang hilang
     * ketika dropdown-nya dihapus.
     */
    private function daftarMateri(string $kataKunci, string $kategori, string $urut): LengthAwarePaginator
    {
        $query = Materi::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->when($kategori !== '', fn (Builder $query) => $query->kategori($kategori))
            ->when($kataKunci !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $this->kriteriaPencarian($query, $kataKunci)));

        (self::urutan()[$urut])($query);

        return $query->paginate(DaftarMateriAdmin::perHalaman())->withQueryString();
    }

    /**
     * Daftar kosong karena halamannya di luar rentang, bukan karena tidak
     * ada materi.
     *
     * "?page=999" menghasilkan daftar kosong, dan kalau dibiarkan halaman
     * menampilkan empty state "Belum ada materi" — padahal materinya ada —
     * sambil paginasinya ikut hilang karena tidak ada baris yang bisa
     * ditautkan. Admin lalu terjebak sampai query string-nya dihapus
     * sendiri, dan bisa mengira materinya habis terhapus.
     *
     * Ditandai dari total, bukan dari kekosongan daftar saja: total nol
     * berarti halaman ini memang kosong, dan empty state yang tampil saat
     * itu sudah benar.
     */
    private function diLuarRentang(LengthAwarePaginator $daftar): bool
    {
        return $daftar->isEmpty()
            && $daftar->total() > 0
            && $daftar->currentPage() > 1;
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
     * Status aktif tidak ikut disaring. Daftar di halaman ini memuat semua
     * materi terbit, termasuk yang kategorinya sudah dinonaktifkan lewat
     * Pengaturan, jadi kategori itu harus tetap ditawarkan — kalau tidak,
     * jumlahnya hilang dari dropdown sementara materinya tetap ikut
     * terhitung di "Semua kategori (n)", dan materi itu tak bisa disaring.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function daftarKategori(): Collection
    {
        $jumlah = Materi::query()
            ->terbit()
            ->selectRaw('pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('pelajaran_id')
            ->pluck('jumlah', 'pelajaran_id');

        return DaftarKategori::filter($jumlah, false);
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
