@props([
    'statusAktif' => null,
    'jumlahStatus' => [],
    'daftarUrutan' => [],
    'urutAktif' => 'terbaru',
    'kataKunci' => '',
    // Route tujuan form, ikut berbeda kalau sedang melihat satu quiz.
    'aksi' => 'user.hasil',
    'param' => [],
])

{{--
    Kolom pencarian + dropdown sorting, untuk ditaruh di kanan header
    section "Riwayat Hasil Quiz".

    Keduanya satu form GET, sama seperti components/materi/cari: mengetik
    memicu submit setelah jeda dan mengganti urutan langsung submit
    (dikerjakan initCari() di resources/js/app.js). Filter status
    sekarang tinggal di tab di bawah header, jadi tidak perlu ikut
    dibawa sebagai hidden.
--}}

<form action="{{ route($aksi, $param) }}" method="GET" data-cari-form
    {{ $attributes->class(['flex min-w-0 flex-wrap items-center gap-2.5']) }}>

    <div class="relative w-full min-w-0 sm:w-56 sm:flex-none">
        <span
            class="pointer-events-none absolute left-3.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white text-dark/40 shadow-[0_2px_8px_rgba(33,26,58,0.06)]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>

        <input type="search" name="q" value="{{ $kataKunci }}" data-cari-input autocomplete="off"
            placeholder="Cari quiz..." aria-label="Cari hasil quiz"
            class="kolom-cari w-full py-2 pl-12 text-sm {{ $kataKunci !== '' ? 'pr-10' : 'pr-4' }}">

        @if ($kataKunci !== '')
            <a href="{{ route($aksi, array_merge($param, array_filter(['urut' => $urutAktif !== 'terbaru' ? $urutAktif : null]))) }}"
                class="absolute right-2.5 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-dark/35 transition hover:bg-brand-bg hover:text-dark"
                aria-label="Hapus kata kunci">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </a>
        @endif
    </div>

    <div class="relative w-full min-w-0 sm:w-40 sm:flex-none">
        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-dark/35" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h12m0 0-3-3m3 3-3 3m-9 9h12m0 0-3 3m3-3-3-3M6 12h.008v.008H6V12Z" />
            </svg>
        </span>

        <select name="urut" data-cari-filter class="pilih-kategori py-2 pl-10 text-sm" aria-label="Urutkan hasil">
            @foreach ($daftarUrutan as $pilihan)
                <option value="{{ $pilihan['nilai'] }}" @selected($urutAktif === $pilihan['nilai'])>
                    {{ $pilihan['label'] }}
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
