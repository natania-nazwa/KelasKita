@extends('layouts.app')

@section('title', 'Simpan | KelasKita')

@section('content')
    {{--
        Halaman "Simpan": materi dan quiz yang disimpan pengguna lewat
        tombol bookmark di pojok kanan atas kartu.

        Dua tab (Materi / Quiz) memakai query string ?tab=, jadi
        perpindahan tab tetap jalan walau JavaScript dimatikan dan
        alamatnya bisa disalin. Isinya selalu simpanan milik sendiri:
        filter kepemilikan ada di query, bukan di Blade.

        Bentuknya sengaja sama dengan halaman Materi dan Quiz: papan hero
        di atas (x-simpanan.kepala), lalu grid kartu lima kolom
        (x-simpanan.daftar) memakai komponen kartu yang sama dengan kedua
        halaman itu. Yang berbeda hanya jumlah kolom dan jumlah kartu per
        halaman (25, lihat SimpananController::perHalaman) supaya lima
        baris lima kolom penuh dan tidak banyak ruang kosong.

        Daftar tidak memakai tautan pagination maupun baris "jumlah data
        yang tampil". Kartu berikutnya ditambahkan di tempat lewat
        initMuatLebih() di app.js, jadi pengguna cukup menggulir ke bawah.
        Tanpa JavaScript tombol "Muat lagi" tetap tautan biasa ke ?page=,
        jadi daftar tetap bisa dibaca habis.

        data-simpanan-halaman dipakai initBookmark() di app.js sebagai
        tanda bahwa melepas simpanan di halaman ini harus ikut membuang
        kartunya dari daftar, bukan hanya mengubah warna tombol.
    --}}
    <div data-simpanan-halaman class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- =========================
             KEPALA + TAB
        ==========================
             Papan hero mengisi bagian atas halaman, dan tab duduk di
             dalamnya, jadi tidak ada area putih kosong di antara kepala
             dan daftar. Angka di tab adalah jumlah simpanan yang masih
             tayang, jadi tetap sama walau berpindah tab atau memuat
             halaman berikutnya. Pencarian memakai kolom global di top
             bar, tidak diulang di sini. --}}
        <x-simpanan.kepala :jumlah-materi="$jumlahPerTab['materi']" :jumlah-quiz="$jumlahPerTab['quiz']">
            <x-simpanan.tab :tab="$tab" :jumlah-materi="$jumlahPerTab['materi']"
                :jumlah-quiz="$jumlahPerTab['quiz']" :kata-kunci="$kataKunci" />
        </x-simpanan.kepala>

        {{-- =========================
             DAFTAR SIMPAN
        ==========================
             Kartunya memakai komponen kartu yang sama dengan halaman
             Materi dan Quiz, termasuk tombol bookmark di pojok kanan
             atasnya: menekannya di halaman ini melepas simpanan dan
             kartunya dibuang dari daftar.

             data-simpan-grid jadi target initMuatLebih() di app.js:
             kartu halaman berikutnya disisipkan ke dalam grid ini,
             bukan menggantinya. --}}
        @if ($daftar === [])
            <x-simpanan.kosong :alasan="$alasanKosong" :tab="$tab" />
        @else
            {{-- Baris pengantar di atas grid: membuat jarak antar blok
                 terasa disengaja, bukan sekadar jarak dari kartu. --}}
            <h2 data-reveal class="mt-7 text-base font-bold tracking-tight text-dark">
                {{ $tab === 'quiz' ? 'Quiz Disimpan' : 'Materi Disimpan' }}
            </h2>

            <div class="mt-4">
                <x-simpanan.daftar :daftar="$daftar" :jenis="$tab" :kata-kunci="$kataKunci" />
            </div>

            @if ($paginasi->hasMorePages())
                <div class="mt-8 flex justify-center">
                    <a href="{{ $paginasi->nextPageUrl() }}" data-muat-lebih
                        class="tombol-garis inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 12.75v6.75a1.5 1.5 0 0 1-1.5 1.5H6a1.5 1.5 0 0 1-1.5-1.5v-6.75M12 4.5v11.25m0 0-4.5-4.5m4.5 4.5 4.5-4.5" />
                        </svg>

                        Muat lagi
                    </a>
                </div>
            @endif
        @endif
    </div>
@endsection
