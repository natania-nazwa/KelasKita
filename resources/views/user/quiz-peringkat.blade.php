@extends('layouts.app')

@section('title', 'Peringkat ' . $quiz->judul . ' | KelasKita')

@section('content')
    {{--
        Halaman peringkat sesi quiz mode kode.

        Semua baris berasal dari App\Support\DaftarPeringkat, bukan dihitung
        ulang di view: nama dan warnanya dari DaftarPeserta, nilai dan
        rinciannya dari tb_pengerjaan_quiz. Jadi angka di sini sama dengan
        angka di kartu hasil peserta itu sendiri, dan sama dengan rekap yang
        dilihat host di /user/sesi/{sesi}/hasil.

        Tiga besar diletakkan di podium 2-1-3 (peringkat 1 di tengah dan
        paling tinggi), sisanya turun ke daftar di bawahnya. Peserta yang
        belum menjawab tetap ikut tampil dengan nilai 0 supaya tidak terlihat
        hilang, dan keadaannya ditulis terpisah dari angkanya.
    --}}
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        <div class="mx-auto w-full max-w-4xl">

            {{-- ==================== KEPALA ====================
                 Judul quiz, jumlah peserta, dan status sesi. Status ditulis
                 apa adanya supaya peserta tahu peringkat yang sedang dilihatnya
                 masih bisa berubah selama quiz belum ditutup. --}}
            <section data-reveal="zoom" class="peringkat-kepala">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="peringkat-kepala__ikon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('piala') }}" />
                        </svg>
                    </span>

                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-extrabold tracking-tight text-dark">Peringkat Peserta</h1>

                        <p class="truncate text-xs text-dark/55" title="{{ $quiz->judul }}">{{ $quiz->judul }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="peringkat-chip">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('orang') }}" />
                        </svg>

                        {{ $jumlahPeserta }} peserta
                    </span>

                    <span class="peringkat-chip">{{ $sesi->labelStatus() }}</span>
                </div>
            </section>

            {{-- ==================== POSISI SAYA ====================
                 Hanya untuk peserta. Host memandu dan tidak menjawab soal,
                 jadi ia tidak punya baris di daftar ini; karena itu kartu ini
                 tidak pernah muncul baginya. --}}
            @if ($saya !== null)
                <section data-reveal class="peringkat-saya">
                    <div class="min-w-0">
                        <p class="peringkat-saya__label">Posisi kamu</p>

                        <p class="peringkat-saya__peringkat">
                            #{{ $saya['peringkat'] }}
                            <span>dari {{ $jumlahPeserta }} peserta</span>
                        </p>
                    </div>

                    <div class="peringkat-saya__nilai">
                        <p class="peringkat-saya__nilai-angka">{{ $saya['nilai'] }}</p>
                        <p class="peringkat-saya__nilai-skala">dari 100</p>
                    </div>
                </section>
            @elseif ($adalahHost)
                <p data-reveal class="peringkat-catatan">
                    Kamu adalah pembuat quiz ini, jadi namamu tidak masuk peringkat. Di bawah ini nilai semua peserta yang masuk lewat kode.
                </p>
            @endif

            {{-- ==================== PODIUM 2 - 1 - 3 ====================
                 Hanya dirender kalau pesertanya memang tiga atau lebih.
                 Dengan dua orang atau satu orang, podiumnya hanya jadi satu
                 petak bertulis "1", dan daftar polos di bawahnya sudah
                 menampilkan semuanya dengan lebih rapi. --}}
            @if ($podium !== [])
                <section data-reveal="zoom" class="peringkat-podium" aria-label="Tiga peringkat teratas">
                    @foreach ($podium as $baris)
                        <article class="peringkat-podium__petak peringkat-podium__petak--{{ $baris['peringkat'] }}"
                            @if ($saya !== null && (int) $saya['pengguna_id'] === (int) $baris['pengguna_id'])
                                data-saya="true"
                            @endif>

                            {{-- Angkanya disembunyikan dari pembaca layar
                                 supaya tidak dibacakan sebagai daftar
                                 angka telanjang; kalimatnya yang memberi
                                 tahu peringkat berapa. --}}
                            <span class="peringkat-podium__nomor" aria-hidden="true">{{ $baris['peringkat'] }}</span>

                            <span class="sr-only">Peringkat {{ $baris['peringkat'] }},</span>

                            <span class="peringkat-podium__avatar"
                                style="--a: {{ $baris['warna'] }}; --a-gelap: {{ $baris['warna_gelap'] }};"
                                aria-hidden="true">{{ $baris['inisial'] }}</span>

                            <p class="peringkat-podium__nama" title="{{ $baris['nama'] }}">{{ $baris['nama'] }}</p>

                            <p class="peringkat-podium__nilai">
                                {{ $baris['nilai'] }}
                                <span>/100</span>
                            </p>

                            <p class="peringkat-podium__rincian">
                                @if ($baris['sudah_selesai'])
                                    {{ $baris['benar'] }}/{{ $baris['jumlah_soal'] }} benar
                                @else
                                    Belum menjawab
                                @endif
                            </p>
                        </article>
                    @endforeach
                </section>
            @endif

            {{-- ==================== DAFTAR PESERTA ==================== --}}
            @if ($daftar === [])
                <section data-reveal class="peringkat-kosong">
                    <span class="peringkat-kosong__ikon" aria-hidden="true">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('orang') }}" />
                        </svg>
                    </span>

                    <h2 class="peringkat-kosong__judul">Belum ada peserta</h2>

                    <p class="peringkat-kosong__teks">
                        Belum ada yang berhasil masuk lewat kode. Bagikan kodenya ke teman supaya mereka bisa ikut serta.
                    </p>
                </section>
            @elseif ($sisanya !== [])
                {{-- Kartu ini hanya dirender kalau memang ada sisa di
                     bawah podium. Kalau semua orang sudah ada di podium,
                     judul "Semua Peserta" dengan daftar kosong hanya
                     menambah ruang kosong. --}}
                <section data-reveal class="peringkat-kartu">
                    <h2 class="peringkat-kartu__judul">
                        {{ $podium !== [] ? 'Semua Peserta' : 'Daftar Peserta' }}
                    </h2>

                    <ol class="peringkat-daftar">
                        @foreach ($sisanya as $baris)
                            <li @class([
                                'peringkat-daftar__baris',
                                'peringkat-daftar__baris--saya' => $saya !== null && (int) $saya['pengguna_id'] === (int) $baris['pengguna_id'],
                            ])>

                                <span class="peringkat-daftar__nomor" aria-label="Peringkat {{ $baris['peringkat'] }}">
                                    {{ $baris['peringkat'] }}
                                </span>

                                <span class="peringkat-daftar__avatar"
                                    style="--a: {{ $baris['warna'] }}; --a-gelap: {{ $baris['warna_gelap'] }};"
                                    aria-hidden="true">{{ $baris['inisial'] }}</span>

                                <div class="min-w-0 flex-1">
                                    <p class="peringkat-daftar__nama" title="{{ $baris['nama'] }}">{{ $baris['nama'] }}</p>

                                    <p class="peringkat-daftar__rincian">
                                        @if ($baris['sudah_selesai'])
                                            {{ $baris['benar'] }}/{{ $baris['jumlah_soal'] }} benar
                                        @else
                                            Belum menjawab
                                        @endif
                                    </p>
                                </div>

                                <span class="peringkat-daftar__nilai">{{ $baris['nilai'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- ==================== KAKI ====================
                 Peserta diarahkan ke kartu hasil miliknya, host ke rekap
                 sesinya. Dua-duanya memakai URL lama /sesi/{sesi}/hasil:
                 controller di sana yang mengarahkan ke tampilan yang tepat
                 untuk perannya, jadi tautan ini tidak pernah melenceng ke
                 halaman yang salah. --}}
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2.5">
                <a href="{{ route('user.sesi.hasil', $sesi) }}" class="tombol-garis">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                    </svg>

                    {{ $adalahHost ? 'Kembali ke Rekap' : 'Kembali ke Hasil' }}
                </a>

                <a href="{{ route('user.dashboard') }}" class="tombol-garis">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('rumah') }}" />
                    </svg>

                    Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection
