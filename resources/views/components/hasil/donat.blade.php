@props([
    // Persentase 0-100 untuk panjang busur donat.
    'persen' => 0,
    'label' => 'Rata-rata Nilai',
    'warna' => '#6c4de6',
    // Angka pada legenda, semuanya dari agregasi di server.
    'benar' => 0,
    'salah' => 0,
    'soal' => 0,
])

{{--
    Donat / circular chart untuk rata-rata nilai.

    Digambar langsung sebagai SVG, bukan memakai pustaka chart:
    proyek ini tidak punya library chart dan donat ini hanya butuh satu
    lingkaran plus satu busur, jadi menambah dependency justru berlebihan.
    Pustaka baru tidak ditambahkan tanpa perlu, sesuai aturan proyek.

    Cara hitung busurnya: keliling lingkaran (2 * PI * r) dikalikan
    persen, lalu dipotong dari circle atas. Sudut selalu mulai dari atas
    (12 o'clock) dan bertambah searah jarum jam.
--}}

@php
    $persen = max(0.0, min(100.0, (float) $persen));
    $jari = 54;
    $keliling = 2 * M_PI * $jari;
    $panjang = $keliling * ($persen / 100);
    $tebal = 13;
    $teks = rtrim(rtrim(number_format($persen, 1, ',', ''), '0'), ',');
@endphp

<div class="hasil-donat">
    <div class="relative mx-auto h-40 w-40 shrink-0">
        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img"
            aria-label="{{ $label }}: {{ $teks }} persen">

            {{-- Jalur latar: lingkaran penuh, warna lavender. --}}
            <circle cx="60" cy="60" r="{{ $jari }}" fill="none" stroke="var(--color-lavender)"
                stroke-width="{{ $tebal }}" />

            {{-- Busur nilai. Kalau 0, lingkaran penuh dirender sebagai
                 stroke abu-abu supaya kartu tidak terasa rusak. --}}
            @if ($persen > 0)
                <circle cx="60" cy="60" r="{{ $jari }}" fill="none" stroke="{{ $warna }}"
                    stroke-width="{{ $tebal }}" stroke-linecap="round"
                    stroke-dasharray="{{ round($panjang, 2) }} {{ round($keliling, 2) }}" />
            @else
                <circle cx="60" cy="60" r="{{ $jari }}" fill="none" stroke="#e2e0ee"
                    stroke-width="{{ $tebal }}" />
            @endif
        </svg>

        {{-- Angka di tengah lingkaran. --}}
        <span class="hasil-donat__tengah">
            <span class="hasil-donat__angka">
                {{ $persen > 0 ? $teks : '0' }}<span class="hasil-donat__persen">%</span>
            </span>

            <span class="hasil-donat__label">{{ $label }}</span>
        </span>
    </div>

    {{-- Legenda. Nilai diambil dari server, bukan dihitung ulang di
         markup, supaya angka yang tampil sama dengan angka yang disimpan. --}}
    <dl class="hasil-donat__keterangan">
        <div class="hasil-donat__baris">
            <dt>
                <span class="hasil-donat__titik hasil-donat__titik--benar" aria-hidden="true"></span>
                Benar
            </dt>

            <dd>{{ $benar }}</dd>
        </div>

        <div class="hasil-donat__baris">
            <dt>
                <span class="hasil-donat__titik hasil-donat__titik--salah" aria-hidden="true"></span>
                Salah
            </dt>

            <dd>{{ $salah }}</dd>
        </div>

        <div class="hasil-donat__baris">
            <dt>
                <span class="hasil-donat__titik hasil-donat__titik--soal" aria-hidden="true"></span>
                Total Soal
            </dt>

            <dd>{{ $soal }}</dd>
        </div>
    </dl>
</div>
