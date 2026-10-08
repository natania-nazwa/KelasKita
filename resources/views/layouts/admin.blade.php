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
    /*
     * Setiap menu punya "kunci": nama pendek yang tidak pernah tampil dan
     * hanya dipakai untuk memilih menu mana yang ikut navigasi bawah.
     */
    $menuUtama = [
        [
            'kunci' => 'dashboard',
            'label' => 'Dashboard',
            'bawahLabel' => 'Dashboard',
            'ikon' => 'papan',
            'href' => route('admin.dashboard'),
            'aktif' => request()->routeIs('admin.dashboard'),
        ],
        [
            'kunci' => 'konten',
            'label' => 'Konten Pembelajaran',
            'bawahLabel' => 'Konten',
            'ikon' => 'buku',
            'href' => route('admin.konten'),
            'aktif' => request()->routeIs('admin.konten*'),
        ],
        [
            'kunci' => 'verifikasi',
            'label' => 'Verifikasi',
            'bawahLabel' => 'Verifikasi',
            'ikon' => 'buku-centang',
            'href' => route('admin.verifikasi'),
            'aktif' => request()->routeIs('admin.verifikasi'),
            'jumlah' => $jumlahVerifikasi,
        ],
        [
            'kunci' => 'materi',
            'label' => 'Materi',
            'bawahLabel' => 'Materi',
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
            'kunci' => 'quiz',
            'label' => 'Quiz',
            'bawahLabel' => 'Quiz',
            'ikon' => 'centang',
            'href' => route('admin.quiz'),
            'aktif' => request()->routeIs('admin.quiz*'),
        ],
        [
            'kunci' => 'pengguna',
            'label' => 'Pengguna',
            'bawahLabel' => 'Pengguna',
            'ikon' => 'grup',
            'href' => route('admin.pengguna'),
            'aktif' => request()->routeIs('admin.pengguna'),
        ],
    ];

    /*
     * Navigasi bawah untuk layar kecil: enam menu, tidak kurang tidak
     * lebih. Karena sidebar disembunyikan di layar itu, enam menu inilah
     * satu-satunya cara pindah halaman, jadi "Pengaturan" yang ada di
     * sidebar tidak ikut di sini; ia masih terbuka lewat ikon roda di
     * header ponsel dan lewat tombol "Lihat Semua" di dashboard.
     *
     * "Materi" ikut masuk. Dulu ia sengaja ditahan supaya barisnya tetap
     * lima, dan admin mencapainya lewat tombol "Lihat Semua" di dashboard.
     * Sekarang baris bawahnya menambah satu menu: enam tautan di 320px
     * masih muat (lihat ukuran label di resources/css/admin.css), jadi
     * Materi — katalog konten yang sudah tayang — bisa dibuka langsung
     * tanpa lewat dashboard.
     *
     * Daftar ini disaring dari $menuUtama, bukan ditulis ulang, supaya
     * ikon, tujuan tautan, angka menunggu, dan penanda halaman aktifnya
     * selalu sama dengan yang tertulis di sidebar desktop. Kalau suatu saat
     * sidebar berubah, navigasi bawah ikut berubah tanpa ikut disentuh.
     *
     * Urutannya ikut urutan $menuUtama, jadi "Materi" muncul tepat setelah
     * "Verifikasi" tanpa perlu menyusun ulang di sini.
     */
    $menuNavigasiBawah = collect($menuUtama)
        ->filter(fn (array $menu) => in_array($menu['kunci'], ['dashboard', 'konten', 'verifikasi', 'materi', 'quiz', 'pengguna'], true))
        ->values();

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
     * Topbar area admin tidak lagi punya isi: tidak ada kotak pencarian,
     * tidak ada lonceng notifikasi, dan tidak ada menu akun. Yang tersisa
     * hanya tombol buka sidebar, dan tombol itu sendiri disembunyikan di layar
     * lebar — jadi di desktop area admin berjalan tanpa strip apa pun di atas
     * judul halaman.
     *
     * Kenapa tidak ada satu pun dari tiga hal itu:
     *
     *   - Pencarian. Setiap daftar sudah punya kolom cari sendiri di dalam
     *     halamannya (x-materi.cari, x-quiz.cari, dan kotak pencarian di
     *     components/admin), jadi kotak di topbar hanya jadi kembaran yang
     *     menulis "Cari materi, quiz, pengguna" padahal isinya satu daftar.
     *     Di halaman form, kotak itu justru menyela admin dari isian yang
     *     sedang diketik.
     *   - Notifikasi. Saklarnya yang masih hidup ada di Pengaturan, dan baris
     *     notifikasinya tetap dibuat di database; yang hilang hanya tempat
     *     membacanya, karena lonceng tidak pernah menampilkan apa-apa yang
     *     tidak sudah terlihat di halaman yang baru saja dibuka admin.
     *   - Menu akun. Isinya tautan ke akun yang sedang dipakai plus tombol
     *     Keluar, dan keduanya sudah ada di kaki sidebar. Nama akunnya juga
     *     sudah tertulis di kaki sidebar itu.
     *
     * Jadi tidak ada fungsi yang benar-benar hilang, dan tidak ada satu pun
     * query notifikasi yang perlu dijalankan di setiap halaman admin.
     */
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

        {{-- ---------- HEADER PONSEL ---------- --}}
        {{--
            Header khusus layar kecil (<= 767px). Di lebar itu sidebar
            disembunyikan dan navigasi bawah yang menggantikannya, jadi
            halaman akan kehilangan merek, nama akun, dan jalan ke
            Pengaturan kalau tidak ada strip ini.

            Bentuknya sengaja seperti header aplikasi mobile, bukan seperti
            topbar admin: merek di kiri, dua aksi di kanan, satu baris saja
            supaya tidak memakan tinggi layar yang sudah sempit.

            Isinya:
              - logo dan nama yang sama persis dengan kepala sidebar, supaya
                brand-nya tidak berubah bentuk hanya karena layarnya kecil;
              - ikon roda yang menuju /admin/pengaturan. Di layar kecil
                sidebar tidak ada, dan Pengaturan adalah satu-satunya halaman
                yang memuat "Keluar dari Akun" serta saklar notifikasi, jadi
                kedua fitur itu harus tetap bisa dijangkau;
              - avatar admin yang menuju /admin/pengaturan/profil.

            Tanpa JavaScript semua tombol di sini tetap tautan biasa, dan
            halaman tetap bisa dipakai. Yang hilang di layar kecil hanyalah
            menu lewat, dan itu memang dipindah ke navigasi bawah.

            Seluruh elemen ini disembunyikan di >= 768px oleh .ad-hp, jadi
            tampilan desktop tidak bertambah apa pun.
        --}}
        <header class="ad-hp">
            <a href="{{ route('admin.dashboard') }}" class="ad-hp__merek">
                <span class="ad-hp__logo">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo KelasKita"
                        class="w-full h-full object-contain"
                    />
                </span>

                <span class="ad-hp__merek-teks">
                    <span class="ad-hp__nama">KelasKita</span>
                    <span class="ad-hp__sapaan">Admin Panel</span>
                </span>
            </a>

            <span class="ad-hp__aksi">
                <a href="{{ route('admin.pengaturan') }}" class="ad-hp__tombol" aria-label="Pengaturan">
                    <x-admin.ikon nama="roda" ukuran="w-5 h-5" />
                </a>

                <a href="{{ route('admin.pengaturan.profil') }}" class="ad-hp__tombol ad-hp__tombol--avatar"
                    aria-label="Profil {{ $admin?->nama ?? 'admin' }}">
                    <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="kecil" />
                </a>
            </span>
        </header>

        {{-- ---------- TOPBAR ---------- --}}
        {{--
            Topbar area admin sekarang hanya tombol buka sidebar. Kotak
            pencarian, lonceng notifikasi, dan menu akun tidak lagi dirender di
            halaman mana pun di area ini; alasannya ada di blok @php di atas
            file ini.

            Header memakai kelas .ad-atas--ringkas karena itu yang
            menyembunyikannya di layar lebar: isinya cuma tombol yang sudah
            disembunyikan di sana, jadi render tanpa kelas itu akan memakai
            strip kosong setinggi 4rem di atas judul setiap halaman. Di bawah
            1024px tombolnya justru satu-satunya cara membuka sidebar, jadi
            topbarnya tetap ada.

            Di <= 767px topbar ini ikut disembunyikan (lihat blok .ad-atas di
            resources/css/admin.css): di layar itu sidebar dan tombol buka
            drawer tidak lagi dipakai, dan membiarkannya tampil hanya menambah
            strip kosong di atas header ponsel. Di 768px-1023px drawer masih
            dipakai, jadi topbarnya tetap tampil persis seperti sebelumnya.

            x-admin.topbar-kanan sengaja tidak dipanggil. Komponennya masih
            ada di resources/views/components/admin/topbar-kanan.blade.php
            supaya tidak ikut hilang kalau topbar ini dipakai lagi; selama
            tidak dipanggil, ia tidak merender apa pun dan query notifikasi
            pun tidak jalan.
        --}}
        <header class="ad-atas ad-atas--ringkas">
            <div class="ad-atas__baris">
                <button type="button" class="ad-atas__buka" data-sisi-buka aria-controls="sisi-admin"
                    aria-expanded="false" aria-label="Buka menu">
                    <x-admin.ikon nama="menu" ukuran="w-5 h-5" />
                </button>
            </div>
        </header>

        {{-- ---------- KONTEN ---------- --}}
        <main class="ad-isi">
            @yield('content')
        </main>
    </div>

    {{-- ---------- NAVIGASI BAWAH ---------- --}}
    {{--
        Navigasi bawah untuk layar kecil (<= 767px). Di lebar itu sidebar
        disembunyikan, jadi enam tautan inilah yang menggantikannya. Di jalur
        ini tidak ada tombol menu lipat (hamburger) sama sekali.

        Bentuknya <nav> biasa berisi tautan, bukan tombol ber-JavaScript:
        tanpa JS pun admin tetap bisa berpindah halaman, dan tidak ada satu
        pun baris JavaScript baru yang perlu ditulis untuk navigasi ini.

        Isinya disaring dari $menuUtama di blok @php di atas, jadi ikon,
        tujuan tautan, angka yang menunggu di Verifikasi, dan penanda halaman
        aktif selalu sama dengan sidebar desktop. Halaman yang tidak punya
        salah satu dari enam menu ini -- misalnya /admin/pengaturan dan form
        edit -- tidak menyalakan apa pun, bukan menyalakan menu yang kebetulan
        mirip.

        Padding bawah memakai env(safe-area-inset-bottom) supaya di iPhone
        dengan bar home gestural, enam menu ini tidak tertimpa bar itu.
    --}}
    <nav class="ad-bawah" aria-label="Navigasi utama admin">
        <ul class="ad-bawah__daftar">
            @foreach ($menuNavigasiBawah as $menu)
                <li>
                    <a href="{{ $menu['href'] }}" @if ($menu['aktif']) aria-current="page" @endif
                        @class([
                            'ad-bawah__tautan',
                            'ad-bawah__tautan--aktif' => $menu['aktif'],
                        ])>
                        {{-- Pil lavender di belakang ikon hanya muncul pada
                             menu yang sedang terbuka, jadi "halaman ini"
                             terbaca sekilas tanpa harus membaca labelnya. --}}
                        <span class="ad-bawah__ikon" aria-hidden="true">
                            <x-admin.ikon :nama="$menu['ikon']" ukuran="w-5 h-5" />

                            @if (($menu['jumlah'] ?? 0) > 0)
                                <span class="ad-bawah__jumlah">{{ $menu['jumlah'] }}</span>
                            @endif
                        </span>

                        {{-- "Verifikasi" label terpanjang (10 huruf), dan enam kolom
                             di 320px hanya 53px masing-masing — makanya
                             labelnya 9px, bukan 10px seperti tadi. --}}
                        <span class="ad-bawah__label">{{ $menu['bawahLabel'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>

</body>
</html>
