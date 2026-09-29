@props([
    'aksi',
])

@php
    /*
     * Satu kartu aksi cepat (Jelajahi Materi / Buat Quiz / Masukkan Kode).
     *
     * Bentuk array yang diharapkan:
     *   judul, deskripsi, ikon, warna, warna_gelap, tautan, sorot
     *
     * Kartu pertama ditandai "sorot" supaya tint warnanya sedikit lebih
     * pekat dan jadi titik masuk utama. Warnanya tetap lembut: hanya
     * latar kartu yang diberi warna, ikon dan teks tetap gelap.
     */
    $sorot = (bool) ($aksi['sorot'] ?? false);
@endphp

<a href="{{ $aksi['tautan'] }}"
    style="--k: {{ $aksi['warna'] }}; --k-gelap: {{ $aksi['warna_gelap'] }};"
    @class([
        'dash-kartu dash-kartu--pindah dash-aksi min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40',
        'dash-kartu--sorot' => $sorot,
    ])
    aria-label="{{ $aksi['judul'] }}: {{ $aksi['deskripsi'] }}">

    <span class="dash-ikon-kotak">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $aksi['ikon'] }}" />
        </svg>
    </span>

    <h3 class="dash-aksi-judul">{{ $aksi['judul'] }}</h3>

    <p class="dash-aksi-desk">{{ $aksi['deskripsi'] }}</p>

    <span class="dash-aksi-panah">
        {{ $sorot ? 'Mulai' : 'Lanjut' }}
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    </span>
</a>
