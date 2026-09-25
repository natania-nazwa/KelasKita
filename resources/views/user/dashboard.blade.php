@extends('layouts.app')

@section('title', 'Dashboard | KelasKita')

@section('content')

<div>
    <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">Halo, Pengguna 👋</h1>
    <p class="mt-1 text-dark/60">Selamat datang kembali di KelasKita.</p>
</div>

<div class="mt-8 grid sm:grid-cols-2 xl:grid-cols-4 gap-5">
    <div class="rounded-2xl bg-white border border-lavender p-6">
        <div class="flex items-center justify-between">
            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                </svg>
            </span>
        </div>
        <p class="mt-5 text-3xl font-extrabold text-dark">12</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Materi Dipelajari</p>
    </div>

    <div class="rounded-2xl bg-white border border-lavender p-6">
        <div class="flex items-center justify-between">
            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </span>
        </div>
        <p class="mt-5 text-3xl font-extrabold text-dark">8</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Quiz Dikerjakan</p>
    </div>

    <div class="rounded-2xl bg-white border border-lavender p-6">
        <div class="flex items-center justify-between">
            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                </svg>
            </span>
        </div>
        <p class="mt-5 text-3xl font-extrabold text-dark">3</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Quiz Dibuat</p>
    </div>

    <div class="rounded-2xl bg-white border border-lavender p-6">
        <div class="flex items-center justify-between">
            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-lavender text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                </svg>
            </span>
        </div>
        <p class="mt-5 text-3xl font-extrabold text-dark">85</p>
        <p class="mt-1 text-sm text-dark/60 font-medium">Nilai Terakhir</p>
    </div>
</div>

<div class="mt-10">
    <div class="flex items-center justify-between">
        <h2 class="text-lg sm:text-xl font-bold text-dark">Quiz Terbaru</h2>
        <a href="#" class="text-sm font-semibold text-primary hover:text-primary-dark">Lihat Semua</a>
    </div>

    <div class="mt-5 grid md:grid-cols-3 gap-5">
        <a href="#" class="rounded-2xl bg-white border border-lavender p-6 hover:shadow-lg hover:shadow-dark/5 transition">
            <span class="text-xs font-semibold text-primary bg-lavender px-3 py-1 rounded-full">Matematika</span>
            <h3 class="mt-4 font-bold text-dark">Quiz Aljabar Dasar</h3>
            <p class="mt-1 text-sm text-dark/50">10 Soal • 15 Menit</p>
            <div class="mt-5 flex items-center justify-between">
                <span class="text-sm font-semibold text-dark">8 Dikerjakan</span>
                <span class="text-sm font-bold text-primary">Mulai →</span>
            </div>
        </a>

        <a href="#" class="rounded-2xl bg-white border border-lavender p-6 hover:shadow-lg hover:shadow-dark/5 transition">
            <span class="text-xs font-semibold text-primary bg-lavender px-3 py-1 rounded-full">IPA</span>
            <h3 class="mt-4 font-bold text-dark">Quiz Tata Surya</h3>
            <p class="mt-1 text-sm text-dark/50">8 Soal • 12 Menit</p>
            <div class="mt-5 flex items-center justify-between">
                <span class="text-sm font-semibold text-dark">5 Dikerjakan</span>
                <span class="text-sm font-bold text-primary">Mulai →</span>
            </div>
        </a>

        <a href="#" class="rounded-2xl bg-white border border-lavender p-6 hover:shadow-lg hover:shadow-dark/5 transition">
            <span class="text-xs font-semibold text-primary bg-lavender px-3 py-1 rounded-full">Bahasa</span>
            <h3 class="mt-4 font-bold text-dark">Quiz Kosakata Inggris</h3>
            <p class="mt-1 text-sm text-dark/50">12 Soal • 20 Menit</p>
            <div class="mt-5 flex items-center justify-between">
                <span class="text-sm font-semibold text-dark">12 Dikerjakan</span>
                <span class="text-sm font-bold text-primary">Mulai →</span>
            </div>
        </a>
    </div>
</div>

@endsection