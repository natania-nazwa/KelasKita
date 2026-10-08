<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use App\Support\StatistikAdmin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Halaman "Pengguna": daftar semua akun yang memakai KelasKita.
 *
 * Halaman ini membaca daftar sekaligus menyediakan satu aksi: hapus akun.
 * Status aktif tidak lagi di sini — tidak ada tombol yang mengubahnya, karena
 * aktif/nonaktif kini dihitung otomatis dari kapan terakhir pengguna membuka
 * aplikasi (lihat App\Http\Middleware\CatatAktivitasPengguna). Ubah peran juga
 * sengaja tidak ada: hanya ada dua peran (ADMIN dan USER).
 *
 * Penjagaan hapus, ditegakkan di controller dan bukan hanya dengan
 * menyembunyikan tombol di view:
 *   - akun sendiri tidak bisa dihapus, supaya admin tidak menghapus dirinya;
 *   - admin terakhir tidak bisa dihapus, supaya platform tidak pernah
 *     kehabisan admin.
 */
class PenggunaController extends Controller
{
    /**
     * Pengguna per halaman.
     */
    private const PER_HALAMAN = 10;

    /**
     * Nilai tab peran: semua | admin | user.
     *
     * Setelah "semua", dua nilai berikutnya persis nilai kolom "peran", jadi
     * tidak ada tab yang bisa difilter tapi tidak ada di data. Labelnya ditulis
     * di view supaya controller tidak ikut menentukan istilah UI.
     *
     * @var array<int, string>
     */
    private const PERAN = ['semua', User::PERAN_ADMIN, User::PERAN_USER];

    /**
     * Nilai tab status: semua | aktif | nonaktif.
     *
     * @var array<int, string>
     */
    private const STATUS = ['semua', 'aktif', 'nonaktif'];

    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));
        $peran = $this->terpilih($request->query('peran'), self::PERAN, 'semua');
        $status = $this->terpilih($request->query('status'), self::STATUS, 'semua');

        $daftar = $this->saring(User::query(), $kataKunci, $peran, $status)
            ->withCount([
                'materi',
                'quiz',
                'pengerjaanQuiz',
                'pengerjaanQuizSelesai as pengerjaan_selesai_count',
            ])
            /*
             * Rata-rata hanya menghitung yang selesai: pengerjaan yang masih
             * berjalan punya nilai sementara yang belum boleh menggambarkan
             * kemampuan orang itu.
             *
             * withAvg() tidak menerima kondisi tambahan, jadi batas "selesai"
             * dibuat lewat relasi pengerjaanQuizSelesai() di model User, bukan
             * lewat closure. Alias dipakai supaya atributnya membaca
             * "nilai_rata" dan bukan nama hasil penggabungan yang panjang.
             */
            ->withAvg('pengerjaanQuizSelesai as nilai_rata', 'nilai')
            /*
             * Urutannya created_at lalu id, bukan created_at saja. Satu
             * halaman admin bisa memuat lebih dari satu baris dengan waktu
             * bergabung yang sama persis, dan tanpa id sebagai pemutus,
             * baris yang sama bisa muncul di dua halaman atau dilewati sama
             * sekali. Notifikasi dan pencarian tidak terpengaruh, jadi ini
             * hanya menutup celah pagination.
             */
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        return view('admin.pengguna', [
            'daftar' => $daftar,
            'kataKunci' => $kataKunci,
            'peranAktif' => $peran,
            'statusAktif' => $status,
            'tabPeran' => $this->tabPeran($status, $kataKunci),
            'tabStatus' => $this->tabStatus($peran, $kataKunci),
            'statistik' => $this->statistik(),
            'ringkasan' => $this->ringkasan(),
            'detail' => $this->detail($daftar),
            'aksi' => $this->aksi($daftar, $request->user()),
        ]);
    }

    /**
     * Hapus satu akun beserta data yang menggantung padanya.
     *
     * Sebagian besar tabel memakai cascadeOnDelete, jadi quiz, pengerjaan,
     * jawaban, jadwal, simpanan, notifikasi, dan riwayat login ikut terhapus.
     * Materi tidak: kolom pembuatnya nullOnDelete, jadi materinya tetap ada
     * sebagai konten tanpa pemilik, bukan ikut hilang dari halaman pengguna.
     */
    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        $alasan = $this->terkunci($pengguna, $request->user());

        if ($alasan !== null) {
            return back()->with('galat', $alasan);
        }

        $nama = $pengguna->nama;

        $pengguna->delete();

        return back()
            ->with('sukses', 'Akun dihapus')
            ->with('suksesDetail', $nama.' dan data yang menggantung padanya sudah dihapus.');
    }

    /**
     * Alasan sebuah akun tidak boleh dihapus, atau null kalau boleh.
     *
     * Dipakai baik oleh controller (sumber kebenaran penjagaan) maupun oleh
     * view (untuk menyembunyikan tombol), jadi aturannya tidak mungkin
     * berbeda antara apa yang terlihat dan apa yang benar-benar diizinkan.
     */
    private function terkunci(User $pengguna, ?User $penggunaSaatIni): ?string
    {
        if ($penggunaSaatIni !== null && $pengguna->is($penggunaSaatIni)) {
            return 'Akun Anda sendiri tidak bisa dihapus.';
        }

        if ($pengguna->isAdmin() && User::query()->where('peran', User::PERAN_ADMIN)->count() <= 1) {
            return 'Admin satu-satunya tidak bisa dihapus.';
        }

        return null;
    }

    /**
     * Peta aturan aksi per id pengguna, dikunci dengan id.
     *
     * View membacanya untuk memutuskan tombol mana yang tampil dan alasan apa
     * yang ditulis sebagai gantinya. Dihitung sekali untuk seluruh halaman,
     * bukan sekali per baris.
     *
     * @param  LengthAwarePaginator<int, User>  $daftar
     * @return array<int|string, array{alasan: string|null}>
     */
    private function aksi(LengthAwarePaginator $daftar, ?User $penggunaSaatIni): array
    {
        $hasil = [];

        foreach ($daftar as $pengguna) {
            $hasil[$pengguna->getKey()] = [
                'alasan' => $this->terkunci($pengguna, $penggunaSaatIni),
            ];
        }

        return $hasil;
    }

    /**
     * Empat angka utama kartu statistik, semuanya dihitung dari database.
     *
     * Angka tidak pernah diambil dari $daftar->total(): itu hanya menghitung
     * baris yang cocok dengan filter dan pagination yang sedang aktif, jadi
     * angkanya ikut berubah-ubah tergantung apa yang diketik di kolom cari dan
     * tab mana yang sedang terbuka.
     *
     * @return array{
     *     total: int,
     *     aktif: int,
     *     nonaktif: int,
     *     bergabung: int,
     *     naik: string|null,
     *     keterangan: string
     * }
     */
    private function statistik(): array
    {
        /*
         * Tren memakai helper yang sama dengan kartu "Pengguna" di dashboard,
         * jadi perbandingan "dari bulan lalu" di kedua halaman itu dihitung
         * dengan definisi yang sama persis. Ronde 30 hari, bukan kalender
         * bulan, supaya pembandingnya selalu rentang sepanjang yang sama.
         */
        $tren = StatistikAdmin::perubahanBaru(User::query());

        $aktif = (int) User::query()->aktif()->count();

        return [
            'total' => $tren['total'],
            'aktif' => $aktif,
            'nonaktif' => max(0, $tren['total'] - $aktif),
            'bergabung' => (int) User::query()->where('created_at', '>=', now()->startOfDay())->count(),

            /*
             * Persentase hanya dikirim kalau benar-benar naik. Kartu statistik
             * menaruh anak panah hijau di sebelah angkanya, jadi persentase
             * negatif akan tampil sebagai panah naik hijau sementara
             * keterangannya bilang "turun" — dua tanda yang saling
             * bertentangan di satu kartu. Kalau trennya turun atau belum ada
             * pembanding, kartu ini diam saja dan view jatuh ke keterangannya.
             */
            'naik' => $tren['arah'] !== 'naik' || $tren['persen'] === null
                ? null
                : rtrim(rtrim(number_format($tren['persen'], 1, ',', ''), '0'), ',').'%',
            'keterangan' => $tren['bulan_ini'].' bergabung 30 hari terakhir',
        ];
    }

    /**
     * Angka ringkas platform untuk baris informasi di kaki tabel.
     *
     * Dua nilai ini dulu berdiri sendiri sebagai kartu, dan sengaja tetap
     * dihitung: jumlah karya per pengguna ada di tabel, tapi "berapa karya
     * yang ada di platform ini" dan "berapa akun yang mengurus konten" hanya
     * berarti sebagai satu angka untuk seluruh halaman.
     *
     * @return array{admin: int, karya: int, rata_karya: string}
     */
    private function ringkasan(): array
    {
        $total = (int) User::query()->count();
        $admin = (int) User::query()->where('peran', User::PERAN_ADMIN)->count();
        $karya = (int) Materi::query()->count() + (int) Quiz::query()->count();

        return [
            'admin' => $admin,
            'karya' => $karya,
            'rata_karya' => $total > 0
                ? rtrim(rtrim(number_format($karya / $total, 1, ',', ''), '0'), ',')
                : '0',
        ];
    }

    /**
     * Tab filter peran, lengkap dengan jumlahnya.
     *
     * Tabnya tautan biasa ke URL yang sudah ada, bukan tombol JavaScript, jadi
     * filter tetap jalan kalau JavaScript mati dan tombol "kembali" browser
     * tetap mengembalikan tab sebelumnya.
     *
     * Jumlah dihitung setelah filter status, bukan dari seluruh tabel, supaya
     * angkanya ikut menunjukkan berapa yang akan benar-benar terlihat kalau
     * tab itu diklik. Kata kunci pencarian sengaja ikut diabaikan: kartu
     * statistik juga ikut berubah kalau difilter, tapi tab tidak, supaya
     * cara hitungnya sama seperti tab status di halaman Verifikasi.
     *
     * @return array<string, array{label: string, nilai: string, jumlah: int, href: string}>
     */
    private function tabPeran(string $status, string $kataKunci): array
    {
        $jumlah = $this->saring(User::query(), '', 'semua', $status)
            ->selectRaw('peran, COUNT(*) as jumlah')
            ->groupBy('peran')
            ->pluck('jumlah', 'peran');

        /*
         * "semua" dijumlahkan dari dua peran yang ada, bukan diambil dari
         * query terpisah. Kunci "semua" tidak pernah muncul di hasil
         * groupBy('peran') — yang muncul hanya nilai peran aslinya — jadi
         * kalau tidak dijumlahkan, tab "Semua" akan selalu nol.
         */
        $jumlah['semua'] = array_sum($jumlah->all());

        $label = [
            'semua' => 'Semua',
            User::PERAN_ADMIN => 'Admin',
            User::PERAN_USER => 'User',
        ];

        $tab = [];

        foreach (self::PERAN as $nilai) {
            $tab[$nilai] = [
                'label' => $label[$nilai],
                'nilai' => $nilai,
                'jumlah' => (int) ($jumlah[$nilai] ?? 0),
                'href' => route('admin.pengguna', [
                    'peran' => $nilai,
                    'status' => $status,
                    'q' => $kataKunci,
                ]),
            ];
        }

        return $tab;
    }

    /**
     * Tab filter status, lengkap dengan jumlahnya.
     *
     * Jumlah dihitung setelah filter peran. Tab "Nonaktif" tidak dihitung
     * dengan query sendiri: yang aktif dihitung lewat scope, sisanya dari
     * tab "Semua" dikurangi tab "Aktif".
     *
     * @return array<string, array{label: string, nilai: string, jumlah: int, href: string}>
     */
    private function tabStatus(string $peran, string $kataKunci): array
    {
        $query = $this->saring(User::query(), '', $peran, 'semua');

        $jumlah = [
            'semua' => (int) (clone $query)->count(),
            'aktif' => (int) (clone $query)->aktif()->count(),
        ];

        $label = [
            'semua' => 'Semua',
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
        ];

        $tab = [];

        foreach (self::STATUS as $nilai) {
            $tab[$nilai] = [
                'label' => $label[$nilai],
                'nilai' => $nilai,
                'jumlah' => $nilai === 'nonaktif'
                    ? max(0, $jumlah['semua'] - $jumlah['aktif'])
                    : $jumlah[$nilai],
                'href' => route('admin.pengguna', [
                    'peran' => $peran,
                    'status' => $nilai,
                    'q' => $kataKunci,
                ]),
            ];
        }

        return $tab;
    }

    /**
     * Data isi dialog detail, dikunci dengan id pengguna.
     *
     * Bentuknya map, bukan daftar, supaya JavaScript cukup mencari satu id: satu
     * dialog dipakai ulang untuk semua baris, dan tidak ada baris pun yang
     * menulis angka statistiknya sendiri di markup.
     *
     * Rata-rata nilai hanya ikut kalau ada pengerjaan yang selesai. Kalau belum
     * ada, labelnya "—", bukan 0: nol di sini berarti "rata-ratanya benar-benar
     * nol", sedangkan kenyataannya belum ada yang dinilai.
     *
     * @param  LengthAwarePaginator<int, User>  $daftar
     * @return array<int|string, array<string, mixed>>
     */
    private function detail(LengthAwarePaginator $daftar): array
    {
        $hasil = [];

        foreach ($daftar as $pengguna) {
            $avatar = $pengguna->warnaAvatar();
            $selesai = (int) $pengguna->pengerjaan_selesai_count;
            $nilaiRata = $pengguna->nilai_rata === null
                ? null
                : round((float) $pengguna->nilai_rata, 1);

            $hasil[$pengguna->getKey()] = [
                'nama' => (string) $pengguna->nama,
                'email' => (string) $pengguna->email,
                'inisial' => $pengguna->inisial(),
                'warna' => $avatar['warna'],
                'warna_gelap' => $avatar['warna_gelap'],
                'foto' => $pengguna->fotoProfilUrl(),

                'peran' => (string) $pengguna->peran,
                'peran_label' => $pengguna->isAdmin() ? 'Admin' : 'User',
                'aktif' => $pengguna->isAktif(),
                'status_label' => $pengguna->isAktif() ? 'Aktif' : 'Nonaktif',

                /*
                 * "Berapa lama" disusun server, bukan di JavaScript: teks
                 * bahasa Indonesianya butuh perhitungan waktu, dan formatnya
                 * lebih baik punya satu sumber saja. Yang belum pernah masuk
                 * dapat kalimat sendiri, bukan "0 menit lalu".
                 */
                'aktivitas' => $pengguna->aktivitasLabel(),

                'bergabung' => $pengguna->created_at?->translatedFormat('d M Y') ?? '-',
                'bergabung_jam' => $pengguna->created_at?->format('H:i') ?? '',

                'materi' => (int) $pengguna->materi_count,
                'quiz' => (int) $pengguna->quiz_count,
                'pengerjaan' => (int) $pengguna->pengerjaan_quiz_count,
                'selesai' => $selesai,

                'nilai_rata_label' => $nilaiRata === null
                    ? '—'
                    : rtrim(rtrim(number_format($nilaiRata, 1, ',', ''), '0'), ','),
                'nilai_keterangan' => $selesai > 0
                    ? 'Dari '.$selesai.' quiz yang selesai'
                    : 'Belum ada quiz yang selesai',
            ];
        }

        return $hasil;
    }

    /**
     * Query daftar pengguna dengan ketiga filter sekaligus.
     *
     * Ketiganya method biasa, bukan local scope: filter ini hanya dipakai
     * halaman ini dan tidak pantas jadi bagian dari model User yang dipakai
     * seluruh aplikasi.
     */
    private function saring(Builder $query, string $kataKunci, string $peran, string $status): Builder
    {
        if ($kataKunci !== '') {
            // Hanya dua kolom yang dibaca di tabel, jadi hanya dua yang dicari.
            $query->where(function (Builder $isi) use ($kataKunci): void {
                $isi->where('nama', 'like', '%'.$kataKunci.'%')
                    ->orWhere('email', 'like', '%'.$kataKunci.'%');
            });
        }

        $query->when(
            in_array($peran, [User::PERAN_ADMIN, User::PERAN_USER], true),
            fn (Builder $saring) => $saring->where('peran', $peran)
        );

        return $query->when($status === 'aktif',
            fn (Builder $saring) => $saring->aktif())
            ->when($status === 'nonaktif',
                fn (Builder $saring) => $saring->nonaktif());
    }

    /**
     * Nilai dari query string, dipaksa ke salah satu yang diizinkan.
     *
     * Tanpa penjaga ini ?status=ngawur akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya dibatasi di sini dan nilai asing jatuh ke
     * bawaannya.
     *
     * @param  array<int, string>  $pilihan
     */
    private function terpilih(mixed $nilai, array $pilihan, string $bawaan): string
    {
        return in_array((string) $nilai, $pilihan, true) ? (string) $nilai : $bawaan;
    }
}
