@extends('layouts.admin')

@section('title', 'Dashboard Admin | KelasKita')

@section('content')

    {{--
        Dashboard admin.

        Susunan dari atas: banner sapaan, lalu dua kartu analytics ("Pelajaran
        yang Disukai" dan "Aktivitas Login Mingguan"), lalu empat kartu
        statistik, lalu satu baris dua kolom. Kolom kiri: "Perlu Ditinjau"
        dengan "Menu Cepat" tepat di bawahnya. Kolom kanan: kartu kutipan
        dengan "Aktivitas Terbaru" tepat di bawahnya.

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
         * pembanding.         */
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

    {{-- ==================== ANALITIK ==================== --}}
    {{--
        Letak analitik tepat di bawah banner sapaan.
    --}}
    <section class="ad-seksi ad-grid ad-grid--analitik" aria-label="Analitik dashboard">
        <x-admin.disukai :data="$pelajaranDisukai" />

        <x-admin.login-mingguan :data="$loginMingguan" />
    </section>

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

    {{-- ==================== DUA KOLOM: TINJAU + CEPAT | KUTIPAN + AKTIVITAS ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--dua">

        {{--
            Kolom kiri: "Perlu Ditinjau" di atas, "Menu Cepat" tepat di
            bawahnya.

            Dulu "Menu Cepat" berdiri sebagai baris sendiri di bawah baris
            ini, jadi posisinya ditentukan oleh kolom kanan yang paling
            tinggi (kartu kutipan + "Aktivitas Terbaru"). Akibatnya "Menu
            Cepat" turun jauh dan tidak menempel di bawah "Perlu Ditinjau".
            Sekarang keduanya satu kolom, jadi jaraknya hanya 1rem.

            Lebar "Menu Cepat" tidak berubah: kolom ini tetap kolom 1,65fr
            milik .ad-grid--dua, sama seperti saat card-nya berdiri sendiri
            sebagai grid item. Ukuran, padding, empat shortcut, dan empat
            kolomnya juga tidak disentuh.
        --}}
        <div class="ad-sisi-kolom">
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

                <div class="ad-kartu__badan ad-kartu__badan--luas">
                    {{--
                        ad-tinjau--besar: tiap baris tinjau dibuat lebih tinggi
                        dan lebih lega, supaya empat antrean terbaru muat
                        dengan nyaman. Kelas .ad-tinjau__* yang sama dipakai
                        halaman Verifikasi, jadi pembesarannya dikurung di
                        bawah class baru ini supaya halaman itu tidak ikut
                        berubah.
                    --}}
                    <div class="ad-tinjau ad-tinjau--besar">
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
        </div>

        {{--
            Kolom kanan: kartu kutipan di atas, "Aktivitas Terbaru" tepat
            di bawahnya.

            Dulu "Aktivitas Terbaru" berdiri sebagai baris sendiri di
            bawah "Perlu Ditinjau", sedangkan kartu kutipan di sebelah
            kanannya cuma setinggi isinya. Selisih tinggi itu jadi ruang
            kosong sehingga "Aktivitas Terbaru" terlihat melayang jauh.
            Menumpuknya di satu kolom menghilangkan ruang kosong itu: kolom
            kanannya terisi dari atas sampai bawah, dan "Aktivitas Terbaru"
            menempel tepat di bawah teks kutipan.
        --}}
        <div class="ad-sisi-kolom">
            <x-admin.kutip />

            <div class="ad-kartu">
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
        </div>
    </section>

@endsection
