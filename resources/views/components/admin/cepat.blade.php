@props([
    'ikon',
    'judul',
    'keterangan' => null,
    'href' => '#',
])

{{--
    Satu kartu "Menu Cepat".

    Seluruh kartu adalah satu tautan, bukan tombol kecil di dalam kartu,
    supaya di HP tidak ada target klik yang cuma setengah kartu. Ikonnya
    berubah warna saat di-hover supaya entice-nya jelas.
--}}

<a href="{{ $href }}" {{ $attributes->class(['ad-cepat']) }}>
    <span class="ad-cepat__ikon">
        <x-admin.ikon :nama="$ikon" ukuran="w-4.5 h-4.5" />
    </span>

    <span class="ad-cepat__teks">
        <span class="ad-cepat__judul">{{ $judul }}</span>

        @if (filled($keterangan))
            <span class="ad-cepat__keterangan">{{ $keterangan }}</span>
        @endif
    </span>

    <x-admin.ikon nama="panah-kanan" class="ad-cepat__panah" />
</a>
