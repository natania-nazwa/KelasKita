@props([
    /**
     * Deret data untuk grafik batang.
     *
     * Bentuk tiap butir: ['label' => 'Sen', 'nilai' => 4, 'warna' => '#6D4AFF']
     * "warna" opsional: kalau tidak diisi, semua batang memakai warna
     * gradien yang sama supaya grafik tidak jadi pelangi.
     */
    'data' => [],
    'tinggi' => 190,
    'warna' => '#6D4AFF',
    'warnaGelap' => '#8B6CFF',
    'labelNilai' => 'jumlah',
])

{{--
    Grafik batang sederhana (native SVG, tanpa pustaka chart).

    Proyek ini belum memakai library chart, dan grafik admin ini hanya
    butuh batang + garis bantu, jadi menambah dependency tidak
    sebanding. SVG yang sama sudah dipakai di halaman Hasil milik user.

    Skala tinggi dan lebar memakai viewBox, lalu diperlebar 100% lewat
    CSS. Efeknya grafik selalu pas di lebar kartunya, dari 320px sampai
    layar lebar, tanpa perlu menghitung ulang titik di JavaScript.
--}}

@php
    $jumlah = count($data);
    $lebar = max(320, $jumlah * 64);
    $bawah = $tinggi - 24;
    $tinggiBatang = $bawah - 8;
    $lebarBatang = $jumlah > 0 ? max(10.0, min(34.0, ($lebar / $jumlah) * 0.5)) : 10.0;
    $maksimum = max(1, max(array_map(fn ($butir) => (int) $butir['nilai'], $data ?: [['nilai' => 0]])));
    // Pembulatan ke atas supaya batang tertinggi tidak pernah menyentuh
    // garis paling atas dan terlihat seperti keluar dari kanvas.
    $batas = (int) (ceil($maksimum / 4) * 4) ?: 4;
@endphp

<div {{ $attributes->class(['ad-grafik']) }}>
    <svg class="ad-grafik__kanvas" viewBox="0 0 {{ $lebar }} {{ $tinggi }}" role="img"
        aria-label="Grafik batang {{ $labelNilai }}">

        {{-- Garis bantu horizontal + angka di kiri. --}}
        @for ($i = 0; $i <= 4; $i++)
            @php
                $y = $bawah - ($tinggiBatang / 4) * $i;
                $angka = (int) round(($batas / 4) * $i);
            @endphp

            <line class="ad-grafik__kisi" x1="34" y1="{{ round($y, 2) }}" x2="{{ $lebar }}"
                y2="{{ round($y, 2) }}" />
            <text class="ad-grafik__label" x="28" y="{{ round($y + 3, 2) }}" text-anchor="end">
                {{ $angka }}
            </text>
        @endfor

        {{-- Batang + label kategori. --}}
        @foreach ($data as $b => $butir)
            @php
                $tinggiIsi = $batas > 0 ? ($tinggiBatang / $batas) * (int) $butir['nilai'] : 0;
                $tinggiIsi = max((int) $butir['nilai'] > 0 ? 4.0 : 0.0, $tinggiIsi);
                $tengah = ($lebar / $jumlah) * $b + ($lebar / $jumlah) / 2;
                $xKiri = round($tengah - $lebarBatang / 2, 2);
                $yAtas = round($bawah - $tinggiIsi, 2);
                $warnaBatang = $butir['warna'] ?? $warna;
            @endphp

            <rect x="{{ $xKiri }}" y="{{ $yAtas }}" width="{{ round($lebarBatang, 2) }}" height="{{ round($tinggiIsi, 2) }}"
                rx="5" fill="{{ $warnaBatang }}" opacity="{{ $loop->last ? 1 : 0.82 }}" />

            <text class="ad-grafik__label" x="{{ round($tengah, 2) }}" y="{{ $tinggi - 6 }}" text-anchor="middle">
                {{ $butir['label'] }}
            </text>
        @endforeach
    </svg>
</div>
