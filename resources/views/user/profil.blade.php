@extends ('layouts.app')

@section ('title', 'Profil | KelasKita')

@section ('content')
    <div data-reveal class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold text-dark sm:text-3xl">Profil</h1>

            <p class="mt-1 text-dark/60">Kelola informasi akun dan preferensi kamu.</p>
        </div>

        {{-- Top bar halaman ini disembunyikan (lihat $sembunyiTopbar di
             layouts/app), jadi "Kembali" menggantikan cara pulang biasa:
             tombolnya duduk di kanan atas, lurus dengan judul. --}}
        <a href="{{ route('user.dashboard') }}"
            class="tombol-garis ml-auto shrink-0 self-start py-2">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>

            Kembali
        </a>
    </div>

    {{-- Kabar berhasil. Semua aksi di halaman ini (simpan profil, hapus
        foto, ubah password) berakhir dengan redirect ke halaman Profil,
        jadi session ini juga jadi pemberitahuan hasil aksi tersebut. --}}
    @if (session('sukses'))
        <div
            data-reveal
            class="mt-6 flex items-start gap-3 rounded-2xl border border-lavender bg-white px-4 py-3 text-sm text-dark shadow-[0_14px_30px_-26px_rgba(33,26,58,0.5)]"
        >
            <span
                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                aria-hidden="true"
            >
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.4"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </span>

            <p class="min-w-0 font-medium">{{ session('sukses') }}</p>
        </div>
    @endif

    @include ('user.partials.profil.kepala')

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        @include ('user.partials.profil.akun')

        @include ('user.partials.profil.keamanan')
    </div>

    @include ('user.partials.profil.tampilan')

    @include ('user.partials.profil.bahaya')

    {{-- =========================
         DIALOG
    ==========================
         Keempat dialog halaman ini dirender di sini, di luar kartu mana
         pun, supaya posisinya tidak ikut bergeser mengikuti grid dan
         tidak pernah terpotong overflow kartu. --}}
    <x-profil.edit :pengguna="$pengguna" />

    <x-profil.kata-sandi />

    <x-profil.hapus-foto />

    <x-profil.hapus-akun />

    {{-- Toast ringan untuk aksi yang belum punya endpoint server. --}}
    <div
        data-toast
        hidden
        class="pointer-events-none fixed inset-x-4 bottom-24 z-[80] mx-auto w-fit max-w-[calc(100%-2rem)] rounded-2xl border border-lavender bg-white px-4 py-3 text-sm font-medium text-dark shadow-[0_24px_44px_-24px_rgba(33,26,58,0.55)] sm:inset-x-auto sm:right-6 lg:bottom-6"
        role="status"
        aria-live="polite"
    ></div>
@endsection
