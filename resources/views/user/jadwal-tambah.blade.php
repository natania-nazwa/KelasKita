@extends('layouts.app')

@section('title', ($jadwal ?? null ? 'Edit' : 'Tambah').' Jadwal | KelasKita')

@section('content')
    {{--
        Halaman "Tambah Jadwal" sekaligus "Edit Jadwal".

        Dua mode ini sengaja satu halaman dan satu komponen (x-jadwal.form):
        isian, tujuan form, dan isi tombol yang berubah mengikuti $jadwal.
        Tanpa itu, mengedit akan punya form kedua yang bisa menyimpang dari
        form tambah.

        min-h-[100dvh], bukan 100dvh dikurangi 4rem seperti halaman lain:
        halaman ini tidak memakai top bar, jadi tinggi yang tersedia memang
        satu layar penuh.
    --}}
    @php
        $jadwal = $jadwal ?? null;
        $modeEdit = $jadwal !== null;
    @endphp

    <div class="kanvas-jadwal -m-6 min-h-[100dvh] p-5 sm:p-6 lg:-m-10 lg:p-6 xl:p-8">
        <div class="mx-auto w-full max-w-3xl">

            {{-- =========================
                 BREADCRUMB
            ========================== --}}
            <nav aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                    <li>
                        <a href="{{ route('user.dashboard') }}" class="text-muted transition hover:text-ungu">
                            Home
                        </a>
                    </li>

                    <li class="text-ungu-soft" aria-hidden="true">/</li>

                    <li>
                        <a href="{{ route('user.jadwal') }}" class="text-muted transition hover:text-ungu">
                            Jadwal
                        </a>
                    </li>

                    <li class="text-ungu-soft" aria-hidden="true">/</li>

                    <li class="text-dark" aria-current="page">
                        {{ $modeEdit ? 'Edit Jadwal' : 'Tambah Jadwal' }}
                    </li>
                </ol>
            </nav>

            {{-- =========================
                 HEADER HALAMAN
            ========================== --}}
            <header class="mt-4 flex items-start gap-4">
                <span class="mt-0.5 flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-ungu-line bg-ungu-bg text-ungu sm:h-14 sm:w-14"
                    aria-hidden="true">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" stroke-width="1.7"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                        {{ $modeEdit ? 'Edit Jadwal' : 'Tambah Jadwal' }}
                    </h1>

                    <p class="mt-1 text-sm leading-relaxed text-muted sm:text-base">
                        {{ $modeEdit
                            ? 'Perbaiki jam, pelajaran, atau tugas untuk jadwal ini. Jadwal tetap milikmu.'
                            : 'Tambahkan satu jam pelajaran ke jadwalmu. Jadwal ini hanya terlihat olehmu.' }}
                    </p>
                </div>
            </header>

            {{-- Ringkasan galat di atas form, supaya yang gagal tidak cuma
                 terlihat kalau scrolled sampai ke field-nya. --}}
            @if ($errors->any())
                <div role="alert"
                    class="mt-5 flex items-start gap-3 rounded-2xl border border-[#f3c9cb] bg-[#fdecee] px-4 py-3 text-sm font-medium text-[#a8323c]">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>

                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form id="form-jadwal" method="POST" class="mt-5"
                action="{{ $modeEdit ? route('user.jadwal.update', $jadwal->getKey()) : route('user.jadwal.tambah.store') }}">
                @csrf

                @if ($modeEdit)
                    @method('PUT')
                @endif

                <x-jadwal.form :jadwal="$jadwal" :hari-aktif="$hariAktif ?? null" />
            </form>

            {{-- =========================
                 KARTU AKSI

                 Terpisah dari kartu form, tapi tetap menunjuk ke form yang
                 sama lewat atribut form="form-jadwal", jadi tidak ada form
                 kedua yang harus mengirim datanya sendiri. Bentuknya
                 disamakan dengan kartu form di atasnya supaya keduanya
                 terbaca sebagai satu blok, bukan sebagai baris lengket di
                 tepi layar.
            ========================== --}}
            <section class="kartu-form mt-4">
                <div class="kartu-form__badan">
                    <p class="text-xs leading-relaxed text-muted">
                        {{ $modeEdit
                            ? 'Perubahanmu langsung tersimpan ke jadwal ini dan muncul di halaman Jadwal.'
                            : 'Jadwal langsung tersimpan dan muncul di halaman Jadwal.' }}
                    </p>

                    <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('user.jadwal') }}" class="tombol-garis justify-center">
                            Batal
                        </a>

                        <button type="submit" form="form-jadwal" class="tombol-utama justify-center">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.5 12.75 12 4.5l7.5 8.25M6 19.5h12a2.25 2.25 0 0 0 2.25-2.25V9.108a2.25 2.25 0 0 0-.659-1.591l-7.5-6.636a2.25 2.25 0 0 0-3.182 0l-7.5 6.636A2.25 2.25 0 0 0 4.5 9.108v8.142A2.25 2.25 0 0 0 6.75 19.5Z" />
                            </svg>

                            {{ $modeEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
