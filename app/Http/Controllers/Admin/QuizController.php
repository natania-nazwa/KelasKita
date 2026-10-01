<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\DaftarQuizAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman "Quiz": mengelola quiz yang sudah dipublikasikan.
 *
 * Pasangannya adalah halaman "Materi" dan disengaja ditiru seluruhnya:
 * published-only, tanpa tab status, tanpa setujui/tolak, dengan hero, kartu
 * filter, grid kartu, dan paginasi yang sama. Batasnya tegas — quiz yang
 * masih menunggu keputusan milik halaman Verifikasi, dan yang ditolak atau
 * masih draft milik "Karya Saya" pemiliknya — jadi halaman ini tidak pernah
 * menampilkan quiz yang belum tayang seolah-olah sudah tayang.
 *
 * Yang bisa dilakukan di sini: mencari, menyaring, membuka detail,
 * mengubah, dan menghapus.
 *
 * Tombol Edit pada kartu hanya muncul untuk quiz yang dibuat admin yang
 * sedang login; quiz buatan pengguna lain bisa dibaca dan dihapus, tapi
 * soalnya bukan hak admin untuk diubah. Aturan yang sama ditegakkan lagi di
 * Admin\QuizKelolaController, jadi menyembunyikan tombolnya bukan satu-
 * satunya penjaga.
 */
class QuizController extends Controller
{
    /**
     * Urutan daftar yang bisa dipilih, dan Closure pengurutannya.
     *
     * Hanya dua, dan keduanya dibaca dari tanggal terbit: urutan daftar di
     * sini sama dengan urutan kemunculan quiz di halaman pengguna, jadi
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
        $urut = $this->urutanTerpilih($request->query('urut'));

        $daftar = $this->daftarQuiz($kataKunci, $kategori, $urut);

        return view('admin.quiz', [
            'daftar' => DaftarQuizAdmin::petikan($daftar->items(), $request->user()?->getKey()),
            'paginasi' => $daftar,
            'kataKunci' => $kataKunci,
            'kategoriAktif' => $kategori,
            'daftarKategori' => $this->daftarKategori(),
            'urutAktif' => $urut,
            'pilihanUrut' => $this->pilihanUrut(),
            'totalQuiz' => Quiz::query()->terbit()->count(),
        ]);
    }

    /**
     * Quiz sesuai kata kunci, kategori, dan urutan.
     *
     * Pencarian dibungkus satu grup WHERE supaya tidak bertabrakan dengan
     * filter yang dipasang sebelumnya: tanpa grup itu, mengetik kata kunci
     * akan membuat "atau" ikut meloloskan quiz dari kategori lain.
     *
     * Jumlah soal ikut dihitung lewat withCount dan langsung diberi nama
     * jumlah_soal_termuat supaya Quiz::jumlahSoal() membacanya dari sana.
     * Tanpa itu, kartu di daftar akan menjalankan satu query per baris.
     *
     * Tidak ada filter pembuat, sama seperti halaman Materi. Nama pembuat
     * tetap bisa dicari lewat kolom cari, jadi tidak ada yang hilang ketika
     * dropdown-nya dihapus.
     */
    private function daftarQuiz(string $kataKunci, string $kategori, string $urut): LengthAwarePaginator
    {
        $query = Quiz::query()
            ->terbit()
            ->with(['pelajaran', 'pembuat'])
            ->withCount(['soal as jumlah_soal_termuat' => fn (Builder $soal) => $soal->aktif()])
            ->when($kategori !== '', fn (Builder $query) => $query->kategori($kategori))
            ->when($kataKunci !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $this->kriteriaPencarian($query, $kataKunci)));

        (self::urutan()[$urut])($query);

        return $query->paginate(DaftarQuizAdmin::perHalaman())->withQueryString();
    }

    /**
     * Kata kunci dicocokkan ke judul, deskripsi, nama pelajaran, dan
     * identitas pembuat, supaya admin bisa menemukan quiz lewat judul, lewat
     * kategori, maupun lewat siapa yang membuatnya.
     *
     * Isi soalnya sengaja tidak ikut dicari: satu query LIKE ke tb_soal untuk
     * tiap kata kunci akan membuat daftar terasa lambat, dan mencari lewat
     * teks soal bukan hal yang perlu dilakukan dari daftar.
     */
    private function kriteriaPencarian(Builder $query, string $kataKunci): Builder
    {
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pola, $operator) {
            $query->where('judul', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola))
                ->orWhereHas('pembuat', fn (Builder $pembuat) => $pembuat->where('nama', $operator, $pola)->orWhere('email', $operator, $pola));
        });
    }

    /**
     * Kategori yang punya quiz published, lengkap dengan jumlahnya.
     *
     * Hanya kategori yang benar-benar berisi quiz yang ditawarkan, supaya
     * memilih salah satunya tidak pernah menghasilkan daftar kosong karena
     * alasan yang tidak terlihat. Urutannya mengikuti katalog Pelajaran,
     * lalu abjad, supaya urutannya tidak ikut berubah setiap kali
     * dipaginasi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function daftarKategori(): Collection
    {
        $jumlah = Quiz::query()
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
