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

    Bentuk kartunya tetap .dash-kartu (yang sama dengan kartu dashboard)
    supaya deret ini terasa sebagai bagian dari aplikasi ini, bukan widget
    yang ditempel. Yang membedakan hanya warnanya: tiap kartu memakai warna
    kategorinya sendiri lewat --k / --k-gelap, jadi empat angka ini terlihat
    sebagai empat hal berbeda dan bukan empat angka yang sama.
--}}

@php
    $daftar = [
        [
            'nilai' => $materi,
            'label' => 'Materi Saya',
            'catatan' => 'materi yang sudah kamu buat',
            'ikon' => 'buku',
            'warna' => '#a78bfa',
            'warna_gelap' => '#6d4fd6',
        ],
        [
            'nilai' => $quiz,
            'label' => 'Quiz Saya',
            'catatan' => 'quiz yang sudah kamu buat',
            'ikon' => 'benar',
            'warna' => '#5ed3e8',
            'warna_gelap' => '#0f8fa8',
        ],
        [
            'nilai' => $soal,
            'label' => 'Total Soal',
            'catatan' => 'soal di semua quiz milikmu',
            'ikon' => 'dokumen',
            'warna' => '#f9a8b8',
            'warna_gelap' => '#d9557a',
        ],
        [
            'nilai' => $menunggu,
            'label' => 'Menunggu Persetujuan',
            'catatan' => $menunggu > 0 ? 'materi & quiz sedang ditinjau admin' : 'sudah ditinjau admin',
            'ikon' => 'jam',
            'warna' => '#fcd34d',
            'warna_gelap' => '#b07d09',
        ],
    ];
@endphp

{{-- Jarak ke elemen di atas dikendalikan oleh halaman, bukan di sini,
     supaya deret kartu ini bisa ditumpuk di atas papan kepala. --}}
<div data-reveal-stagger class="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($daftar as $item)
        <article style="--k: {{ $item['warna'] }}; --k-gelap: {{ $item['warna_gelap'] }};"
            class="dash-kartu karya-angka">

            <span class="dash-ikon-kotak" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($item['ikon']) }}" />
                </svg>
            </span>

            <p class="karya-angka__nilai">{{ $item['nilai'] }}</p>

            <p class="karya-angka__label">{{ $item['label'] }}</p>

            <p class="karya-angka__catatan">{{ $item['catatan'] }}</p>
        </article>
    @endforeach
</div>
