@props([
    'judul',
    'subjudul' => null,
    // Tautan di kanan kepala, mis. "Lihat Semua".
    'aksiTeks' => null,
    'aksiHref' => null,
    // Slot "ikon": lingkaran pastel di sebelah judul.
    'ikon' => null,
    'ikonNada' => null,
])

{{--
    Kepala halaman admin: judul besar, subjudul, dan tautan aksi di kanan.

    Satu komponen untuk semua halaman admin supaya jarak antara judul,
    subjudul, dan aksi selalu sama. Lebarnya dibuat penuh supaya
    elemen berikutnya bisa menentukan jaraknya sendiri.
--}}

<div {{ $attributes->class(['ad-kepala']) }}>
    <div class="ad-kepala__teks">
        <h1 class="ad-kepala__judul">{{ $judul }}</h1>

        @if (filled($subjudul))
            <p class="ad-kepala__sub">{{ $subjudul }}</p>
        @endif
    </div>

    @if (filled($aksiHref))
        <a href="{{ $aksiHref }}" class="ad-tautan">
            {{ $aksiTeks ?? 'Lihat Semua' }}

            <x-admin.ikon nama="panah-kanan" />
        </a>
    @elseif (filled($aksiTeks))
        <span class="ad-tautan" aria-hidden="true">{{ $aksiTeks }}</span>
    @endif
</div>
