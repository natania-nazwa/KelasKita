@extends('layouts.app')

@section('title', 'Materi Pembelajaran | KelasKita')

@section('content')
    {{-- min-h: tinggi layar dikurangi top bar (4rem), supaya latar
         bertekstur tetap menutup layar tanpa memaksa scroll. --}}
    <div class="kanvas-materi -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- Kabar berhasil menambah materi. --}}
        @if (session('sukses'))
            <div data-reveal
                class="mb-5 flex items-start gap-3 rounded-2xl border border-lavender bg-white px-4 py-3 text-sm text-dark shadow-[0_14px_30px_-26px_rgba(33,26,58,0.5)]">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    aria-hidden="true">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>

                <p class="min-w-0 font-medium">{{ session('sukses') }}</p>
            </div>
        @endif

        {{-- =========================
             JUDUL HALAMAN
        ========================== --}}
        <section data-reveal="fade">
            <span
                class="inline-flex items-center gap-2 rounded-full bg-lavender px-3.5 py-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>

                Pustaka Belajar
            </span>

            <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                Materi Pembelajaran
            </h1>

            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-dark/60">
                Temukan dan pelajari berbagai materi yang dibuat oleh guru maupun teman-temanmu.
            </p>
        </section>

        {{-- =========================
             PENCARIAN + FILTER KATEGORI
        ==========================
             Sengaja tanpa kartu pembungkus: kolom cari dan dropdown
             kategori sudah punya bentuknya sendiri, jadi tidak perlu
             dilingkari panel putih lagi.

             Tidak ada tombol tambah di sini: materi dibuat dan dikelola
             lewat menu "Karya Saya". --}}
        <section data-reveal class="mt-6">
            <x-materi.cari :kategori="$kategori" :kategori-aktif="$kategoriAktif"
                :kata-kunci="$kataKunci" :total-materi="$totalMateri" class="max-w-3xl" />
        </section>

        {{-- =========================
             DAFTAR MATERI
        ========================== --}}
        @if ($daftar === [])
            <div data-reveal
                class="mt-5 flex flex-col items-center overflow-clip rounded-[2rem] border border-lavender bg-white px-6 py-16 text-center shadow-[0_20px_45px_-34px_rgba(33,26,58,0.4)]">
                <span class="relative flex h-16 w-16 items-center justify-center">
                    <span class="absolute inset-0 rounded-3xl bg-lavender/70 blur-xl" aria-hidden="true"></span>

                    <span class="relative flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-bg text-dark/25">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                </span>

                <h2 class="mt-6 text-lg font-bold tracking-tight text-dark">
                    Materi belum ditemukan
                </h2>

                <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-dark/50">
                    @if ($kataKunci !== '')
                        Tidak ada materi yang cocok dengan kata kunci itu. Coba kata yang lebih umum atau pilih kategori lain.
                    @else
                        Belum ada materi pada kategori ini. Admin bisa menambahkannya lewat menu Materi.
                    @endif
                </p>

                <a href="{{ $kataKunci !== '' ? route('user.materi') : route('user.karya-saya') }}"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
                    {{ $kataKunci !== '' ? 'Lihat semua materi' : 'Buat Materi Pertamamu' }}
                </a>
            </div>
        @else
            <div class="mt-5">
                <x-materi.grid :daftar="$daftar" :kata-kunci="$kataKunci" />
            </div>

            <div class="mt-8">
                {{ $materi->links() }}
            </div>
        @endif
    </div>
@endsection
