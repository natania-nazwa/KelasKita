@props([
    'seksi',
    'judulMateri',
    'tautan',
    'babAwal' => false,
])

@php
    /*
     * Kartu latihan di akhir materi.
     *
     * Seksi yang judulnya mengandung "latihan", "tugas", "soal", atau
     * "ujian" (dicek di IsiMateri) dirender jadi kartu ini, bukan daftar
     * paragraf biasa.
     */

    $paragraf = array_values(array_filter(
        $seksi['blok'],
        fn (array $blok) => $blok['tipe'] === 'paragraf'
    ));

    // Tanpa paragraf, tetap ditampilkan kalimat ajakan yang wajar supaya
    // kartu tidak terasa kosong.
    $deskripsi = $paragraf === []
        ? 'Kerjakan soal-soal singkat untuk menguji pemahamanmu tentang materi ini.'
        : implode(' ', array_column($paragraf, 'html'));
@endphp

<section id="{{ $seksi['slug'] }}" data-bab="{{ $seksi['slug'] }}"
    @unless($babAwal) hidden @endunless class="materi-seksi">
    <div class="latihan-kartu overflow-hidden rounded-[1.75rem]">

        <div class="flex items-start gap-4 p-5 sm:p-7">
            <span class="latihan-kartu__ikon" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('piala') }}" />
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-primary">
                    {{ $seksi['nomor'] }}. {{ $seksi['judul'] }}
                </p>

                <h2 class="mt-1.5 text-lg font-extrabold leading-snug tracking-tight text-dark sm:text-xl">
                    Sudah memahami materi {{ $judulMateri }}?
                </h2>

                <p class="isi-materi mt-2 text-sm">{!! $deskripsi !!}</p>

                <a href="{{ $tautan }}" class="tombol-latihan mt-5 inline-flex">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>

                    Mulai Latihan
                </a>
            </div>
        </div>
    </div>
</section>
