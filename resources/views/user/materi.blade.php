@extends('layouts.app')

@section('title', 'Materi | KelasKita')

@section('content')
    <div data-reveal>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">Materi</h1>

        <p class="mt-1 text-dark/60">Semua materi belajar yang tersedia untukmu.</p>
    </div>

    <div data-reveal
        class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-lavender bg-white px-6 py-16 text-center">
        <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-brand-bg text-dark/30">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
        </span>

        <h2 class="mt-5 text-lg font-bold text-dark">Belum ada materi</h2>

        <p class="mt-1 max-w-sm text-sm text-dark/50">
            Materi akan ditambahkan oleh admin. Halaman ini sudah siap menampilkan daftar materi.
        </p>
    </div>
@endsection
