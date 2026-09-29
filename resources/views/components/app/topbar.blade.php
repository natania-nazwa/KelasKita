@props([
    'pengguna' => null,
    /*
     * Halaman tujuan kolom pencarian global. Default-nya halaman Materi
     * supaya perilaku lama tidak berubah; halaman Quiz mengeset nilainya
     * sendiri supaya mengetik di sini mencari quiz, bukan materi.
     */
    'aksi' => 'user.materi',
    'placeholder' => 'Cari materi...',
    /*
     * Parameter lain yang harus ikut saat pencarian dikirim, mis. tab aktif
     * di halaman Karya Saya. Bentuknya ['tab' => 'quiz'].
     */
    'param' => [],
])

@php
    /*
     * Top bar untuk halaman user: pencarian global, notifikasi, dan
     * identitas pengguna. Muncul di desktop (di samping sidebar) dan
     * tetap menyatu dengan header mobile yang sudah ada di layout.
     */
    $pengguna ??= auth()->user();
    $nama = $pengguna?->nama ?? 'Tamu';
@endphp

{{-- Latar memakai kelas .latar-atas yang sama persis dengan kanvas
     materi, jadi tidak ada garis sambung antara top bar dan konten di
     bawahnya. --}}
<header data-app-topbar
    class="latar-atas sticky top-0 z-30">

    <div class="flex h-16 items-center gap-3 px-4 sm:gap-4 sm:px-6 lg:px-10">

        {{-- Pencarian: submit ke halaman yang sedang dibuka, jadi typing
             lalu Enter langsung membawa user ke hasil pencarian.

             min-w-0 + flex-1 tanpa batas lebar membuat kolom ini mengisi
             seluruh ruang kosong di kiri, jadi kolomnya berhenti tepat di
             sebelah ikon notifikasi. Di layar sempit min-w-0 yang membuatnya
             ikut menyusut, bukan memaksa halaman melebar. --}}
        <form action="{{ route($aksi, $param) }}" method="GET" class="min-w-0 flex-1">
            <label for="cari-topbar" class="sr-only">{{ $placeholder }}</label>

            {{-- Parameter halaman (mis. tab aktif) ikut dibawa supaya hasil
                 pencarian tidak memantul ke tab default. --}}
            @foreach ($param as $kunci => $nilai)
                <input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">
            @endforeach

            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-dark/35"
                    aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>

                <input id="cari-topbar" type="search" name="q" placeholder="{{ $placeholder }}" autocomplete="off"
                    value="{{ request('q') }}"
                    class="kolom-cari w-full py-2 pl-11 pr-4 text-sm"
                    aria-label="{{ $placeholder }}">
            </div>
        </form>

        <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-3">

            {{-- Notifikasi --}}
            <button type="button"
                class="relative flex h-10 w-10 items-center justify-center rounded-full border border-lavender bg-brand-bg text-dark/60 transition hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                aria-label="Notifikasi">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>

                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-primary ring-2 ring-white" aria-hidden="true"></span>
            </button>

            <span class="hidden h-8 w-px bg-lavender sm:block" aria-hidden="true"></span>

            {{-- Identitas pengguna.

                 <details> dipakai supaya panah turunnya benar-benar
                 bekerja tanpa JavaScript: browser menutup <details> yang
                 sedang terbuka begitu diklik lagi atau diklik di luar,
                 jadi tidak perlu event listener tambahan. --}}
            <details class="group relative">
                <summary
                    class="flex cursor-pointer list-none items-center gap-2.5 rounded-full border border-lavender bg-white py-1 pl-1 pr-3 transition hover:border-primary sm:pr-4 [&::-webkit-details-marker]:hidden">

                    {{--
                        Avatar di top bar memakai komponen yang sama dengan
                        halaman Profil, jadi nama dan foto yang baru
                        disimpan langsung ikut terpakai di sini tanpa ada
                        dua aturan avatar yang harus dijaga sinkron.

                        Kalau foto profil dihapus, kolomnya jadi kosong dan
                        komponen ini otomatis kembali ke inisial nama
                        depan.
                    --}}
                    <x-profil.avatar :pengguna="$pengguna" ukuran="kecil" />

                    <span class="hidden min-w-0 flex-col leading-tight sm:flex">
                        <span class="truncate text-sm font-semibold text-dark">{{ $nama }}</span>
                    </span>
                </summary>

                {{-- absolute + right-0: menu turun dari chip, bukan dari
                     ujung halaman, jadi posisinya tidak bergeser di mobile. --}}
                <div
                    class="absolute right-0 top-full z-40 mt-2 w-52 overflow-hidden rounded-2xl border border-lavender bg-white p-1.5 shadow-[0_24px_44px_-28px_rgba(33,26,58,0.6)]">
                    <a href="{{ route('user.profil') }}"
                        class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-dark/75 transition hover:bg-lavender hover:text-primary-dark">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>

                        Profil Saya
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-dark/75 transition hover:bg-lavender hover:text-primary-dark">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>

                            Keluar
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
