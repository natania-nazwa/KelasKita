@extends('layouts.app')

@section('title', 'Hasil Quiz | ' . $quiz->judul)

@section('content')
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        <div class="mx-auto w-full max-w-3xl">

            {{-- Judul sesi + status. --}}
            <section data-reveal="zoom" class="lobi-kartu">
                <div class="lobi-kartu__kepala">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-extrabold tracking-tight text-dark">{{ $quiz->judul }}</h1>

                        <p class="text-xs text-dark/50">
                            {{ $adalahHost ? 'Rekap nilai peserta' : 'Hasil jawabanmu' }}
                            &middot; {{ $sesi->labelStatus() }}
                        </p>
                    </div>

                    <a href="{{ route('user.quiz') }}" class="tombol-garis">Semua Quiz</a>
                </div>

                <div class="lobi-kartu__badan">
                    @if ($adalahHost)
                        {{-- ============================ HOST ============================ --}}
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="lobi-hitung">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('orang') }}" />
                                </svg>

                                {{ $jumlahPeserta }} {{ $jumlahPeserta === 1 ? 'peserta' : 'peserta' }}
                            </span>

                            @unless ($sesi->sudahSelesai())
                                <form method="POST" action="{{ route('user.sesi.akhiri', $sesi) }}"
                                    data-lobi-konfirmasi-judul="Akhiri quiz?"
                                    data-lobi-konfirmasi-pesan="Peserta yang belum selesai akan langsung melihat halaman hasil. Nilai yang sudah masuk tetap tersimpan."
                                    data-lobi-konfirmasi-tombol="Akhiri Quiz"
                                    data-lobi-konfirmasi-danger>
                                    @csrf

                                    <button type="submit" class="tombol-garis tombol-garis--henti">
                                        Akhiri Quiz
                                    </button>
                                </form>
                            @endunless
                        </div>

                        {{-- Tabel rekap, diurutkan dari nilai tertinggi. --}}
                        <div class="mt-5 space-y-2">
                            @forelse ($daftarNilai as $baris)
                                <div class="lobi-daftar__item">
                                    <span class="lobi-peringkat {{ $baris['peringkat'] === 1 ? 'lobi-peringkat--emas' : '' }} {{ $baris['peringkat'] === 2 ? 'lobi-peringkat--perak' : '' }} {{ $baris['peringkat'] === 3 ? 'lobi-peringkat--perunggu' : '' }}"
                                        aria-label="Peringkat {{ $baris['peringkat'] }}">{{ $baris['peringkat'] }}</span>

                                    <span class="lobi-daftar__avatar"
                                        style="--a: {{ $baris['warna'] }}; --a-gelap: {{ $baris['warna_gelap'] }};"
                                        aria-hidden="true">{{ $baris['inisial'] }}</span>

                                    <span class="lobi-daftar__nama" title="{{ $baris['nama'] }}">{{ $baris['nama'] }}</span>

                                    <span class="hidden text-xs text-dark/45 sm:inline">
                                        {{ $baris['benar'] }}/{{ $baris['jumlah_soal'] }} benar
                                    </span>

                                    <span class="lobi-daftar__status {{ $baris['sudah_selesai'] ? 'lobi-daftar__status--selesai' : 'lobi-daftar__status--mengerjakan' }}"
                                        title="{{ $baris['sudah_selesai'] ? 'Sudah selesai' : 'Masih dikerjakan' }}">
                                        {{ $baris['nilai'] }}
                                    </span>
                                </div>
                            @empty
                                <p class="lobi-daftar__kosong">
                                    Belum ada peserta yang bergabung ke sesi ini.
                                </p>
                            @endforelse
                        </div>
                    @else
                        {{-- =========================== PESERTA =========================== --}}
                        @if ($pengerjaan === null)
                            <div class="lobi-tunggu">
                                <span class="lobi-titik" aria-hidden="true"><i></i><i></i><i></i></span>

                                <p class="text-sm font-bold text-dark">Kamu belum mengerjakan soal</p>

                                <p class="max-w-sm text-xs leading-relaxed text-dark/55">
                                    Nilai akan muncul di sini setelah kamu menjawab soalnya.
                                </p>
                            </div>
                        @else
                            {{-- Nilai besar di tengah: satu angka yang paling
                                 mudah dibaca sekilas. --}}
                            <div class="text-center">
                                <p class="lobi-hasil__nilai">
                                    {{ $pengerjaan->nilai }}<span>/100</span>
                                </p>

                                <p class="mt-1 text-xs text-dark/50">Nilai kamu</p>
                            </div>

                            {{-- Rincian: berapa benar, salah, dan dijawab. --}}
                            <div class="mt-5 grid grid-cols-3 gap-2.5 text-center">
                                <div class="rounded-xl bg-[#eafaf3] px-2 py-3">
                                    <p class="text-lg font-extrabold leading-none text-[#0f7a5b]">{{ $pengerjaan->jumlah_benar }}</p>

                                    <p class="mt-1 text-[0.6875rem] font-semibold text-[#0f7a5b]/70">Benar</p>
                                </div>

                                <div class="rounded-xl bg-[#fdecee] px-2 py-3">
                                    <p class="text-lg font-extrabold leading-none text-[#a8323c]">{{ $pengerjaan->jumlah_salah }}</p>

                                    <p class="mt-1 text-[0.6875rem] font-semibold text-[#a8323c]/70">Salah</p>
                                </div>

                                <div class="rounded-xl bg-lavender px-2 py-3">
                                    <p class="text-lg font-extrabold leading-none text-primary-dark">{{ $pengerjaan->jumlah_dijawab }}</p>

                                    <p class="mt-1 text-[0.6875rem] font-semibold text-primary-dark/70">Dijawab</p>
                                </div>
                            </div>

                            @if (! $pengerjaan->sudahSelesai() && ! $sesi->sudahSelesai())
                                <p class="mt-4 text-center text-xs text-dark/45">
                                    Kamu masih bisa kembali menjawab selama quiz belum ditutup host.
                                </p>

                                <a href="{{ route('user.sesi.soal', [$sesi, 1]) }}" class="tombol-garis mt-3 w-full">
                                    Kembali Mengerjakan
                                </a>
                            @endif
                        @endif
                    @endif
                </div>
            </section>

            <div class="mt-4 text-center">
                <a href="{{ route('user.dashboard') }}" class="tombol-garis">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <x-sesi.konfirmasi judul="Akhiri quiz?"
        pesan="Peserta yang belum selesai akan langsung melihat halaman hasil. Nilai yang sudah masuk tetap tersimpan."
        tombol="Akhiri Quiz"
        tone="henti" />
@endsection
