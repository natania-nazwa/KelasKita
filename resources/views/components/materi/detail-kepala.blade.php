@props([
    'detail',
])

@php
    /*
     * Kepala materi: thumbnail di kiri, ringkasan di kanan.
     *
     * Sumber datanya array polos dari App\Support\DetailMateri, jadi
     * komponen ini tidak terikat Eloquent.
     *
     * Bentuk array yang diharapkan:
     *   judul, deskripsi, thumbnail, tingkat_kesulitan, waktu_baca, tanggal,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     */

    $kategori = $detail['kategori'];
    $pembuat = $detail['pembuat'];
    $kesulitan = strtolower((string) $detail['tingkat_kesulitan']);

    $warnaKesulitan = [
        'mudah' => 'bg-[#dcfce7] text-[#15803d]',
        'sedang' => 'bg-[#fef3c7] text-[#b45309]',
        'sulit' => 'bg-[#fee2e2] text-[#b91c1c]',
    ];
@endphp

<article data-reveal
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-detail overflow-hidden">

    {{-- flex-col di mobile supaya thumbnail naik ke atas; sm:flex-row
         mengembalikan syarat dua kolom di tablet dan desktop. --}}
    <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-stretch sm:gap-6 sm:p-6">

        {{-- A. Thumbnail landscape, tidak memenuhi seluruh lebar card. --}}
        <div class="thumb-materi shrink-0 sm:w-60">
            @if (filled($detail['thumbnail']))
                <img src="{{ $detail['thumbnail'] }}" loading="lazy"
                    alt="Thumbnail materi {{ $detail['judul'] }}" class="h-full w-full object-cover">
            @else
                {{-- Belum ada gambar: gradasi warna kategori + ikon mapel
                     sebagai pengganti, sama seperti kartu di halaman daftar. --}}
                <span class="thumb-materi__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
            @endif
        </div>

        <div class="flex min-w-0 flex-1 flex-col">

            {{-- B. Badge kategori + tingkat kesulitan. --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="lencana bg-lavender text-primary-dark">
                    <span class="mr-1.5" aria-hidden="true">{{ $kategori['ikon'] }}</span>

                    {{ $kategori['nama'] }}
                </span>

                @if (filled($detail['tingkat_kesulitan']))
                    <span class="lencana capitalize {{ $warnaKesulitan[$kesulitan] ?? 'bg-lavender text-dark/60' }}">
                        {{ $detail['tingkat_kesulitan'] }}
                    </span>
                @endif
            </div>

            {{-- C. Judul + deskripsi. --}}
            <h1 class="mt-3 text-2xl font-extrabold leading-tight tracking-tight text-dark sm:text-3xl">
                {{ $detail['judul'] }}
            </h1>

            @if (filled($detail['deskripsi']))
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-dark/65">
                    {{ $detail['deskripsi'] }}
                </p>
            @endif

            {{-- D. Baris informasi: pembuat, tanggal, waktu baca.
                 flex-wrap supaya di mobile turun ke baris berikutnya dan
                 tidak pernah membuat halaman melebar. --}}
            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-dark/55">

                @if ($pembuat)
                    <span class="flex items-center gap-2">
                        <span class="kartu-materi__avatar"
                            style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                            aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                        <span class="font-semibold text-dark/75">{{ $pembuat['nama'] }}</span>
                    </span>
                @endif

                @if (filled($detail['tanggal']))
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-dark/40" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalender') }}" />
                        </svg>

                        {{ $detail['tanggal'] }}
                    </span>
                @endif

                <span class="flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-dark/40" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                    </svg>

                    {{ $detail['waktu_baca'] }} menit baca
                </span>
            </div>

            {{-- E. Aksi Simpan, menempel di kanan bawah kartu.
                 data-bookmark dipakai ulang oleh initBookmark() di app.js
                 supaya status tersimpan konsisten dengan kartu materi. --}}
            <div class="mt-5 flex items-center justify-end gap-3 lg:mt-auto lg:pt-6">
                <button type="button" data-bookmark="{{ $detail['slug'] }}" aria-pressed="false"
                    data-bookmark-teks="Simpan"
                    class="tombol-simpan">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>

                    <span data-bookmark-teks-nowel>Simpan</span>
                </button>
            </div>
        </div>
    </div>
</article>
