@extends('layouts.app')

@section('title', $quiz->judul.' | KelasKita')

@section('content')
    @php
        /*
         * Padding negatif menetralkan padding <main> (p-6 / lg:p-10) supaya
         * latar bertekstur menutup seluruh viewport, lalu padding kecil
         * dikembalikan sebagai jarak halaman. Nilainya harus selalu pasangan:
         * -m-6 dengan p-4, dan lg:-m-10 dengan lg:p-6.
         *
         * Lebar isinya TIDAK dibatasi (dulu dikunci max-w-6xl) supaya
         * halaman ini selebar halaman detail di area admin, yang memang
         * memakai konten tanpa batas lebar. Susunan kartu, warna, dan
         * komponennya tidak ikut berubah.
         */
        $kategori = $kartu['kategori'];

        /*
         * "Lihat semua" pada Daftar Soal mengarah ke daftar quiz di kategori
         * yang sama, karena tidak ada halaman khusus daftar soal. Tautannya
         * baru dirender kalau masih ada soal tersembunyi (lihat
         * x-quiz.detail-daftar-soal), dan kategori kosong tidak pernah
         * diberi tautan, supaya tidak muncul "kategori=" kosong di URL.
         */
        $tautanKategori = filled($kategori['slug'])
            ? route('user.quiz', ['kategori' => $kategori['slug']])
            : route('user.quiz');

        $tautanBagikan = route('user.quiz.detail', $quiz);
    @endphp

    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-4 lg:-m-10 lg:p-6">
        <div class="w-full">

            {{-- 1. Kembali ke daftar quiz. --}}
            <a href="{{ route('user.quiz') }}" class="tombol-kembali">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                </svg>

                Kembali
            </a>

            {{--
                Kabar gagal memulai quiz atau membuka sesinya, mis. quiznya
                belum punya soal atau belum punya kode gabung. Keduanya datang
                dari dua form yang ada di halaman ini, jadi pesannya ditulis
                di bawah satu blok.
            --}}
            @if ($errors->has('quiz') || $errors->has('sesi'))
                <div class="mt-4 flex items-start gap-3 rounded-2xl border border-lavender bg-white px-4 py-3 text-sm text-dark shadow-[0_14px_30px_-26px_rgba(33,26,58,0.5)]"
                    role="alert">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                        aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </span>

                    <p class="min-w-0 font-medium">{{ $errors->first('sesi') ?: $errors->first('quiz') }}</p>
                </div>
            @endif

            {{--
                2. Kepala halaman: kartu quiz di kiri (sekitar 2/3 lebar) dan
                kartu informasi di kanan (sekitar 1/3). Keduanya diregangkan
                sama tinggi, dan di bawah 1024px berubah jadi satu kolom dengan
                kartu informasi turun ke bawah.
            --}}
            <div
                class="mt-4 grid min-w-0 items-stretch gap-4 sm:mt-5 sm:gap-5 lg:grid-cols-[minmax(0,68fr)_minmax(0,32fr)]">
                <x-quiz.detail-kartu :kartu="$kartu" :jumlah-soal="$jumlahSoal" :sesi-host="$sesiHost" />

                <x-quiz.detail-informasi :kartu="$kartu" :jumlah-soal="$jumlahSoal" />
            </div>

            {{-- 3. Daftar soal, melebar penuh di bawah kedua kartu. --}}            <x-quiz.detail-daftar-soal :soal="$soal" :tautan="$tautanKategori" />

            {{-- 4. Quiz lain di kategori yang sama, kalau ada. --}}
            @if ($rekomendasi !== [])
                <section class="mt-8" aria-label="Quiz lain di kategori ini">
                    <h2 class="text-base font-bold text-dark">Quiz lain di {{ $kategori['nama'] }}</h2>

                    <div class="mt-3 grid grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($rekomendasi as $item)
                            <x-quiz.kartu :quiz="$item" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- 5. Dialog Bagikan, selalu ikut di-render tapi tersembunyi. --}}
        <x-quiz.detail-bagikan data-tautan="{{ $tautanBagikan }}" />
    </div>
@endsection
