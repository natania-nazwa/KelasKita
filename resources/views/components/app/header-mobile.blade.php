@props([
    'pengguna' => null,
    /*
     * Konfigurasi kolom pencarian global, identik dengan x-app.topbar:
     * tujuan halaman, placeholder, dan parameter tambahan yang harus ikut.
     */
    'aksi' => 'user.materi',
    'placeholder' => 'Cari materi...',
    'param' => [],
    /*
     * Halaman yang top bar-nya disembunyikan (lihat $sembunyiTopbar di
     * layouts/app) juga kehilangan baris pencarian dan lonceng di sini.
     * Satu daftar di layout yang memutuskan, bukan dua.
     *
     * Tapi satu halaman bisa kehilangan baris pencarian saja tanpa
     * kehilangan lonceng: lihat prop tampilLonceng di bawah.
     */
    'tampilCari' => true,
    /*
     * Lonceng sengaja dipisah dari $tampilCari. Sebelumnya lonceng
     * ikut hilang bersama baris pencarian karena keduanya selalu
     * sebanding, tapi halaman detail hasil Wassena hanya tidak punya
     * yang bisa dicari — notifikasi di sana tetap berguna.
     *
     * Nilai kosong berarti "ikut $tampilCari", jadi halaman yang
     * tidak mengatur prop ini persis seperti sebelumnya.
     */
    'tampilLonceng' => null,
    /*
     * Menu lengkap dari layout. Item bertanda "bawah" sudah tampil di
     * navigasi bawah, jadi yang tersisa hanya menu tambahan: Jadwal,
     * Hasil, Simpan, dan tautan Admin kalau peran pengguna berlaku.
     */
    'menu' => [],
    /*
     * Tautan "Dashboard Admin" hanya ditampilkan kalau peran pengguna
     * berlaku. Proteksi sesungguhnya tetap ada di middleware "admin";
     * ini murni soal tampilan, sama seperti sidebar.
     */
    'admin' => false,
])

@php
    /*
     * Header khusus layar kecil. Menggantikan sepasang baris lama
     * (top bar desktop yang ikut tampil + strip chip horizontal),
     * menjadi dua baris ringkas: identitas + tombol penting, lalu
     * kolom pencarian. Di seluruh layar ≥1024px seluruh elemen ini
     * disembunyikan dan top bar biasa yang bekerja.
     */
    $pengguna ??= auth()->user();

    $tampilLonceng ??= $tampilCari;

    $menuTambahan = collect($menu)
        ->reject(fn (array $item) => $item['bawah'] ?? false)
        ->values();
@endphp

{{-- Latar .latar-atas sama dengan kanvas halaman, jadi tidak ada garis
     sambung yang mencolok di mode terang maupun gelap. --}}
<header class="latar-atas sticky top-0 z-30 text-dark lg:hidden">

    {{-- ============== Baris 1: identitas + tombol penting ============== --}}
    <div class="flex h-14 items-center gap-2 px-4 sm:px-5">
        <a href="{{ route('user.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="" width="32" height="32"
                class="h-8 w-8 shrink-0 object-contain">

            <span class="truncate text-base font-extrabold tracking-tight text-primary">
                KelasKita
            </span>
        </a>

        <div class="ml-auto flex shrink-0 items-center gap-1.5">
            {{-- Lonceng hanya ada kalau halaman ini memang memakai baris
                 kedua; di halaman fokus (form, kerjakan soal, dsb.) ia
                 ikut hilang persis seperti perilaku top bar lama. --}}
            @if ($tampilLonceng)
                <x-app.notifikasi :daftar="$notifikasi" :sisa="$notifikasiBelumDibaca" />
            @endif

            <x-app.menu-tambahan :daftar="$menuTambahan" :admin="$admin" />

            {{--
                Chip akun tinggal foto profil bulat saja — tanpa nama,
                tanpa panah turun. <details> tetap dipakai supaya
                menu "Profil Saya" / "Keluar" muncul begitu diklik tanpa
                perlu JavaScript: browser menutup <details> yang sedang
                terbuka begitu diklik lagi atau di luarnya.

                Panah sengaja tidak digambar karena foto profilnya
                sendiri sudah jelas menandai ini menu akun; lingkaran
                2.5rem dari x-profil.avatar sudah melewati target sentuh
                40px tanpa bantuan padding sama sekali.
            --}}
            <details class="group relative">
                <summary
                    class="flex min-h-10 min-w-10 cursor-pointer list-none items-center justify-center rounded-full transition hover:opacity-80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary [&::-webkit-details-marker]:hidden"
                    aria-label="Menu profil" title="Menu profil">
                    <x-profil.avatar :pengguna="$pengguna" ukuran="kecil" />
                </summary>

                <div
                    class="absolute right-0 top-full z-40 mt-2 w-52 overflow-hidden rounded-2xl border border-lavender bg-white p-1.5 shadow-[0_24px_44px_-28px_rgba(33,26,58,0.6)]">
                    <a href="{{ route('user.profil') }}"
                        class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-dark/75 transition hover:bg-lavender hover:text-primary-dark">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>

                        Profil Saya
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-dark/75 transition hover:bg-lavender hover:text-primary-dark">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>

                            Keluar
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>

    {{-- ================== Baris 2: pencarian global ================== --}}
    @if ($tampilCari)
        <div class="px-4 pb-3 sm:px-5">
            <form action="{{ route($aksi, $param) }}" method="GET">
                <label for="cari-mobile" class="sr-only">{{ $placeholder }}</label>

                {{-- Parameter halaman (tab/kategori/status yang sedang
                     aktif) ikut dibawa supaya pencarian tidak mematikan
                     filter yang sudah dipilih. --}}
                @foreach ($param as $kunci => $nilai)
                    <input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">
                @endforeach

                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-dark/35"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>

                    <input id="cari-mobile" type="search" name="q" placeholder="{{ $placeholder }}"
                        autocomplete="off" value="{{ request('q') }}" aria-label="{{ $placeholder }}"
                        class="kolom-cari w-full py-2 pl-11 pr-4 text-sm">
                </div>
            </form>
        </div>
    @endif
</header>
