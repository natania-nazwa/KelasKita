@props([
    'daftar' => [],
])

{{--
    Panel "Akses Cepat" di sidebar: dua tombol full width ke fitur yang paling
    sering dipakai.

    Bentuk array yang diharapkan:
    Judul, deskripsi, ikon, warna, warna_gelap, tautan
--}}

<section class="dash-kartu dash-panel min-w-0" aria-label="Akses cepat"
    style="--k: #6c4de6; --k-gelap: #5a3fd4;">

    {{-- Isinya sudah berupa daftar tombol, jadi tidak ada aksi "Lihat Semua". --}}
    <x-dashboard.kepala judul="Akses Cepat" ikon="petir" label="" />

    <div data-reveal-stagger class="grid gap-2">
        @foreach ($daftar as $item)
            <a href="{{ $item['tautan'] }}"
                style="--k: {{ $item['warna'] }}; --k-gelap: {{ $item['warna_gelap'] }};"
                class="dash-akses focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                aria-label="{{ $item['judul'] }}: {{ $item['deskripsi'] }}">

                <span class="dash-ikon-kotak">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                    </svg>
                </span>

                <span class="min-w-0 flex-1">
                    <span class="dash-akses-judul block">{{ $item['judul'] }}</span>
                    <span class="dash-akses-desk block">{{ $item['deskripsi'] }}</span>
                </span>

                <svg class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        @endforeach
    </div>
</section>
