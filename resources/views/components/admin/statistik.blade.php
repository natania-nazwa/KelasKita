@props([
    'ikon',
    'label',
    'nilai',
    // Baris kecil di bawah angka, mis. "4 materi • 3 quiz".
    'keterangan' => null,
    // Kalau diisi, baris kecil diganti jadi "{nilai} dari bulan lalu"
    // dengan warna hijau dan ikon panah naik.
    'naik' => null,
    // Nada ikon: null (ungu) | sukses | peringatan | info
    'nada' => null,
    // Kalau diisi, seluruh kartu jadi tautan.
    'href' => null,
])

{{--
    Satu kartu statistik: ikon dalam lingkaran pastel, angka besar,
    label kecil, lalu satu baris keterangan.

    Angka dan keterangan selalu datang dari server. Tidak ada angka
    contoh yang dikarang, supaya kartu tetap jujur saat datanya masih
    sedikit.
--}}

@php
    $tag = $href ? 'a' : 'div';
    $kelasIkon = 'ad-stat__ikon'.($nada ? ' ad-stat__ikon--'.$nada : '');
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['ad-stat']) }}>
    <span class="{{ $kelasIkon }}">
        <x-admin.ikon :nama="$ikon" ukuran="w-5 h-5" />
    </span>

    <div class="ad-stat__isi">
        <p class="ad-stat__label">{{ $label }}</p>

        <p class="ad-stat__nilai">{{ $nilai }}</p>

        @if (filled($naik))
            <p class="ad-stat__ket">
                <span class="ad-stat__naik">
                    <x-admin.ikon nama="naik" ukuran="w-3 h-3" />

                    {{ $naik }}
                </span>

                dari bulan lalu
            </p>
        @elseif (filled($keterangan))
            <p class="ad-stat__ket">{{ $keterangan }}</p>
        @endif
    </div>
</{{ $tag }}>
