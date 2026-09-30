@props([
    /**
     * Deret data untuk grafik garis.
     *
     * Bentuk tiap butir: ['label' => 'Sen', 'nilai' => 12]
     * atau ['label' => 'Sen', 'nilai' => 12, 'warna' => '#5B8DEF'].
     *
     * Untuk label yang panjang (mis. label mingguan "29 Sep") satu butir
     * boleh menambah dua kunci opsional:
     *
     *   'bulan'   baris kedua sumbu X ("5 Okt"), null kalau satu baris cukup
     *   'rentang' teks lengkap untuk tooltip dan pembaca layar
     */
    'data' => [],
    'tinggi' => 190,
    'warna' => '#6D4AFF',
    'labelNilai' => 'jumlah',
    /**
     * Lebar satu slot titik di dalam viewBox (bukan lebar di layar).
     *
     * Label hari hanya beberapa huruf, sedangkan label minggu bisa
     * "29 Sep". Slot yang lebih lebar membuat kanvas lebih lebar
     * supaya label tetap punya ruang dan tidak saling tumpang tindih.
     * Kanvas tetap diperlebar 100% lewat CSS, jadi grafik hanya jadi
     * lebih rapat, bukan lebih besar di layar.
     */
    'lebarTitik' => 56,
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

    Setiap titik punya <title> (tooltip bawaan browser, tanpa pustaka
    JavaScript) yang isinya rentang minggu dan angkanya, dan label sumbu
    X boleh dua baris lewat kunci 'bulan'.

    Warna garis bisa diganti lewat prop $warna; id gradientnya ikut
    diturunkan dari warna itu supaya dua grafik dengan warna berbeda di
    halaman yang sama tidak saling memakai gradient.
--}}

@php
    $jumlah = count($data);

    /*
     * Batas area gambar.
     *
     * $kiri menyisakan ruang untuk angka sumbu Y, $kanan memberi napas di
     * tepi kanan. Titik pertama dan terakhir selalu duduk tepat di dalam
     * batas ini, dan garis bantu mulai dari $kiri, jadi garis, area, titik,
     * dan kisi tidak pernah saling melewati. Kalau titik pertama digambar
     * di kiri garis bantu, garisnya terlihat keluar dari sumbu dan
     * melompati angka Y.
     */
    $kiri = 40;
    $kanan = 16;
    $lebar = max(320, $jumlah * $lebarTitik);
    $bawah = $tinggi - 24;
    $tinggiPlot = $bawah - 10;
    $maksimum = max(1, max(array_map(fn ($butir) => (int) $butir['nilai'], $data ?: [['nilai' => 0]])));
    $batas = (int) (ceil($maksimum / 4) * 4) ?: 4;

    // Id gradient dibuat dari warna garis supaya unik di satu halaman:
    // dua grafik ungu yang sama-sama memakai id yang sama akan saling
    // menimpa definisi gradient-nya.
    $idGradien = 'gradien-'.substr(md5($warna), 0, 8);

    // Lebar satu langkah antar titik. Kalau hanya ada satu titik, titik
    //nya diletakkan di tengah kanvas supaya tidak menempel tepi.
    $langkah = $jumlah > 1 ? ($lebar - $kiri - $kanan) / ($jumlah - 1) : 0;

    $titik = [];
    foreach ($data as $b => $butir) {
        $x = $jumlah > 1 ? $kiri + $langkah * $b : $lebar / 2;
        $tinggiIsi = ($tinggiPlot / $batas) * (int) $butir['nilai'];
        $titik[] = [
            'x' => round($x, 2),
            'y' => round($bawah - $tinggiIsi, 2),
            'label' => $butir['label'],
            'bulan' => $butir['bulan'] ?? null,
            'nilai' => (int) $butir['nilai'],
            'tooltip' => trim(($butir['rentang'] ?? $butir['label']).': '.(int) $butir['nilai'].' '.$labelNilai),
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

        {{-- Warna di bawah garis memudar ke bawah, jadi garis tetap yang
             dibaca mata dan area di bawahnya cuma supportive. --}}
        <defs>
            <linearGradient id="{{ $idGradien }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $warna }}" stop-opacity="0.28" />
                <stop offset="100%" stop-color="{{ $warna }}" stop-opacity="0" />
            </linearGradient>
        </defs>

        {{-- Garis bantu horizontal + angka di kiri. --}}
        @for ($i = 0; $i <= 4; $i++)
            @php
                $y = round($bawah - ($tinggiPlot / 4) * $i, 2);
                $angka = (int) round(($batas / 4) * $i);
            @endphp

            <line class="ad-grafik__kisi" x1="{{ $kiri - 6 }}" y1="{{ $y }}" x2="{{ $lebar - $kanan }}"
                y2="{{ $y }}" />
            <text class="ad-grafik__label" x="{{ $kiri - 12 }}" y="{{ $y + 3 }}" text-anchor="end">
                {{ $angka }}
            </text>
        @endfor

        @if ($jumlah > 0)
            {{-- Area di bawah garis diisi gradient, bukan warna polos, supaya
                 ujungnya lurun ke bawah dan tidak terlihat seperti kotak. --}}
            @if ($jumlah > 1)
                <polygon points="{{ $area }}" fill="url(#{{ $idGradien }})" />
            @endif

            <polyline points="{{ $garis }}" fill="none" stroke="{{ $warna }}" stroke-width="3"
                stroke-linecap="round" stroke-linejoin="round" />

            {{-- Titik data: lingkaran putih dengan tepi ungu, jadi
                 terbaca di atas garis dan di atas garis bantu. Tiap titik
                 punya <title> supaya angka minggu itu bisa dibaca tanpa
                 harus menebak dari garis. Lingkaran luar (halo) digambar
                 lebih dulu supaya titik utama tetap tajam di atasnya. --}}
            @foreach ($titik as $t)
                <circle class="ad-grafik__halo" cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="7" fill="{{ $warna }}"
                    opacity="0.14" />
            @endforeach

            @foreach ($titik as $t)
                <circle class="ad-grafik__titik" cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="4.5" fill="#FFFFFF"
                    stroke="{{ $warna }}" stroke-width="2.5">
                    <title>{{ $t['tooltip'] }}</title>
                </circle>
            @endforeach

            {{-- Label kategori di bawah, satu atau dua baris. --}}
            @foreach ($titik as $t)
                @if (filled($t['bulan']))
                    <text class="ad-grafik__label" x="{{ $t['x'] }}" y="{{ $tinggi - 13 }}" text-anchor="middle">
                        {{ $t['label'] }}
                    </text>

                    <text class="ad-grafik__label" x="{{ $t['x'] }}" y="{{ $tinggi - 2 }}" text-anchor="middle">
                        {{ $t['bulan'] }}
                    </text>
                @else
                    <text class="ad-grafik__label" x="{{ $t['x'] }}" y="{{ $tinggi - 6 }}" text-anchor="middle">
                        {{ $t['label'] }}
                    </text>
                @endif
            @endforeach
        @else
            <text class="ad-grafik__label" x="{{ $lebar / 2 }}" y="{{ $bawah / 2 }}" text-anchor="middle">
                Belum ada data
            </text>
        @endif
    </svg>
</div>
