@props([
    'blok',
])

@php
    /*
     * Satu blok isi. IsiMateri sudah menandai tiap blok dengan "tipe", jadi
     * komponen ini hanya meneruskannya ke penampil yang tepat.
     *
     * Bentuk tiap tipe:
     *   paragraf => [tipe, teks, html]
     *   sub      => [tipe, judul]
     *   daftar   => [tipe, butir => html[]]
     *   catatan  => [tipe, teks, html]
     *   kode     => [tipe, bahasa, kode, sorot]
     *
     * Isi "html" sudah aman (IsiMateri meng-escape teks lebih dulu), jadi
     * tidak perlu e() lagi di sini.
     */
@endphp

@switch ($blok['tipe'])
    @case('sub')
        <h3 class="pt-1 text-base font-extrabold tracking-tight text-dark sm:text-lg">
            {{ $blok['judul'] }}
        </h3>
        @break

    @case('daftar')
        <ul class="space-y-2">
            @foreach ($blok['butir'] as $butir)
                <li class="flex gap-2.5">
                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary/45" aria-hidden="true"></span>

                    <span class="isi-materi min-w-0">{!! $butir !!}</span>
                </li>
            @endforeach
        </ul>
        @break

    @case('catatan')
        <p class="flex gap-3 rounded-2xl border border-lavender bg-brand-bg px-4 py-3.5 text-[13px] leading-relaxed text-dark/70">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>

            <span class="min-w-0">{!! $blok['html'] !!}</span>
        </p>
        @break

    @case('kode')
        <x-materi.detail-kode :kode="$blok['kode']" :bahasa="$blok['bahasa']" :sorot="$blok['sorot']" />
        @break

    @default
        <p class="isi-materi">{!! $blok['html'] !!}</p>
@endswitch
