@props([
    'detail',
])

@php
    /*
     * Kepala materi: thumbnail besar di kiri, identitas dan metadata di
     * kanan, tombol Simpan di ujung kanan baris metadata.
     *
     * Sumber datanya array polos dari App\Support\DetailMateri, jadi
     * komponen ini tidak terikat Eloquent. Bentuk array yang dipakai:
     *   judul, deskripsi, thumbnail, tingkat_kesulitan, waktu_baca, tanggal,
     *   dilihat, tersimpan,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     */

    $kategori = $detail['kategori'];
    $pembuat = $detail['pembuat'];
    $tersimpan = (bool) ($detail['tersimpan'] ?? false);
    $kesulitan = strtolower((string) $detail['tingkat_kesulitan']);

    $warnaKesulitan = [
        'mudah' => 'bg-[#dcfce7] text-[#15803d]',
        'sedang' => 'bg-[#fef3c7] text-[#b45309]',
        'sulit' => 'bg-[#fee2e2] text-[#b91c1c]',
    ];
@endphp

<article class="kartu-detail kartu-kepala">

    {{--
        flex-col: thumbnail naik ke atas di ponsel dan memakai lebar penuh;
        sm:flex-row mengembalikan susunan dua kolom di tablet/desktop.
    --}}
    <div class="kartu-kepala__isi">
        <div class="thumb-materi kartu-kepala__thumb">
            @if (filled($detail['thumbnail']))
                <img src="{{ $detail['thumbnail'] }}" loading="lazy"
                    alt="Thumbnail materi {{ $detail['judul'] }}" class="thumb-materi__foto">
            @else
                {{-- Belum ada gambar: gradasi warna kategori + ikon mapel,
                     sama seperti kartu di halaman daftar. --}}
                <span class="thumb-materi__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
            @endif
        </div>

        <div class="kartu-kepala__identitas">

            {{-- A. Badge kategori (utama) + tingkat kesulitan. --}}
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

            {{-- B. Judul. --}}
            <h1 class="kartu-kepala__judul">{{ $detail['judul'] }}</h1>

            @if (filled($detail['deskripsi']))
                <p class="kartu-kepala__deskripsi">{{ $detail['deskripsi'] }}</p>
            @endif

            {{--
                C. Metadata + tombol Simpan. Tombolnya berada di baris yang
                sama dengan metadata (ujung kanan), persis seperti rancangan
                halaman. Di ponsel seluruh baris wrap: tombol turun sendiri
                dan melebar penuh supaya mudah ditekan.
            --}}
            <div class="kartu-kepala__meta">

                @if ($pembuat)
                    <span class="kartu-kepala__butir">
                        <span class="kartu-materi__avatar"
                            style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                            aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                        <span class="font-semibold text-dark/75">{{ $pembuat['nama'] }}</span>
                    </span>
                @endif

                @if (filled($detail['tanggal']))
                    <span class="kartu-kepala__butir">
                        <svg class="kartu-kepala__ikon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalender') }}" />
                        </svg>

                        {{ $detail['tanggal'] }}
                    </span>
                @endif

                <span class="kartu-kepala__butir" title="{{ number_format($detail['jumlah_dilihat'], 0, ',', '.') }} kali dibuka">
                    <svg class="kartu-kepala__ikon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('mata') }}" />
                    </svg>

                    {{ $detail['dilihat'] }}
                </span>

                <span class="kartu-kepala__butir">
                    <svg class="kartu-kepala__ikon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                    </svg>

                    {{ $detail['waktu_baca'] }} menit baca
                </span>

                <button type="button" data-bookmark="{{ $detail['slug'] }}" aria-pressed="{{ $tersimpan ? 'true' : 'false' }}"
                    data-bookmark-teks="Simpan" class="tombol-simpan kartu-kepala__simpan">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('markah') }}" />
                    </svg>

                    <span data-bookmark-teks-nowel>{{ $tersimpan ? 'Tersimpan' : 'Simpan' }}</span>
                </button>
            </div>
        </div>
    </div>
</article>
