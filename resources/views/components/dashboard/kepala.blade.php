@props([
    'judul',
    'ikon' => null,
    'tautan' => null,
    'label' => 'Lihat Semua',
    'warna' => '#6c4de6',
    'warnaGelap' => '#5a3fd4',
])

@php
    /*
     * Kepala seksi dashboard: ikon kotak + judul di kiri, aksi "Lihat Semua"
     * di kanan. Dipakai bersama oleh section di kolom utama maupun panel di
     * sidebar supaya keduanya konsisten.
     *
     * Kalau "label" kosong, aksi kanan disembunyikan sepenuhnya (dipakai
     * panel yang isinya sudah berupa daftar, mis. Akses Cepat).
     * Kalau "label" ada tapi "tautan" kosong, aksi ditampilkan redup karena
     * panelnya belum punya halaman tujuan sendiri.
     */
@endphp

<div class="dash-kepala" style="--k: {{ $warna }}; --k-gelap: {{ $warnaGelap }};">
    <div class="flex min-w-0 items-center gap-2.5">
        @if (filled($ikon))
            <span class="dash-ikon-kotak">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($ikon) }}" />
                </svg>
            </span>
        @endif

        <h2 class="dash-kepala-judul">{{ $judul }}</h2>
    </div>

    @if (filled($label))
        @if (filled($tautan))
            <a href="{{ $tautan }}" class="dash-lihat">
                {{ $label }}
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
                <span class="sr-only"> {{ $judul }}</span>
            </a>
        @else
            <span class="dash-lihat dash-lihat--mati">
                {{ $label }}
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </span>
        @endif
    @endif
</div>
