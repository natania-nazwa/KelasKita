@extends('layouts.app')

@section('title', 'Quiz | KelasKita')

@section('content')
    {{-- min-h: tinggi layar dikurangi top bar (4rem), supaya latar
         bertekstur tetap menutup layar tanpa memaksa scroll. --}}
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- Kabar berhasil membuat quiz. --}}
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
        <x-quiz.kepala />

        {{-- =========================
             PENCARIAN
        ==========================
             Sengaja tanpa kartu pembungkus: kolom cari sudah punya
             bentuknya sendiri, jadi tidak perlu dilingkari panel putih
             lagi.

             Tidak ada tombol buat di sini: quiz dibuat dan dikelola lewat
             menu "Karya Saya". --}}
        <section data-reveal class="mt-6">
            <x-quiz.cari :kata-kunci="$kataKunci" class="max-w-xl" />
        </section>

        {{-- =========================
             DAFTAR QUIZ
        ==========================
             Rangka (skeleton) selalu ikut di-render supaya initQuizMuat()
             bisa menampilkannya begitu pengguna mengetik atau berpindah
             halaman, tanpa kedipan layout. --}}
        <x-quiz.tulang />

        @if ($daftar === [])
            <x-quiz.kosong :alasan="$alasanKosong" />
        @else
            <div class="mt-5">
                <x-quiz.grid :daftar="$daftar" :kata-kunci="$kataKunci" />
            </div>

            <div data-quiz-navigasi class="mt-8">
                {{ $quiz->links() }}
            </div>
        @endif
    </div>
@endsection
