<?php

namespace App\Support;

use App\Models\Jadwal;
use App\Models\Pelajaran;
use Carbon\Carbon;

/**
 * Sumber data tunggal untuk jadwal pelajaran.
 *
 * Tiga tempat memakai kelas ini:
 *   - panel "Jadwal Hari Ini" di sidebar dashboard
 *   - halaman /user/jadwal
 *   - form tambah dan edit jadwal
 *
 * Keduanya membaca dari sini, bukan menyalin query-nya masing-masing, supaya
 * baris yang terlihat di dashboard dijamin sama dengan baris yang terlihat di
 * halaman jadwal untuk tanggal yang sama.
 *
 * Alur pemakaiannya dua tahap, supaya halaman jadwal hanya menjalankan satu
 * query saja meski perlu melihat sebulan penuh:
 *
 *   1. jadwalMinggu() menarik seluruh jadwal milik pengguna dan memetakannya
 *      jadi peta per hari: [0 => [...], 1 => [...], ..., 6 => [...]]
 *   2. hari(), minggu(), bulan(), dan kategori() membaca dari peta itu
 *
 * Status (selesai / sedang / akan datang) sengaja dihitung di tahap kedua,
 * bukan di jadwalMinggu(), karena status bergantung pada tanggal sedangkan
 * peta itu belum tahu tanggalnya.
 *
 * Bentuk array per baris (yang dipakai komponen):
 *   id, hari, nama_hari, mulai, selesai, durasi_menit, durasi_label, judul,
 *   kelas, ruang, ikon, warna, warna_gelap, punya_pr, pr, pr_dikumpulkan,
 *   pr_keterangan, meta, bisa_diubah, tautan_edit, tautan_hapus
 *
 * "meta" sudah berupa pasangan label => nilai untuk baris detail di bawah
 * judul, dan hanya berisi yang terisi. "kategori" => [nama, slug, ikon,
 * warna, warna_gelap] ikut ditambahkan di baris yang sama.
 *
 * Ditambah status, status_label, lewat, dan sedang oleh hari().
 *
 * Tabel tb_jadwal adalah satu-satunya sumber baris jadwal. Tidak ada jadwal
 * contoh lagi: pengguna yang belum pernah menambah jadwal mendapat peta hari
 * yang kosong, dan yang tampil di halaman maupun di dashboard adalah empty
 * state "Belum Ada Jadwal" dengan tombol menambah, sama seperti halaman
 * Karya Saya, Hasil, dan Simpan.
 *
 * Nama hari dan bulan ditulis manual dalam bahasa Indonesia karena locale
 * aplikasi masih "en" (lihat config/app.php). Daftar singkatnya sengaja sama
 * dengan kalender di dashboard (User\DashboardController::kalender) supaya
 * keduanya tidak terlihat berbeda.
 */
final class DaftarJadwal
{
    public const STATUS_BERLANGSUNG = 'Berlangsung';

    public const STATUS_AKAN_DATANG = 'Akan Datang';

    public const STATUS_SELESAI = 'Selesai';

    /**
     * Nama hari untuk header kalender, diurutkan dari Minggu.
     *
     * @var array<int, string>
     */
    private const NAMA_HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    /**
     * Nama hari lengkap, diurutkan dari Minggu.
     *
     * @var array<int, string>
     */
    private const NAMA_HARI_PENUH = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /**
     * Nama bulan untuk header kalender, diurutkan dari Januari.
     *
     * @var array<int, string>
     */
    private const NAMA_BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Ikon garis untuk tiap mata pelajaran.
     *
     * Pelajaran::KATALOG menyimpan ikon berupa emoji atau teks pendek (mis.
     * "</>"), yang tidak bisa dipakai sebagai path SVG. Karena itu ikon jadwal
     * diambil dari Ikon supaya tiap baris benar-benar punya gambar.
     *
     * @var array<string, string>
     */
    private const IKON = [
        'bahasa-indonesia' => 'pena',
        'bahasa-inggris' => 'buku',
        'matematika' => 'kalkulator',
        'ipa' => 'target',
        'ips' => 'orang',
        'ppkn' => 'bintang',
        'pemrograman' => 'kode',
        'pjkr' => 'grafik',
        'desain-web' => 'petir',
        'database' => 'dokumen',
    ];

    /**
     * Seluruh jadwal milik pengguna, sudah dipetakan jadi peta per hari.
     *
     * Hasilnya diindeks 0-6 (Minggu sampai Sabtu) supaya pemanggilnya bisa
     * langsung mengambil hari yang dibutuhkan tanpa cek batas array.
     *
     * Pengguna yang belum punya satu pun baris di tb_jadwal — termasuk akun
     * baru — mendapat peta yang seluruh harinya kosong. Halaman dan kartu
     * dashboard yang menangani keadaan itu lewat empty state mereka
     * sendiri, bukan lewat jadwal contoh.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function jadwalMinggu(?int $idPengguna): array
    {
        $peta = array_fill(0, 7, []);

        if ($idPengguna === null) {
            return $peta;
        }

        $baris = Jadwal::query()
            ->milik($idPengguna)
            ->urut()
            ->get();

        foreach ($baris as $jadwal) {
            $hari = (int) $jadwal->hari;

            if (! array_key_exists($hari, $peta)) {
                continue;
            }

            $peta[$hari][] = self::petakanModel($jadwal);
        }

        return $peta;
    }

    /**
     * Jadwal hari ini, untuk kartu di dashboard.
     *
     * Barisnya sama persis dengan hasil hari() supaya panel dashboard dan
     * halaman jadwal tidak mungkin menampilkan data yang berbeda.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function hariIni(?int $idPengguna): array
    {
        $peta = self::jadwalMinggu($idPengguna);

        return self::hari($peta);
    }

    /**
     * Seluruh pelajaran pada satu tanggal, sudah difilter, dicari, diurutkan
     * berdasarkan jam mulai, dan diberi statusnya.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $peta  hasil jadwalMinggu()
     * @return array<int, array<string, mixed>>
     */
    public static function hari(array $peta, ?Carbon $tanggal = null, string $kategori = '', string $kataKunci = ''): array
    {
        $tanggal ??= now();
        $nomorHari = (int) $tanggal->dayOfWeek;

        $jadwal = array_map(
            fn (array $baris) => self::beriStatus($baris, $tanggal),
            $peta[$nomorHari] ?? []
        );

        $jadwal = array_values(array_filter(
            $jadwal,
            fn (array $baris) => $kategori === '' || $baris['kategori']['slug'] === $kategori
        ));

        $jadwal = array_values(array_filter(
            $jadwal,
            fn (array $baris) => $kataKunci === '' || self::cocok($baris, $kataKunci)
        ));

        usort($jadwal, fn (array $a, array $b) => [$a['mulai'], $a['selesai']] <=> [$b['mulai'], $b['selesai']]);

        return $jadwal;
    }

    /**
     * Tujuh hari dalam minggu yang sama dengan tanggal terpilih, dipakai
     * strip pemilih hari.
     *
     * Minggu sekolah di Indonesia dimulai dari Senin, jadi tanggal senin
     * dihitung lebih dulu lalu digeser mundur sebanyak yang diperlukan.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $peta  hasil jadwalMinggu()
     * @return array<int, array{hari: string, nama: string, angka: int, tanggal: string, tautan: string, hari_ini: bool, aktif: bool, jumlah: int}>
     */
    public static function minggu(array $peta, ?Carbon $tanggal = null, string $kategori = '', string $kataKunci = ''): array
    {
        $tanggal ??= now();
        $senin = $tanggal->copy()->startOfWeek(Carbon::MONDAY);
        $hariIni = now()->toDateString();

        $daftar = [];

        for ($i = 0; $i < 7; $i++) {
            $hari = $senin->copy()->addDays($i);

            $daftar[] = [
                'hari' => self::namaHari($hari),
                'nama' => self::namaHariPenuh($hari),
                'angka' => (int) $hari->day,
                'tanggal' => $hari->toDateString(),
                'tautan' => self::tautan($hari, $kategori, $kataKunci),
                'hari_ini' => $hari->toDateString() === $hariIni,
                'aktif' => $hari->isSameDay($tanggal),
                'jumlah' => count(self::hari($peta, $hari, $kategori, $kataKunci)),
            ];
        }

        return $daftar;
    }

    /**
     * Kalender bulanan untuk sidebar halaman jadwal.
     *
     * Grid dihitung di server (bukan JavaScript) supaya navigasi bulan
     * sebelumnya / berikutnya cukup berupa link biasa (?bulan=YYYY-MM) dan
     * tetap jalan walau JavaScript dimatikan.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $peta  hasil jadwalMinggu()
     * @return array{nama_bulan: string, nama_hari: array<int, string>, sel: array<int, array<string, mixed>>, sebelumnya: string, berikutnya: string}
     */
    public static function bulan(array $peta, Carbon $bulan, string $kategori = '', string $kataKunci = ''): array
    {
        $awal = $bulan->copy()->startOfMonth();
        $jumlahHari = (int) $awal->copy()->endOfMonth()->day;

        // Hari dalam seminggu dimulai dari Minggu (0). Sel kosong di depan
        // supaya tanggal 1 jatuh di kolom yang benar, dan baris terakhir
        // dilengkapi ke tujuh kolom.
        $geser = (int) $awal->dayOfWeek;
        $jumlahSel = (int) (ceil(($geser + $jumlahHari) / 7) * 7);
        $jangkar = $awal->copy()->subDays($geser);

        $sel = [];

        for ($i = 0; $i < $jumlahSel; $i++) {
            $hari = $jangkar->copy()->addDays($i);
            $dalamBulan = $hari->month === $awal->month;

            $jadwal = $dalamBulan ? self::hari($peta, $hari, $kategori, $kataKunci) : [];

            $sel[] = [
                'angka' => (int) $hari->day,
                'dalam_bulan' => $dalamBulan,
                'hari_ini' => $hari->isSameDay(now()),
                'jumlah' => count($jadwal),
                'tanggal' => $dalamBulan ? $hari->toDateString() : null,
                'tautan' => $dalamBulan ? self::tautan($hari, $kategori, $kataKunci) : null,
            ];
        }

        return [
            'nama_bulan' => self::namaBulan($awal),
            'nama_hari' => self::NAMA_HARI,
            'sel' => $sel,
            'sebelumnya' => self::tautanBulan($awal->copy()->subMonth(), $kategori, $kataKunci),
            'berikutnya' => self::tautanBulan($awal->copy()->addMonth(), $kategori, $kataKunci),
        ];
    }

    /**
     * Daftar pilihan filter mata pelajaran, diambil dari seluruh jadwal
     * sepekan.
     *
     * Diambil dari sepekan, bukan dari hari yang sedang dibuka, supaya pilihan
     * di dropdown tidak ikut hilang begitu pengguna pindah hari.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $peta  hasil jadwalMinggu()
     * @return array<int, array{nama: string, slug: string, jumlah: int}>
     */
    public static function kategori(array $peta): array
    {
        $jumlah = [];

        foreach ($peta as $barisHari) {
            foreach ($barisHari as $baris) {
                $slug = $baris['kategori']['slug'];
                $jumlah[$slug] = ($jumlah[$slug] ?? 0) + 1;
            }
        }

        // Diurutkan mengikuti katalog Pelajaran, sama seperti filter kategori
        // di halaman Materi, supaya urutan pilihannya tidak lompat-lompat.
        $urutan = collect(Pelajaran::KATALOG)
            ->pluck('slug')
            ->mapWithKeys(fn (string $slug, int $index) => [$slug => $index]);

        $daftar = [];

        foreach ($jumlah as $slug => $total) {
            $daftar[] = [
                'nama' => Pelajaran::warna($slug)['nama'],
                'slug' => $slug,
                'jumlah' => $total,
            ];
        }

        usort($daftar, fn (array $a, array $b) => [
            $urutan[$a['slug']] ?? 99,
            $a['nama'],
        ] <=> [
            $urutan[$b['slug']] ?? 99,
            $b['nama'],
        ]);

        return $daftar;
    }

    /**
     * Ringkasan singkat di kepala halaman dan di sidebar.
     *
     * Semua angka dihitung dari daftar yang sudah difilter, jadi kartu
     * ringkasan dan daftar di bawahnya selalu bicara tentang hal yang sama.
     *
     * @param  array<int, array<string, mixed>>  $jadwal  hasil hari()
     * @return array{jumlah: int, jam: float, jam_label: string, berlangsung: string|null, sedang: array<string, mixed>|null, berikut: array<string, mixed>|null, selesai: int, akan_datang: int, nama_hari: string, tanggal_label: string, libur: bool}
     */
    public static function ringkasan(array $jadwal, ?Carbon $tanggal = null): array
    {
        $tanggal ??= now();

        $menit = 0;
        $sedang = null;
        $berikut = null;
        $selesai = 0;
        $akanDatang = 0;

        foreach ($jadwal as $baris) {
            $menit += $baris['durasi_menit'];

            if ($baris['sedang']) {
                $sedang ??= $baris;
            }

            if ($baris['lewat']) {
                $selesai++;

                continue;
            }

            /*
             * Yang sedang berlangsung sengaja tidak ikut dihitung sebagai
             * "akan datang". Kalau ikut, kartu yang berbunyi "3 akan datang"
             * akan menampilkan pelajaran yang sedang terjadi sebagai salah
             * satu di antaranya — dua label berbeda untuk satu baris yang sama.
             */
            if ($baris['status'] !== self::STATUS_AKAN_DATANG) {
                continue;
            }

            // Daftar sudah diurutkan berdasarkan jam mulai, jadi pelajaran
            // "akan datang" yang pertama otomatis yang paling dekat.
            $akanDatang++;
            $berikut ??= $baris;
        }

        $jam = $menit / 60;

        return [
            'jumlah' => count($jadwal),
            'jam' => $jam,
            // Jam selalu ditulis dengan satu desimal supaya "4,5 jam" tidak
            // muncul sebagai "4 jam" padahal setengah jamnya sudah dihitung.
            'jam_label' => number_format($jam, 1, ',', '.'),
            'berlangsung' => $sedang['judul'] ?? null,
            'sedang' => $sedang,
            'berikut' => $berikut,
            'selesai' => $selesai,
            'akan_datang' => $akanDatang,
            'nama_hari' => self::namaHariPenuh($tanggal),
            'tanggal_label' => $tanggal->day.' '.self::namaBulan($tanggal).' '.$tanggal->year,
            // Kalau hari yang sedang dibaca hari Minggu, kosong di hari itu
            // wajar terjadi, bukan akibat filter yang tidak cocok.
            'libur' => $jadwal === [] && (int) $tanggal->dayOfWeek === Carbon::SUNDAY,
        ];
    }

    /**
     * Daftar pilihan pelajaran untuk form tambah dan edit jadwal.
     *
     * Berasal dari katalog Pelajaran, bukan dari jadwal yang sudah ada, supaya
     * pelajaran baru bisa dipilih walaupun belum pernah dipakai di jadwal.
     *
     * @return array<int, array{nama: string, slug: string}>
     */
    public static function pilihanPelajaran(): array
    {
        return array_map(
            fn (array $item) => ['nama' => $item['nama'], 'slug' => $item['slug']],
            Pelajaran::KATALOG
        );
    }

    /**
     * Nama hari lengkap, dipakai di form tambah dan edit.
     *
     * Diurutkan dari Senin, bukan dari Minggu, karena di form yang dipakai
     * adalah urutan sekolah: Senin lebih dulu, Minggu terakhir.
     *
     * @return array<int, array{nilai: int, nama: string}>
     */
    public static function pilihanHari(): array
    {
        $urutan = [1, 2, 3, 4, 5, 6, 0];

        return array_map(
            fn (int $nomor) => [
                'nilai' => $nomor,
                'nama' => self::namaHariPenuh(Carbon::createFromDate(2026, 1, 4)->addDays($nomor)),
            ],
            $urutan
        );
    }

    /**
     * Baca parameter ?tanggal=YYYY-MM-DD.
     *
     * Nilai di luar kalender yang nyata dianggap tidak ada, supaya URL yang
     * diketik manual tidak membuat halaman error.
     */
    public static function tanggalDariQuery(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $nilai, $bagian) !== 1) {
            return null;
        }

        if (! checkdate((int) $bagian[2], (int) $bagian[3], (int) $bagian[1])) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay();
    }

    /**
     * Baca parameter ?bulan=YYYY-MM, atau null kalau tidak valid.
     */
    public static function bulanDariQuery(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || preg_match('/^(\d{4})-(\d{2})$/', $nilai, $bagian) !== 1) {
            return null;
        }

        if (! checkdate((int) $bagian[2], 1, (int) $bagian[1])) {
            return null;
        }

        return Carbon::createFromFormat('Y-m', $nilai)->startOfMonth();
    }

    /**
     * Tanggal terdekat yang jatuh pada hari tertentu, mis. tanggal Senin
     * terdekat untuk hari = 1.
     *
     * Dipakai setelah menambah, mengubah, atau menghapus jadwal supaya
     * pengguna langsung melihat hari yang barusan ia kerjakan, bukan
     * lngkapan ke jadwal hari ini.
     */
    public static function tanggalDekat(int $hari): ?string
    {
        $tanggal = now()->startOfDay();

        for ($i = 0; $i < 7; $i++) {
            if ((int) $tanggal->dayOfWeek === $hari) {
                return $tanggal->toDateString();
            }

            $tanggal = $tanggal->copy()->addDay();
        }

        return null;
    }

    /**
     * Nama hari singkat (Sen, Sel, ...) untuk header kalender.
     */
    public static function namaHari(Carbon $tanggal): string
    {
        return self::NAMA_HARI[(int) $tanggal->dayOfWeek];
    }

    /**
     * Nama hari lengkap dalam bahasa Indonesia.
     */
    public static function namaHariPenuh(Carbon $tanggal): string
    {
        return self::NAMA_HARI_PENUH[(int) $tanggal->dayOfWeek];
    }

    /**
     * Nama bulan dalam bahasa Indonesia, mis. "September".
     */
    public static function namaBulan(Carbon $tanggal): string
    {
        return self::NAMA_BULAN[(int) $tanggal->month - 1];
    }

    /**
     * Petakan satu baris dari tb_jadwal ke bentuk array.
     *
     * @return array<string, mixed>
     */
    private static function petakanModel(Jadwal $jadwal): array
    {
        return self::bentuk([
            'id' => $jadwal->getKey(),
            'hari' => (int) $jadwal->hari,
            'mulai' => $jadwal->jamMulai(),
            'selesai' => $jadwal->jamSelesai(),
            'judul' => $jadwal->judul,
            'kelas' => $jadwal->kelas,
            'ruang' => $jadwal->ruang,
            'slug' => $jadwal->pelajaran,
            'pr' => $jadwal->pr,
            'pr_dikumpulkan' => $jadwal->pr_dikumpulkan?->toDateString(),
            'tautan_edit' => route('user.jadwal.edit', $jadwal->getKey()),
            'tautan_hapus' => route('user.jadwal.destroy', $jadwal->getKey()),
        ]);
    }

    /**
     * Bagian atas yang sama untuk semua sumber data: warna, ikon, durasi,
     * PR, meta, dan tautan edit/hapus.
     *
     * @param  array{id: int, hari: int, mulai: string, selesai: string, judul: string, kelas: string|null, ruang: string|null, slug: string, pr: string|null, pr_dikumpulkan: string|null, tautan_edit: string|null, tautan_hapus: string|null}  $baris
     * @return array<string, mixed>
     */
    private static function bentuk(array $baris): array
    {
        $kategori = Pelajaran::warna($baris['slug']);
        $menit = self::selisihMenit($baris['mulai'], $baris['selesai']);
        $punyaPr = filled($baris['pr']);

        return [
            'id' => $baris['id'],
            'hari' => $baris['hari'],
            'nama_hari' => self::namaHariPenuh(Carbon::createFromDate(2026, 1, 4)->addDays($baris['hari'])),
            'mulai' => $baris['mulai'],
            'selesai' => $baris['selesai'],
            'durasi_menit' => $menit,
            'durasi_label' => self::durasiLabel($menit),
            'judul' => $baris['judul'],
            'kelas' => $baris['kelas'],
            'ruang' => $baris['ruang'],
            'ikon' => Ikon::path(self::IKON[$baris['slug']] ?? 'buku'),
            'warna' => $kategori['warna'],
            'warna_gelap' => $kategori['warna_gelap'],
            'kategori' => [
                'nama' => $kategori['nama'],
                'slug' => $kategori['slug'],
                'ikon' => $kategori['ikon'],
                'warna' => $kategori['warna'],
                'warna_gelap' => $kategori['warna_gelap'],
            ],
            'punya_pr' => $punyaPr,
            'pr' => $baris['pr'],
            'pr_dikumpulkan' => $baris['pr_dikumpulkan'],
            'pr_keterangan' => self::keteranganTenggat($baris['pr_dikumpulkan']),
            'meta' => self::metaBaris($baris['kelas'], $baris['ruang'], $menit),
            'bisa_diubah' => $baris['tautan_edit'] !== null,
            'tautan_edit' => $baris['tautan_edit'],
            'tautan_hapus' => $baris['tautan_hapus'],
        ];
    }

    /**
     * Pasangan label => nilai untuk baris detail di bawah judul pelajaran.
     *
     * Hanya berisi yang benar-benar diisi. Kelas dan ruang boleh dikosongkan,
     * jadi tidak boleh muncul sebagai baris kosong: lebih baik tidak
     * ditampilkan daripada menampilkan label dengan nilai yang tidak berarti.
     *
     * Durasi selalu ikut karena dihitung dari jam, bukan dari isian pengguna.
     *
     * @return array<string, string>
     */
    private static function metaBaris(?string $kelas, ?string $ruang, int $menit): array
    {
        $meta = [];

        if (filled($kelas)) {
            $meta['Kelas'] = $kelas;
        }

        if (filled($ruang)) {
            $meta['Ruang'] = $ruang;
        }

        $meta['Durasi'] = self::durasiLabel($menit);

        return $meta;
    }

    /**
     * Tenggat PR dalam bentuk kalimat siap tampil, mis. "Lewat 2 hari" atau
     * "3 hari lagi".
     *
     * Tenggat PR yang sudah lewat tetap dikembalikan (bukan disembunyikan),
     * justru di situ nilainya: pengguna perlu tahu PR itu sudah melewati batas.
     *
     * @return array{teks: string, nada: 'terlambat'|'sekarang'|'dekat'|'aman'}|null
     */
    private static function keteranganTenggat(?string $tanggal): ?array
    {
        if ($tanggal === null) {
            return null;
        }

        $tenggat = Carbon::parse($tanggal)->startOfDay();
        $hariIni = now()->startOfDay();

        // Selisih diambil sebagai nilai mutlak, lalu arahnya dibaca dari
        // perbandingan tanggalnya. diffInDays() di Carbon 3 mengembalikan
        // nilai bertanda dengan arah yang membingungkan, jadi lebih aman
        // memutuskan arahnya lewat perbandingan tanggal langsung.
        $selisih = (int) $tenggat->diffInDays($hariIni);
        $sudahLewat = $tenggat->lt($hariIni);

        return match (true) {
            $sudahLewat => ['teks' => 'Terlambat '.$selisih.' hari', 'nada' => 'terlambat'],
            $selisih === 0 => ['teks' => 'Dikumpulkan hari ini', 'nada' => 'sekarang'],
            $selisih === 1 => ['teks' => 'Dikumpulkan besok', 'nada' => 'dekat'],
            $selisih <= 3 => ['teks' => $selisih.' hari lagi', 'nada' => 'dekat'],
            default => ['teks' => $tenggat->translatedFormat('d M'), 'nada' => 'aman'],
        };
    }

    /**
     * Tambahkan status ke satu baris jadwal. Dipisah dari bentuk() karena
     * status bergantung pada tanggal, sedangkan peta per hari belum tahu
     * tanggalnya.
     *
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    private static function beriStatus(array $baris, Carbon $tanggal): array
    {
        $status = self::status($tanggal, $baris['mulai'], $baris['selesai']);

        return [
            ...$baris,
            'status' => $status,
            'status_label' => self::labelStatus($status),
            'lewat' => $status === self::STATUS_SELESAI,
            'sedang' => $status === self::STATUS_BERLANGSUNG,
        ];
    }

    /**
     * Status satu pelajaran terhadap jam sekarang.
     *
     * Jam pelajaran dibandingkan dengan waktu pada tanggal yang dipilih, bukan
     * dengan tanggal penuh. Untuk tanggal selain hari ini hasilnya jelas: semua
     * sudah lewat kalau tanggalnya di masa lalu, semua masih akan datang kalau
     * tanggalnya di masa depan.
     */
    private static function status(Carbon $tanggal, string $mulai, string $selesai): string
    {
        $dasar = $tanggal->copy()->startOfDay();
        $mulaiCarbon = $dasar->copy()->setTimeFromTimeString($mulai);
        $selesaiCarbon = $dasar->copy()->setTimeFromTimeString($selesai);

        if ($tanggal->isFuture()) {
            return self::STATUS_AKAN_DATANG;
        }

        if ($tanggal->isPast()) {
            return self::STATUS_SELESAI;
        }

        $sekarang = now();

        return match (true) {
            $sekarang->greaterThanOrEqualTo($selesaiCarbon) => self::STATUS_SELESAI,
            $sekarang->greaterThanOrEqualTo($mulaiCarbon) => self::STATUS_BERLANGSUNG,
            default => self::STATUS_AKAN_DATANG,
        };
    }

    /**
     * Label status yang lebih enak dibaca. Ditulis terpisah dari statusnya
     * supaya perbandingan nilai status di markup tidak bergantung pada teks.
     */
    private static function labelStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_BERLANGSUNG => 'Sedang Berlangsung',
            self::STATUS_SELESAI => 'Sudah Selesai',
            default => 'Akan Datang',
        };
    }

    /**
     * Selisih menit antara dua jam "HH:MM".
     *
     * Dihitung dari angka jamnya langsung, bukan dari tanggal, karena jam
     * pelajaran tidak pernah melewati tengah malam.
     */
    private static function selisihMenit(string $mulai, string $selesai): int
    {
        [$jamMulai, $menitMulai] = array_map('intval', explode(':', $mulai));
        [$jamSelesai, $menitSelesai] = array_map('intval', explode(':', $selesai));

        return ($jamSelesai * 60 + $menitSelesai) - ($jamMulai * 60 + $menitMulai);
    }

    /**
     * "1 jam 30 menit" untuk durasi yang bulat, "45 menit" untuk yang tidak.
     */
    private static function durasiLabel(int $menit): string
    {
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return match (true) {
            $jam === 0 => $sisa.' menit',
            $sisa === 0 => $jam.' jam',
            default => $jam.' jam '.$sisa.' menit',
        };
    }

    /**
     * Kata kunci dicocokkan ke judul pelajaran, nama kelas, ruang, nama
     * pelajarannya, dan isi PR-nya, jadi mengetik "PR halaman" atau "Lab
     * Komputer" pun masih menemukan jadwalnya.
     *
     * @param  array<string, mixed>  $baris
     */
    private static function cocok(array $baris, string $kataKunci): bool
    {
        $needle = mb_strtolower($kataKunci);

        $kandidat = [
            $baris['judul'],
            $baris['kelas'] ?? '',
            $baris['ruang'] ?? '',
            $baris['kategori']['nama'],
            $baris['pr'] ?? '',
        ];

        foreach ($kandidat as $teks) {
            if (str_contains(mb_strtolower($teks), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Link ke halaman jadwal untuk satu tanggal. Filter yang sedang aktif
     * ikut dibawa supaya memilih tanggal tidak mematikan filter.
     */
    private static function tautan(Carbon $tanggal, string $kategori, string $kataKunci): string
    {
        return route('user.jadwal', array_filter([
            'tanggal' => $tanggal->toDateString(),
            'kategori' => $kategori,
            'q' => $kataKunci,
        ], fn ($nilai) => filled($nilai)));
    }

    /**
     * Link ke kalender bulan tertentu. Tanggal yang sedang dibaca ikut dibawa
     * supaya membuka bulan lain tidak menggeser hari terpilih.
     */
    private static function tautanBulan(Carbon $bulan, string $kategori, string $kataKunci): string
    {
        return route('user.jadwal', array_filter([
            'bulan' => $bulan->format('Y-m'),
            'kategori' => $kategori,
            'q' => $kataKunci,
        ], fn ($nilai) => filled($nilai)));
    }
}
