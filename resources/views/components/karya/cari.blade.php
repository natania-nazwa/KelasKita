@props([
    // Tab aktif: 'materi' atau 'quiz'. Determines the placeholder and which
    // tab is preserved when the search is submitted or reset.
    'tab' => 'materi',
    'kataKunci' => '',
])

{{--
    Kolom pencarian karya milik sendiri. Ketik memicu submit setelah jeda
    (dikerjakan oleh initCari() di resources/js/app.js), jadi hasilnya tetap
    punya URL yang bisa disalin atau di-bookmark.

    Berbeda dengan halaman Materi/Quiz, tidak ada filter kategori di sini:
    isinya sudah sempit, hanya karya pengguna yang sedang login.
--}}

<form action="{{ route('user.karya-saya') }}" method="GET" data-cari-form
    {{ $attributes->class(['min-w-0 flex-1']) }}>

    {{-- Tab aktif ikut dibawa, jadi hasil pencarian tetap di tab yang sama. --}}
    @if ($tab !== 'materi')
        <input type="hidden" name="tab" value="{{ $tab }}">
    @endif

    <div class="relative">
        <span
            class="pointer-events-none absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white text-dark/40 shadow-[0_2px_8px_rgba(33,26,58,0.06)]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>

        <input type="search" name="q" value="{{ $kataKunci }}" data-cari-input autocomplete="off"
            placeholder="{{ $tab === 'quiz' ? 'Cari quiz milikmu...' : 'Cari materi milikmu...' }}"
            aria-label="Cari karya saya" class="kolom-cari w-full pl-14 {{ $kataKunci !== '' ? 'pr-12' : 'pr-4' }}">

        @if ($kataKunci !== '')
            <a href="{{ route('user.karya-saya', array_filter(['tab' => $tab !== 'materi' ? $tab : null], fn ($n) => filled($n))) }}"
                class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35 transition hover:bg-brand-bg hover:text-dark"
                aria-label="Hapus kata kunci">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </a>
        @endif
    </div>
</form>
