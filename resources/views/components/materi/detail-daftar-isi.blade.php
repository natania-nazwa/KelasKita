@props([
    'seksi' => [],
])

@php
    /*
     * Sidebar "Daftar Isi".
     *
     * Tiap seksi punya id sendiri (lihat IsiMateri), jadi tautan di sini
     * bisa langsunghop ke seksi yang dipilih. Item aktif mengikuti posisi
     * scroll lewat materi-detail.js, yang memberi kelas is-aktif.
     *
     * Di mobile daftar ini jadi strip horizontal yang bisa di-scroll; di
     * desktop berubah jadi kolom dan menempel (sticky) supaya tetap terlihat
     * saat user membaca.
     *
     * Bentuk array per seksi: nomor, slug, judul.
     */
@endphp

<nav data-daftar-isi aria-label="Daftar isi materi" class="kartu-detail p-4 sm:p-5">

    <div class="flex items-center justify-between gap-3">
        <h2 class="text-sm font-extrabold tracking-tight text-dark">
            Daftar Isi
        </h2>

        {{-- Hanya muncul di bawah lg, tempat daftarnya collapsible. --}}
        <button type="button" data-daftar-isi-alih aria-expanded="true" aria-controls="daftar-isi-materi"
            class="flex h-8 w-8 items-center justify-center rounded-lg border border-lavender bg-white text-dark/60 transition hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 lg:hidden"
            aria-label="Buka atau tutup daftar isi">
            <svg data-daftar-isi-alih-ikon class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 6-6M4.5 6.75l6-6 6 6" />
            </svg>
        </button>
    </div>

    {{--
        min-w-0 + overflow-x-auto: di mobile daftarnya meluap ke samping dan
        bisa di-scroll, bukan membuat halaman ikut melebar.

        Kelas .daftar-isi__items punya aturan display:none untuk atribut
        hidden, karena kelas utilitas flex di atas akan mengalah kalau
        atribut itu dipakai polos.
    --}}
    <div id="daftar-isi-materi" data-daftar-isi-items
        class="daftar-isi__items mt-3 flex min-w-0 gap-1.5 overflow-x-auto pb-1 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0">

        @foreach ($seksi as $s)
            <a href="#{{ $s['slug'] }}" data-daftar-isi-tautan="{{ $s['slug'] }}"
                class="daftar-isi__item min-w-0 shrink-0 lg:w-full"
                aria-label="Buka seksi {{ $s['nomor'] }}. {{ $s['judul'] }}">

                <span class="daftar-isi__nomor" aria-hidden="true">{{ $s['nomor'] }}</span>

                {{-- truncate + whitespace-nowrap: judul panjang di mobile
                     memotong satu baris, bukan menambah tinggi kartu. --}}
                <span class="truncate whitespace-nowrap lg:overflow-visible lg:whitespace-normal">{{ $s['judul'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
