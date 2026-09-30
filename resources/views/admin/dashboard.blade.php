@extends('layouts.admin')

@section('title', 'Dashboard Admin | KelasKita')

@section('content')

    {{--
        Dashboard admin.

        Susunan dari atas: banner sapaan, empat kartu statistik, dua kolom
        (Perlu Ditinjau + kartu kutipan), dua kolom (Menu Cepat + Aktivitas
        Terbaru), lalu dua kartu analytics (donat pelajaran + garis login).

        Semua angka datang dari server: $ringkasan, $perluDitinjau,
        $aktivitas, $pelajaranDisukai, dan $loginMingguan dihitung di
        App\Support\StatistikAdmin. Tidak ada angka yang dikarang di markup,
        dan setiap daftar punya empty state supaya dashboard tidak pernah
        menampilkan angka nol yang disamar jadi data.
    --}}

    @php
        /*
         * Keterangan kartu statistik.
         *
         * Persentase naik hanya boleh ditulis kalau pembandingnya ada.
         * Kalau bulan lalu masih nol, persentasenya tidak dihitung di
         * server, dan yang ditampilkan adalah jumlah baru bulan ini.
         * Menampilkan "0% dari bulan lalu" pada situation seperti itu
         * memberi kesan ada yang turun padahal memang belum ada
         * pembanding.
         */
        $keterangan = [
            'pengguna' => $ringkasan['perubahan']['pengguna']['bulan_ini'].' bergabung bulan ini',
            'materi' => $ringkasan['perubahan']['materi']['bulan_ini'].' materi baru bulan ini',
            'quiz' => $ringkasan['perubahan']['quiz']['bulan_ini'].' quiz baru bulan ini',
        ];

        $persen = collect($ringkasan['perubahan'])
            ->map(fn (array $baris): ?string => $baris['persen'] === null
                ? null
                : rtrim(rtrim(number_format($baris['persen'], 1, ',', ''), '0'), ',').'%');

        $totalMenunggu = $ringkasan['materi_menunggu'] + $ringkasan['quiz_menunggu'];
    @endphp

    {{-- ==================== BANNER SAPAAN ==================== --}}
    <x-admin.banner :total-menunggu="$totalMenunggu" />

    {{-- ==================== STATISTIK UTAMA ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--statistik" aria-label="Ringkasan platform">
        <x-admin.statistik ikon="grup" label="Pengguna" :nilai="$ringkasan['pengguna']" nada="info"
            :naik="$persen['pengguna']" :keterangan="$persen['pengguna'] ? null : $keterangan['pengguna']"
            :href="route('admin.pengguna')" />

        <x-admin.statistik ikon="buku" label="Materi" :nilai="$ringkasan['materi']"
            :naik="$persen['materi']" :keterangan="$persen['materi'] ? null : $keterangan['materi']"
            :href="route('admin.materi')" />

        <x-admin.statistik ikon="soal" label="Quiz" :nilai="$ringkasan['quiz']" nada="sukses"
            :naik="$persen['quiz']" :keterangan="$persen['quiz'] ? null : $keterangan['quiz']"
            :href="route('admin.quiz')" />

        <x-admin.statistik ikon="jam" label="Menunggu Verifikasi" :nilai="$totalMenunggu" nada="peringatan"
            :keterangan="$ringkasan['materi_menunggu'].' materi · '.$ringkasan['quiz_menunggu'].' quiz'"
            :href="route('admin.verifikasi')" />
    </section>

    {{-- ==================== PERLU DITINJAU + KUTIPAN ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--dua">

        {{-- Perlu Ditinjau --}}
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon ad-cepat__ikon--kuning" aria-hidden="true">
                        <x-admin.ikon nama="daftar-cek" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="ad-kartu__kepala-judul">Perlu Ditinjau</h2>
                </div>

                <a href="{{ route('admin.verifikasi') }}" class="ad-tautan">
                    Lihat Semua
                    <x-admin.ikon nama="panah-kanan" />
                </a>
            </header>

            <div class="ad-kartu__badan">
                <div class="ad-tinjau">
                    @forelse ($perluDitinjau as $item)
                        <x-admin.tinjau :jenis="$item['jenis']" :judul="$item['judul']" :pembuat="$item['pembuat']"
                            :kategori="$item['kategori']" :kategori-warna="$item['kategori_warna']"
                            :kategori-ikon="$item['kategori_ikon']" :rincian="$item['rincian']"
                            :tanggal="$item['dibuat_pada']?->translatedFormat('d M Y')"
                            :tautan="$item['tautan']" />
                    @empty
                        <x-admin.kosong ikon="tanda-centang" judul="Semua sudah ditinjau"
                            teks="Tidak ada materi atau quiz yang menunggu persetujuan. Bagus, tidak ada yang tertunda." />
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Kartu kutipan: dekoratif, bukan fitur. --}}
        <x-admin.kutip />
    </section>

    {{-- ==================== MENU CEPAT + AKTIVITAS TERBARU ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--dua">

        {{-- Menu Cepat --}}
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon ad-cepat__ikon--biru" aria-hidden="true">
                        <x-admin.ikon nama="petir" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="ad-kartu__kepala-judul">Menu Cepat</h2>
                </div>
            </header>

            <div class="ad-kartu__badan ad-kartu__badan--rapat">
                <div class="ad-daftar-cepat">
                    <x-admin.cepat ikon="buku" judul="Lihat Semua Materi"
                        keterangan="Kelola materi yang tersedia di platform" :href="route('admin.materi')" />

                    <x-admin.cepat ikon="soal" judul="Lihat Semua Quiz"
                        keterangan="Kelola quiz yang sudah dibuat dan terbit" :href="route('admin.quiz')" />

                    <x-admin.cepat ikon="grup" judul="Kelola Pengguna"
                        keterangan="Lihat siapa saja yang memakai KelasKita" :href="route('admin.pengguna')" />

                    <x-admin.cepat ikon="grafik" judul="Lihat Statistik"
                        keterangan="Tren belajar, nilai, dan pelajaran terpopuler" :href="route('admin.statistik')" />
                </div>
            </div>
        </div>

        {{-- Aktivitas Terbaru --}}
        {{--
            ad-kartu--ringkas: kartu ini tidak ikut diregangkan setinggi
            "Menu Cepat" di sebelahnya, jadi ujungnya berhenti tepat di
            bawah item terakhir. Tanpa class ini, grid meregangkan kedua
            kartu sama tinggi dan ruang kosong muncul di bawah aktivitas.
        --}}
        <div class="ad-kartu ad-kartu--ringkas">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon ad-cepat__ikon--pink" aria-hidden="true">
                        <x-admin.ikon nama="kilau" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="ad-kartu__kepala-judul">Aktivitas Terbaru</h2>
                </div>

                <a href="{{ route('admin.materi') }}" class="ad-tautan">
                    Lihat Semua
                    <x-admin.ikon nama="panah-kanan" />
                </a>
            </header>

            <div class="ad-kartu__badan ad-kartu__badan--padat">
                @forelse ($aktivitas as $item)
                    <x-admin.aktivitas :inisial="$item['inisial']" :warna="$item['warna']"
                        :warna-gelap="$item['warna_gelap']" :judul="$item['judul']" :detail="$item['detail']"
                        :waktu="$item['waktu']" :ikon="$item['ikon']" />
                @empty
                    <x-admin.kosong ikon="jam" judul="Belum ada aktivitas"
                        teks="Materi atau quiz yang dibuat pengguna akan muncul di sini." />
                @endforelse
            </div>
        </div>
    </section>

    {{-- ==================== ANALITIK ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--analitik" aria-label="Analitik dashboard">
        <x-admin.disukai :data="$pelajaranDisukai" />

        <x-admin.login-mingguan :data="$loginMingguan" />
    </section>

@endsection
