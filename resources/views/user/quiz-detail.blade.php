@extends('layouts.app')

@section('title', $quiz->judul.' | KelasKita')

@section('content')
    @php
        $kategori = $kartu['kategori'];
        $pembuat = $kartu['pembuat'];
    @endphp

    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        {{-- Kembali ke daftar, mengikuti tab yang tadi dipakai. --}}
        <a href="{{ route('user.quiz') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>

            Kembali ke daftar quiz
        </a>

        {{-- =========================
             KEPALA QUIZ
        ========================== --}}
        <section data-reveal="fade"
            style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
            class="mt-4 overflow-hidden rounded-[1.75rem] border border-lavender bg-white shadow-[0_20px_45px_-34px_rgba(33,26,58,0.4)]">

            <div class="kartu-quiz__gambar !aspect-[21/8]">
                @if (filled($kartu['thumbnail']))
                    <img src="{{ $kartu['thumbnail'] }}" alt="" class="kartu-quiz__foto">
                @else
                    <span class="kartu-quiz__gambar-ikon !text-5xl" aria-hidden="true">{{ $kategori['ikon'] }}</span>
                @endif

                <span class="kartu-quiz__lencana">{{ $kategori['nama'] }}</span>
            </div>

            <div class="p-5 sm:p-6">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Quiz yang belum tayang hanya boleh dilihat pemiliknya,
                         jadi statusnya perlu disebut di sini. --}}
                    @if ($quiz->status !== \App\Models\Quiz::STATUS_PUBLISHED)
                        <span class="lencana bg-lavender text-primary-dark">{{ $kartu['status_label'] }}</span>
                    @endif

                    @if ($kartu['visibilitas'] === \App\Models\Quiz::VISIBILITAS_PRIVAT)
                        <span class="lencana bg-brand-bg text-dark/55">Privat</span>
                    @endif
                </div>

                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                    {{ $kartu['judul'] }}
                </h1>

                @if (filled($kartu['deskripsi']))
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-dark/60">
                        {{ $kartu['deskripsi'] }}
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-2.5">
                        <span class="kartu-quiz__avatar !h-9 !w-9 !text-xs"
                            style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                            aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                        <span class="flex flex-col leading-tight">
                            <span class="text-xs text-dark/45">Dibuat oleh</span>
                            <span class="text-sm font-semibold text-dark">{{ $pembuat['nama'] }}</span>
                        </span>
                    </span>

                    <span class="kartu-quiz__jumlah !px-3 !py-1.5 !text-xs">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>

                        {{ $jumlahSoal }} Soal
                    </span>

                    @if ($kartu['durasi'] > 0)
                        <span class="kartu-quiz__jumlah !px-3 !py-1.5 !text-xs">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>

                            {{ $kartu['durasi'] }} Menit
                        </span>
                    @endif
                </div>
            </div>
        </section>

        {{-- =========================
             DAFTAR SOAL
        ========================== --}}
        <section class="mt-6" aria-label="Soal quiz">
            <h2 class="text-base font-bold text-dark">Soal-soal</h2>

            @if ($soal->isEmpty())
                <div class="mt-3 flex flex-col items-center rounded-[1.5rem] border border-dashed border-lavender bg-white px-6 py-12 text-center">
                    <h3 class="text-base font-bold text-dark">Soalnya belum diisi</h3>

                    <p class="mt-1.5 max-w-sm text-sm text-dark/50">
                        Quiz ini sudah dibuat tapi belum punya soal. Pembuatnya bisa menambahkannya lewat form
                        tambah quiz.
                    </p>
                </div>
            @else
                <ol class="mt-3 space-y-4">
                    @foreach ($soal as $nomor => $item)
                        <li data-reveal
                            class="rounded-2xl border border-lavender bg-white p-5 shadow-[0_1px_2px_rgba(33,26,58,0.04)]">
                            <p class="flex gap-3 text-sm font-semibold leading-relaxed text-dark">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-lavender text-xs font-bold text-primary-dark">
                                    {{ $nomor + 1 }}
                                </span>

                                {{ $item->pertanyaan }}
                            </p>

                            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($item->pilihan() as $huruf => $isi)
                                    <li class="flex items-start gap-2.5 rounded-xl border border-lavender bg-brand-bg px-3 py-2.5 text-sm text-dark/75">
                                        <span class="mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-white text-[11px] font-bold text-primary">
                                            {{ $huruf }}
                                        </span>

                                        {{ $isi }}
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- =========================
             QUIZ LAINNYA
        ========================== --}}
        @if ($rekomendasi !== [])
            <section class="mt-8" aria-label="Quiz lain di kategori ini">
                <h2 class="text-base font-bold text-dark">Quiz lain di {{ $kategori['nama'] }}</h2>

                <div class="mt-3 grid grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rekomendasi as $item)
                        <x-quiz.kartu :quiz="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
