<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Admin | KelasKita')</title>

    {{--
        Tema harus sudah terpasang SEBELUM halaman digambar, kalau tidak admin
        akan melihat kilatan terang sedikit lebih dulu setiap kali memuat
        halaman dalam mode gelap.

        Yang dibaca di sini ada dua, dan urutannya disengaja:

          - localStorage "kk-tema" = pilihan terakhir di perangkat ini. Ini yang
            menang, karena admin yang sedang membuka halaman ini baru saja
            memilih tema itu dan mungkin belum sempat menyalinnya ke
            localStorage.

          - $temaAdmin             = kolom "tema" di tb_preferensi untuk akun
            ini, dikirimkan dari view composer. Ini yang berlaku di perangkat
            lain, di mana localStorage masih kosong.

        Kalau keduanya tidak ada (admin belum pernah membuka Pengaturan, dan
        storage diblokir mode privat), tempatnya jatuh ke terang.

        localStorage dan database sengaja dua-duanya dipakai: yang pertama
        membuat tema langsung berubah tanpa kedipan, yang kedua membuat
        pilihan yang sama ikut berlaku di perangkat lain.
    --}}
    <script>
        (function () {
            var dariServer = @json($temaAdmin ?? 'terang');

            try {
                var tersimpan = window.localStorage.getItem('kk-tema');

                document.documentElement.dataset.theme =
                    tersimpan === 'gelap' || tersimpan === 'terang' ? tersimpan : dariServer;
            } catch (e) {
                document.documentElement.dataset.theme = dariServer;
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Tanpa JavaScript, daftar soal di halaman detail quiz dibuka penuh
        dan tombol "Lihat semua" yang melipatnya disembunyikan. Aturan yang
        sama persis ada di layouts/app (noscript-nya) karena kedua layout
        merender komponen x-quiz.detail-daftar-soal yang sama.
    --}}
    <noscript>
        <style>
            .daftar-soal__baris[hidden] {
                display: flex !important;
            }

            [data-daftar-soal-tombol] {
                display: none !important;
            }
        </style>
    </noscript>
</head>
{{--
    Layout area admin KelasKita.

    Struktur: sidebar di kiri (fixed, jadi jadi drawer di bawah 1024px)
    dan kolom konten di kanan yang berisi topbar lalu halaman.

    Nilai variabel di bawah dipakai sidebar supaya menu aktif dan jumlah
    konten yang menunggu hanya ditulis satu kali.
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
     *
     * "Konten Pembelajaran" adalah menu baru dan berdiri sendiri. Ia bukan
     * pengganti "Materi" atau "Quiz", dan bukan juga "Verifikasi":
     *
     *   - Materi / Quiz  = katalog konten yang sudah tayang, siapa pun
     *                      yang membuatnya (dibaca, dan diubah kalau karya
     *                      admin sendiri).
     *   - Verifikasi     = antrean setujui / tolak untuk karya pengguna.
     *   - Konten         = ruang kerja admin: tambah, sunting, gandakan,
     *                      terbitkan, dan tarik kembali karyanya sendiri.
     *
     * Dulu menu ini punya dua anak (Materi dan Quiz) yang membuka satu
     * halaman dengan tab berbeda. Anak-anaknya dihapus karena membuat
     * "Materi" dan "Quiz" muncul dua kali di sidebar — sekali sebagai anak
     * tanpa ikon dan sekali sebagai menu utama yang berikon — dan yang
     * paling membingungkan justru yang tidak berikon. Berpindah antara
     * Materi dan Quiz di ruang kerja admin sekarang lewat tab di dalam
     * halaman Konten Pembelajaran (nav.ad-tab di admin/konten.blade.php),
     * yang sudah ada dan tidak bergantung pada menu turunan.
     */
    $menuUtama = [
        [
            'label' => 'Dashboard',
            'ikon' => 'papan',
            'href' => route('admin.dashboard'),
            'aktif' => request()->routeIs('admin.dashboard'),
        ],
        [
            'label' => 'Konten Pembelajaran',
            'ikon' => 'buku',
            'href' => route('admin.konten'),
            'aktif' => request()->routeIs('admin.konten*'),
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
             *
             * Penanda aktif memakai admin.quiz* supaya menu ini tetap menyala
             * di halaman detail dan form editnya, sama seperti menu Materi
             * di atasnya.
             */
            'label' => 'Quiz',
            'ikon' => 'centang',
            'href' => route('admin.quiz'),
            'aktif' => request()->routeIs('admin.quiz*'),
        ],
        [
            'label' => 'Pengguna',
            'ikon' => 'grup',
            'href' => route('admin.pengguna'),
            'aktif' => request()->routeIs('admin.pengguna'),
        ],
    ];

    $menuBawah = [
        [
            /*
             * "admin.pengaturan*" dengan bintang, bukan route yang persis sama.
             * Pengaturan punya banyak halaman anak (profil, keamanan, pelajaran,
             * sesi, sistem, tentang), dan memakai nama route yang sama persis
             * membuat menunya menyala hanya di /admin/pengaturan. Menu yang
             * sedang dibuka harus tetap terlihat aktif di seluruh sub-halamannya.
             */
            'label' => 'Pengaturan',
            'ikon' => 'roda',
            'href' => route('admin.pengaturan'),
            'aktif' => request()->routeIs('admin.pengaturan*'),
        ],
    ];

    /*
     * Topbar punya dua bentuk. Pengaturan dan semua sub-halamannya memakai
     * bentuk ringkas: tombol buka sidebar saja.
     *
     * Dengan bintang, bukan route yang persis sama, supaya /admin/pengaturan
     * /profil ikut termasuk. Daftar sub-halaman ini akan pernah bertambah, dan
     * setiap halaman baru itu otomatis ikut mendapat bentuk topbar yang sama
     * tanpa perlu daftar kedua.
     *
     * Halaman lain memakai bentuk penuh, yang isinya ada di
     * components/admin/topbar-kanan.blade.php.
     */
    $topbarRingkas = request()->routeIs('admin.pengaturan*');
@endphp

{{--
        Aturan "Konfirmasi sebelum Publish" dari Pengaturan, ditaruh di sini
        supaya seluruh halaman admin membacanya dari satu tempat dan
        resources/js/konten-publish.js tidak perlu query apa pun.

        Nilai "0" berarti dimatikan, jadi tombol publish langsung mengirim
        form. Nilai lain berarti dialog konfirmasi dibuka lebih dulu.
    --}}
<body class="tubuh-admin antialiased" data-konten-konfirmasi="{{ $konfirmasiPublikasi ?? '1' }}">

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
        {{--
            Dua bentuk topbar, dan yang mana dipakai ditentukan lewat
            $topbarRingkas di blok @php atas.

            Bentuk penuh, di semua halaman admin kecuali Pengaturan: kotak
            pencarian, lonceng notifikasi, dan menu akun.

            Bentuk ringkas, di semua halaman /admin/pengaturan*: tombol buka
            sidebar saja.

            Kenapa Pengaturan dibedakan? Karena di halaman itu tiga hal itu memang
            tidak berguna: setiap daftar sudah punya kotak pencarian sendiri,
            saklar notifikasi ada tepat di halaman itu, dan tautan Pengaturan di
            menu akun menunjuk halaman yang sedang dibuka. Tombol Keluar di kaki
            sidebar juga sudah ada, dan posisinya sama di semua halaman.

            Notifikasi admin tidak ikut hilang: barisnya tetap dibuat di database
            dan saklarnya tetap mengatur. Hanya tempat membacanya yang tidak ada
            di halaman ini.

            Bentuk ringkas disembunyikan di layar lebar, karena satu-satunya
            isinya tombol yang sudah disembunyikan di sana. Kalau tidak, halaman
            Pengaturan akan memakai strip kosong setinggi topbar di atas judulnya
            sendiri. Di bawah 1024px tombolnya justru satu-satunya cara membuka
            sidebar, jadi topbarnya tetap ada.
        --}}
        <header @class(['ad-atas', 'ad-atas--ringkas' => $topbarRingkas])>
            <div class="ad-atas__baris">
                <button type="button" class="ad-atas__buka" data-sisi-buka aria-controls="sisi-admin"
                    aria-expanded="false" aria-label="Buka menu">
                    <x-admin.ikon nama="menu" ukuran="w-5 h-5" />
                </button>

                @unless ($topbarRingkas)
                    <x-admin.topbar-kanan :admin="$admin" />
                @endunless
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
