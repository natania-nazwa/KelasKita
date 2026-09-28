@props([
    // Array dari App\Support\DaftarJadwal::bulan().
    'kalender' => [],
    'tanggal' => null,
])

{{--
    Kalender mini bulan berjalan untuk sidebar halaman jadwal.

    Berbeda dari kalender di dashboard, setiap tanggal di sini adalah link
    yang benar-benar bisa diklik untuk pindah hari, dan titik di bawah
    tanggal menunjukkan jumlah pelajaran pada hari itu. Jadi panel ini
    berfungsi sebagai pemilih tanggal kedua, selain strip tujuh hari di
    kepala halaman.

    Navigasi bulan sebelumnya / berikutnya sengaja link biasa
    (?bulan=YYYY-MM), bukan tombol JavaScript, supaya tetap jalan walau JS
    dimatikan dan URL-nya bisa dibagikan.

    Bentuk array yang diharapkan:
    nama_bulan, nama_hari[], sel[], sebelumnya, berikutnya
--}}

<section class="jadwal-kartu" aria-label="Kalender jadwal"
    style="--k: var(--color-primary); --k-gelap: var(--color-primary-dark);">

    <div class="jadwal-kartu__kepala">
        <span class="jadwal-kartu__ikon" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalender') }}" />
            </svg>
        </span>

        <h2 class="jadwal-kartu__judul">Kalender</h2>
    </div>

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

        @foreach ($kalender['sel'] as $sel)
            @php $aktif = $sel['tanggal'] !== null && $sel['tanggal'] === $tanggal?->toDateString(); @endphp

            @if ($sel['tautan'] === null)
                {{-- Tanggal dari bulan sebelumnya / berikutnya: tidak punya
                     jadwal, jadi hanya jadi teks abu, bukan link. --}}
                <span @class([
                    'kalender-sel',
                    'kalender-sel--luar',
                    'kalender-sel--aktif' => $aktif,
                ]) aria-hidden="true">
                    {{ $sel['angka'] }}
                </span>
            @else
                <a href="{{ $sel['tautan'] }}" @class([
                    'kalender-sel',
                    'kalender-sel--aktif' => $aktif,
                ]) @if ($aktif) aria-current="date" @endif
                    @if ($sel['hari_ini']) aria-label="Hari ini" @endif
                    @if ($sel['jumlah'] > 0) title="{{ $sel['angka'] }} - {{ $sel['jumlah'] }} pelajaran" @endif>
                    {{ $sel['angka'] }}

                    @if ($sel['jumlah'] > 0)
                        <span class="kalender-acara" aria-hidden="true"></span>
                    @endif
                </a>
            @endif
        @endforeach
    </div>

    <p class="jadwal-kartu__catatan">
        Titik ungu pada tanggal berarti ada pelajaran di hari itu. Klik tanggalnya untuk membuka jadwalnya.
    </p>
</section>
