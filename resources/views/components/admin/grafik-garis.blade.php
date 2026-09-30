@props([
    /**
     * Deret data untuk grafik garis.
     *
     * Bentuk tiap butir: ['label' => 'Sen', 'nilai' => 12]
     * atau ['label' => 'Sen', 'nilai' => 12, 'warna' => '#5B8DEF'].
     */
    'data' => [],
    'tinggi' => 190,
    'warna' => '#6D4AFF',
    'labelNilai' => 'jumlah',
])

{{--
    Grafik garis (native SVG, tanpa pustaka chart).

    Satu deret nilai. Kalau deretnya kosong atau semuanya nol, garis
    tetap digambar di dasar kanvas supaya kartu tidak terlihat rusak,
    dan angka nolnya ditulis di tengah supaya kebaca sebagai data,
    bukan sebagai gambar yang gagal dimuat.

    Titik koordinat dihitung di Blade dari viewBox tetap, lalu
    diperlebar 100% lewat CSS: sama seperti grafik batang, hasilnya
    selalu pas di lebar kartu apa pun lebarnya.
--}}

@php
    $jumlah = count($data);
    $lebar = max(320, $jumlah * 56);
    $bawah = $tinggi - 24;
    $tinggiPlot = $bawah - 8;
    $maksimum = max(1, max(array_map(fn ($butir) => (int) $butir['nilai'], $data ?: [['nilai' => 0]])));
    $batas = (int) (ceil($maksimum / 4) * 4) ?: 4;

    // Lebar satu langkah antar titik. Kalau hanya ada satu titik, titik
    //nya diletakkan di tengah kanvas supaya tidak menempel tepi.
    $langkah = $jumlah > 1 ? ($lebar - 40) / ($jumlah - 1) : 0;

    $titik = [];
    foreach ($data as $b => $butir) {
        $x = $jumlah > 1 ? 20 + $langkah * $b : $lebar / 2;
        $tinggiIsi = ($tinggiPlot / $batas) * (int) $butir['nilai'];
        $titik[] = [
            'x' => round($x, 2),
            'y' => round($bawah - $tinggiIsi, 2),
            'label' => $butir['label'],
            'nilai' => (int) $butir['nilai'],
        ];
    }

    // Polyline butuh semua titik; polygone dibuat dari garis itu lalu
    // ditutup ke bawah supaya area di bawah garis bisa diwarnai.
    $garis = collect($titik)->map(fn ($t) => $t['x'].','.$t['y'])->implode(' ');
    $area = $jumlah > 0
        ? $titik[0]['x'].','.$bawah.' '.$garis.' '.$titik[$jumlah - 1]['x'].','.$bawah
        : '';
@endphp

<div {{ $attributes->class(['ad-grafik']) }}>
    <svg class="ad-grafik__kanvas" viewBox="0 0 {{ $lebar }} {{ $tinggi }}" role="img"
        aria-label="Grafik garis {{ $labelNilai }}">

        {{-- Garis bantu horizontal + angka di kiri. --}}
        @for ($i = 0; $i <= 4; $i++)
            @php
                $y = round($bawah - ($tinggiPlot / 4) * $i, 2);
                $angka = (int) round(($batas / 4) * $i);
            @endphp

            <line class="ad-grafik__kisi" x1="34" y1="{{ $y }}" x2="{{ $lebar }}" y2="{{ $y }}" />
            <text class="ad-grafik__label" x="28" y="{{ $y + 3 }}" text-anchor="end">{{ $angka }}</text>
        @endfor

        @if ($jumlah > 0)
            {{-- Area di bawah garis, sangat transparan supaya garisnya tetap dominan. --}}
            @if ($jumlah > 1)
                <polygon points="{{ $area }}" fill="{{ $warna }}" opacity="0.1" />
            @endif

            <polyline points="{{ $garis }}" fill="none" stroke="{{ $warna }}" stroke-width="3"
                stroke-linecap="round" stroke-linejoin="round" />

            {{-- Titik data: lingkaran putih dengan tepi ungu, jadi
                 terbaca di atas garis dan di atas garis bantu. --}}
            @foreach ($titik as $t)
                <circle cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="5" fill="#FFFFFF" stroke="{{ $warna }}"
                    stroke-width="3" />
            @endforeach

            {{-- Label kategori di bawah. --}}
            @foreach ($titik as $t)
                <text class="ad-grafik__label" x="{{ $t['x'] }}" y="{{ $tinggi - 6 }}" text-anchor="middle">
                    {{ $t['label'] }}
                </text>
            @endforeach
        @else
            <text class="ad-grafik__label" x="{{ $lebar / 2 }}" y="{{ $bawah / 2 }}" text-anchor="middle">
                Belum ada data
            </text>
        @endif
    </svg>
</div>
