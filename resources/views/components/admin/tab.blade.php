@props([
    /**
     * Tab filter. Bentuk tiap butir:
     *   ['nilai' => 'pending', 'label' => 'Menunggu', 'jumlah' => 7, 'href' => '...']
     *
     * "href" boleh null untuk tab yang tidak punya URL sendiri.
     */
    'tab' => [],
    'aktif' => null,
    'label' => 'Filter',
])

{{--
    Tab / filter status.

    Tab ini tautan biasa ke URL yang sudah ada (dengan query string),
    bukan tombol JavaScript: filter tetap bekerja kalau JavaScript mati,
    dan tombol "kembali" di browser tetap mengembalikan tab sebelumnya.
--}}

<nav aria-label="{{ $label }}" {{ $attributes->class(['ad-tab']) }}>
    @foreach ($tab as $butir)
        @php
            $kelas = $aktif === $butir['nilai']
                ? 'ad-tab__item ad-tab__item--aktif'
                : 'ad-tab__item';
        @endphp

        @if (filled($butir['href'] ?? null))
            <a href="{{ $butir['href'] }}"
                class="{{ $kelas }}" @if ($aktif === $butir['nilai']) aria-current="page" @endif>
                {{ $butir['label'] }}

                @if (array_key_exists('jumlah', $butir))
                    <span class="ad-tab__jumlah">{{ $butir['jumlah'] }}</span>
                @endif
            </a>
        @else
            <span class="{{ $kelas }}" @if ($aktif === $butir['nilai']) aria-current="page" @endif>
                {{ $butir['label'] }}

                @if (array_key_exists('jumlah', $butir))
                    <span class="ad-tab__jumlah">{{ $butir['jumlah'] }}</span>
                @endif
            </span>
        @endif
    @endforeach
</nav>
