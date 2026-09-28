@extends('layouts.app')

@section('title', 'Masukkan Kode | KelasKita')

@section('content')
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        <a href="{{ route('user.dashboard') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>

            Kembali ke dashboard
        </a>

        <div class="mx-auto mt-4 w-full max-w-lg">
            <section data-reveal="zoom" class="lobi-kartu overflow-hidden">

                {{-- Kepala: ikon kode + judul "Gabung Quiz". --}}
                <div class="lobi-kartu__kepala">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="dash-ikon-kotak" style="--k: #8b80e6; --k-gelap: #5a4cc9;" aria-hidden="true">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kode') }}" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-extrabold tracking-tight text-dark">Gabung Quiz</h1>

                            <p class="text-xs text-dark/50">Masukkan kode quiz untuk bergabung.</p>
                        </div>
                    </div>
                </div>

                <div class="lobi-kartu__badan">
                    <p class="text-sm leading-relaxed text-dark/60">
                        Masukkan kode quiz untuk bergabung ke dalam quiz. Kamu akan menunggu di ruang
                        sampai pembuat quiz memulai.
                    </p>

                    <form method="POST" action="{{ route('user.sesi.gabung.store') }}" class="mt-5">
                        @csrf

                        <label for="kode" class="label-form">Kode Quiz</label>

                        <input type="text" id="kode" name="kode" value="{{ old('kode', $kode) }}"
                            placeholder="Masukkan kode quiz" autocomplete="off" autocapitalize="characters"
                            spellcheck="false" inputmode="text" maxlength="10" required
                            class="kolom-form mt-1.5 font-mono tracking-[0.2em] uppercase"
                            @error('kode') aria-invalid="true" aria-describedby="kode-galat" @enderror>

                        @error('kode')
                            <p id="kode-galat" role="alert" class="mt-2 flex items-start gap-1.5 text-xs font-medium text-[#a8323c]">
                                <svg class="mt-px h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>

                                {{ $message }}
                            </p>
                        @enderror

                        <button type="submit" class="tombol-utama mt-5 w-full">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('tambah') }}" />
                            </svg>

                            Gabung
                        </button>
                    </form>

                    {{-- Penjelasan singkat cara kerjanya, supaya peserta tahu
                         harus menunggu di lobby dan tidak boleh langsung
                         membuka soal. --}}
                    <div class="kartu-tips mt-5">
                        <p class="flex items-center gap-2 text-xs font-bold text-ungu-dark">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>

                            Cara kerjanya
                        </p>

                        <ol class="mt-2 space-y-1.5 text-xs leading-relaxed text-dark/60">
                            <li class="flex gap-2">
                                <span class="font-mono font-bold text-ungu">1.</span>
                                Masukkan kode yang diberikan pembuat quiz.
                            </li>
                            <li class="flex gap-2">
                                <span class="font-mono font-bold text-ungu">2.</span>
                                Kamu masuk ke lobby dan melihat siapa saja yang sudah bergabung.
                            </li>
                            <li class="flex gap-2">
                                <span class="font-mono font-bold text-ungu">3.</span>
                                Soal baru bisa dibuka setelah pembuat menekan Mulai Quiz.
                            </li>
                        </ol>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
