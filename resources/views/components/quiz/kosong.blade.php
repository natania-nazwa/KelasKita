@props([
    // Alasan kosongnya halaman: 'kosong' (pustaka quiz memang belum
    // terisi) atau 'cari' (tidak ada yang cocok dengan kata kunci).
    'alasan' => 'kosong',
])

{{--
    Dua kondisi kosong punya pesan dan tombol yang berbeda:
      cari   -> ada kata kunci, tawarkan untuk menghapusnya
      kosong -> pustaka quiz belum terisi, arahkan ke menu Karya Saya

    Halaman ini menampilkan seluruh quiz, jadi tidak ada lagi kondisi
    "tab Saya Buat belum berisi apa-apa".
--}}
@php
    $isi = match ($alasan) {
        'cari' => [
            'judul' => 'Quiz tidak ditemukan',
            'deskripsi' => 'Coba gunakan kata kunci lain.',
            'ikon' => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75c0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75v-.75c0-.828.705-1.466 1.45-1.827a2.25 2.25 0 0 0 .67-.442c1.171-1.025 1.171-2.687 0-3.712M12.75 6h.008v.008H12.75V6Z',
        ],
        default => [
            'judul' => 'Belum ada quiz',
            'deskripsi' => 'Belum tersedia quiz yang sesuai dengan pencarianmu.',
            'ikon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
    };
@endphp

<div data-quiz-navigasi
    class="mt-5 flex flex-col items-center overflow-clip rounded-[2rem] border border-lavender bg-white px-6 py-16 text-center shadow-[0_20px_45px_-34px_rgba(33,26,58,0.4)]">

    <span class="relative flex h-16 w-16 items-center justify-center">
        <span class="absolute inset-0 rounded-3xl bg-lavender/70 blur-xl" aria-hidden="true"></span>

        <span class="relative flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-bg text-dark/25">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $isi['ikon'] }}" />
            </svg>
        </span>
    </span>

    <h2 class="mt-6 text-lg font-bold tracking-tight text-dark">{{ $isi['judul'] }}</h2>

    <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-dark/50">{{ $isi['deskripsi'] }}</p>

    @if ($alasan === 'cari')
        <a href="{{ route('user.quiz') }}"
            class="mt-6 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
            Reset Pencarian
        </a>
    @else
        {{-- Membuat quiz hanya lewat menu Karya Saya. --}}
        <a href="{{ route('user.karya-saya', ['tab' => 'quiz']) }}"
            class="mt-6 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
            Buat Quiz di Karya Saya
        </a>
    @endif
</div>
