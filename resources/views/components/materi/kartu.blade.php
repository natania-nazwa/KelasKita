@props([
    'materi',
    'kataKunci' => '',
])

@php
    /*
     * Kartu materi. Sumber datanya array polos dari App\Support\DaftarMateri,
     * jadi komponen ini tidak terikat Eloquent dan bisa dipakai ulang di
     * halaman lain (mis. daftar rekomendasi) maupun diisi dari API.
     *
     * Bentuk array yang diharapkan:
     *   id, slug, judul, deskripsi, thumbnail, tingkat_kesulitan,
     *   waktu_baca, jumlah_bab, tautan, jumlah_materi,
     *   kategori  => [nama, slug, ikon, warna, warna_gelap],
     *   pembuat   => [nama, inisial, warna, warna_gelap]
     */

    $kategori = $materi['kategori'];
    $pembuat = $materi['pembuat'];

    /*
     * Warna lencana tingkat kesulitan. Paletnya sama dengan badge
     * kesulitan di halaman detail materi, jadi "Sulit" selalu terbaca
     * merah di kedua halaman.
     */
    $kesulitan = strtolower((string) ($materi['tingkat_kesulitan'] ?? ''));

    $warnaKesulitan = [
        'mudah' => 'bg-[#dcfce7] text-[#15803d]',
        'sedang' => 'bg-[#fef3c7] text-[#b45309]',
        'sulit' => 'bg-[#fee2e2] text-[#b91c1c]',
    ];

    /*
     * Menyalin teks aman (sudah di-escape) lalu menebalkan kata yang sedang
     * dicari, supaya user langsung tahu bagian mana yang cocok.
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
@endphp

<a href="{{ $materi['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-materi group min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Buka materi {{ $materi['judul'] }}">

    {{-- A. Thumbnail: tinggi seragam, membulat di bagian atas. --}}
    <div class="kartu-materi__gambar">
        @if (filled($materi['thumbnail'] ?? null))
            <img src="{{ $materi['thumbnail'] }}" alt="" loading="lazy" class="kartu-materi__foto">
        @else
            {{-- Tanpa kolom gambar di database, banner memakai gradasi
                 warna kategori + ikon mapel sebagai gantinya. --}}
            <span class="kartu-materi__gambar-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Lencana tingkat kesulitan, melayang di pojok kiri atas. --}}
        @if (filled($materi['tingkat_kesulitan'] ?? null))
            <span class="kartu-materi__kesulitan capitalize {{ $warnaKesulitan[$kesulitan] ?? 'bg-white/90 text-dark/60' }}">
                {{ $materi['tingkat_kesulitan'] }}
            </span>
        @endif

        {{-- Bookmark: tombol, bukan tautan, supaya tidak membuka materi. --}}
        <button type="button" data-bookmark="{{ $materi['slug'] }}" aria-pressed="false"
            class="kartu-materi__simpan"
            aria-label="Simpan materi {{ $materi['judul'] }} untuk dibaca nanti">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        </button>

        {{-- B. Badge kategori, melayang di atas thumbnail. --}}
        <span class="kartu-materi__lencana">{{ $kategori['nama'] }}</span>

        {{-- Penanda aksi, muncul saat kartu di-hover: memberi tahu bahwa
             seluruh kartu adalah tautan "Baca". --}}
        <span class="kartu-materi__aksi" aria-hidden="true">
            Baca
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </div>

    {{-- C-E. Garis aksen, judul, deskripsi, dan informasi pembuat. --}}
    <div class="kartu-materi__badan">
        {{-- -mx-4 -mt-3.5: menetralkan padding badan supaya garis aksen
             menempel persis di tepi atas, menyambung ke thumbnail. --}}
        <span class="kartu-materi__aksen -mx-4 -mt-3.5 mb-3 block" aria-hidden="true"></span>

        <h2 class="kartu-materi__judul">{!! $tebalkan($materi['judul']) !!}</h2>

        <p class="kartu-materi__deskripsi">{!! $tebalkan($materi['deskripsi']) !!}</p>

        {{-- mt-auto: baris info terdorong ke bawah, jadi semua kartu
             dalam satu baris tetap sejajar. --}}
        <div class="kartu-materi__meta">
            <span class="kartu-materi__pembuat">
                <span class="kartu-materi__avatar"
                    style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                    aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                <span class="kartu-materi__pembuat-nama">{{ $pembuat['nama'] }}</span>
            </span>

            {{-- F. Waktu baca dan jumlah bab materi ini. ml-auto pada
                 kelompoknya menjaga chip tetap rata kanan saat melipat
                 ke baris kedua di kartu sempit. --}}
            <span class="ml-auto flex shrink-0 items-center gap-1.5">
                <span class="kartu-materi__jumlah kartu-materi__jumlah--waktu">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                    {{ (int) $materi['waktu_baca'] }} mnt
                </span>

                <span class="kartu-materi__jumlah kartu-materi__jumlah--bab">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>

                    {{ (int) $materi['jumlah_bab'] }} Bab
                </span>
            </span>
        </div>
    </div>
</a>
