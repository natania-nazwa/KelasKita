@props([
    'kalender' => [],
])

{{--
    Panel "Kalender" di sidebar: tampilan bulan berjalan.

    Grid-nya sudah dihitung di server (App\Support tidak perlu disentuh),
    termasuk sel kosong di depan supaya tanggal 1 jatuh di kolom yang benar
    dan baris terakhir dilengkapi ke tujuh kolom.

    Navigasi bulan sebelumnya / berikutnya sengaja dibuat sebagai link biasa
    (?bulan=YYYY-MM), bukan tombol JavaScript, jadi tetap jalan walau JS
    dimatikan dan bisa dibagikan lewat URL.

    Bentuk array yang diharapkan:
    nama_bulan, nama_hari[], sel[], sebelumnya, berikutnya
--}}

<section class="dash-kartu dash-panel min-w-0" aria-label="Kalender"
    style="--k: #6c4de6; --k-gelap: #5a3fd4;">

    {{-- Aksi "Lihat Semua" disembunyikan: panel kalender tidak punya halaman
         tujuan sendiri, navigasi sudah lewat tombol bulan sebelumnya dan
         berikutnya di dalam panel. --}}
    <x-dashboard.kepala judul="Kalender" ikon="kalender" label="" />

    <div class="kalender-kepala">
        <p class="kalender-judul">{{ $kalender['nama_bulan'] }}</p>

        <span class="kalender-nav">
            <a href="{{ $kalender['sebelumnya'] }}" rel="prev" aria-label="Bulan sebelumnya">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>

            <a href="{{ $kalender['berikutnya'] }}" rel="next" aria-label="Bulan berikutnya">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        </span>
    </div>

    <div class="kalender-grid">
        @foreach ($kalender['nama_hari'] as $hari)
            <span class="kalender-hari" aria-hidden="true">{{ $hari }}</span>
        @endforeach

        @foreach ($kalender['sel'] as $tanggal)
            <span @class([
                'kalender-sel',
                'kalender-sel--luar' => ! $tanggal['dalam_bulan'],
                'kalender-sel--aktif' => $tanggal['hari_ini'],
            ])
                @if ($tanggal['hari_ini']) aria-current="date" @endif
                @if (filled($tanggal['acara'])) title="{{ $tanggal['angka'] }} - {{ $tanggal['acara'] }}" @endif>

                {{ $tanggal['angka'] }}

                @if (filled($tanggal['acara']))
                    <span class="kalender-acara" aria-hidden="true"></span>
                @endif
            </span>
        @endforeach
    </div>
</section>
