@props([
    // Kenapa daftar kosong: 'saya' (belum pernah membuat apa-apa) atau
    // 'cari' (ada kata kunci tapi tidak ada yang cocok).
    'alasan' => 'saya',
    'tab' => 'materi',
])

{{--
    Empty state halaman "Karya Saya".

    Dua kondisi punya pesan dan tombol yang berbeda:
      cari  -> ada kata kunci, tawarkan untuk menghapusnya
      saya  -> belum ada karya, tawarkan membuat lewat form yang sudah ada

    Tombol membuat mengarah ke form yang sudah ada (Tambah Materi / Tambah
    Quiz), bukan form baru.
--}}

@php
    $isi = $alasan === 'cari'
        ? [
            'judul' => 'Karya tidak ditemukan',
            'deskripsi' => 'Tidak ada karya yang cocok dengan kata kunci itu. Coba kata yang lebih umum.',
            'ikon' => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75c0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75v-.75c0-.828.705-1.466 1.45-1.827a2.25 2.25 0 0 0 .67-.442c1.171-1.025 1.171-2.687 0-3.712M12.75 6h.008v.008H12.75V6Z',
        ]
        : [
            'judul' => $tab === 'quiz' ? 'Belum ada quiz yang kamu buat' : 'Belum ada materi yang kamu buat',
            'deskripsi' => $tab === 'quiz'
                ? 'Buat quiz pertamamu di sini, lalu semuanya bisa dicoba lewat menu Karya Saya.'
                : 'Materi yang kamu buat akan muncul di sini. Mulai dengan menambahkan materi pertamamu.',
            'ikon' => 'M12 4.5v15m7.5-7.5h-15',
        ];
@endphp

<div class="mt-5 flex flex-col items-center overflow-clip rounded-[2rem] border border-lavender bg-white px-6 py-16 text-center shadow-[0_20px_45px_-34px_rgba(33,26,58,0.4)]">

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
        <a href="{{ route('user.karya-saya', array_filter(['tab' => $tab !== 'materi' ? $tab : null], fn ($n) => filled($n))) }}"
            class="mt-6 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
            Reset Pencarian
        </a>
    @elseif ($tab === 'quiz')
        <x-quiz.tambah label="Tambah Quiz" class="mt-6" />
    @else
        <x-materi.tambah class="mt-6" />
    @endif
</div>
