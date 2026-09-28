@props([
    'kode',
    'bahasa' => 'teks',
    'sorot' => null,
])

@php
    /*
     * Blok kode gelap dengan tombol salin.
     *
     * Kepalanya sengaja dibuat minim (hanya tombol Copy) supaya isi kode
     * yang jadi fokus halaman. Bahasa tidak ditampilkan lagi; informasi itu
     * sudah tersimpan di data blok dan tetap bisa dibaca lewat atribut
     * data-bahasa di bawah.
     *
     * $sorot sudah dihitung di App\Support\SorotKode waktu isi materi
     * dipecah, sehingga halaman tidak menyorot kode berulang kali.
     *
     * Tombol menyalin teks asli dari <code> lewat textContent: <span> pewarna
     * tidak ikut terbaca, jadi yang tersalin persis kode yang ditulis.
     */
@endphp

<div class="kode-blok" data-blok-kode data-bahasa="{{ $bahasa }}">

    <div class="kode-blok__kepala">
        <button type="button" data-salin-kode
            class="tombol-salin"
            aria-label="Salin kode ke papan klip">
            <svg data-salin-ikon class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V6.75a2.25 2.25 0 0 1 2.25-2.25c.639 0 1.28.135 1.927.184" />
            </svg>

            <span data-salin-teks>Copy</span>
        </button>
    </div>

    {{-- overflow-x auto ada di .kode-blok__isi: kode panjang di-scroll
         horizontal di dalam kotaknya, bukan di seluruh halaman. --}}
    <pre class="kode-blok__isi"><code data-salin-sumber class="font-mono">{!! $sorot ?? e($kode) !!}</code></pre>
</div>
