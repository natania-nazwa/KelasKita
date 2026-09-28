@props([
    'quiz' => null,
])

{{--
    Banner halaman Hasil.

    Bentuknya tinggi dan rata seperti strip, bukan hero besar: gradien
    ungu tua -> ungu -> lavender dari kiri ke kanan, piala emoji di paling
    kiri, teks di sebelahnya, dan dekorasi trophy / grafik / checklist /
    bintang di lapisan background sebelah kanan.

    Emoji (bukan SVG) supaya konsisten dengan kartu motivasi di bawahnya
    dan dengan emoji yang sudah dipakai di banner dashboard serta
    kategori di landing page.

    Dekorasinya memakai ikon garis yang sudah ada di
    App\Support\Ikon supaya tidak perlu asset gambar baru: proyek ini
    tidak punya pustaka ilustrasi, semua visualnya digambar dengan SVG
    inline dan gradien CSS.

    Kalau dibuka dari kartu "Quiz Terpopuler" (ada $quiz), judulnya
    diikuti nama quiz itu supaya pengguna tahu sedang melihat riwayat
    quiz yang mana.
--}}

@php
    $judul = $quiz !== null ? $quiz->judul : 'Hasil';
    $deskripsi = $quiz !== null
        ? 'Riwayat pengerjaan ' . $quiz->judul . ' lengkap dengan nilai dan rincian jawabannya.'
        : 'Lihat riwayat pengerjaan quiz kamu dan pantau perkembangan belajarmu di Kelas Kita.';

    $dekorasi = [
        ['ikon' => 'piala', 'kelas' => 'hasil-banner__dekor--piala'],
        ['ikon' => 'grafik', 'kelas' => 'hasil-banner__dekor--grafik'],
        ['ikon' => 'centang', 'kelas' => 'hasil-banner__dekor--check'],
        ['ikon' => 'bintang', 'kelas' => 'hasil-banner__dekor--bintang'],
    ];
@endphp

<section {{ $attributes->class(['hasil-banner']) }}>

    {{-- Dekorasi di lapisan background di sisi kanan banner. --}}
    @foreach ($dekorasi as $dekor)
        <span class="hasil-banner__dekor {{ $dekor['kelas'] }}" aria-hidden="true">
            <svg class="h-full w-full" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($dekor['ikon']) }}" />
            </svg>
        </span>
    @endforeach

    {{-- Isi banner: piala emoji, lalu judul dan deskripsi. min-w-0
         menjaga judul panjang tidak mendorong dekorasi keluar. --}}
    <div class="relative flex min-w-0 items-center gap-3.5">

        <span class="hasil-banner__piala" role="img" aria-label="Piala">🏆</span>

        <div class="min-w-0">
            @if ($quiz !== null)
                <p class="hasil-banner__label">Riwayat Quiz</p>
            @endif

            <h1 class="hasil-banner__judul" title="{{ $judul }}">{{ $judul }}</h1>

            <p class="hasil-banner__deskripsi">{{ $deskripsi }}</p>
        </div>
    </div>
</section>
