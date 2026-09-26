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

        $warnaMateri = [
            'pink' => 'bg-[#fce7f3] text-[#be185d]',
            'ungu' => 'bg-lavender text-primary',
            'merah' => 'bg-[#fee2e2] text-[#b91c1c]',
        ];
    @endphp

    {{-- Search + Profil --}}
    <div class="-mt-4 flex items-center gap-4 lg:-mt-6">
        <form action="{{ route('user.materi') }}" method="GET" class="relative min-w-0 flex-1">
            <svg
                class="pointer-events-none absolute top-1/2 left-4 w-4 h-4 -translate-y-1/2 text-dark/35"
                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>

            <input type="search" name="q" placeholder=" Cari materi, quiz, atau topik..."
                class="w-full rounded-full border border-lavender bg-brand-bg py-2.5 pr-4 pl-11 text-sm text-dark placeholder:text-dark/35 focus:border-primary focus:ring-2 focus:ring-primary/15 focus:outline-none">
        </form>

        <div class="flex shrink-0 items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-lavender">
                <img src="{{ asset('images/logo.png') }}" alt="Foto {{ $pengguna->nama }}"
                    class="h-full w-full object-contain">
            </span>

            <div class="hidden min-w-0 sm:block">
                <p class="truncate text-sm font-semibold text-dark">{{ $pengguna->nama }}</p>

                <p class="text-[11px] font-medium text-dark/50">
                    {{ $pengguna->isAdmin() ? 'Admin' : 'User' }}
                </p>
            </div>
        </div>
    </div>

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

    {{-- Lanjutkan Belajar + Materi Terbaru --}}
    <div class="mt-6 flex items-center justify-between">
        <p class="font-mono text-[10px] font-semibold tracking-[0.14em] text-dark/40 uppercase">
            Belajar
        </p>

        <a href="{{ route('user.materi') }}"
            class="text-[11px] font-semibold text-primary transition hover:text-primary-dark">
            Lihat semua materi
        </a>
    </div>

    <div data-reveal-stagger class="mt-3 grid gap-5 lg:grid-cols-5">
        <section
            class="rounded-2xl border border-lavender bg-gradient-to-br from-white to-brand-bg p-5 shadow-[0_1px_2px_rgba(33,26,58,0.04)] transition duration-300 hover:shadow-[0_14px_34px_-20px_rgba(33,26,58,0.22)] lg:col-span-3">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-lavender text-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>

                    <h2 class="text-lg font-bold text-dark">Lanjutkan Belajar</h2>
                </div>

                <a href="{{ route('user.materi') }}"
                    class="flex shrink-0 items-center gap-1 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-primary shadow-[0_1px_2px_rgba(33,26,58,0.05)] transition hover:bg-primary hover:text-white">
                    Lihat Materi
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            <div
                class="mt-4 flex items-center gap-4 rounded-2xl border border-lavender bg-gradient-to-r from-lavender/70 via-white to-brand-bg p-4 transition duration-300 hover:shadow-[0_10px_26px_-18px_rgba(33,26,58,0.3)]">
                <span
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#a66bea] to-primary text-white shadow-lg shadow-primary/25">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $lanjutkan['ikon'] }}" />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <span
                        class="inline-flex items-center rounded-full bg-white/80 px-2.5 py-0.5 font-mono text-[10px] font-semibold tracking-[0.14em] text-primary uppercase">
                        Sedang dipelajari
                    </span>

                    <h3 class="mt-1.5 truncate text-base font-bold text-dark">{{ $lanjutkan['judul'] }}</h3>

                    <p class="mt-0.5 text-xs leading-relaxed text-dark/55">{{ $lanjutkan['deskripsi'] }}</p>

                    <div class="mt-3 flex items-center gap-3">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/80">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#a66bea] to-primary transition-all duration-700"
                                style="width: {{ $lanjutkan['progress'] }}%"></div>
                        </div>

                        <span
                            class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-primary shadow-[0_1px_2px_rgba(33,26,58,0.05)]">{{ $lanjutkan['progress'] }}%</span>
                    </div>
                </div>

                <a href="{{ route('user.materi') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/25 transition hover:bg-primary-dark"
                    aria-label="Lanjut belajar {{ $lanjutkan['judul'] }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </section>

        <section
            class="rounded-2xl border border-lavender bg-gradient-to-br from-white to-brand-bg p-5 shadow-[0_1px_2px_rgba(33,26,58,0.04)] transition duration-300 hover:shadow-[0_14px_34px_-20px_rgba(33,26,58,0.22)] lg:col-span-2">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-lavender text-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>

                <h2 class="text-lg font-bold text-dark">Materi Terbaru</h2>
            </div>

            <div class="mt-3 space-y-1.5">
                @foreach ($materiTerbaru as $materi)
                    <a href="{{ route('user.materi') }}"
                        class="group flex items-center gap-3 rounded-2xl border border-transparent px-3 py-2.5 transition duration-300 hover:border-lavender hover:bg-white hover:shadow-[0_10px_26px_-18px_rgba(33,26,58,0.3)]">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-mono text-[11px] font-bold shadow-[0_1px_2px_rgba(33,26,58,0.05)] {{ $warnaMateri[$materi['warna']] }}">
                            {{ $materi['kode'] }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-dark">{{ $materi['judul'] }}</p>

                            <p class="truncate text-xs text-dark/50">{{ $materi['kategori'] }}</p>
                        </div>

                        <span
                            class="hidden shrink-0 items-center gap-1 rounded-full bg-brand-bg px-2 py-1 text-[11px] text-dark/45 sm:flex">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ $materi['waktu'] }}
                        </span>

                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-lavender/60 text-primary transition duration-300 group-hover:bg-primary group-hover:text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection