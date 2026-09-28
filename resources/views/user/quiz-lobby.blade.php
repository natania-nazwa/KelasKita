@extends('layouts.app')

@section('title', ($adalahHost ? 'Lobby Quiz' : 'Menunggu Quiz Dimulai') . ' | KelasKita')

@section('content')
    <div
        class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10"
        data-lobi
        data-tautan-status="{{ route('user.sesi.data', $sesi) }}"
        data-tautan-soal="{{ route('user.sesi.soal', [$sesi, 1]) }}"
        data-tautan-hasil="{{ route('user.sesi.hasil', $sesi) }}"
    >
        <div class="mx-auto w-full max-w-3xl">

            {{-- Kartu judul sesi. --}}
            <section data-reveal="zoom" class="lobi-kartu">
                <div class="lobi-kartu__kepala">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="dash-ikon-kotak" style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};" aria-hidden="true">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalkulator') }}" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-extrabold tracking-tight text-dark">{{ $quiz->judul }}</h1>

                            <p class="text-xs text-dark/50">
                                {{ $jumlahSoal }} soal &middot; Host: {{ $host?->nama ?? 'Tidak diketahui' }}
                            </p>
                        </div>
                    </div>

                    {{-- Status sesi di-poll, jadi atribut data dipakai
                         resources/js/quiz-lobby.js. --}}
                    <span data-lobi-status
                        class="lobi-status {{ $sesi->masihMenunggu() ? 'lobi-status--menunggu' : 'lobi-status--berjalan' }}">
                        <span data-lobi-status-teks>{{ $sesi->labelStatus() }}</span>
                    </span>
                </div>

                <div class="lobi-kartu__badan">

                    {{-- Kode join: hanya host yang butuh melihatnya. --}}
                    @if ($adalahHost)
                        <div class="lobi-kode">
                            <p class="lobi-kode__label">Kode Quiz</p>

                            <strong class="lobi-kode__nilai" data-lobi-kode>{{ $sesi->kode }}</strong>

                            <p class="lobi-kode__petunjuk">Bagikan kode ini kepada peserta.</p>

                            <button type="button" class="lobi-kode__salin" data-lobi-salin
                                aria-label="Salin kode quiz ke papan klip">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V6.75a2.25 2.25 0 0 1 2.25-2.25c.639 0 1.28.135 1.927.184" />
                                </svg>

                                <span data-lobi-salin-teks>Salin Kode</span>
                            </button>
                        </div>
                    @else
                        {{-- Peserta tidak perlu kode; cukup tahu sedang menunggu. --}}
                        <div class="lobi-tunggu" data-lobi-tunggu>
                            <span class="lobi-titik" aria-hidden="true"><i></i><i></i><i></i></span>

                            <p class="text-sm font-bold text-dark">Menunggu host memulai quiz</p>

                            <p class="max-w-sm text-xs leading-relaxed text-dark/55">
                                Kamu sudah bergabung. Soal akan terbuka otomatis begitu
                                <span class="font-semibold text-ungu-dark">{{ $host?->nama ?? 'host' }}</span>
                                menekan tombol Mulai Quiz.
                            </p>
                        </div>
                    @endif

                    {{-- Jumlah peserta ikut di-poll supaya host tidak perlu
                         me-refresh untuk tahu sudah berapa yang masuk. --}}
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <span class="lobi-hitung {{ $jumlahPeserta === 0 ? 'lobi-hitung--sepi' : '' }}"
                            data-lobi-hitung data-jumlah="{{ $jumlahPeserta }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('orang') }}" />
                            </svg>

                            <span data-lobi-hitung-teks>
                                {{ $jumlahPeserta }} {{ $jumlahPeserta === 1 ? 'peserta bergabung' : 'peserta bergabung' }}
                            </span>
                        </span>

                        <p class="text-xs text-dark/45" data-lobi-keterangan>
                            Halaman ini memperbarui dirinya sendiri secara otomatis.
                        </p>
                    </div>

                    {{-- Daftar nama peserta. --}}
                    <x-sesi.daftar-peserta :peserta="$peserta" />
                </div>

                {{-- Aksi host: mulai atau akhiri. Peserta tidak punya
                     tombol apa pun di lobby. --}}
                @if ($adalahHost)
                    <div class="border-t border-lavender/60 px-5 py-4">
                        @if ($sesi->masihMenunggu())
                            <form method="POST" action="{{ route('user.sesi.mulai', $sesi) }}"
                                data-lobi-konfirmasi-judul="Mulai quiz sekarang?"
                                data-lobi-konfirmasi-pesan="Semua peserta yang sudah bergabung akan langsung masuk ke soal pertama."
                                data-lobi-konfirmasi-tombol="Mulai Quiz">
                                @csrf

                                <button type="submit" class="tombol-utama w-full">
                                    Mulai Quiz
                                </button>
                            </form>
                        @else
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <a href="{{ route('user.sesi.soal', [$sesi, 1]) }}" class="tombol-utama flex-1">
                                    Buka Soal
                                </a>

                                <form method="POST" action="{{ route('user.sesi.akhiri', $sesi) }}" class="flex-1"
                                    data-lobi-konfirmasi-judul="Akhiri quiz?"
                                    data-lobi-konfirmasi-pesan="Peserta yang belum selesai akan langsung melihat halaman hasil. Nilai yang sudah masuk tetap tersimpan."
                                    data-lobi-konfirmasi-tombol="Akhiri Quiz"
                                    data-lobi-konfirmasi-tone="henti">
                                    @csrf

                                    <button type="submit" class="tombol-garis tombol-garis--henti w-full">
                                        Akhiri Quiz
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>

    {{-- Dialog konfirmasi hanya ada untuk host: peserta tidak pernah punya
         tombol yang perlu dikonfirmasi, jadi dialognya tidak dikirim ke
         browser mereka.

         Dua dialog karena ada dua aksi dengan tones berbeda: "Mulai Quiz"
         ungu dan "Akhiri Quiz" merah. --}}
    @if ($adalahHost)
        <x-sesi.konfirmasi judul="Mulai quiz sekarang?"
            pesan="Semua peserta yang sudah bergabung akan langsung masuk ke soal pertama."
            tombol="Mulai Quiz" />

        <x-sesi.konfirmasi judul="Akhiri quiz?"
            pesan="Peserta yang belum selesai akan langsung melihat halaman hasil. Nilai yang sudah masuk tetap tersimpan."
            tombol="Akhiri Quiz"
            tone="henti" />
    @endif
@endsection
