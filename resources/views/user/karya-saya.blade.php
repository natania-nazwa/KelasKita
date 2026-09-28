@extends('layouts.app')

@section('title', 'Karya Saya | KelasKita')

@section('content')
    {{--
        Halaman "Karya Saya": pusat pengelolaan materi dan quiz milik
        pengguna yang sedang login.

        Dua tab (Materi Saya / Quiz Saya) memakai query string ?tab=, jadi
        perpindahan tab tetap jalan walau JavaScript dimatikan dan alamatnya
        bisa disalin. Daftar hanya berisi karya pengguna sendiri: filter
        dibuat oleh scope milik() di model, bukan oleh Blade.
    --}}
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- Kabar berhasil menambah, mengubah, atau menghapus karya. --}}
        @if (session('sukses'))
            <div data-reveal
                class="mb-5 flex items-start gap-3 rounded-2xl border border-lavender bg-white px-4 py-3 text-sm text-dark shadow-[0_14px_30px_-26px_rgba(33,26,58,0.5)]">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    aria-hidden="true">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>

                <p class="min-w-0 font-medium">{{ session('sukses') }}</p>
            </div>
        @endif

        {{-- =========================
             JUDUL HALAMAN
        ========================== --}}
        <x-karya.kepala />

        {{-- =========================
             RINGKASAN KARYA
        ==========================
             Empat angka besar di atas halaman: berapa materi, berapa quiz,
             berapa soal, dan berapa yang masih menunggu persetujuan admin.
             Angkanya mencakup seluruh karya, jadi tetap sama walau berpindah
             tab atau memuat halaman berikutnya. --}}
        <x-karya.ringkasan :materi="$ringkasan['materi']" :quiz="$ringkasan['quiz']"
            :soal="$ringkasan['soal']" :menunggu="$ringkasan['menunggu']" />

        {{-- =========================
             TAB + PENCARIAN + TOMBOL TAMBAH
        ==========================
             Sengaja tanpa kartu pembungkus: tab, kolom cari, dan tombol
             sudah punya bentuknya sendiri, jadi tidak perlu dilingkari
             panel putih lagi. --}}
        <section data-reveal class="mt-6">
            <x-karya.tab :tab="$tab" :jumlah-materi="$jumlahPerTab['materi']"
                :jumlah-quiz="$jumlahPerTab['quiz']" :kata-kunci="$kataKunci" />
        </section>

        {{-- =========================
             DAFTAR KARYA
        ==========================
             Tiga kolom di layar besar: kartu di halaman ini lebih banyak
             isinya (lencana status, tiga baris informasi, tiga tombol),
             jadi tiga kolom membuatnya lega. Dua kolom di tablet, satu di
             ponsel. --}}
        @if ($daftar === [])
            <x-karya.kosong :alasan="$alasanKosong" :tab="$tab" />
        @else
            <div data-reveal-stagger
                class="mt-5 grid grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($daftar as $kartu)
                    @if ($tab === 'quiz')
                        <x-karya.kartu-quiz :quiz="$kartu" />
                    @else
                        <x-karya.kartu-materi :materi="$kartu" />
                    @endif
                @endforeach
            </div>

            <div class="mt-8">
                {{ $paginasi->links() }}
            </div>
        @endif>

        {{-- =========================
             DIALOG KONFIRMASI HAPUS
        ==========================
             Satu dialog untuk semua kartu; isi dan form yang dijalankan
             diambil dari kartu yang tombol "Hapus"-nya ditekan. --}}
        <x-app.konfirmasi />
    </div>
@endsection
