@props([
    'materi' => 0,
    'quiz' => 0,
    'soal' => 0,
    'menunggu' => 0,
])

{{--
    Empat kartu ringkasan di atas halaman "Karya Saya".

    Angkanya sengaja mencakup seluruh karya pengguna, bukan hanya kartu yang
    sedang tampil: jadi jumlahnya tetap sama walau berpindah tab atau
    memuat halaman berikutnya.

    Kartu "Menunggu Persetujuan" menghitung materi dan quiz sekaligus karena
    keduanya lewat persetujuan admin yang sama, jadi pemiliknya tidak perlu
    membuka tiap tab untuk tahu apakah masih ada yang ditunggu.

    Kartu putih dan kotak ikon memakai .dash-kartu / .dash-ikon-kotak yang
    sama dengan kartu dashboard, supaya deret ini terasa sebagai bagian dari
    aplikasi ini, bukan widget yang ditempel.
--}}

@php
    $daftar = [
        [
            'nilai' => $materi,
            'label' => 'Materi Saya',
            'catatan' => 'materi yang sudah kamu buat',
            'ikon' => 'buku',
            'warna' => '#8b7bf0',
            'warna_gelap' => '#5b46cf',
        ],
        [
            'nilai' => $quiz,
            'label' => 'Quiz Saya',
            'catatan' => 'quiz yang sudah kamu buat',
            'ikon' => 'benar',
            'warna' => '#4fd0e0',
            'warna_gelap' => '#0e8ba0',
        ],
        [
            'nilai' => $soal,
            'label' => 'Total Soal',
            'catatan' => 'soal di semua quiz milikmu',
            'ikon' => 'dokumen',
            'warna' => '#f78299',
            'warna_gelap' => '#d63a63',
        ],
        [
            'nilai' => $menunggu,
            'label' => 'Menunggu Persetujuan',
            'catatan' => $menunggu > 0 ? 'materi & quiz sedang ditinjau admin' : 'sudah ditinjau admin',
            'ikon' => 'jam',
            'warna' => '#f6cd6b',
            'warna_gelap' => '#b8830c',
        ],
    ];
@endphp

<div data-reveal-stagger class="mt-6 grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($daftar as $item)
        <article style="--k: {{ $item['warna'] }}; --k-gelap: {{ $item['warna_gelap'] }};" class="dash-kartu karya-angka">
            <span class="dash-ikon-kotak" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($item['ikon']) }}" />
                </svg>
            </span>

            <p class="karya-angka__nilai">{{ $item['nilai'] }}</p>

            <p class="karya-angka__label">{{ $item['label'] }}</p>

            <p class="karya-angka__catatan">{{ $item['catatan'] }}</p>
        </article>
    @endforeach
</div>
