@props([
    'materi',
    'kataKunci' => '',
])

@php
    /*
     * Kartu materi. Sumber datanya array polos dari App\Support\DaftarMateri,
     * jadi komponen ini tidak terikat Eloquent dan bisa dipakai ulang di
     * halaman lain (mis. daftar rekomendasi) maupun diisi dari API.
     *
     * Bentuk array yang diharapkan:
     *   id, slug, judul, deskripsi, thumbnail, tingkat_kesulitan,
     *   waktu_baca, tautan, jumlah_materi,
     *   kategori  => [nama, slug, ikon, warna, warna_gelap],
     *   pembuat   => [nama, inisial, warna, warna_gelap]
     */

    $kategori = $materi['kategori'];
    $pembuat = $materi['pembuat'];
    $jumlahMateri = (int) $materi['jumlah_materi'];

    /*
     * Menyalin teks aman (sudah di-escape) lalu menebalkan kata yang sedang
     * dicari, supaya user langsung tahu bagian mana yang cocok.
     */
    $tebalkan = function (?string $teks) use ($kataKunci) {
        $aman = e((string) $teks);

        if (mb_strlen(trim((string) $kataKunci)) < 2) {
            return $aman;
        }

        return str_ireplace(
            e(trim($kataKunci)),
            '<mark class="rounded bg-lavender px-0.5 font-bold text-primary-dark">'.e(trim($kataKunci)).'</mark>',
            $aman
        );
    };
@endphp

<a href="{{ $materi['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-materi group min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Buka materi {{ $materi['judul'] }}">

    {{-- A. Thumbnail: tinggi seragam, membulat di bagian atas. --}}
    <div class="kartu-materi__gambar">
        @if (filled($materi['thumbnail'] ?? null))
            <img src="{{ $materi['thumbnail'] }}" alt="" loading="lazy" class="kartu-materi__foto">
        @else
            {{-- Tanpa kolom gambar di database, banner memakai gradasi
                 warna kategori + ikon mapel sebagai gantinya. --}}
            <span class="kartu-materi__gambar-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Bookmark: tombol, bukan tautan, supaya tidak membuka materi. --}}
        <button type="button" data-bookmark="{{ $materi['slug'] }}" aria-pressed="false"
            class="kartu-materi__simpan"
            aria-label="Simpan materi {{ $materi['judul'] }} untuk dibaca nanti">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        </button>

        {{-- B. Badge kategori, melayang di atas thumbnail. --}}
        <span class="kartu-materi__lencana">{{ $kategori['nama'] }}</span>
    </div>

    {{-- C-E. Judul, deskripsi, dan informasi pembuat. --}}
    <div class="kartu-materi__badan">
        <h2 class="kartu-materi__judul">{!! $tebalkan($materi['judul']) !!}</h2>

        <p class="kartu-materi__deskripsi">{!! $tebalkan($materi['deskripsi']) !!}</p>

        {{-- mt-auto: baris info terdorong ke bawah, jadi semua kartu
             dalam satu baris tetap sejajar. --}}
        <div class="kartu-materi__meta">
            <span class="kartu-materi__pembuat">
                <span class="kartu-materi__avatar"
                    style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                    aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                <span class="kartu-materi__pembuat-nama">{{ $pembuat['nama'] }}</span>
            </span>

            {{-- F. Jumlah materi pada kategori ini. --}}
            <span class="kartu-materi__jumlah">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>

                {{ $jumlahMateri }} Materi
            </span>
        </div>
    </div>
</a>
