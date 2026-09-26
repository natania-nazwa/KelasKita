@extends('layouts.app')

@section('title', 'Dashboard | KelasKita')

@section('content')
    <div data-reveal>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">
            Halo, {{ $pengguna->nama }} 👋
        </h1>

        <p class="mt-1 text-dark/60">Selamat datang kembali di KelasKita.</p>
    </div>

    {{-- Ringkasan --}}
    <div data-reveal-stagger class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Materi Dipelajari', 'ikon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
            ['label' => 'Quiz Dikerjakan', 'ikon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['label' => 'Nilai Rata-rata', 'ikon' => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.563.563 0 0 0-.182-.557l-4.204-3.602a.563.563 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z'],
            ['label' => 'Materi Selesai', 'ikon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ] as $stat)
            <div class="rounded-2xl bg-white border border-lavender p-6">
                <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['ikon'] }}" />
                    </svg>
                </span>

                <p class="mt-5 text-3xl font-extrabold text-dark">0</p>

                <p class="mt-1 text-sm font-medium text-dark/60">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Aktivitas --}}
    <div class="mt-10 grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl bg-white border border-lavender p-6">
            <h2 class="text-lg font-bold text-dark">Aktivitas Terakhir</h2>

            <div class="mt-5 flex flex-col items-center rounded-xl border border-dashed border-lavender px-6 py-12 text-center">
                <span class="flex items-center justify-center w-12 h-12 rounded-2xl bg-brand-bg text-dark/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>

                <p class="mt-4 text-sm font-semibold text-dark">Belum ada aktivitas</p>

                <p class="mt-1 text-sm text-dark/50">Mulai dengan membuka materi pilihanmu.</p>

                <a href="{{ route('user.materi') }}"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
                    Lihat Materi
                </a>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-lavender p-6">
            <h2 class="text-lg font-bold text-dark">Menu Cepat</h2>

            <div class="mt-5 space-y-2">
                @foreach([
                    ['route' => 'user.materi', 'label' => 'Materi'],
                    ['route' => 'user.quiz', 'label' => 'Quiz'],
                    ['route' => 'user.profil', 'label' => 'Profil'],
                ] as $menu)
                    <a href="{{ route($menu['route']) }}"
                        class="flex items-center justify-between rounded-xl bg-brand-bg px-4 py-3 text-sm font-semibold text-dark transition hover:bg-lavender">
                        {{ $menu['label'] }}

                        <span class="text-primary">&rarr;</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
