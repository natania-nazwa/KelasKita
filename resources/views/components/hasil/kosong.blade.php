@props([
    // Kenapa daftar kosong: 'saya' = belum pernah mengerjakan quiz,
    // 'cari' = ada data tapi tidak cocok dengan filter/pencarian.
    'alasan' => 'saya',
    // Route tombol aksi, supaya tetap benar saat sedang melihat riwayat
    // satu quiz saja.
    'aksi' => 'user.hasil',
    'param' => [],
])

{{--
    Empty state di dalam panel "Riwayat Hasil Quiz".

    Dua kondisi punya pesan dan tombol yang berbeda:
      saya -> belum pernah mengerjakan, arahkan ke halaman Quiz
      cari -> ada hasil tapi tidak cocok, tawarkan membersihkan filter

    Angka nol sengaja tidak dimasukkan ke dalam pesan ini: kartu
    statistik di atas sudah menampilkannya sebagai jawaban database, dan
    mengulangnya di sini hanya membuat dua kali "0".
--}}

@php
    $isi = $alasan === 'cari'
        ? [
            'judul' => 'Hasil tidak ditemukan',
            'deskripsi' => 'Tidak ada pengerjaan quiz yang cocok dengan filter atau kata kunci itu. Coba kata yang lebih umum, atau ganti tabnya.',
            'ikon' => \App\Support\Ikon::path('dokumen'),
        ]
        : [
            'judul' => 'Belum Ada Hasil Quiz',
            'deskripsi' => 'Kamu belum memiliki riwayat pengerjaan quiz. Yuk mulai belajar dan kerjakan quiz pertamamu!',
            'ikon' => \App\Support\Ikon::path('piala'),
        ];
@endphp

<div {{ $attributes->class(['flex flex-col items-center px-6 py-12 text-center']) }}>

    <span class="hasil-kosong__ikon" aria-hidden="true">
        <svg class="h-full w-full" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $isi['ikon'] }}" />
        </svg>
    </span>

    <h3 class="mt-4 text-base font-bold tracking-tight text-dark">{{ $isi['judul'] }}</h3>

    <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-dark/50">{{ $isi['deskripsi'] }}</p>

    @if ($alasan === 'cari')
        <a href="{{ route($aksi, $param) }}"
            class="mt-5 inline-flex items-center gap-2 rounded-full border border-lavender bg-white px-5 py-2 text-sm font-semibold text-primary-dark transition hover:border-primary hover:bg-primary hover:text-white">
            Reset Filter
        </a>
    @else
        <a href="{{ route('user.quiz') }}" class="tombol-utama mt-5">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('benar') }}" />
            </svg>

            Mulai Quiz
        </a>
    @endif
</div>
