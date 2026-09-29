@props([
    'quiz',
])

@php
    /*
     * Satu kartu quiz di section "Quiz Terbaru" dashboard.
     *
     * Bentuk array yang diharapkan (sama persis dengan hasil
     * App\Support\DaftarQuiz::petkan, jadi sumber datanya bisa diganti API):
     *   id, slug, judul, deskripsi, thumbnail, durasi, jumlah_soal,
     *   tingkat_kesulitan, saya, tautan,
     *   kategori => [nama, slug, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     *
     * Susunannya sengaja meniru kartu materi (x-dashboard.materi-kartu):
     * banner 16:9 di atas, garis aksen, judul, deskripsi, lalu baris meta.
     * Dua section itu jadi terbaca sebagai satu set, bukan dua gaya berbeda.
     */
    $kategori = $quiz['kategori'];
    $pembuat = $quiz['pembuat'];
    $jumlahSoal = (int) $quiz['jumlah_soal'];
    $durasi = (int) ($quiz['durasi'] ?? 0);
@endphp

<a href="{{ $quiz['tautan'] }}"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="dash-kartu dash-kartu--pindah dash-quiz min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    aria-label="Buka quiz {{ $quiz['judul'] }}">

    {{-- A. Banner 16:9. Kalau quiz punya gambar, gambarnya yang dipakai;
         kalau belum ada, banner memakai gradasi warna kategori + ikon mapel. --}}
    <div class="dash-quiz__gambar">
        @if (filled($quiz['thumbnail'] ?? null))
            <img src="{{ $quiz['thumbnail'] }}" alt="" loading="lazy" class="dash-quiz__foto">
        @else
            <span class="dash-quiz__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- B. Durasi, melayang di pojok kiri atas banner. --}}
        @if ($durasi > 0)
            <span class="dash-quiz__durasi">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                </svg>
                {{ $durasi }} Menit
            </span>
        @endif

        {{-- C. Bookmark: tombol, bukan tautan, supaya diklik tidak membuka
             halaman detail. initBookmark() di app.js yang mengurusnya. --}}
        <button type="button" data-bookmark="{{ $quiz['id'] }}" data-bookmark-ruang="quiz" aria-pressed="false"
            class="dash-quiz__simpan"
            aria-label="Simpan quiz {{ $quiz['judul'] }} untuk dikerjakan nanti">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        </button>

        {{-- D. Badge kategori, melayang di pojok kiri bawah banner. --}}
        <span class="dash-quiz__lencana">{{ $kategori['nama'] }}</span>

        {{-- E. Penanda aksi, muncul saat kursor di atas kartu. --}}
        <span class="dash-quiz__aksi" aria-hidden="true">
            Kerjakan
            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </div>

    {{-- F-I. Judul, deskripsi, dan informasi pembuat. --}}
    <div class="dash-quiz__badan">
        {{-- Garis aksen warna kategori, pemisah dari banner. -mx-3.5 -mt-3.5
             menetralkan padding badan supaya garisnya menyambung ke banner. --}}
        <span class="dash-quiz__aksen -mx-3.5 -mt-3.5 mb-2.5 block" aria-hidden="true"></span>

        <div class="flex items-start gap-1.5">
            <h3 class="dash-quiz__judul min-w-0">{{ $quiz['judul'] }}</h3>

            {{-- Quiz milik pengguna yang sedang login ditandai, supaya
                 karyanya mudah dikenali di antara quiz yang lain. --}}
            @if ($quiz['saya'] ?? false)
                <span class="dash-quiz__milik">Quiz Saya</span>
            @endif
        </div>

        <p class="dash-quiz__deskripsi">{{ $quiz['deskripsi'] }}</p>

        {{-- margin-top:auto mendorong meta ke dasar kartu, jadi semua kartu
             dalam satu baris tetap sama tinggi. --}}
        <div class="dash-quiz__meta">
            <span class="dash-quiz__pembuat">
                <span class="dash-avatar"
                    style="--a: {{ $pembuat['warna'] }}; --a-gelap: {{ $pembuat['warna_gelap'] }};"
                    aria-hidden="true">{{ $pembuat['inisial'] }}</span>

                <span class="dash-quiz__nama">{{ $pembuat['nama'] }}</span>
            </span>

            <span class="dash-quiz__jumlah">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('dokumen') }}" />
                </svg>
                {{ $jumlahSoal }} Soal
            </span>
        </div>
    </div>
</a>
