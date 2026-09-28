@props([
    'aksi' => 'user.materi',
    'kataKunci' => '',
    'kategoriAktif' => '',
    'kategori' => [],
    'totalMateri' => 0,
])

{{--
    Kolom pencarian + dropdown filter kategori dalam satu form.
    Ketik memicu submit setelah jeda, ganti kategori langsung submit
    (dikerjakan oleh initCari() di resources/js/app.js).

    Halaman Materi menampilkan seluruh materi yang ada di aplikasi, jadi
    tidak ada parameter kepemilikan yang perlu dibawa.
--}}

<form action="{{ route($aksi) }}" method="GET" data-cari-form
    {{ $attributes->class(['flex flex-col gap-3 sm:flex-row sm:items-center']) }}>
    <div class="relative min-w-0 flex-1">
        {{-- bg-brand-bg, bukan bg-white: lingkaran ikon tetap terlihat
             di halaman putih maupun di dalam papan hero (inputnya putih). --}}
        <span
            class="kepala-cari__ikon pointer-events-none absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-brand-bg text-dark/40">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>

        <input type="search" name="q" value="{{ $kataKunci }}" data-cari-input autocomplete="off"
            placeholder="Cari materi..." aria-label="Cari materi"
            class="kolom-cari w-full pl-14 {{ $kataKunci !== '' ? 'pr-12' : 'pr-4' }}">

        @if ($kataKunci !== '')
            <a href="{{ route($aksi, array_filter(['kategori' => $kategoriAktif], fn ($n) => filled($n))) }}"
                class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35 transition hover:bg-brand-bg hover:text-dark"
                aria-label="Hapus kata kunci">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </a>
        @endif
    </div>

    {{-- Dropdown filter kategori, duduk di samping kolom search. --}}
    <div class="relative min-w-0 sm:w-52">
        <span class="pointer-events-none absolute left-4 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35"
            aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
        </span>

        <select name="kategori" data-cari-filter class="pilih-kategori" aria-label="Saring menurut kategori">
            <option value="">Semua ({{ $totalMateri }})</option>

            @foreach ($kategori as $item)
                <option value="{{ $item['slug'] }}" @selected($kategoriAktif === $item['slug'])>
                    {{ $item['nama'] }} ({{ $item['jumlah'] }})
                </option>
            @endforeach
        </select>

        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-dark/35" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </span>
    </div>
</form>
