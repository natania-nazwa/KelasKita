@extends('layouts.app')

@section('title', 'Quiz | KelasKita')

@section('content')
    <div data-reveal>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">Quiz</h1>

        <p class="mt-1 text-dark/60">Uji pemahamanmu dengan soal-soal interaktif.</p>
    </div>

    <div data-reveal
        class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-lavender bg-white px-6 py-16 text-center">
        <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-brand-bg text-dark/30">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </span>

        <h2 class="mt-5 text-lg font-bold text-dark">Belum ada quiz</h2>

        <p class="mt-1 max-w-sm text-sm text-dark/50">
            Quiz yang sudah tersedia akan muncul di sini. Halaman ini sudah siap terhubung ke data quiz.
        </p>
    </div>
@endsection
