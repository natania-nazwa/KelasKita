<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use App\Support\StatistikAdmin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Halaman "Pengguna": daftar semua akun yang memakai KelasKita.
 *
 * Halaman ini murni membaca. Tidak ada tombol yang mengubah akun apa pun:
 * tidak ada edit, tidak ada ubah peran, tidak ada hapus. Management peran
 * sengaja tidak dibuat — saat ini hanya ada dua peran (ADMIN dan USER) dan
 * hanya ada satu admin, jadi tidak ada apa pun yang perlu dikelola.
 */
class PenggunaController extends Controller
{
    /**
     * Pengguna per halaman.
     */
    private const PER_HALAMAN = 15;

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
        ]);
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

        $aktif = (int) User::query()->where('aktif', true)->count();

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
     * dengan query sendiri: kolom "aktif" menyimpan boolean, jadi menjumlahkan
     * tab Aktif dan tab Semua sudah menghasilkan sisanya.
     *
     * @return array<string, array{label: string, nilai: string, jumlah: int, href: string}>
     */
    private function tabStatus(string $peran, string $kataKunci): array
    {
        $query = $this->saring(User::query(), '', $peran, 'semua');

        $jumlah = [
            'semua' => (int) (clone $query)->count(),
            'aktif' => (int) (clone $query)->where('aktif', true)->count(),
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
            fn (Builder $saring) => $saring->where('aktif', true))
            ->when($status === 'nonaktif',
                fn (Builder $saring) => $saring->where('aktif', false));
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
