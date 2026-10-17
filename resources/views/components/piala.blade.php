@props([
    /*
     * Ukuran glyph lewat font-size, mis. 'text-xl'. Emoji punya ukuran
     * sendiri, jadi tidak perlu kotak transparan seperti ikon SVG.
     */
    'ukuran' => 'text-base',
    /*
     * Emoji di sebelah teks yang sudah menjelaskan artinya (mis. di dalam
     * tombol "Lihat Peringkat"): hanya hiasan, jadi disembunyikan dari
     * pembaca layar. Kalau berdiri sendiri, dibiarkan terbaca sebagai gambar.
     */
    'diam' => false,
])

{{--
    Emoji piala (🏆) pengganti ikon SVG piala di halaman pengguna. Dipakai
    supaya lambang piala di seluruh aplikasi seragam, mengikuti emoji yang
    sudah dipakai di banner dan kartu motivasi halaman Hasil.
--}}

@if ($diam)
    <span {{ $attributes->class([$ukuran, 'leading-none']) }} aria-hidden="true">🏆</span>
@else
    <span {{ $attributes->class([$ukuran, 'leading-none']) }} role="img" aria-label="Piala">🏆</span>
@endif
