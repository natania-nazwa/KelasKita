@props([
    'seksi' => [],
])

@php
    /*
     * Sidebar "Daftar Isi".
     *
     * Tiap barisnya juga berperan sebagai pemilih bab: seluruh seksi ikut
     * dirender di halaman, tapi hanya satu yang tampil pada satu waktu
     * (lihat materi-detail.js). Karena itu tautannya memakai href="#slug"
     * supaya tetap masuk akal tanpa JavaScript, sementara JS mengganti
     * perilakunya menjadi pergantian bab.
     *
     * Daftar ini selalu disusun ke bawah (satu baris per bab, urut
     * 1, 2, 3, ...) di semua lebar layar: di mobile strip horizontal
     * yang di-scroll membuat judul bab terpotong dan sulit dibaca,
     * sedangkan di desktop kolom sudah sejak awal bentuk yang diinginkan.
     * Lipatan lewat tombol di kanan judul hanya berlaku di bawah lg.
     *
     * Bentuk array per seksi: nomor, slug, judul.
     */
@endphp

<nav data-daftar-isi aria-label="Daftar isi materi" class="kartu-detail daftar-isi">

    <div class="flex items-center justify-between gap-3">
        <h2 class="daftar-isi__judul">
            Daftar Isi
        </h2>

        {{-- Hanya muncul di bawah lg, tempat daftarnya collapsible. --}}
        <button type="button" data-daftar-isi-alih aria-expanded="true" aria-controls="daftar-isi-materi"
            class="daftar-isi__alih" aria-label="Buka atau tutup daftar isi">
            <svg data-daftar-isi-alih-ikon class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 6-6M4.5 6.75l6-6 6 6" />
            </svg>
        </button>
    </div>

    {{--
        flex-col: daftar selalu turun ke bawah, tanpa overflow-x, jadi
        tidak ada strip yang bisa di-scroll ke samping di ponsel.

        Kelas .daftar-isi__items punya aturan display:none untuk atribut
        hidden, karena kelas utilitas flex di atas akan mengalah kalau
        atribut itu dipakai polos.
    --}}
    <div id="daftar-isi-materi" data-daftar-isi-items
        class="daftar-isi__items mt-3 flex min-w-0 flex-col gap-1">

        @foreach ($seksi as $s)
            <a href="#{{ $s['slug'] }}" data-daftar-isi-tautan="{{ $s['slug'] }}"
                class="daftar-isi__item min-w-0"
                aria-label="Buka seksi {{ $s['nomor'] }}. {{ $s['judul'] }}">

                <span class="daftar-isi__nomor" aria-hidden="true">{{ $s['nomor'] }}</span>

                {{-- min-w-0: judul panjang turun ke baris berikutnya di
                     kartu sempit, bukan meluber ke luar kartu. --}}
                <span class="min-w-0">{{ $s['judul'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
