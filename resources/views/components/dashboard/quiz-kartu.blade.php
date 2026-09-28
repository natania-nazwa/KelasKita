@props([
    'quiz',
])

@php
    /*
     * Satu kartu quiz di section "Quiz Terbaru".
     *
     * Bentuk array yang diharapkan:
     *   slug, judul, jumlah_soal, durasi, tautan, kategori[]
     *
     * Kategori hanya dipakai untuk warna (badge + tombol), jadi teks badge-nya
     * diambil dari nama kategori.
     */
    $kategori = $quiz['kategori'];
@endphp

<a href="{{ $quiz['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="dash-kartu dash-kartu--pindah dash-quiz min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Mulai quiz {{ $quiz['judul'] }}">

    <span class="dash-lencana">{{ $kategori['nama'] }}</span>

    <h3 class="dash-quiz-judul">{{ $quiz['judul'] }}</h3>

    <p class="dash-quiz-meta">
        <span>
            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ $quiz['jumlah_soal'] }} soal
        </span>

        <span aria-hidden="true">&bull;</span>

        <span>
            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
            </svg>
            {{ $quiz['durasi'] }} menit
        </span>
    </p>

    <span class="dash-tombol">
        Mulai Quiz
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    </span>
</a>
