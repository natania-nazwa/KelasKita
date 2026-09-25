@extends('layouts.guest')

@section('title', 'Daftar | KelasKita')

@section('content')

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">

    <a href="{{ url('/') }}" class="flex items-center gap-2.5 mb-8">
        <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-primary text-white">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M9.664 1.319a.75.75 0 0 1 .672 0 41.059 41.059 0 0 1 8.198 5.424.751.751 0 0 1 0 1.141c-1.362 1.151-2.653 2.162-3.828 3.14-.54 1.89-1.892 3.507-3.641 5.269a.75.75 0 0 1-1.13 0c-1.75-1.762-3.101-3.379-3.641-5.269C4.669 10.045 3.378 9.034 2.016 7.883a.75.75 0 0 1 0-1.141 41.06 41.06 0 0 1 7.648-5.423ZM8.5 6.375a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd"/>
            </svg>
        </span>
        <span class="text-xl font-bold text-dark">KelasKita</span>
    </a>

    <div class="w-full max-w-md rounded-3xl bg-white border border-lavender shadow-xl shadow-dark/5 p-8 sm:p-10">
        <h1 class="text-2xl font-extrabold text-dark">Buat Akun Baru</h1>
        <p class="mt-2 text-sm text-dark/60">Bergabung dan mulai belajar bersama KelasKita.</p>

        <form action="#" method="POST" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-dark">Nama</label>
                <input type="text" id="name" name="name" required autocomplete="name" placeholder="Nama lengkapmu"
                    class="mt-2 w-full rounded-xl border border-dark/10 bg-brand-bg px-4 py-2.5 text-sm text-dark placeholder:text-dark/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-dark">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="nama@email.com"
                    class="mt-2 w-full rounded-xl border border-dark/10 bg-brand-bg px-4 py-2.5 text-sm text-dark placeholder:text-dark/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-dark">Kata Sandi</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter"
                    class="mt-2 w-full rounded-xl border border-dark/10 bg-brand-bg px-4 py-2.5 text-sm text-dark placeholder:text-dark/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-dark">Konfirmasi Kata Sandi</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi"
                    class="mt-2 w-full rounded-xl border border-dark/10 bg-brand-bg px-4 py-2.5 text-sm text-dark placeholder:text-dark/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <button type="submit"
                class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                Daftar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-dark/60">
            Sudah punya akun?
            <a href="{{ url('/login') }}" class="font-semibold text-primary hover:text-primary-dark">Masuk</a>
        </p>
    </div>

    <a href="{{ url('/') }}" class="mt-6 inline-flex items-center gap-2 text-sm text-dark/50 hover:text-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Kembali ke Beranda
    </a>
</div>

@endsection