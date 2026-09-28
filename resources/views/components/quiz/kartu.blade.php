@props([
    'quiz',
    'kataKunci' => '',
])

@php
    /*
     * Kartu quiz. Sumber datanya array polos dari App\Support\DaftarQuiz,
     * jadi komponen ini tidak terikat Eloquent dan bisa diisi dari API
     * nanti tanpa menyentuh markup.
     *
     * Bentuk array yang diharapkan:
     *   id, slug, judul, deskripsi, thumbnail, durasi, jumlah_soal,
     *   status, status_label, visibilitas, saya, tautan,
     *   kategori  => [nama, slug, ikon, warna, warna_gelap],
     *   pembuat   => [nama, inisial, warna, warna_gelap]
     *
     * Seluruh kartu dibungkus <a>, kecuali tombol bookmark di dalam
     * thumbnail: bookmark memakai event.stopPropagation() di
     * resources/js/app.js supaya diklik tidak ikut membuka detail quiz.
     */

    $kategori = $quiz['kategori'];
    $pembuat = $quiz['pembuat'];
    $jumlahSoal = (int) $quiz['jumlah_soal'];

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

<a href="{{ $quiz['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-quiz group min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Buka quiz {{ $quiz['judul'] }}">

    {{-- A. Thumbnail: tinggi seragam (16:9), membulat di bagian atas. --}}
    <div class="kartu-quiz__gambar">
        @if (filled($quiz['thumbnail'] ?? null))
            <img src="{{ $quiz['thumbnail'] }}" alt="" loading="lazy" class="kartu-quiz__foto">
        @else
            {{-- Tanpa gambar di database, banner memakai gradasi warna
                 kategori + ikon mapel sebagai gantinya (lihat
                 .kartu-quiz__gambar di app.css). --}}
            <span class="kartu-quiz__gambar-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Bookmark: tombol, bukan tautan, supaya tidak membuka quiz. --}}
        <button type="button" data-bookmark="{{ $quiz['id'] }}" data-bookmark-ruang="quiz" aria-pressed="false"
            class="kartu-quiz__simpan"
            aria-label="Simpan quiz {{ $quiz['judul'] }} untuk dikerjakan nanti">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        </button>

        {{-- B. Badge kategori, melayang di pojok kiri bawah thumbnail. --}}
        <span class="kartu-quiz__lencana">{{ $kategori['nama'] }}</span>
    </div>

    {{-- C-E. Judul, deskripsi, dan informasi pembuat. --}}
    <div class="kartu-quiz__badan">
        <h2 class="kartu-quiz__judul">{!! $tebalkan($quiz['judul']) !!}</h2>

        <p class="kartu-quiz__deskripsi">{!! $tebalkan($quiz['deskripsi']) !!}</p>

        {{-- mt-auto: baris info terdorong ke bawah, jadi semua kartu
             dalam satu baris tetap sejajar. --}}
        <div class="kartu-quiz__meta">
            <span class="kartu-quiz__pembuat">
                <span class="kartu-quiz__avatar"
                    style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                    aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                <span class="kartu-quiz__pembuat-nama">{{ $pembuat['nama'] }}</span>
            </span>

            {{-- F. Jumlah soal pada quiz ini. --}}
            <span class="kartu-quiz__jumlah">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>

                {{ $jumlahSoal }} Soal
            </span>
        </div>
    </div>
</a>
