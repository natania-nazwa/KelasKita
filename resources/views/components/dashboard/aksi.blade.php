@props([
    'daftar' => [],
])

{{--
    Tiga kartu aksi cepat di bawah kartu statistik.
    - Desktop: 3 kolom
    - Tablet : 3 kolom (tetap muat, kartu memakai layout vertikal)
    - Mobile : 1 kolom

    min-w-0 mencegah kartu melebar mengikuti isi teks, jadi kolom tidak
    pernah memicu scroll horizontal.
--}}

@if (filled($daftar))
    <div data-reveal-stagger class="mt-6 grid min-w-0 gap-4 sm:grid-cols-3">
        @foreach ($daftar as $item)
            <x-dashboard.aksi-kartu :aksi="$item" />
        @endforeach
    </div>
@endif
