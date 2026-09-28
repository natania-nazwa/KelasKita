@props([
    'seksi' => [],
    'detail',
])

@php
    /*
     * Kolom konten materi.
     *
     * Seluruh seksi ikut dirender supaya isi halaman tetap terbaca oleh
     * mesin pencari, browser tanpa JavaScript, dan tes integrasi. Yang
     * dibatasi hanya tampilannya: materi-detail.js menandai satu seksi
     * sebagai bab aktif, sehingga halaman terasa seperti satu bab per layar
     * dan tiap bab punya tombol Sebelumnya/Berikutnya.
     *
     * Tanpa JS semuanya tampil penuh (aturan [hidden] dinetralkan lewat
     * <noscript> di layouts/app.blade.php) dan tautan Daftar Isi tetap
     * bekerja sebagai lompatan biasa.
     */

    $jumlahBab = count($seksi);
@endphp

<div class="min-w-0" data-bab-wadah>

    <div class="space-y-4 sm:space-y-5">
        @foreach ($seksi as $indeks => $s)
            @if ($s['latihan'])
                <x-materi.detail-latihan :seksi="$s" :judul-materi="$detail['judul']"
                    :tautan="$detail['tautan_latihan']" :bab-awal="$indeks === 0" />
            @else
                <x-materi.detail-seksi :seksi="$s" :bab-awal="$indeks === 0" />
            @endif
        @endforeach
    </div>

    @if ($jumlahBab > 1)
        {{--
            Navigasi bab. Tersembunyi secara bawaan (.bab-nav) supaya tanpa
            JavaScript tidak muncul tombol yang tidak berfungsi; JS akan
            memunculkannya dan mengisi judul bab tetangga.
        --}}
        <nav data-bab-nav class="bab-nav" aria-label="Navigasi antar bab">

            <button type="button" data-bab-sebelum class="bab-nav__tombol bab-nav__tombol--kiri" hidden>
                <svg class="bab-nav__ikon" fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                </svg>

                <span class="bab-nav__teks">
                    <span class="bab-nav__label">Sebelumnya</span>

                    <span class="bab-nav__judul" data-bab-sebelum-judul></span>
                </span>
            </button>

            <button type="button" data-bab-sikut class="bab-nav__tombol bab-nav__tombol--kanan" hidden>
                <span class="bab-nav__teks">
                    <span class="bab-nav__label">Berikutnya</span>

                    <span class="bab-nav__judul" data-bab-sikut-judul></span>
                </span>

                <svg class="bab-nav__ikon bab-nav__ikon--kanan" fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kanan') }}" />
                </svg>
            </button>
        </nav>
    @endif
</div>
