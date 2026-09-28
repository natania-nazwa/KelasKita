@props([
    'materi',
])

{{--
    Kartu materi milik sendiri di halaman "Karya Saya".

    Sumber datanya array polos dari App\Support\DaftarMateri, jadi komponen
    ini tidak terikat Eloquent. Bentuk array yang dipakai di sini:
      judul, deskripsi, thumbnail, jumlah_bab, waktu_baca, aktif,
      status_label, dibuat_pada, tautan, tautan_edit, tautan_hapus,
      kategori => [nama, ikon, warna, warna_gelap]

    Berbeda dengan kartu di halaman Materi, kartu ini bukan satu tautan utuh:
    hanya judul dan tombol "Lihat" yang membuka halaman detail, supaya tombol
    Edit dan Hapus tidak ikut terbuka.

    Lencana status sengaja diletakkan di pojok kanan thumbnail, bukan di
    samping judul: di tiga kolom judulnya jadi jauh lebih lega dan tidak
    pernah tersempit oleh lencana.
--}}

@php
    $kategori = $materi['kategori'];
    $status = $materi['aktif'] ? 'aktif' : 'nonaktif';
@endphp

<article style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-materi group min-w-0">

    {{-- A. Thumbnail: tinggi seragam (16:9), sama seperti kartu di halaman Materi. --}}
    <div class="kartu-materi__gambar">
        @if (filled($materi['thumbnail'] ?? null))
            <img src="{{ $materi['thumbnail'] }}" alt="" loading="lazy" class="kartu-materi__foto">
        @else
            <span class="kartu-materi__gambar-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Lencana kategori, melayang di pojok kiri bawah thumbnail. --}}
        <span class="kartu-materi__lencana">{{ $kategori['nama'] }}</span>

        {{-- Status di pojok kanan thumbnail. --}}
        <span class="karya-status karya-status--{{ $status }} karya-pojok">
            <span class="karya-status__titik" aria-hidden="true"></span>

            {{ $materi['status_label'] }}
        </span>
    </div>

    {{-- B-E. Judul, deskripsi, informasi, dan baris aksi. --}}
    <div class="kartu-materi__badan">
        <h2 class="kartu-materi__judul karya-judul">
            <a href="{{ $materi['tautan'] }}">{{ $materi['judul'] }}</a>
        </h2>

        <p class="kartu-materi__deskripsi">{{ $materi['deskripsi'] }}</p>

        {{-- Jumlah bab, perkiraan waktu baca, dan tanggal dibuat. --}}
        <div class="karya-info mt-3.5">
            <span class="karya-info__butir">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>

                {{ $materi['jumlah_bab'] }} Bab
            </span>

            <span class="karya-info__butir">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>

                {{ $materi['waktu_baca'] }} menit
            </span>

            <span class="karya-info__butir karya-info__butir--sepuh">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>

                {{ $materi['dibuat_pada']?->translatedFormat('d M Y') }}
            </span>
        </div>

        {{-- Baris aksi. Tombol hapus membuka dialog konfirmasi lebih dulu
             (dikerjakan initKonfirmasi() di resources/js/app.js). --}}
        <div class="karya-aksi">
            <a href="{{ $materi['tautan'] }}" class="karya-aksi__tombol karya-aksi__tombol--lihat karya-aksi__tombol--utama"
                title="Buka halaman materi ini">
                Lihat

                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>

            <a href="{{ $materi['tautan_edit'] }}" class="karya-aksi__tombol" title="Ubah materi ini">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                </svg>

                Edit
            </a>

            <form method="POST" action="{{ $materi['tautan_hapus'] }}" data-konfirmasi-form
                data-konfirmasi-judul="Hapus Materi?"
                data-konfirmasi-pesan="Materi ini akan dihapus dan tidak dapat dikembalikan.">
                @csrf
                @method('DELETE')

                <button type="button" class="karya-aksi__tombol karya-aksi__tombol--hapus" data-konfirmasi
                    title="Hapus materi ini" aria-label="Hapus materi {{ $materi['judul'] }}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>

                    Hapus
                </button>
            </form>
        </div>
    </div>
</article>
