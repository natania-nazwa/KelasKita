<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Support\DaftarKonten;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Halaman "Konten Pembelajaran": tempat admin membuat dan mengelola seluruh
 * materi dan quiz miliknya sendiri.
 *
 * Batasnya sengaja berbeda dari tiga halaman admin lain yang sudah ada, dan
 * ketiganya tidak diubah oleh halaman ini:
 *
 *   - "Materi" dan "Quiz" adalah kumpulan konten yang sudah tayang, siapa pun
 *     yang membuatnya. Hanya dibaca dan, untuk karya admin sendiri, diubah.
 *   - "Verifikasi" adalah antrean keputusan untuk karya pengguna: setujui
 *     atau tolak.
 *   - "Konten Pembelajaran" (halaman ini) adalah ruang kerja admin sendiri:
 *     tambah, ubah, hapus, duplikat, terbitkan, dan batalkan terbitkan.
 *
 * Yang tampil di sini hanya karya admin yang sedang login, bukan seluruh isi
 * database: sama seperti "Karya Saya" milik pengguna, daftar ini soal milik
 * sendiri. Karya pengguna tetap managing lewat Verifikasi (keputusan) dan
 * lewat katalog Materi / Quiz (karya yang sudah tayang).
 *
 * Daftar kontennya berupa baris, bukan kartu besar, supaya admin bisa
 * memindai judul dan statusnya tanpa mengejar dekorasi. Barisnya dibangun oleh
 * DaftarKonten, yang juga sudah memasang tautan aksinya.
 *
 * Karena admin di aplikasi ini satu role dan dia sendiri yang membuat
 * kontennya, tidak ada tahap persetujuan di sini. Status yang dipakai tetap
 * dua yang sudah ada di database, "draft" dan "published", jadi tidak ada
 * tabel atau status baru yang bisa bentrok dengan halaman lain.
 */
class KontenController extends Controller
{
    /** Tab yang sedang dibuka: materi atau quiz. */
    private const TAB_MATERI = 'materi';

    private const TAB_QUIZ = 'quiz';

    /**
     * Urutan daftar yang bisa dipilih, dan Closure pengurutannya.
     *
     * Empat. Yang dua pertama membaca tanggal yang sama dengan yang dipakai
     * kartu di daftar admin yang sudah ada, jadi "Terbaru" di sini berarti hal
     * yang sama seperti di halaman Materi dan Quiz. Dua terakhir mengurutkan
     * judul, yang kolomnya berbeda antara materi (kolom "nama") dan quiz
     * (kolom "judul") — jadi Closure-nya menerima nama kolom dari pemanggil.
     *
     * Method, bukan const: Closure adalah kode yang baru jalan saat aplikasi
     * dijalankan, sedangkan nilai const harus bisa dihitung saat file
     * dikompilasi. Closure di dalam const membuat PHP 8.5 menolak seluruh
     * file, dan karena kelas ini ikut termuat saat boot, satu baris itu bisa
     * menjatuhkan seluruh aplikasi.
     *
     * @return array<string, \Closure(Builder, string): Builder>
     */
    private static function urutan(): array
    {
        return [
            'terbaru' => fn (Builder $query, string $kolom) => $query->orderByDesc('dipublish_pada')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'terlama' => fn (Builder $query, string $kolom) => $query->orderBy('dipublish_pada')
                ->orderBy('created_at')
                ->orderBy('id'),
            'az' => fn (Builder $query, string $kolom) => $query
                ->orderByRaw(self::judulUrut($query, $kolom))
                ->orderByDesc('id'),
            'za' => fn (Builder $query, string $kolom) => $query
                ->orderByRaw(self::judulUrut($query, $kolom).' desc')
                ->orderByDesc('id'),
        ];
    }

    /**
     * Ekspresi pengurutan judul untuk orderByRaw.
     *
     * Postgres membandingkan teks mengikuti collation lokalnya, jadi "abjad"
     * dan "Abjad" bisa urutannya berbeda padahal pembaca menganggapnya sama.
     * COLLATE "C" memaksa perbandingan memakai kode karakter, yang persis
     * seperti urutan A-Z yang biasa dibaca orang.
     *
     * OrderByRaw dipilih karena orderBy() akan membungkus nama kolom dengan
     * tanda kutip, dan ekspresi "nama collate \"C\"" tidak akan survive itu.
     * Nama kolomnya sendiri tetap aman: datang dari daftar empat Closure di
     * atas, bukan dari input pengguna, jadi tidak ada celah injeksi.
     */
    private static function judulUrut(Builder $query, string $kolom): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql'
            ? $kolom.' collate "C"'
            : $kolom;
    }

    public function __invoke(Request $request): View
    {
        $tab = $this->tabTerpilih($request->query('tab'));
        $kataKunci = trim((string) $request->query('q'));
        $status = $this->statusTerpilih($request->query('status'));
        $kategori = trim((string) $request->query('kategori'));
        $urut = $this->urutanTerpilih($request->query('urut'));
        $admin = $request->user();

        /*
         * Query daftar ditutup dan dikembalikan sebagai state "gagal" kalau
         * ada yang salah, supaya halaman tetap tampil utuh: kepala, dua kartu
         * aksi, dan tabnya tidak ikut hilang karena daftar yang gagal dimuat.
         * Pesan errornya sendiri tidak pernah ditampilkan — yang muncul hanya
         * kalimat "Gagal memuat konten" beserta tombol Coba Lagi, dan
         * exception aslinya tetap dilaporkan lewat report().
         */
        $gagal = false;
        $daftar = collect();
        $paginasi = null;

        try {
            $paginasi = $this->daftar($tab, $admin?->getKey(), $kataKunci, $status, $kategori, $urut);
            $daftar = collect(DaftarKonten::petikan($paginasi->items()));
        } catch (Throwable $e) {
            report($e);

            $gagal = true;
        }

        return view('admin.konten', [
            'tab' => $tab,
            'daftar' => $daftar,
            'paginasi' => $paginasi,
            'gagal' => $gagal,
            'kataKunci' => $kataKunci,
            'statusAktif' => $status,
            'kategoriAktif' => $kategori,
            'urutAktif' => $urut,
            'pilihanUrut' => [
                'terbaru' => 'Terbaru',
                'terlama' => 'Terlama',
                'az' => 'A–Z',
                'za' => 'Z–A',
            ],
            'pilihanStatus' => $this->pilihanStatus(),
            'daftarKategori' => Pelajaran::query()->aktif()->urutKatalog()->get(),
            'jumlahMateri' => Materi::query()->milik($admin?->getKey())->count(),
            'jumlahQuiz' => Quiz::query()->milik($admin?->getKey())->count(),
        ]);
    }

    /**
     * Tab aktif; nilai tak dikenal jatuh ke "materi".
     *
     * Tanpa penjaga ini "?tab=ngawur" akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya dipetikan ke tab bawaan.
     */
    private function tabTerpilih(mixed $nilai): string
    {
        return (string) $nilai === self::TAB_QUIZ ? self::TAB_QUIZ : self::TAB_MATERI;
    }

    /**
     * Status yang disaring. Kosong berarti semua status.
     *
     * Hanya "draft" dan "published" yang bisa dipilih, karena itulah dua status
     * yang bisa dihasilkan oleh halaman ini. Materi atau quiz karya pengguna
     * yang masih menunggu atau ditolak tetap bisa dibaca dari daftar, tapi
     * tidak punya filter tersendiri: status itu bukan hasil keputusan admin
     * atas karyanya sendiri, dan tidak bisa diupayain dari sini.
     */
    private function statusTerpilih(mixed $nilai): string
    {
        $status = (string) $nilai;

        return in_array($status, [Materi::STATUS_DRAFT, Materi::STATUS_PUBLISHED], true)
            ? $status
            : '';
    }

    /**
     * @return array<string, string>
     */
    private function pilihanStatus(): array
    {
        return [
            Materi::STATUS_DRAFT => 'Draft',
            Materi::STATUS_PUBLISHED => 'Published',
        ];
    }

    private function urutanTerpilih(mixed $nilai): string
    {
        return array_key_exists((string) $nilai, self::urutan())
            ? (string) $nilai
            : 'terbaru';
    }

    /**
     * Daftar baris untuk tab yang sedang dibuka.
     *
     * Hanya satu tab yang diambil datanya. Tab yang lain cukup menampilkan
     * jumlahnya di badannya, jadi tidak perlu satu query daftar dan satu
     * query paginasi lagi untuk konten yang tidak sedang dilihat.
     *
     * Daftar ini milik admin yang sedang login saja, bukan seluruh isi
     * database. Scope miliknya dipakai apa adanya supaya aturannya sama
     * dengan halaman "Karya Saya" milik pengguna: satu scope, satu arti.
     * Status tidak ikut disaring di sini, jadi draft karya sendiri tetap
     * terlihat dan bisa dikelola dari halaman ini.
     *
     * Konten karya pengguna tetap bisa dibaca dan disetujui dari menu
     * Verifikasi, dan karyanya yang sudah tayang tetap muncul di katalog
     * "Materi" dan "Quiz" — dua menu itu tidak memakai daftar ini.
     *
     * @throws Throwable apa pun yang keluar dari query, supaya pemanggil bisa
     *                   menampilkan state "Gagal memuat konten".
     */
    private function daftar(
        string $tab,
        ?int $idAdmin,
        string $kataKunci,
        string $status,
        string $kategori,
        string $urut
    ): LengthAwarePaginator {
        $model = $tab === self::TAB_QUIZ ? Quiz::class : Materi::class;
        $kolomJudul = $tab === self::TAB_QUIZ ? 'judul' : 'nama';

        $query = $model::query()
            ->milik($idAdmin)
            ->with('pelajaran')
            ->when($tab === self::TAB_QUIZ, fn (Builder $query) => $query->withCount([
                'soal as jumlah_soal_termuat' => fn ($soal) => $soal->aktif(),
            ]))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($kategori !== '', fn (Builder $query) => $query->kategori($kategori))
            ->when(
                $kataKunci !== '',
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $this->kriteriaPencarian($query, $kataKunci, $tab)
                )
            );

        (self::urutan()[$urut])($query, $kolomJudul);

        return $query->paginate(DaftarKonten::PER_HALAMAN)->withQueryString();
    }

    /**
     * Kata kunci dicocokkan ke nama/judul, deskripsi, isi, dan nama
     * kategori, supaya admin bisa menemukan konten lewat judul, lewat
     * isinya, maupun lewat mata pelajarannya.
     */
    private function kriteriaPencarian(Builder $query, string $kataKunci, string $tab): Builder
    {
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        $kolom = $tab === self::TAB_QUIZ ? 'judul' : 'nama';

        return $query->where(function (Builder $query) use ($pola, $operator, $kolom) {
            $query->where($kolom, $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->when(
                    $kolom === 'nama',
                    fn (Builder $query) => $query->orWhere('isi', $operator, $pola)
                )
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola));
        });
    }
}
