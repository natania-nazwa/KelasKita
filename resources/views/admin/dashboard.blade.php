@extends('layouts.admin')

@section('title', 'Dashboard Admin | KelasKita')

@section('content')

<div>
    <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">Dashboard Admin</h1>
    <p class="mt-1 text-dark/60">Kelola pengguna, materi, dan quiz KelasKita di sini.</p>
</div>

<div class="mt-8 grid sm:grid-cols-2 xl:grid-cols-4 gap-5">
    <div class="rounded-2xl bg-white border border-lavender p-6">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
            </svg>
        </span>
        <p class="mt-5 text-3xl font-extrabold text-dark tabular-nums">{{ $jumlahPengguna }}</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Total Pengguna</p>
    </div>

    <div class="rounded-2xl bg-white border border-lavender p-6">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
            </svg>
        </span>
        <p class="mt-5 text-3xl font-extrabold text-dark tabular-nums">{{ $jumlahMateri }}</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Total Materi</p>
    </div>

    <div class="rounded-2xl bg-white border border-lavender p-6">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        </span>
        <p class="mt-5 text-3xl font-extrabold text-dark tabular-nums">{{ $jumlahQuiz }}</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Total Quiz</p>
    </div>

    <div class="rounded-2xl bg-white border border-lavender p-6">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        </span>
        <p class="mt-5 text-3xl font-extrabold text-dark tabular-nums">{{ $jumlahMateriMenunggu + $jumlahQuizMenunggu }}</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Menunggu Review</p>
        <p class="mt-1 text-xs text-dark/50">{{ $jumlahMateriMenunggu }} materi &middot; {{ $jumlahQuizMenunggu }} quiz</p>
        <a href="{{ route('admin.materi') }}"
            class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary-dark">
            Tinjau materi
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </a>
    </div>
</div>

<div class="mt-10">
    <div class="flex items-center justify-between">
        <h2 class="text-lg sm:text-xl font-bold text-dark">Aktivitas Terbaru</h2>
        <a href="{{ route('admin.materi') }}" class="text-sm font-semibold text-primary hover:text-primary-dark">Lihat Semua</a>
    </div>

    <div class="mt-5 rounded-2xl bg-white border border-lavender overflow-hidden">
        <div class="hidden sm:flex items-center gap-4 px-6 py-3 bg-brand-bg text-xs font-bold text-dark/50 uppercase tracking-wide">
            <span class="flex-1">Kegiatan</span>
            <span class="w-32 text-right">Waktu</span>
        </div>

        <div class="divide-y divide-dark/5">
            @forelse ($aktivitas as $item)
                <div class="flex items-center gap-4 px-6 py-4">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-lavender text-primary shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($item['ikon']) }}" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-dark truncate">{{ $item['judul'] }}</p>
                        <p class="text-xs text-dark/50">{{ $item['keterangan'] }} &middot; {{ $item['status'] }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-dark/50 w-32 text-right">{{ $item['waktu'] }}</span>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-dark/50">Belum ada materi yang pernah dibuat.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection