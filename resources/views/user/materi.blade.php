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
             KEPALA + PENCARIAN
        ==========================
             Kepala, angka ringkas, kolom cari, dan filter kategori
             semuanya tinggal di satu papan ungu muda (x-materi.kepala),
             jadi tidak ada area putih kosong di antara bagian atas
             halaman dan daftar materi.

             Tidak ada tombol tambah di sini: materi dibuat dan dikelola
             lewat menu "Karya Saya". --}}
        <x-materi.kepala :total-materi="$totalMateri" :total-pembuat="$totalPembuat"
            :kategori="$kategori" :kategori-aktif="$kategoriAktif" :kata-kunci="$kataKunci" />

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
            {{-- Baris pengantar di atas grid: membuat jarak antar blok
                 terasa disengaja, bukan sekadar jarak dari kartu. --}}
            <div data-reveal class="mt-7 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-bold tracking-tight text-dark">
                    Daftar Materi
                </h2>

                <p class="text-xs text-dark/45">
                    Menampilkan {{ $materi->firstItem() }}&ndash;{{ $materi->lastItem() }}
                    dari {{ $materi->total() }} materi
                </p>
            </div>

            <div class="mt-4">
                <x-materi.grid :daftar="$daftar" :kata-kunci="$kataKunci" />
            </div>

            <div class="mt-8">
                {{ $materi->links() }}
            </div>
        @endif
    </div>
@endsection
