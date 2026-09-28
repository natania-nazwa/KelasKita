@extends('layouts.app')

@section('title', 'Dashboard | KelasKita')

@section('content')
    @php
        // Warna tiap kartu statistik: pastel soft + aksen lembut.
        $warnaKartu = [
            'hijau' => [
                'latar' => 'bg-[#eefaf2] border-[#d9f0e2]',
                'chip' => 'bg-white text-[#3f9e6b]',
                'blob' => 'bg-[#a8ddc4]/30',
                'bar' => 'from-white via-[#a8ddc4] to-[#4fae7f]',
            ],
            'kuning' => [
                'latar' => 'bg-[#fffaec] border-[#f7eccb]',
                'chip' => 'bg-white text-[#c99a2e]',
                'blob' => 'bg-[#f7dfa8]/30',
                'bar' => 'from-white via-[#f7dfa8] to-[#d5a53a]',
            ],
            'oranye' => [
                'latar' => 'bg-[#fffaec] border-[#f7eccb]',
                'chip' => 'bg-white text-[#c2813f]',
                'blob' => 'bg-[#f8d5b4]/30',
                'bar' => 'from-white via-[#f8d5b4] to-[#cb8a45]',
            ],
            'pink' => [
                'latar' => 'bg-[#fdf0f6] border-[#f8dcea]',
                'chip' => 'bg-white text-[#c96a9a]',
                'blob' => 'bg-[#f8c8dd]/30',
                'bar' => 'from-white via-[#f8c8dd] to-[#d1699b]',
            ],
        ];
    @endphp

    {{--
        Search dan identitas pengguna sudah ditangani top bar di layout.

        Struktur halaman:
          - Kolom utama (~70-75%): banner, statistik, aksi cepat,
            materi terbaru, quiz terbaru.
          - Sidebar (~25-30%): akses cepat, jadwal, leaderboard, kalender.

        Sidebar baru pindah ke samping pada layar 2xl (1536px) ke atas.
        Alasannya: materi dan quiz sengaja ditampilkan EMPAT KARTU SEJALAR,
        jadi kolom utama harus punya minimal ~850px supaya tiap kartunya
        tetap di atas 200px. Kalau sidebar sudah tampil di 1280px, kolom
        utama tinggal ~600px dan empat kartu jadi sempit sekali.
        Di bawah 2xl sidebar turun ke bawah kolom utama sebagai grid dua kolom.

        Semua ukuran memakai min-w-0 dan width yang aman, jadi tidak pernah
        muncul scroll horizontal.
    --}}
    <div class="grid min-w-0 items-start gap-4 lg:gap-5 2xl:grid-cols-[minmax(0,1fr)_20rem]">

        {{-- Kolom utama. mt-0 pada anak pertama meniadakan margin atas
             banner supaya sejajar dengan panel pertama di sidebar. --}}
        <div class="min-w-0 [&>section:first-child]:mt-0">

            {{-- Welcome --}}
            <section data-reveal
                class="group relative mt-6 flex min-h-[6cm] flex-col gap-5 overflow-hidden rounded-2xl border border-lavender bg-gradient-to-r from-lavender/70 via-[#f2eeff] to-[#e4dcff] p-5 shadow-[0_1px_2px_rgba(33,26,58,0.04)] transition duration-300 hover:shadow-[0_16px_40px_-24px_rgba(33,26,58,0.28)] sm:h-[6cm] sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <span
                    class="pointer-events-none absolute -top-16 -left-16 h-40 w-40 rounded-full bg-white/40 transition-transform duration-500 group-hover:scale-125"></span>

                <div class="relative max-w-md">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-1 font-mono text-[10px] font-semibold tracking-[0.14em] text-primary uppercase">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ now()->translatedFormat('l, d F Y') }}
                        </span>

                        <span
                            title="Streak {{ $streak['jumlah'] }} hari. Menyala kalau kamu membaca materi atau mengerjakan soal hari ini."
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-mono text-[10px] font-bold tracking-[0.14em] uppercase {{ $streak['aktif'] ? 'bg-[#fee4e2] text-[#dc2626]' : 'bg-dark/5 text-dark/40' }}">
                            <svg class="h-3.5 w-3.5 {{ $streak['aktif'] ? 'text-[#ef4444]' : 'text-dark/35' }}"
                                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
                            </svg>
                            {{ $streak['jumlah'] }} hari
                        </span>
                    </div>

                    <h1 class="mt-2 text-xl font-extrabold text-dark sm:text-2xl">
                        Halo, {{ $pengguna->nama }} &#128075;
                    </h1>

                    <p class="mt-1.5 text-xs leading-relaxed text-dark/60 sm:text-sm">
                        Selamat datang di KelasKita! Terus semangat belajar
                        agar makin hebat dan berkembang.
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a href="{{ route('user.materi') }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-primary px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-primary/25 transition hover:bg-primary-dark">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                            Mulai Belajar
                        </a>

                        <a href="{{ route('user.quiz') }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3.5 py-2 text-xs font-semibold text-primary transition hover:bg-white">
                            Kerjakan Quiz
                        </a>
                    </div>
                </div>

                <div class="relative flex items-center gap-4 sm:justify-end">
                    <img src="{{ asset('images/cover.png') }}" alt="Ilustrasi siswa belajar"
                        class="h-28 max-w-full shrink-0 object-contain object-bottom transition-transform duration-500 group-hover:scale-105 sm:h-[4.5cm] md:-mr-8">

                    <p class="hidden text-right font-hand text-base leading-tight text-primary md:-mt-[2.5cm] md:block">
                        Sedikit demi sedikit,<br>
                        pasti jadi luar biasa! &#10024;
                    </p>
                </div>
            </section>

            {{-- Statistik --}}
            <div class="mt-6 flex items-center justify-between">
                <p class="font-mono text-[10px] font-semibold tracking-[0.14em] text-dark/40 uppercase">
                    Ringkasan Belajar
                </p>

                <p class="text-[11px] text-dark/40">Diperbarui hari ini</p>
            </div>

            <div data-reveal-stagger class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($ringkasan as $stat)
                    @php $warna = $warnaKartu[$stat['warna']]; @endphp

                    <div
                        class="group relative overflow-hidden rounded-2xl border p-4 shadow-[0_1px_2px_rgba(33,26,58,0.03)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_30px_-16px_rgba(33,26,58,0.2)] {{ $warna['latar'] }}">
                        <span
                            class="pointer-events-none absolute -top-8 -right-8 h-20 w-20 rounded-full transition-transform duration-500 group-hover:scale-150 {{ $warna['blob'] }}"></span>

                        <span
                            class="absolute inset-x-4 top-0 h-1 rounded-b-full bg-gradient-to-r {{ $warna['bar'] }}"></span>

                        <span
                            class="relative flex h-9 w-9 items-center justify-center rounded-full shadow-[0_2px_8px_-3px_rgba(33,26,58,0.25)] {{ $warna['chip'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['ikon'] }}" />
                            </svg>
                        </span>

                        <p class="relative mt-3 text-[13px] font-medium text-dark/55">{{ $stat['label'] }}</p>

                        <p class="relative mt-0.5 text-2xl font-extrabold text-dark">{{ $stat['nilai'] }}</p>

                        <p class="relative mt-1 flex items-center gap-1 text-[11px] text-dark/45">
                            <svg class="h-3.5 w-3.5 shrink-0 text-[#4f9e74]" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18 9 11.25l4.306 4.307a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22" />
                            </svg>
                            {{ $stat['perubahan'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Aksi cepat, materi terbaru, quiz terbaru --}}
            <x-dashboard.aksi :daftar="$aksiCepat" />

            <x-dashboard.materi-terbaru :daftar="$materiTerbaru"
                :tautan="route('user.materi')" />

            <x-dashboard.quiz-terbaru :daftar="$quizTerbaru"
                :tautan="route('user.quiz')" />
        </div>

        {{-- Sidebar --}}
        <x-dashboard.sidebar :akses-cepat="$aksesCepat" :jadwal="$jadwal"
            :tautan-jadwal="route('user.jadwal')"
            :peringkat="$peringkat" :kalender="$kalender" />
    </div>
@endsection
