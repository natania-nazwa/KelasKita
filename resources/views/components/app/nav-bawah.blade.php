@props([
    /*
     * Array $menu dari layout, sudah diberi tanda "bawah" pada lima item
     * utama. Sisa item tidak pernah ikut; yang tersisa di header mobile
     * sebagai menu tambahan.
     */
    'menu' => [],
])

{{--
    Navigasi bawah tetap untuk layar < 1024px.

    Di desktop navigasi serupa sudah ada sebagai sidebar, jadi elemen ini
    sengaja disembunyikan lewat lg:hidden — bukan diganti — supaya tampilan
    desktop tidak bergerak sedikit pun.

    Bentuknya <nav> biasa berisi tautan, bukan tombol ber-JavaScript:
    tanpa JS pun pengguna tetap bisa berpindah halaman. Satu-satunya
    tambahan JavaScript ada di resources/js/app.js (penanda halaman yang
    sedang terbuka ketika halaman dimuat lewat AJAX), dan ia bekerja
    sebagai penambah, bukan pengganti.

    Padding bawah memakai env(safe-area-inset-bottom) supaya di iPhone
    dengan bar home gestural lima tombol ini tidak tertimpa bar itu.
--}}
@php
    $utama = collect($menu)->filter(fn (array $item) => $item['bawah'] ?? false)->values();

    $aktif = fn (array $item) => collect($item['pola'] ?? [])
        ->contains(fn (string $pola) => request()->routeIs($pola));
@endphp

<nav aria-label="Navigasi utama"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-lavender bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">

    <ul class="grid grid-cols-5">
        @foreach ($utama as $item)
            @php
                $kini = $aktif($item);
            @endphp

            <li>
                <a href="{{ route($item['route']) }}" @if ($kini) aria-current="page" @endif
                    @class([
                        'flex min-h-[3.4rem] flex-col items-center justify-center gap-0.5 px-1 pt-1.5 pb-1 text-center transition',
                        'text-primary' => $kini,
                        'text-dark/55 hover:text-primary-dark' => ! $kini,
                    ])>
                    {{-- Pil di belakang ikon hanya terlihat pada menu yang
                         sedang terbuka, jadi "halaman ini" terbaca sekilas
                         tanpa harus membaca labelnya. --}}
                    <span @class([
                        'flex h-7 w-11 items-center justify-center rounded-full transition',
                        'bg-lavender' => $kini,
                    ]) aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                        </svg>
                    </span>

                    {{-- "Karya Saya" adalah label terpanjang: 10px +
                         whitespace-nowrap membuatnya muat di 320px di
                         mana tiap kolom hanya 64px. --}}
                    <span @class([
                        'text-[10px] leading-tight whitespace-nowrap',
                        'font-bold' => $kini,
                        'font-semibold' => ! $kini,
                    ])>{{ $item['bawahLabel'] ?? $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
