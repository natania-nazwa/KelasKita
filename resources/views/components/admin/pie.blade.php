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
    Pie chart (native SVG, tanpa pustaka chart).

    Cara gambar busurnya sama dengan donat di halaman Hasil milik user:
    tiap iringan digambar sebagai lingkaran penuh (pie, bukan donat) yang
    dipotong dua busur. Sudut selalu mulai dari jam 12 dan bertambah
    searah jarum jam, jadi urutannya mengikuti urutan $data.

    Deret kosong, atau semua nilainya nol, tidak menggambar apa pun dan
    menampilkan teks di tengah: kartu jadi terbaca "belum ada data",
    bukan kelihatan seperti gambar yang gagal dimuat.
--}}

@php
    $jumlah = count($data);
    $total = $jumlah > 0 ? (float) array_sum(array_column($data, 'nilai')) : 0.0;
    $jari = 54;
    $keliling = 2 * M_PI * $jari;
@endphp

<div {{ $attributes->class(['ad-grafik']) }}>
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
        <div class="relative h-40 w-40 shrink-0">
            <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img"
                aria-label="Pie chart {{ $labelNilai }}">

                @if ($total > 0)
                    @foreach ($data as $butir)
                        @php
                            $porsi = ((float) $butir['nilai']) / $total * $keliling;
                            $panjang = $porsi >= $keliling - 0.01 ? $keliling : round($porsi, 2);
                            $ruang = round(max(0.0, $keliling - $porsi - 1.5), 2);
                        @endphp

                        <circle cx="60" cy="60" r="{{ $jari }}" fill="none" stroke="{{ $butir['warna'] }}"
                            stroke-width="26"
                            stroke-dasharray="{{ $panjang }} {{ $ruang }}" />
                    @endforeach
                @else
                    <circle cx="60" cy="60" r="{{ $jari }}" fill="none" stroke="#E8E4F5" stroke-width="26" />
                @endif
            </svg>

            <span class="ad-pie__tengah">
                <span class="ad-pie__angka">{{ (int) $total }}</span>
                <span class="ad-pie__label">{{ $labelNilai }}</span>
            </span>
        </div>

        {{-- Legenda. Warna di sini memakai nilai yang sama persis dengan
             warna busurnya, jadi tidak ada warna yang bolong di tabel. --}}
        <ul class="ad-pie__legenda">
            @forelse ($data as $butir)
                @php
                    $porsen = $total > 0 ? round(((float) $butir['nilai']) / $total * 100, 1) : 0;
                @endphp

                <li class="ad-pie__baris">
                    <span class="ad-pie__titik" style="background-color: {{ $butir['warna'] }}" aria-hidden="true"></span>

                    <span class="ad-pie__nama">{{ $butir['label'] }}</span>

                    <span class="ad-pie__angka-kecil">{{ $butir['nilai'] }}</span>
                    <span class="ad-pie__porsen">{{ rtrim(rtrim(number_format($porsen, 1, ',', ''), '0'), ',') }}%</span>
                </li>
            @empty
                <li class="text-xs text-[#77739A]">Belum ada pelajaran yang dikerjakan.</li>
            @endforelse
        </ul>
    </div>
</div>
