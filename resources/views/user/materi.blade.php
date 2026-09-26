@extends('layouts.app')

@section('title', 'Materi | KelasKita')

@section('content')
    @php
        use App\Models\Pelajaran;

        /*
         * Warna lencana tingkat kesulitan. Mengikuti palet dashboard.
         */
        $warnaKesulitan = [
            'mudah' => 'bg-[#dcfce7] text-[#15803d]',
            'sedang' => 'bg-[#fef3c7] text-[#b45309]',
            'sulit' => 'bg-[#fee2e2] text-[#b91c1c]',
        ];

        /*
         * Menyalin teks aman (sudah di-escape) lalu menebalkan kata yang
         * sedang dicari supaya user langsung tahu mana yang cocok.
         */
        $tebalkan = function (?string $teks) use ($kataKunci) {
            $aman = e((string) $teks);

            if (mb_strlen(trim((string) $kataKunci)) < 2) {
                return $aman;
            }

            return str_ireplace(
                e(trim($kataKunci)),
                '<mark class="rounded bg-lavender px-0.5 font-bold text-primary-dark">'.e(trim($kataKunci)).'</mark>',
                $aman
            );
        };

        $adaFilter = $kataKunci !== '' || $kategoriAktif !== '';

        /*
         * Filter kategori harus tetap membawa kata kunci yang sedang diketik,
         * begitupun sebaliknya. Nilai kosong tidak perlu ikut ke URL.
         */
        $urlFilter = fn (?string $kategori = null) => route('user.materi', array_filter([
            'q' => $kataKunci,
            'kategori' => $kategori ?? $kategoriAktif,
        ], fn ($nilai) => filled($nilai)));
    @endphp

    {{-- Kanvas full-bleed: margin negatif menetrus padding <main> supaya
         latar bertekstur sampai ke tepi, lalu padding diulang di dalam. --}}
    <div class="kanvas-materi -m-6 min-h-screen p-6 lg:-m-10 lg:p-10">

        {{-- =========================
             HEADER
        ========================== --}}
        <section data-reveal="fade"
            class="relative overflow-clip rounded-[2rem] border border-lavender bg-white p-6 shadow-[0_20px_45px_-34px_rgba(33,26,58,0.45)] sm:p-8">

            {{-- Aksen cahaya, murni dekoratif. overflow-clip menjaga
                 bulatan ini tidak menambah tinggi/lebar halaman. --}}
            <span class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-lavender/60 blur-3xl" aria-hidden="true"></span>
            <span class="pointer-events-none absolute -bottom-28 -left-16 h-56 w-56 rounded-full bg-primary/10 blur-3xl" aria-hidden="true"></span>

            <div class="relative flex flex-wrap items-end justify-between gap-x-6 gap-y-5">
                <div class="min-w-0">
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-lavender px-3.5 py-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>

                        Pustaka Belajar
                    </span>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                        Materi
                    </h1>

                    <p class="mt-1.5 max-w-lg text-sm leading-relaxed text-dark/60">
                        Cari materi berdasarkan kata kunci, lalu saring sesuai mata pelajaran yang kamu pelajari.
                    </p>

                    {{-- Angka ringkas: konteks sebelum user mulai mengetik. --}}
                    <div class="mt-5 flex flex-wrap gap-2.5">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-lavender bg-brand-bg px-3.5 py-1.5 text-xs font-semibold text-dark/70">
                            <svg class="h-3.5 w-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10l4 4v10a2 2 0 0 1-2 2Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 4v4h4" />
                            </svg>

                            {{ $totalMateri }} materi
                        </span>

                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-lavender bg-brand-bg px-3.5 py-1.5 text-xs font-semibold text-dark/70">
                            <svg class="h-3.5 w-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>

                            {{ $kategori->count() }} kategori
                        </span>
                    </div>
                </div>

                <p class="font-hand text-2xl leading-none text-primary/70">Cari, temukan, pelajari!</p>
            </div>
        </section>

        {{-- =========================
             SEARCH + FILTER
        ========================== --}}
        <section data-reveal
            class="mt-5 rounded-3xl border border-lavender bg-white p-5 shadow-[0_18px_40px_-32px_rgba(33,26,58,0.4)] sm:p-6">

            <div class="mb-3.5 flex flex-wrap items-center justify-between gap-2">
                <h2
                    class="inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-dark/45">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>

                    Cari &amp; saring
                </h2>

                @if ($adaFilter)
                    <p class="text-[11px] font-medium text-dark/45">
                        Filter aktif &mdash; ubah pilihan kapan saja, hasilnya langsung ikut berubah.
                    </p>
                @endif
            </div>

            <form action="{{ route('user.materi') }}" method="GET" data-cari-form
                class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <span
                        class="pointer-events-none absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white text-dark/40 shadow-[0_2px_8px_rgba(33,26,58,0.06)]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>

                    <input type="search" name="q" value="{{ $kataKunci }}" data-cari-input autocomplete="off"
                        placeholder="Cari materi di sini, misalnya: hiragana, pecahan, atau Tata Surya..."
                        class="kolom-cari w-full pl-14 {{ $kataKunci !== '' ? 'pr-12' : 'pr-4' }}"
                        aria-label="Cari materi">

                    @if ($kataKunci !== '')
                        <a href="{{ $urlFilter() }}"
                            class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35 transition hover:bg-brand-bg hover:text-dark"
                            aria-label="Hapus kata kunci">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>

                {{-- Filter kategori jadi dropdown di samping search. Mengirim
                     name="kategori" dan auto-submit lewat data-cari-filter. --}}
                <div class="relative min-w-0 shrink-0 sm:w-60">
                    <span
                        class="pointer-events-none absolute left-4 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                        </svg>
                    </span>

                    <select name="kategori" data-cari-filter class="pilih-kategori"
                        aria-label="Saring menurut mata pelajaran">
                        <option value="">Semua ({{ $totalMateri }})</option>

                        @foreach ($kategori as $item)
                            <option value="{{ $item['slug'] }}" @selected($kategoriAktif === $item['slug'])>
                                {{ $item['nama'] }} ({{ $item['jumlah'] }})
                            </option>
                        @endforeach
                    </select>

                    <span
                        class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-dark/40"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </span>
                </div>

                <button type="submit"
                    class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-[0_10px_22px_-12px_rgba(108,77,230,0.95)] transition hover:bg-primary-dark active:scale-95 focus-visible:ring-2 focus-visible:ring-primary/40 sm:flex"
                    aria-label="Mulai pencarian">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </form>
        </section>

        {{-- =========================
             RINGKASAN HASIL
        ========================== --}}
        <div data-reveal="fade" class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <p
                class="inline-flex items-center gap-2 rounded-full border border-lavender bg-white px-4 py-2 text-sm text-dark/60 shadow-[0_10px_24px_-20px_rgba(33,26,58,0.5)]">
                <svg class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                </svg>

                @if ($materi->total() > 0)
                    Menampilkan <span class="font-bold text-dark">{{ $materi->firstItem() }}&ndash;{{ $materi->lastItem() }}</span>
                    dari <span class="font-bold text-dark">{{ $materi->total() }}</span> materi
                @else
                    Tidak ada materi yang cocok
                @endif

                @if ($kataKunci !== '')
                    untuk kata kunci <span class="font-semibold text-primary">&ldquo;{{ $kataKunci }}&rdquo;</span>
                @endif
            </p>

            @if ($adaFilter)
                <a href="{{ route('user.materi') }}"
                    class="group inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-xs font-semibold text-white shadow-[0_12px_26px_-14px_rgba(108,77,230,0.95)] transition hover:bg-primary-dark">
                    <svg class="h-3.5 w-3.5 transition group-hover:-rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 5.29 5.29m0 0a8.002 8.002 0 0 0 0 10.42m0 0 3.716-3.716m0 0 2.014 2.014A8.001 8.001 0 0 0 19.71 14.71m0 0-2.014-2.014m0 0 3.716 3.716" />
                    </svg>

                    Atur ulang
                </a>
            @endif
        </div>

        {{-- =========================
             DAFTAR MATERI
        ========================== --}}
        @if ($materi->isEmpty())
            <div data-reveal
                class="mt-4 flex flex-col items-center overflow-clip rounded-[2rem] border border-lavender bg-white px-6 py-16 text-center shadow-[0_20px_45px_-34px_rgba(33,26,58,0.4)]">
                <span class="relative flex h-16 w-16 items-center justify-center">
                    <span class="absolute inset-0 rounded-3xl bg-lavender/70 blur-xl" aria-hidden="true"></span>

                    <span class="relative flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-bg text-dark/25">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                </span>

                <h2 class="mt-6 text-lg font-bold tracking-tight text-dark">Materi belum ditemukan</h2>

                <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-dark/50">
                    @if ($kataKunci !== '')
                        Tidak ada materi yang cocok dengan kata kunci itu. Coba kata yang lebih umum atau pilih kategori lain.
                    @else
                        Belum ada materi pada kategori ini. Admin bisa menambahkannya lewat menu Materi.
                    @endif
                </p>

                @if ($adaFilter)
                    <a href="{{ route('user.materi') }}"
                        class="mt-6 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
                        Lihat semua materi
                    </a>
                @endif
            </div>
        @else
            {{-- Lebar card dibatasi 18rem (maksimum): 1 kolom di mobile, 2 di
                 tablet, 3 di layar sedang, 4 di desktop/lebar. minmax(0, 18rem)
                 membuat kolom menyusut otomatis, jadi tidak pernah melebar
                 melewati sidebar. --}}
            <div data-reveal-stagger
                class="mt-4 grid min-w-0 gap-5 [grid-template-columns:repeat(1,minmax(0,18rem))] sm:[grid-template-columns:repeat(2,minmax(0,18rem))] lg:[grid-template-columns:repeat(3,minmax(0,18rem))] xl:[grid-template-columns:repeat(4,minmax(0,18rem))]">
                @foreach ($materi as $item)
                    @php
                        $pelajaran = $item->pelajaran;
                        $warna = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
                        $kesulitan = strtolower((string) $item->tingkat_kesulitan);
                    @endphp

                    <a href="{{ route('user.materi.detail', $item->slug) }}"
                        style="--k: {{ $warna['warna'] }}; --k-gelap: {{ $warna['warna_gelap'] }};"
                        class="kartu-materi group min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        aria-label="Buka materi {{ $item->nama }}">

                        {{-- banner gambar di atas, ikut warna kategori --}}
                        <div class="kartu-materi__gambar">
                            <span class="kartu-materi__gambar-ikon">{{ $warna['ikon'] }}</span>

                            <span class="lencana lencana-di-gambar">{{ $warna['nama'] }}</span>
                        </div>

                        {{-- badan card --}}
                        <div class="relative flex min-w-0 flex-1 flex-col px-4 pb-4 pt-3.5">
                            <h2 class="line-clamp-2 text-[0.9375rem] font-bold leading-snug tracking-tight text-dark transition group-hover:text-primary-dark">
                                {!! $tebalkan($item->nama) !!}
                            </h2>

                            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-dark/55">
                                {!! $tebalkan($item->ringkasan(110)) !!}
                            </p>

                            {{-- mt-auto: meta + tombol terdorong ke bawah,
                                 jadi semua kartu dalam satu baris sejajar. --}}
                            <div class="mt-auto flex items-center gap-1.5 border-t border-dashed border-lavender/80 pt-3">
                                @if (filled($item->tingkat_kesulitan))
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold capitalize {{ $warnaKesulitan[$kesulitan] ?? 'bg-brand-bg text-dark/60' }}">
                                        {{ $item->tingkat_kesulitan }}
                                    </span>
                                @endif

                                <span class="flex shrink-0 items-center gap-1 text-[10px] font-medium text-dark/45">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>

                                    {{ $item->waktuBaca() }} menit
                                </span>
                            </div>

                            <span class="kartu-materi__tombol mt-3 self-start">
                                Baca Materi

                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $materi->links() }}
            </div>
        @endif
    </div>
@endsection
