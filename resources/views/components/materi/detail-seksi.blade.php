@props([
    'seksi',
])

@php
    /*
     * Satu seksi materi: judul bernomor + isi.
     *
     * id pada <section> dipakai dua pihak: tautan Daftar Isi untuk
     * scroll ke sini, dan scrollspy di materi-detail.js untuk menentukan
     * seksi mana yang sedang dibaca.
     */
@endphp

<section id="{{ $seksi['slug'] }}" class="kartu-detail materi-seksi p-5 sm:p-7">

    <h2 class="text-lg font-extrabold tracking-tight text-dark sm:text-xl">
        {{ $seksi['nomor'] }}. {{ $seksi['judul'] }}
    </h2>

    @if ($seksi['blok'] !== [])
        <div class="mt-4 space-y-4">
            @foreach ($seksi['blok'] as $blok)
                <x-materi.detail-blok :blok="$blok" />
            @endforeach
        </div>
    @else
        <p class="mt-3 text-sm text-dark/45">
            Bagian ini belum diisi.
        </p>
    @endif
</section>
