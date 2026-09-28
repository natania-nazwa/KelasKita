@props([
    'daftar' => [],
])

{{--
    Panel "Jadwal Hari Ini" di sidebar.

    Tiap baris: jam mulai/selesai, indikator warna tipis, judul pelajaran,
    dan kelas. Warna diambil dari katalog Pelajaran lewat data, baris yang
    statusnya "Selesai" tampil redup.
--}}

<section class="dash-kartu dash-panel min-w-0" aria-label="Jadwal hari ini"
    style="--k: #6c4de6; --k-gelap: #5a3fd4;">

    <x-dashboard.kepala judul="Jadwal Hari Ini" ikon="jam" label="Lihat Semua" />

    <div data-reveal-stagger class="grid gap-0.5">
        @forelse ($daftar as $item)
            <div style="--k: {{ $item['warna'] }}; --k-gelap: {{ $item['warna_gelap'] }};"
                @class([
                    'dash-jadwal',
                    'dash-jadwal--lewat' => $item['status'] === 'Selesai',
                ])
                title="{{ $item['judul'] }} - {{ $item['kelas'] }} ({{ $item['status'] }})">

                <span class="dash-jadwal__waktu">
                    <span class="dash-jadwal__mulai">{{ $item['mulai'] }}</span>
                    <span class="dash-jadwal__selesai">{{ $item['selesai'] }}</span>
                </span>

                <span class="dash-jadwal__indikator" aria-hidden="true"></span>

                <span class="min-w-0 flex-1">
                    <span class="dash-jadwal__judul">{{ $item['judul'] }}</span>
                    <span class="dash-jadwal__kelas">{{ $item['kelas'] }}</span>
                </span>

                <span class="dash-ikon-kotak" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                    </svg>
                </span>
            </div>
        @empty
            <p class="py-2 text-center text-xs text-dark/45">Tidak ada jadwal hari ini.</p>
        @endforelse
    </div>
</section>
