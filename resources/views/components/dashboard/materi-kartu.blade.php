@props([
    'materi',
])

@php
    /*
     * Kartu materi KHUSUS dashboard.
     *
     * Sengaja tidak memakai x-materi.kartu: kartu di halaman Materi
     * dirancang untuk grid tiga kolom (sekitar 330px per kartu), sedangkan
     * di dashboard empat kartu dijajar satu baris sehingga hanya sekitar
     * 200-300px. Karena itu meta di bawah judul disusun dua baris, supaya
     * nama pembuat dan chip waktu/bab tidak saling berebut ruang.
     *
     * Bentuk array yang diharapkan (sama persis dengan hasil
     * App\Support\DaftarMateri::petakan, jadi sumber datanya bisa diganti API):
     *   slug, judul, deskripsi, thumbnail, tingkat_kesulitan, waktu_baca,
     *   jumlah_bab, jumlah_materi, tautan,
     *   kategori => [nama, slug, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     */

    $kategori = $materi['kategori'];
    $pembuat = $materi['pembuat'];

    /*
     * Warna lencana tingkat kesulitan. Paletnya sama persis dengan badge
     * kesulitan di x-materi.kartu, jadi "Sulit" selalu merah di kedua tempat.
     */
    $warnaKesulitan = [
        'mudah' => 'bg-[#dcfce7] text-[#15803d]',
        'sedang' => 'bg-[#fef3c7] text-[#b45309]',
        'sulit' => 'bg-[#fee2e2] text-[#b91c1c]',
    ];

    $kesulitan = strtolower((string) ($materi['tingkat_kesulitan'] ?? ''));
@endphp

<a href="{{ $materi['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="dash-kartu dash-kartu--pindah dash-materi min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Buka materi {{ $materi['judul'] }}">

    {{-- A. Banner 16:9. Kalau materi punya gambar, gambarnya yang dipakai;
         kalau belum ada, banner memakai gradasi warna kategori + ikon mapel. --}}
    <div class="dash-materi__gambar">
        @if (filled($materi['thumbnail'] ?? null))
            <img src="{{ $materi['thumbnail'] }}" alt="" loading="lazy" class="dash-materi__foto">
        @else
            <span class="dash-materi__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- B. Lencana tingkat kesulitan, melayang di pojok kiri atas. --}}
        @if (filled($materi['tingkat_kesulitan'] ?? null))
            <span class="dash-materi__kesulitan capitalize {{ $warnaKesulitan[$kesulitan] ?? 'bg-white/90 text-dark/60' }}">
                {{ $materi['tingkat_kesulitan'] }}
            </span>
        @endif

        {{-- C. Bookmark: tombol, bukan tautan, supaya diklik tidak membuka
             halaman detail. initBookmark() di app.js yang mengurusnya. --}}
        <button type="button" data-bookmark="{{ $materi['slug'] }}" aria-pressed="false"
            class="dash-materi__simpan"
            aria-label="Simpan materi {{ $materi['judul'] }} untuk dibaca nanti">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        </button>

        {{-- D. Badge kategori, melayang di pojok kiri bawah banner. --}}
        <span class="dash-materi__lencana">{{ $kategori['nama'] }}</span>

        {{-- E. Penanda aksi, muncul saat kursor di atas kartu. --}}
        <span class="dash-materi__aksi" aria-hidden="true">
            Baca
            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </div>

    {{-- F-I. Judul, deskripsi, dan informasi pembuat. --}}
    <div class="dash-materi__badan">
        {{-- Garis aksen warna kategori, pemisah dari banner. -mx-3.5 -mt-3.5
             menetralkan padding badan supaya garisnya menyambung ke banner. --}}
        <span class="dash-materi__aksen -mx-3.5 -mt-3.5 mb-2.5 block" aria-hidden="true"></span>

        <h3 class="dash-materi__judul">{{ $materi['judul'] }}</h3>

        <p class="dash-materi__deskripsi">{{ $materi['deskripsi'] }}</p>

        {{-- margin-top:auto mendorong meta ke dasar kartu, jadi semua kartu
             dalam satu baris tetap sama tinggi. --}}
        <div class="dash-materi__meta">
            <span class="dash-materi__pembuat">
                <span class="dash-avatar"
                    style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                    aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                <span class="dash-materi__nama">{{ $pembuat['nama'] }}</span>
            </span>

            {{-- Chip waktu baca dan jumlah bab materi ini. --}}
            <span class="dash-materi__chip">
                <span class="dash-materi__jumlah">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                    </svg>

                    {{ (int) $materi['waktu_baca'] }} mnt
                </span>

                <span class="dash-materi__jumlah">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('daftar') }}" />
                    </svg>

                    {{ (int) $materi['jumlah_bab'] }} bab
                </span>
            </span>
        </div>
    </div>
</a>
