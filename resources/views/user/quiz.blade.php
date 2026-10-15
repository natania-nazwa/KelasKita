@extends('layouts.app')

@section('title', 'Quiz | KelasKita')

@section('content')
    {{-- min-h: tinggi layar dikurangi top bar (4rem), supaya latar
         bertekstur tetap menutup layar tanpa memaksa scroll. --}}
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- Toast hasil aksi dipindahkan ke layouts/app.blade.php. --}}

        {{-- =========================
             KEPALA + FILTER KATEGORI
        ==========================
             Kepala, angka ringkas, dan filter kategori semuanya tinggal
             di satu papan ungu (x-quiz.kepala), jadi tidak ada area
             putih kosong di antara bagian atas halaman dan daftar quiz.
             Kolom carinya sendiri sudah pindah ke top bar dan baris
             kedua header mobile, sehingga tidak ada lagi kotak
             "Cari quiz..." yang menduplikasi pencarian di sini. --}}
        <x-quiz.kepala :total-quiz="$totalQuiz" :total-soal="$totalSoal"
            :jumlah-kategori="$totalKategori" :kategori="$kategori"
            :kategori-aktif="$kategoriAktif" :kata-kunci="$kataKunci" />

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
            {{-- Baris pengantar di atas grid: membuat jarak antar blok
                 terasa disengaja, bukan sekadar jarak dari kartu. --}}
            <div data-reveal class="mt-7 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-bold tracking-tight text-dark">
                    Daftar Quiz
                </h2>

                <p class="text-xs text-dark/45">
                    Menampilkan {{ $quiz->firstItem() }}&ndash;{{ $quiz->lastItem() }}
                    dari {{ $quiz->total() }} quiz
                </p>
            </div>

            <div class="mt-4">
                <x-quiz.grid :daftar="$daftar" :kata-kunci="$kataKunci" />
            </div>

            <div data-quiz-navigasi class="mt-8">
                {{ $quiz->links() }}
            </div>
        @endif
    </div>
@endsection
