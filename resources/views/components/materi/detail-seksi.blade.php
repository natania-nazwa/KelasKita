@props([
    'seksi',
    'babAwal' => false,
])

@php
    /*
     * Satu seksi materi: judul bernomor + isi.
     *
     * id pada <section> dipakai sebagai jangkar tautan Daftar Isi, sementara
     * data-bab dipakai materi-detail.js untuk memilih bab mana yang sedang
     * tampil. Bab pertama tampil lebih dulu; sisanya diberi atribut hidden
     * oleh JS. Tanpa JS semuanya terlihat (lihat <noscript> di layout).
     */
@endphp

<section id="{{ $seksi['slug'] }}" data-bab="{{ $seksi['slug'] }}"
    @unless($babAwal) hidden @endunless
    class="kartu-detail materi-seksi p-5 sm:p-6 lg:p-7">

    <h2 class="materi-seksi__judul">
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
