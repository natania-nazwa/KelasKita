@extends('layouts.app')

@section('title', $detail['judul'].' | KelasKita')

@section('content')
    {{--
        Padding negatif di sini menetralkan padding utama <main> (p-6 / lg:p-10)
        supaya latar bertekstur menutup seluruh viewport, lalu padding kecil
        dikembalikan sebagai jarak kartu. Nilainya harus selalu pasangan:
        -m-6 dengan p-4, dan lg:-m-10 dengan lg:p-5.

        min-h-nya 100dvh, bukan 100dvh dikurangi 4rem: top bar halaman ini
        disembunyikan (lihat $sembunyiTopbar di layouts/app), jadi tidak ada
        lagi baris setinggi 4rem di atas yang perlu dikurangi.
    --}}
    <div class="kanvas-materi -m-6 min-h-[100dvh] p-4 sm:p-5 lg:-m-10 lg:p-5">

        {{-- 1. Kembali ke daftar; filter yang sedang aktif ikut dibawa. --}}
        <div class="mb-3 sm:mb-4">
            <a href="{{ $detail['tautan_daftar'] }}" class="tombol-kembali">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                </svg>

                Kembali
            </a>
        </div>

        {{-- 2. Kepala materi: thumbnail, badge, judul, deskripsi, metadata. --}}
        <x-materi.detail-kepala :detail="$detail" />

        {{--
            3. Isi halaman: Daftar Isi di kiri dan kolom materi di kanan.

            min-w-0 pada kedua kolom: tanpa itu, grid ikut melebar mengikuti
            isi terpanjang, dan blok kode yang lebar akan mendorong seluruh
            halaman, bukan hanya kotaknya sendiri.

            Proporsi 27 : 73 mengikuti rancangan halaman. Sidebar baru tampil
            kalau materi punya lebih dari satu seksi; materi satu halaman
            jadi satu kolom penuh supaya tidak ada ruang kosong di kiri.

            Daftar Isi sengaja TIDAK memakai position: sticky. Ia harus ikut
            ter-scroll bersama halaman dan hilang ke atas begitu digulir,
            supaya tidak menepa isi yang sedang dibaca.
        --}}
        @if (count($detail['seksi']) > 1)
            <div class="mt-4 grid items-start gap-6 sm:mt-5 lg:grid-cols-[minmax(0,27fr)_minmax(0,73fr)]">

                <div class="min-w-0">
                    <x-materi.detail-daftar-isi :seksi="$detail['seksi']" />
                </div>

                <div class="min-w-0">
                    <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
                </div>
            </div>
        @else
            <div class="mt-4 min-w-0 sm:mt-5">
                <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
            </div>
        @endif

        {{-- 4. Saran baca. Kalau semua materi lain berasal dari kategori yang
             sama, namanya disebut; kalau bercampur, judulnya netral saja. --}}
        @if ($terkini !== [])
            <section class="mt-6 sm:mt-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-lg font-extrabold tracking-tight text-dark">
                        Materi lain
                        @if ($semuaSatuKategori)
                            <span class="font-semibold text-dark/45">di {{ $detail['kategori']['nama'] }}</span>
                        @endif
                    </h2>

                    <a href="{{ $detail['tautan_daftar'] }}"
                        class="text-xs font-semibold text-primary transition hover:text-primary-dark">
                        Lihat semua
                    </a>
                </div>

                {{-- Kartu materi lain memakai komponen yang sama dengan
                     halaman daftar, jadi tampilannya selalu konsisten. --}}
                <div class="mt-4">
                    <x-materi.grid :daftar="$terkini" />
                </div>
            </section>
        @endif
    </div>
@endsection
