@extends('layouts.app')

@section('title', $detail['judul'].' | KelasKita')

@section('content')
    <div class="kanvas-materi -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- 1. Kembali ke daftar; filter yang sedang aktif ikut dibawa. --}}
        <a href="{{ $detail['tautan_daftar'] }}"
            class="tombol-kembali">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>

            Kembali
        </a>

        {{-- 2. Kepala materi: thumbnail, judul, dan informasi singkat. --}}
        <div class="mt-4">
            <x-materi.detail-kepala :detail="$detail" />
        </div>

        {{--
            3. Isi halaman: Daftar Isi di kiri (desktop) dan kolom materi di
            kanan.

            min-w-0 pada kedua kolom: tanpa itu, grid ikut melebar mengikuti
            isi terpanjang, dan blok kode yang lebar akan mendorong seluruh
            halaman, bukan hanya kotaknya sendiri.

            Sidebar baru tampil kalau materi punya lebih dari satu seksi.
            Materi satu halaman jadi satu kolom penuh supaya tidak ada ruang
            kosong di sebelah kiri.
        --}}
        @if (count($detail['seksi']) > 1)
            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:gap-8">

                <div class="min-w-0 lg:sticky lg:top-24">
                    <x-materi.detail-daftar-isi :seksi="$detail['seksi']" />
                </div>

                <div class="min-w-0">
                    <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
                </div>
            </div>
        @else
            <div class="mt-6 min-w-0">
                <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
            </div>
        @endif

        {{-- 4. Audio pembelajaran bila materi punya rekaman. --}}
        @if (filled($detail['audio']))
            <div class="kartu-detail mt-6 p-5 sm:p-6">
                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-primary">
                    Dengarkan materi
                </p>

                <audio controls preload="metadata" class="mt-3 w-full" src="{{ $detail['audio'] }}"></audio>
            </div>
        @endif

        {{-- 5. Saran baca. Kalau semua materi lain berasal dari kategori yang
             sama, namanya disebut; kalau bercampur, judulnya netral saja. --}}
        @if ($terkini !== [])
            <section class="mt-8">
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
