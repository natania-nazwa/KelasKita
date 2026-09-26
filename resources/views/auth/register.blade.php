@extends('layouts.guest')

@section('title', 'Daftar | KelasKita')

@section('content')
    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12">

        {{-- Dekorasi background --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-24 -left-20 w-96 h-96 rounded-full bg-lavender/60"></div>
            <div class="absolute -bottom-28 -right-16 w-96 h-96 rounded-full bg-[#f0ebff]"></div>
        </div>

        {{-- Brand --}}
        <a href="{{ url('/') }}" class="group mb-8 flex items-center gap-3">
            <span
                class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl transition-transform duration-300 group-hover:-rotate-3 group-hover:scale-105"
            >
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="Logo KelasKita"
                    class="h-full w-full object-contain"
                />
            </span>

            <span>
                <span class="block font-display text-2xl font-extrabold leading-none text-dark">
                    <span class="text-primary">Kelas</span>kita
                </span>

                <span class="mt-1.5 block text-[11px] font-light tracking-wide text-dark/50">
                    Belajar • Latihan • Naik Level
                </span>
            </span>
        </a>

        {{-- Kartu --}}
        <div
            data-reveal
            class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl shadow-dark/5 ring-1 ring-lavender"
        >
            <span
                class="misi-bar block h-1.5 w-full bg-gradient-to-r from-white via-primary to-primary-dark"
            ></span>

            <div class="p-8 sm:p-10">
                <h1 class="text-center text-2xl font-extrabold text-dark sm:text-3xl">Buat Akun Baru</h1>

                <p class="mt-2 text-center text-sm text-dark/60">
                    Bergabung dan mulai belajar bersama KelasKita.
                </p>

                <form action="#" method="POST" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold text-dark">Nama</label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-dark/35"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </span>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                required
                                autocomplete="name"
                                placeholder="Nama lengkapmu"
                                class="w-full rounded-xl border border-dark/10 bg-brand-bg py-3 pl-11 pr-4 text-sm text-dark transition placeholder:text-dark/40 focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-dark">Email</label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-dark/35"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                autocomplete="email"
                                placeholder="nama@email.com"
                                class="w-full rounded-xl border border-dark/10 bg-brand-bg py-3 pl-11 pr-4 text-sm text-dark transition placeholder:text-dark/40 focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-dark">Kata Sandi</label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-dark/35"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                class="w-full rounded-xl border border-dark/10 bg-brand-bg py-3 pl-11 pr-4 text-sm text-dark transition placeholder:text-dark/40 focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-dark">
                            Konfirmasi Kata Sandi
                        </label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-dark/35"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Ulangi kata sandi"
                                class="w-full rounded-xl border border-dark/10 bg-brand-bg py-3 pl-11 pr-4 text-sm text-dark transition placeholder:text-dark/40 focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-primary px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Daftar
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-dark/60">
                    Sudah punya akun?
                    <a
                        href="{{ url('/login') }}"
                        class="font-semibold text-primary transition-colors hover:text-primary-dark"
                    >
                        Masuk
                    </a>
                </p>
            </div>
        </div>

        <a
            href="{{ url('/') }}"
            class="mt-6 inline-flex items-center gap-2 text-sm text-dark/50 transition-colors hover:text-primary"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>

            Kembali ke Beranda
        </a>
    </div>
@endsection
