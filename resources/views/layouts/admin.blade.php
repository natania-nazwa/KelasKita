<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Admin | KelasKita')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Layout area admin KelasKita.

    Struktur: sidebar di kiri (fixed, jadi jadi drawer di bawah 1024px)
    dan kolom konten di kanan yang berisi topbar lalu halaman.

    Nilai variabel di bawah dipakai sidebar dan topbar supaya menu aktif,
    jumlah konten yang menunggu, dan nama akun hanya ditulis satu kali.
--}}
@php
    $admin = auth()->user();

    /*
     * Jumlah konten yang menunggu keputusan. Nilainya dibagikan oleh
     * view composer di App\Providers\AppServiceProvider supaya sidebar
     * tidak menjalankan query yang sama dengan kartu statistik di
     * dashboard. Nilai bawaan dipakai kalau halaman ini dirender
     * tanpa composer (mis. saat view diuji langsung).
     */
    $jumlahVerifikasi = $jumlahVerifikasi
        ?? (\App\Models\Materi::query()->menunggu()->count() + \App\Models\Quiz::query()->menunggu()->count());

    /*
     * Menu admin. "jumlah" dipakai untuk menampilkan angka konten yang
     * menunggu keputusan di sebelah menu Verifikasi.
     *
     * Pengecualian yang disengaja: "Materi" dan "Quiz" memakai routes
     * yang sudah ada (admin.materi dan admin.quiz) tanpa prefix
     * "verifikasi", jadi URL lama tidak berubah dan tidak ada route
     * yang bentrok. Label UI tetap "Verifikasi" supaya urutannya
     * mengikuti yang diminta.
     */
    $menuUtama = [
        [
            'label' => 'Dashboard',
            'ikon' => 'papan',
            'href' => route('admin.dashboard'),
            'aktif' => request()->routeIs('admin.dashboard'),
        ],
        [
            'label' => 'Verifikasi',
            'ikon' => 'buku-centang',
            'href' => route('admin.verifikasi'),
            'aktif' => request()->routeIs('admin.verifikasi'),
            'jumlah' => $jumlahVerifikasi,
        ],
        [
            'label' => 'Materi',
            'ikon' => 'buku',
            'href' => route('admin.materi'),
            'aktif' => request()->routeIs('admin.materi*'),
        ],
        [
            /*
             * Ikon Quiz sengaja sama persis dengan menu Quiz di sidebar user
             * (lingkaran centang, "centang" di App\Support\Ikon): menu dengan
             * label yang sama harus punya lambang yang sama supaya berpindah
             * antara dua sidebar tidak terasa seperti dua aplikasi berbeda.
             */
            'label' => 'Quiz',
            'ikon' => 'centang',
            'href' => route('admin.quiz'),
            'aktif' => request()->routeIs('admin.quiz'),
        ],
        [
            'label' => 'Pengguna',
            'ikon' => 'grup',
            'href' => route('admin.pengguna'),
            'aktif' => request()->routeIs('admin.pengguna'),
        ],
        [
            'label' => 'Hasil & Statistik',
            'ikon' => 'grafik',
            'href' => route('admin.statistik'),
            'aktif' => request()->routeIs('admin.statistik'),
        ],
    ];

    $menuBawah = [
        [
            'label' => 'Pengaturan',
            'ikon' => 'roda',
            'href' => route('admin.pengaturan'),
            'aktif' => request()->routeIs('admin.pengaturan'),
        ],
    ];
@endphp

<body class="tubuh-admin antialiased">

<div class="ad-rangka">

    {{-- ---------- SIDEBAR ---------- --}}
    <aside class="ad-sisi" id="sisi-admin" aria-label="Menu admin">

        {{-- Merek --}}
        <div class="ad-sisi__kepala">
            <a href="{{ route('admin.dashboard') }}" class="ad-sisi__merek">
                {{--
                    Logo: public/images/logo.png, sama dengan yang dipakai
                    halaman user dan landing page. Sengaja tanpa kartu atau
                    lempeng di belakangnya; yang membuat logonya tetap
                    terbaca di atas sidebar ungu diatur .ad-sisi__logo di
                    admin.css.
                --}}
                <span class="ad-sisi__logo">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo KelasKita"
                        class="w-full h-full object-contain"
                    />
                </span>

                <span class="ad-sisi__merek-teks">
                    <span class="ad-sisi__nama">KelasKita</span>
                    <span class="ad-sisi__sapaan">Admin Panel</span>
                </span>
            </a>

            <button type="button" class="ad-sisi__tutup" data-sisi-tutup aria-label="Tutup menu">
                <x-admin.ikon nama="silang-polos" ukuran="w-5 h-5" />
            </button>
        </div>

        {{-- Menu --}}
        <nav class="ad-sisi__nav">
            @foreach ($menuUtama as $menu)
                <a href="{{ $menu['href'] }}"
                    @class([
                        'ad-sisi__tautan',
                        'ad-sisi__tautan--aktif' => $menu['aktif'],
                    ]) @if ($menu['aktif']) aria-current="page" @endif>

                    <span class="ad-sisi__ikon">
                        <x-admin.ikon :nama="$menu['ikon']" />
                    </span>

                    <span class="ad-sisi__label">{{ $menu['label'] }}</span>

                    @if (($menu['jumlah'] ?? 0) > 0)
                        <span class="ad-sisi__jumlah">{{ $menu['jumlah'] }}</span>
                    @endif
                </a>
            @endforeach

            <div class="ad-sisi__pisah" role="presentation"></div>

            @foreach ($menuBawah as $menu)
                <a href="{{ $menu['href'] }}"
                    @class([
                        'ad-sisi__tautan',
                        'ad-sisi__tautan--aktif' => $menu['aktif'],
                    ]) @if ($menu['aktif']) aria-current="page" @endif>

                    <span class="ad-sisi__ikon">
                        <x-admin.ikon :nama="$menu['ikon']" />
                    </span>

                    <span class="ad-sisi__label">{{ $menu['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Akun + keluar --}}
        <div class="ad-sisi__kaki">
            <div class="ad-sisi__akun">
                <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="sedang" />

                <span class="ad-sisi__akun-teks">
                    <span class="ad-sisi__akun-nama">{{ $admin?->nama ?? 'Admin' }}</span>
                    <span class="ad-sisi__akun-email">{{ $admin?->email ?? 'admin@kelaskita.com' }}</span>
                </span>
            </div>

            {{--
                Keluar memakai form POST ke route "logout" yang sudah ada.
                Sebelumnya tombolnya hanya tautan ke "/" sehingga tidak
                benar-benar mengeluarkan akun.
            --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="ad-sisi__keluar">
                    <x-admin.ikon nama="pintu-keluar" ukuran="w-4 h-4" />

                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Tirai di belakang drawer. --}}
    <div class="ad-sisi__tirai" data-sisi-tutup aria-hidden="true"></div>

    {{-- ---------- KOLOM KONTEN ---------- --}}
    <div class="ad-utama">

        {{-- ---------- TOPBAR ---------- --}}
        <header class="ad-atas">
            <div class="ad-atas__baris">

                <button type="button" class="ad-atas__buka" data-sisi-buka aria-controls="sisi-admin"
                    aria-expanded="false" aria-label="Buka menu">
                    <x-admin.ikon nama="menu" ukuran="w-5 h-5" />
                </button>

                {{--
                    Pencarian topbar. Aplikasi belum punya pencarian
                    global, jadi form ini mengirim "q" ke halaman Kelola
                    Materi yang memang sudah punya kolom pencarian: kotak
                    ini benar-benar bekerja, bukan hanya hiasan.
                --}}
                <form class="ad-atas__cari" method="GET" action="{{ route('admin.materi') }}" role="search">
                    <label for="cari-ad">Cari materi, quiz, pengguna</label>

                    <x-admin.ikon nama="cari" class="ad-atas__cari-ikon" />

                    <input id="cari-ad" name="q" type="search"
                        value="{{ request('q') }}" placeholder="Cari materi, quiz, pengguna..."
                        autocomplete="off">
                </form>

                <div class="ad-atas__kanan">
                    {{--
                        Lonceng notifikasi. Aplikasi belum punya tabel
                        notifikasi, jadi tombolnya sengaja type="button"
                        dan tidak mengirim apa pun: penandanya dekoratif.
                    --}}
                    <button type="button" class="ad-atas__notif" aria-label="Notifikasi">
                        <x-admin.ikon nama="lonceng" ukuran="w-5 h-5" />

                        <span class="ad-atas__titik" aria-hidden="true"></span>
                    </button>

                    {{-- Akun + dropdown --}}
                    <div class="ad-atas__akun">
                        <button type="button" class="ad-atas__akun-tombol" data-akun-tombol aria-expanded="false"
                            aria-controls="akun-menu">
                            <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="kecil" />

                            <span class="ad-atas__akun-teks">
                                <span class="ad-atas__akun-nama">{{ $admin?->nama ?? 'Admin' }}</span>
                                <span class="ad-atas__akun-peran">Admin</span>
                            </span>

                            <x-admin.ikon nama="panah-bawah" class="ad-atas__akun-panah" />
                        </button>

                        <div class="ad-atas__akun-menu" id="akun-menu" data-akun-menu>
                            <div class="ad-atas__akun-menu-kepala">
                                <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="sedang" />

                                <span class="min-w-0">
                                    <span class="ad-atas__akun-menu-nama">{{ $admin?->nama ?? 'Admin' }}</span>
                                    <span class="ad-atas__akun-menu-email">{{ $admin?->email ?? 'admin@kelaskita.com' }}</span>
                                </span>
                            </div>

                            <a href="{{ route('admin.pengaturan') }}" class="ad-atas__akun-menu-aksi">
                                Pengaturan
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="ad-atas__akun-menu-kaki">
                                @csrf

                                <button type="submit" class="ad-atas__akun-menu-aksi">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- ---------- KONTEN ---------- --}}
        <main class="ad-isi">
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
