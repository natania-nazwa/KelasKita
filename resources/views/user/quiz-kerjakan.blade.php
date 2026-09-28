@extends('layouts.app')

@section('title', 'Soal ' . $nomor . ' | ' . $quiz->judul)

@section('content')
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10" data-soal>

        <div class="mx-auto w-full max-w-2xl">

            {{-- Kepala: judul quiz + posisi soal. --}}
            <section data-reveal="zoom" class="lobi-kartu">
                <div class="lobi-kartu__kepala">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="lobi-soal__angka">{{ $nomor }}</span>

                        <div class="min-w-0">
                            <h1 class="truncate text-sm font-extrabold tracking-tight text-dark">{{ $quiz->judul }}</h1>

                            <p class="text-xs text-dark/50">Soal {{ $nomor }} dari {{ $jumlahSoal }}</p>
                        </div>
                    </div>

                    @if ($adalahHost)
                        <span class="karya-status karya-status--menunggu">Host</span>
                    @endif
                </div>

                <div class="lobi-kartu__badan">

                    {{-- Progres: berapa soal yang sudah dijawab. --}}
                    <div class="lobi-soal__progres" role="progressbar"
                        aria-valuemin="0" aria-valuemax="{{ $jumlahSoal }}" aria-valuenow="{{ $jumlahDijawab }}"
                        aria-label="Kemajuan menjawab">
                        <i style="width: {{ $jumlahSoal > 0 ? round($jumlahDijawab / $jumlahSoal * 100) : 0 }}%"></i>
                    </div>

                    <p class="mt-2 text-xs text-dark/45">
                        {{ $jumlahDijawab }} dari {{ $jumlahSoal }} soal sudah dijawab.
                    </p>

                    {{-- Pertanyaan. --}}
                    <h2 class="mt-5 text-base font-bold leading-relaxed text-dark">
                        {{ $soal->pertanyaan }}
                    </h2>

                    {{--
                        Empat pilihan jawaban. Radio aslinya ada di dalam
                        label supaya pilihan yang tersimpan tetap tampil
                        terpilih tanpa perlu JavaScript.
                    --}}
                    <form method="POST" action="{{ route('user.sesi.jawab', [$sesi, $nomor]) }}" class="mt-4 space-y-2.5">
                        @csrf

                        @foreach ($soal->pilihan() as $huruf => $isi)
                            <label class="lobi-pilihan">
                                <input type="radio" name="jawaban" value="{{ $huruf }}"
                                    class="sr-only"
                                    @checked(old('jawaban', $jawaban?->jawaban_dipilih) === $huruf)>

                                <span class="lobi-pilihan__huruf" aria-hidden="true">{{ $huruf }}</span>

                                <span class="min-w-0 pt-1 text-sm leading-relaxed text-dark">{{ $isi }}</span>
                            </label>
                        @endforeach

                        @error('jawaban')
                            <p role="alert" class="flex items-start gap-1.5 text-xs font-medium text-[#a8323c]">
                                <svg class="mt-px h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>

                                {{ $message }}
                            </p>
                        @enderror

                        <button type="submit" class="tombol-utama mt-4 w-full" data-soal-lanjut>
                            {{ $nomor < $jumlahSoal ? 'Simpan & Lanjut' : 'Simpan Jawaban' }}

                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kanan') }}" />
                            </svg>
                        </button>
                    </form>

                    {{-- "Selesai" untuk berhenti di tengah. --}}
                    <form method="POST" action="{{ route('user.sesi.selesai', $sesi) }}" class="mt-2"
                        data-lobi-konfirmasi-judul="Selesai sekarang?"
                        data-lobi-konfirmasi-pesan="Kamu akan langsung melihat hasil jawabanmu."
                        data-lobi-konfirmasi-tombol="Ya, Selesai">
                        @csrf

                        @if ($jumlahDijawab > 0)
                            <button type="submit" class="tombol-garis w-full">
                                Selesai & Lihat Hasil
                            </button>
                        @endif
                    </form>
                </div>
            </section>

            <p class="mt-4 text-center text-xs text-dark/40">
                Jawaban disimpan otomatis setiap kali kamu menekan tombol di atas.
            </p>
        </div>
    </div>

    <x-sesi.konfirmasi judul="Selesai sekarang?"
        pesan="Kamu akan langsung melihat hasil jawabanmu."
        tombol="Ya, Selesai" />
@endsection
