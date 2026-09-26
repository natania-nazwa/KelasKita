@extends('layouts.app')

@section('title', 'Profil | KelasKita')

@section('content')
    <div data-reveal>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">Profil</h1>

        <p class="mt-1 text-dark/60">Kelola informasi akunmu.</p>
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-3">

        {{-- Kartu identitas --}}
        <div data-reveal
            class="rounded-2xl bg-white border border-lavender p-6 text-center lg:col-span-1">
            <span
                class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-brand-bg">
                <img src="{{ asset('images/logo.png') }}" alt="Foto Profil"
                    class="h-full w-full object-contain">
            </span>

            <h2 class="mt-4 text-lg font-extrabold text-dark">{{ $pengguna->nama }}</h2>

            <p class="mt-1 truncate text-sm text-dark/50">{{ $pengguna->email }}</p>

            {{-- Peran ditampilkan sebagai badge, bukan untuk diedit --}}
            <span
                class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-lavender px-3 py-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                {{ $pengguna->isAdmin() ? 'Admin' : 'User' }}
            </span>

            <button type="button"
                class="mt-6 w-full rounded-xl border-2 border-primary/20 px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-[#f1ecff]">
                Ubah Foto
            </button>
        </div>

        {{-- Detail akun --}}
        <div data-reveal style="--reveal-delay: 100ms"
            class="rounded-2xl bg-white border border-lavender p-6 lg:col-span-2">
            <h2 class="text-lg font-bold text-dark">Informasi Akun</h2>

            <dl class="mt-5 divide-y divide-lavender">
                @foreach([
                    ['label' => 'Nama Lengkap', 'nilai' => $pengguna->nama],
                    ['label' => 'Email', 'nilai' => $pengguna->email],
                    ['label' => 'Peran', 'nilai' => $pengguna->isAdmin() ? 'Admin' : 'User'],
                    ['label' => 'Bergabung', 'nilai' => $pengguna->created_at?->translatedFormat('d F Y')],
                ] as $baris)
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <dt class="text-sm text-dark/55">{{ $baris['label'] }}</dt>

                        <dd class="truncate text-sm font-semibold text-dark">{{ $baris['nilai'] ?? '-' }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 rounded-xl bg-brand-bg px-4 py-3.5">
                <p class="text-xs leading-relaxed text-dark/55">
                    Perubahan data akun belum tersedia. Halaman ini sudah siap dihubungkan ke form ubah profil.
                </p>
            </div>
        </div>
    </div>
@endsection
