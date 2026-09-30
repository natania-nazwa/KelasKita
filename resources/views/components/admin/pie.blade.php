@props([
    /**
     * Deret data untuk pie chart.
     *
     * Bentuk tiap butir: ['label' => 'PPLG', 'nilai' => 24, 'warna' => '#6D4AFF']
     * "warna" wajib: pie chart memakai warna untuk membedakan iringan,
     * jadi setiap iringan harus punya warnanya sendiri.
     */
    'data' => [],
    'labelNilai' => 'dikerjakan',
])

{{--
    Donut chart (native SVG, tanpa pustaka chart).

    Bentuknya donat: cincin tebal dengan lubang di tengah, dan angka
    total berdiri di dalam lubang itu.

    Cara gambar iringannya: tiap iringan adalah satu lingkaran penuh
    (bukan busur) yang dipotong dengan stroke-dasharray, lalu digeser
    dengan stroke-dashoffset sebesar panjang iringan-iringan sebelumnya.
    Offset itulah yang membuat tiap iringan mulai tepat setelah iringan
    sebelumnya selesai, bukan semua menumpuk di jam 12. Sudut selalu mulai
    dari jam 12 dan bertambah searah jarum jam, jadi urutannya mengikuti
    urutan $data.

    Celah antar iringan dibuat konstan, bukan sisa pembagian, supaya
    jaraknya antar iringan sama rata dan donat tidak terlihat bergerigi.
    Iringan yang porsinya lebih kecil dari celah dilewati: memaksakan
    iringan sebesar celah akan membuat kategori kecil terlihat lebih besar
    dari porsi aslinya.

    Ukuran cincin menjaga diri supaya tidak pernah terpotong viewBox:
    viewBox 120x120 berarti jari terluar yang aman adalah 60, dan jari
    terluar cincin = jari + setengah tebal. 46 + 11 = 57, jadi masih ada
    3 unit sisa di keempat sisi. Kalau jari dan tebalnya dibuat lebih
    besar, cincin keluar dari kanvas dan sisi kiri-kanasnya terpotong.

    Deret kosong, atau semua nilainya nol, tidak menggambar apa pun dan
    menampilkan teks di tengah: kartu jadi terbaca "belum ada data",
    bukan kelihatan seperti gambar yang gagal dimuat.
--}}

@php
    $jumlah = count($data);
    $total = $jumlah > 0 ? (float) array_sum(array_column($data, 'nilai')) : 0.0;

    $jari = 46;
    $tebal = 22;
    $keliling = 2 * M_PI * $jari;
    $celah = 4;

    // Panjang busur tiap iringan, dihitung dari total, lalu digeser satu
    // per satu supaya tidak ada yang menumpuk.
    $iringan = [];
    $sudah = 0.0;

    if ($total > 0) {
        foreach ($data as $butir) {
            $porsi = ((float) $butir['nilai']) / $total * $keliling;
            $panjang = $porsi - $celah;

            if ($panjang > 0.6) {
                $porsen = round(((float) $butir['nilai']) / $total * 100, 1);
                $porsenTampil = rtrim(rtrim(number_format($porsen, 1, ',', ''), '0'), ',');

                $iringan[] = [
                    'warna' => $butir['warna'],
                    'panjang' => round($panjang, 2),
                    'ruang' => round($keliling - $panjang, 2),
                    'geser' => round(-$sudah, 2),
                    'tooltip' => $butir['label'].': '.$butir['nilai'].' '.$labelNilai.' ('.$porsenTampil.'%)',
                    'label' => $butir['label'],
                    'nilai' => $butir['nilai'],
                    'porsen' => $porsenTampil,
                ];
            }

            $sudah += $porsi;
        }
    }
@endphp

<div {{ $attributes->class(['ad-grafik']) }}>
    <div class="ad-donat__susun">
        <div class="ad-donat__lingkaran">
            <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img"
                aria-label="Donat chart {{ $labelNilai }}">

                {{-- Cincin latar: membuat celah antar iringan terlihat
                     disengaja, bukan sekadar ruang kosong. --}}
                <circle cx="60" cy="60" r="{{ $jari }}" fill="none" stroke="#F1EEFF"
                    stroke-width="{{ $tebal }}" />

                @foreach ($iringan as $iring)
                    {{-- <title> memberi tooltip bawaan browser: nama,
                         jumlah, dan porsinya, tanpa pustaka JavaScript. --}}
                    <circle class="ad-donat__iris" cx="60" cy="60" r="{{ $jari }}" fill="none"
                        stroke="{{ $iring['warna'] }}" stroke-width="{{ $tebal }}"
                        stroke-dasharray="{{ $iring['panjang'] }} {{ $iring['ruang'] }}"
                        stroke-dashoffset="{{ $iring['geser'] }}">
                        <title>{{ $iring['tooltip'] }}</title>
                    </circle>
                @endforeach
            </svg>

            <span class="ad-donat__tengah">
                <span class="ad-donat__angka">{{ (int) $total }}</span>
                <span class="ad-donat__label">{{ $labelNilai }}</span>
            </span>
        </div>

        {{-- Legenda. Warna di sini memakai nilai yang sama persis dengan
             warna busurnya, jadi tidak ada warna yang bolong di tabel, dan
             tiap baris diberi semburat warna sendiri supaya tabelnya tidak
             abu-abu. --}}
        <ul class="ad-donat__legenda">
            @forelse ($iringan as $iring)
                <li class="ad-donat__baris" style="--ad-donat: {{ $iring['warna'] }};">
                    <span class="ad-donat__titik" aria-hidden="true"></span>

                    <span class="ad-donat__nama">{{ $iring['label'] }}</span>

                    <span class="ad-donat__angka-kecil">{{ $iring['nilai'] }}</span>
                    <span class="ad-donat__porsen">{{ $iring['porsen'] }}%</span>
                </li>
            @empty
                <li class="ad-donat__kosong">Belum ada {{ $labelNilai }}.</li>
            @endforelse
        </ul>
    </div>
</div>
