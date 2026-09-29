<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Pintu API bookmark materi (lihat initBookmark() di resources/js/app.js).
         "__slug__" diganti JS dengan slug materi yang sedang diklik. --}}
    <meta name="simpanan-daftar" content="{{ route('user.simpanan.materi') }}">
    <meta name="simpanan-toggle" content="{{ route('user.materi.simpan', ['materi' => '__slug__']) }}">

    {{-- Pintu API bookmark quiz, kembaran dari materi di atas. Kuncinya
         id quiz, jadi "__id__" diganti JS dengan id yang sedang diklik. --}}
    <meta name="simpanan-quiz-daftar" content="{{ route('user.simpanan.quiz') }}">
    <meta name="simpanan-quiz-toggle" content="{{ route('user.quiz.simpan', ['quiz' => '__id__']) }}">
    <title>@yield('title', 'Dashboard | KelasKita')</title>

    {{--
        Mode gelap dipasang SEBELUM stylesheet dimuat, bukan sesudahnya.

        Kalau atribut data-theme baru ditambahkan setelah CSS selesai
        diunduh, layar pertama sudah terlanjur dicat terang lalu berubah
        gelap beberapa saat kemudian (kilatan putih). Satu blok script
        kecil yang hanya menulis atribut ke <html> menutup celah itu,
        dan pencatatannya tetap di localStorage supaya pilihan pengguna
        bertahan ketika aplikasi dibuka lagi.

        Nilai "terang" dipakai kalau belum ada apa pun tersimpan, jadi
        kunjungan pertama tetap mengikuti tampilan bawaan. Tanpa
        JavaScript blok ini tidak jalan dan <html> tidak pernah memakai
        data-theme, artinya aplikasi tetap tampil terang.
    --}}
    <script>
        (function () {
            var tema;

            try {
                tema = window.localStorage.getItem('kk-tema');
            } catch (e) {
                tema = null;
            }

            document.documentElement.dataset.theme = tema === 'gelap' ? 'gelap' : 'terang';
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Tanpa JavaScript halaman detail materi menampilkan seluruh bab
        sekaligus (atribut hidden dilepas di sini) supaya isi materi tetap
        terbaca penuh. Dengan JS, bab non-aktif memang sengaja disembunyikan.

        Aturan kedua untuk form edit materi: catatan pendukung baru muncul
        setelah tombol "Ajukan Persetujuan" ditekan. Tanpa JS tombol itu jadi
        submit biasa, jadi isian harus sudah terlihat sejak awal.
    --}}
    <noscript>
        <style>
            .materi-seksi[hidden],
            [data-catatan-wadah][hidden] {
                display: block !important;
            }

            /* Baris tombol "Tambah Materi" sengaja disembunyikan sampai
               halaman di-scroll ke bawah. Tanpa JavaScript tidak ada yang
               bisa memunculkannya, jadi di sini dikembalikan. */
            [data-action-bar] {
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
            }
        </style>
    </noscript>
</head>

<body class="bg-brand-bg font-sans text-dark antialiased">

    <div class="lg:flex min-h-screen">

        {{-- Sidebar (desktop) --}}
<aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-brand-bg text-dark border-r border-lavender">
            <div class="flex items-center gap-3 px-6 h-16 border-b border-lavender">
                <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo KelasKita"
                        class="w-9 h-9 object-contain"
                    />

                    <span class="text-lg font-semibold text-dark">KelasKita</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1">
                @php
                    /*
                     * Menu utama user. Satu array dipakai dua kali: sidebar
                     * desktop (dengan ikon) dan navigasi mobile (label saja).
                     *
                     * "pola" adalah daftar route yang harus menyalakan menu
                     * tersebut, bukan hanya route tujuannya. Tanpa itu,
                     * halaman-halaman turunan menu akan tampil tanpa ada menu
                     * yang aktif sama sekali: membuka detail quiz, lobby,
                     * mengerjakan soal, dan membaca hasil semuanya masih
                     * bagian dari menu Quiz, jadi semuanya masuk ke sini.
                     *
                     * "Karya Saya" adalah pusat pengelolaan materi dan quiz
                     * milik pengguna sendiri: satu-satunya tempat membuat,
                     * mengubah, dan menghapus karya. Menu Materi dan Quiz
                     * tetap menampilkan seluruh isi aplikasi.
                     *
                     * "Hasil" menggabungkan seluruh pengerjaan quiz milik
                     * pengguna yang sedang login, apa pun sesinya. Ikonnya
                     * papan klip berisi baris catatan, supaya tidak sama
                     * dengan lingkaran centang milik menu Quiz.
                     *
                     * "Jadwal" adalah tujuan tombol "Lihat Semua" di panel
                     * "Jadwal Hari Ini" milik dashboard, jadi ikut ada di
                     * sidebar supaya halaman itu bisa dibuka langsung dari
                     * menu tanpa harus lewat dashboard dulu.
                     *
                     * "Simpan" adalah tujuan tombol bookmark di pojok
                     * kanan atas kartu materi dan kartu quiz: yang disimpan
                     * lewat tombol itu muncul sebagai kartu di halaman ini.
                     */
                    $menu = [
                        ['route' => 'user.dashboard', 'pola' => ['user.dashboard'], 'label' => 'Dashboard', 'ikon' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                        ['route' => 'user.jadwal',    'pola' => ['user.jadwal'], 'label' => 'Jadwal',    'ikon' => \App\Support\Ikon::path('jam')],
                        ['route' => 'user.materi',    'pola' => ['user.materi'], 'label' => 'Materi',    'ikon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
                        ['route' => 'user.quiz',      'pola' => ['user.quiz', 'user.sesi.*', 'user.judulsoal.*', 'user.uiux.hasil'], 'label' => 'Quiz',      'ikon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        ['route' => 'user.karya-saya','pola' => ['user.karya-saya'], 'label' => 'Karya Saya','ikon' => \App\Support\Ikon::path('pena')],
                        ['route' => 'user.hasil',     'pola' => ['user.hasil'], 'label' => 'Hasil',     'ikon' => \App\Support\Ikon::path('catatan')],
                        ['route' => 'user.simpanan',  'pola' => ['user.simpanan*'], 'label' => 'Simpan', 'ikon' => \App\Support\Ikon::path('markah')],
                        ['route' => 'user.profil',    'pola' => ['user.profil'], 'label' => 'Profil',    'ikon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                    ];

                    $menuAktif = fn (array $item) => collect($item['pola'])
                        ->contains(fn (string $pola) => request()->routeIs($pola));
                @endphp

                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $menuAktif($item) ? 'bg-primary text-white' : 'text-primary/80 hover:bg-lavender hover:text-primary-dark' }}">

                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                        </svg>

                        {{ $item['label'] }}
                    </a>
                @endforeach

                {{--
                    Menu admin hanya dirender kalau peran di database = admin.
                    Ini PUNYA TAMPILAN saja. Proteksi sesungguhnya ada di
                    middleware "admin" (App\Http\Middleware\EnsureAdmin).
                --}}
                @if (auth()->user()?->isAdmin())
                    <div class="pt-5 mt-5 border-t border-white/10">
                        <p class="px-4 pb-2 font-mono text-[10px] uppercase tracking-[0.14em] text-white/35">
                            Admin
                        </p>

                        <a href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-primary text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            Dashboard Admin
                        </a>
                    </div>
                @endif
            </nav>

            <div class="p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-lavender bg-white px-4 py-2.5 text-sm font-semibold text-dark transition hover:border-primary hover:bg-primary hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>

                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col lg:ml-64">

            @php
                /*
                 * Halaman yang top bar-nya disembunyikan.
                 *
                 * "Masukkan Kode": di halaman itu yang ada hanya satu tugas,
                 * yaitu mengetik kode. Pencarian global, notifikasi, dan chip
                 * akun tidak ada gunanya dan hanya mengalihkan perhatian ke
                 * form yang harus diisi.
                 *
                 * "Jadwal": di halaman itu kolom cari global justru
                 * kembar dari pencarian yang sudah ada di panel daftar, dan
                 * notifikasi serta chip akun tidak menyumbang apa pun pada
                 * halaman jadwal. Karena isinya cuma itu, tidak ada sisa
                 * yang layak dipertahankan, jadi header-nya dihilang
                 * seluruhnya, bukan dikosongkan.
                 *
                 * "Tambah/Edit Materi" dan "Tambah/Edit Quiz": di halaman itu
                 * pengguna sudah tahu persis sedang mengerjakan apa, jadi
                 * pencarian global tidak relevan, notifikasi tidak pernah
                 * dibaca, dan chip akun cuma menduplikasi menu Profil di
                 * sidebar. "Detail Materi" dan "Detail Quiz": pengguna sedang
                 * fokus membaca satu materi atau satu quiz, jadi pencarian
                 * global dan notifikasi hanya mengalihkan perhatian. "Profil":
                 * chip akun di top bar tidak berguna karena isinya justru
                 * halaman itu sendiri.
                 * Baris utuhnya dihapus, bukan dikosongkan.
                 *
                 * "Mengerjakan soal": sama seperti dua halaman di atas, peserta
                 * sedang fokus ke satu soal, dan di sana ada timer yang
                 * berjalan, jadi pencarian global, notifikasi, dan chip akun
                 * semuanya hanya mengalihkan perhatian dari soal yang sedang
                 * dikerjakan.
                 *
                 * "Karya Saya": di halaman itu pencarian global kembar dari
                 * kolom cari yang sudah ada di dalam halaman (komponen
                 * karya.cari), notifikasi tidak pernah dibaca, dan chip akun
                 * cuma menduplikasi menu Profil di sidebar. Ketiganya
                 * dihapus supaya ruang atas halaman langsung dipakai papan
                 * kepala Karya Saya.
                 *
                 * Efeknya: header mobile di bawah tidak lagi punya top bar
                 * untuk dilompati, maka posisinya ikut naik dari top-16 ke
                 * top-0. Sidebar dan navigasi mobile tetap ada.
                 *
                 * Halaman lain tidak tersentuh; daftar white-list ini
                 * satu-satunya tempat yang memutuskan top bar tampil
                 * atau tidak.
                 */
                $sembunyiTopbar = request()->routeIs(
                    'user.sesi.gabung',
                    'user.jadwal',
                    'user.materi.tambah',
                    'user.materi.edit',
                    'user.materi.detail',
                    'user.quiz.tambah',
                    'user.quiz.edit',
                    'user.quiz.detail',
                    'user.judulsoal.*',
                    'user.profil',
                    'user.karya-saya',
                );
            @endphp

            @php
                /*
                 * Halaman mana yang dituju kolom cari di top bar.
                 * Default-nya Materi, jadi setiap halaman baru tetap punya
                 * perilaku yang masuk akal tanpa harus mengatur apa pun.
                 *
                 * Di "Hasil" kolom ini ikut mencari nama quiz pada riwayat,
                 * karena di halaman itu satu-satunya yang dicari adalah quiz.
                 *
                 * Halaman "Jadwal" dan "Karya Saya" sengaja tidak punya
                 * cabang di sini: top bar keduanya disembunyikan (lihat
                 * $sembunyiTopbar di atas) dan pencarian mereka sudah ada
                 * di dalam halaman masing-masing.
                 */
                $cariTopbar = match (true) {
                    request()->routeIs('user.hasil.daftar') => [
                        'aksi' => 'user.hasil.daftar',
                        'placeholder' => 'Cari hasil...',
                        'param' => ['quiz' => request()->route('quiz')],
                    ],
                    request()->routeIs('user.hasil*') => [
                        'aksi' => 'user.hasil',
                        'placeholder' => 'Cari hasil...',
                        'param' => array_filter(
                            ['status' => request('status'), 'urut' => request('urut')],
                            fn ($nilai) => filled($nilai)
                        ),
                    ],
                    request()->routeIs('user.quiz', 'user.quiz.*', 'user.sesi.*', 'user.judulsoal.*', 'user.uiux.hasil') => [
                        'aksi' => 'user.quiz',
                        'placeholder' => 'Cari kuis...',
                        // Kategori yang sedang disaring ikut dibawa, supaya
                        // mengetik di top bar tidak mematikan filternya.
                        'param' => array_filter(
                            ['kategori' => request('kategori')],
                            fn ($nilai) => filled($nilai)
                        ),
                    ],
                    request()->routeIs('user.simpanan*') => [
                        'aksi' => 'user.simpanan',
                        'placeholder' => 'Cari simpan...',
                        // Tab yang sedang aktif ikut dibawa, supaya mengetik
                        // di sini tetap mencari pada tab yang sama.
                        'param' => ['tab' => request('tab') === 'quiz' ? 'quiz' : 'materi'],
                    ],
                    default => [
                        'aksi' => 'user.materi',
                        'placeholder' => 'Cari materi...',
                        'param' => [],
                    ],
                };
            @endphp

            {{-- Top bar: pencarian, notifikasi, dan identitas pengguna.
     Muncul di semua ukuran layar, menempel di atas halaman.

     Kolom cari mengikuti halaman yang sedang dibuka supaya placeholder
     dan tujuannya tidak membingungkan: mengetik di halaman Quiz mencari
     quiz, di halaman lain mencari materi.

     Dilewati di halaman yang tidak butuh (lihat $sembunyiTopbar di atas),
     supaya tidak ada baris kosong setinggi 4rem di atas halaman itu. --}}
            @unless ($sembunyiTopbar)
                <x-app.topbar :aksi="$cariTopbar['aksi']" :placeholder="$cariTopbar['placeholder']"
                    :param="$cariTopbar['param']" />
            @endunless

            {{-- Header (mobile). Kalau top bar ada, top-16: menempel tepat
     di bawahnya, jadi keduanya tidak saling menutupi. Kalau top bar-nya
     tidak ada, header ini jadi elemen paling atas dan harus menempel di
     top-0; kalau tidak, ada 4rem ruang kosong di atasnya yang tidak pernah
     terisi. Latarnya juga .latar-atas supaya tidak ada garis sambung
     antara top bar dan header ini. --}}
<header class="lg:hidden latar-atas sticky {{ $sembunyiTopbar ? 'top-0' : 'top-16' }} z-20 text-dark">
                <div class="flex items-center justify-between h-16 px-4">
                    <a href="{{ route('user.dashboard') }}" class="flex items-center gap-2.5">
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="Logo KelasKita"
                            class="w-8 h-8 object-contain"
                        />

                        <span class="text-base font-semibold text-primary">KelasKita</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="text-sm text-primary hover:text-primary-dark">
                            Keluar
                        </button>
                    </form>
                </div>

                <nav class="flex gap-1 px-3 pb-3 overflow-x-auto">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $menuAktif($item) ? 'bg-primary text-white' : 'text-primary/80' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    @if (auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.*') ? 'bg-primary text-white' : 'text-primary/80' }}">
                            Admin
                        </a>
                    @endif
                </nav>
            </header>

            <main class="p-6 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>

</body>

</html>
