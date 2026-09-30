@extends('layouts.admin')

@section('title', 'Dashboard Admin | KelasKita')

@section('content')

    {{--
        Dashboard admin.

        Susunan dari atas: hero, empat kartu statistik, dua kolom
        (Perlu Ditinjau + kartu branding, Aktivitas Terbaru), lalu Menu
        Cepat. Semua angka datang dari $ringkasan, yang dihitung di
        App\Support\StatistikAdmin, jadi tidak ada angka yang dikarang
        di markup.
    --}}

    @php
        /*
         * Keterangan kartu statistik.
         *
         * Persentase naik hanya boleh ditulis kalau pembandingnya ada.
         * Kalau bulan lalu masih nol, persentasenya tidak dihitung di
         * server, dan yang ditampilkan adalah jumlah baru bulan ini.
         * Menampilkan "0% dari bulan lalu" di situation itu akan memberi
         * kesan ada yang turun padahal memang belum ada pembanding.
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

    {{-- ==================== HERO ==================== --}}
    <section class="ad-seksi ad-hero">
        <div class="ad-hero__susun">
            <div class="ad-hero__teks">
                <p class="ad-hero__lencana">
                    <x-admin.ikon nama="kap" />
                    Platform Belajar Siswa
                </p>

                <h1 class="ad-hero__judul">Selamat datang, Admin &#128075;</h1>

                <p class="ad-hero__sub">
                    Kelola aktivitas belajar dan konten KelasKita dengan mudah.
                </p>

                <div class="ad-hero__aksi">
                    @if ($totalMenunggu > 0)
                        <a href="{{ route('admin.verifikasi') }}" class="ad-tombol ad-tombol--utama">
                            <x-admin.ikon nama="daftar-cek" />

                            Tinjau {{ $totalMenunggu }} konten
                        </a>
                    @else
                        <a href="{{ route('admin.materi') }}" class="ad-tombol ad-tombol--utama">
                            <x-admin.ikon nama="buku" />
                            Kelola Materi
                        </a>
                    @endif

                    <a href="{{ route('admin.statistik') }}" class="ad-tombol ad-tombol--halus">
                        <x-admin.ikon nama="grafik" />
                        Lihat statistik
                    </a>
                </div>
            </div>

            <div class="ad-hero__ilustrasi">
                <x-admin.ilustrasi-siswa />
            </div>
        </div>
    </section>

    {{-- ==================== STATISTIK ==================== --}}
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

    {{-- ==================== PERLU DITINJAU + BRANDING ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--dua">

        {{-- Perlu Ditinjau --}}
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
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

        {{-- Kartu branding: dekoratif, bukan fitur. --}}
        <div class="flex flex-col gap-5">
            <div class="ad-kutip">
                <p class="ad-kutip__teks">
                    <span class="ad-kutip__tanda" aria-hidden="true">&ldquo;</span>

                    Konten berkualitas, untuk pembelajaran yang lebih baik.
                </p>

                <div class="ad-kutip__ilustrasi">
                    <x-admin.ilustrasi-buku />
                </div>
            </div>

            {{-- Aktivitas Terbaru --}}
            <div class="ad-kartu">
                <header class="ad-kartu__kepala">
                    <div class="ad-kartu__kepala-titik">
                        <span class="ad-cepat__ikon" aria-hidden="true">
                            <x-admin.ikon nama="kilau" ukuran="w-5 h-5" />
                        </span>

                        <h2 class="ad-kartu__kepala-judul">Aktivitas Terbaru</h2>
                    </div>

                    <a href="{{ route('admin.materi') }}" class="ad-tautan">
                        Lihat Semua
                        <x-admin.ikon nama="panah-kanan" />
                    </a>
                </header>

                @forelse ($aktivitas as $item)
                    <x-admin.aktivitas :inisial="$item['inisial']" :warna="$item['warna']"
                        :warna-gelap="$item['warna_gelap']" :judul="$item['judul']" :detail="$item['detail']"
                        :waktu="$item['waktu']" :ikon="$item['ikon']" />
                @empty
                    <div class="ad-kartu__badan">
                        <p class="ad-teks-2 ad-teks-2--tengah">Belum ada materi atau quiz yang pernah dibuat.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ==================== MENU CEPAT ==================== --}}
    <section class="ad-seksi">
        <h2 class="ad-seksi__judul mb-4">Menu Cepat</h2>

        <div class="ad-grid sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.cepat ikon="buku" judul="Lihat Semua Materi"
                keterangan="Kelola materi yang tersedia di platform" :href="route('admin.materi')" />

            <x-admin.cepat ikon="soal" judul="Lihat Semua Quiz"
                keterangan="Kelola quiz yang sudah dibuat dan terbit" :href="route('admin.quiz')" />

            <x-admin.cepat ikon="grup" judul="Kelola Pengguna"
                keterangan="Lihat siapa saja yang memakai KelasKita" :href="route('admin.pengguna')" />

            <x-admin.cepat ikon="grafik" judul="Lihat Statistik"
                keterangan="Tren belajar, nilai, dan pelajaran terpopuler" :href="route('admin.statistik')" />
        </div>
    </section>

@endsection
