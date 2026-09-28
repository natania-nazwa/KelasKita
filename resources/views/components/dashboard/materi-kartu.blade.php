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
     * nama pembuat dan jumlah materi tidak saling berebut ruang.
     *
     * Bentuk array yang diharapkan (sama persis dengan
     * App\Support\DaftarMateri, jadi sumber datanya bisa diganti API):
     *   slug, judul, deskripsi, thumbnail, jumlah_materi, tautan,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     */

    $kategori = $materi['kategori'];
    $pembuat = $materi['pembuat'];
@endphp

<a href="{{ $materi['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="dash-kartu dash-kartu--pindah dash-materi min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Buka materi {{ $materi['judul'] }}">

    {{-- A. Thumbnail 16:9. Kalau materi punya gambar, gambarnya yang
         dipakai; kalau belum ada (kolom gambar belum dibuat) banner
         memakai gradasi warna kategori + ikon mapel. --}}
    <div class="dash-materi__gambar">
        @if (filled($materi['thumbnail'] ?? null))
            <img src="{{ $materi['thumbnail'] }}" alt="" loading="lazy" class="dash-materi__foto">
        @else
            <span class="dash-materi__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Bookmark: tombol, bukan tautan, supaya diklik tidak membuka
             halaman detail. initBookmark() di app.js yang mengurusnya. --}}
        <button type="button" data-bookmark="{{ $materi['slug'] }}" aria-pressed="false"
            class="dash-materi__simpan"
            aria-label="Simpan materi {{ $materi['judul'] }} untuk dibaca nanti">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        </button>

        {{-- B. Badge kategori, melayang di pojok kiri bawah thumbnail. --}}
        <span class="dash-materi__lencana">{{ $kategori['nama'] }}</span>
    </div>

    {{-- C-E. Judul, deskripsi, dan informasi pembuat. --}}
    <div class="dash-materi__badan">
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

            {{-- F. Jumlah materi pada kategori ini. --}}
            <span class="dash-materi__jumlah">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>

                {{ (int) $materi['jumlah_materi'] }} Materi
            </span>
        </div>
    </div>
</a>
