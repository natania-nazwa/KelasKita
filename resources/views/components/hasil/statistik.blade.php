@props([
    // Array dari App\Support\StatistikHasil::ringkas()['kartu'].
    'kartu' => [],
])

{{--
    Empat kartu statistik: total quiz dikerjakan, rata-rata nilai, nilai
    tertinggi, dan waktu belajar.

    Kartu ini SELALU dirender, termasuk ketika belum ada satu pun quiz
    selesai. Angkanya lalu 0, dan itu memang jawaban database, bukan
    data dummy. Yang tidak boleh ada adalah angka pembanding yang
    dikarang, jadi catatan bawah kartu langsung menyebut kalau
    pembanding minggu lalu belum ada, tanpa panah naik/turun.

    Semua isi kartu sudah diformat di App\Support\StatistikHasil, jadi
    view ini tidak menghitung apa pun.
--}}

{{--
    Ponsel: grid-cols-2, jadi keempatnya jadi 2x2. Satu-satu menumpuk
    (grid-cols-1) butuh lebih dari satu layar penuh hanya untuk
    sekilas angka, sebelum daftar riwayat yang justru menjadi isi
    utama halaman ini belum kelihatan. Ukuran huruf dan padding kartu
    diturunkan lewat .hasil-kartu di blok media query app.css, karena
    kolom yang tersisa hanya sekitar 105px dan angka "166,7 jam" pada
    1,6rem akan keluar kartu.

    sm:grid-cols-2 lalu lg:grid-cols-4. minmax(0, 1fr) dan min-w-0
    mencegah kartu melebar sendiri saat angka atau labelnya panjang,
    jadi tidak ada scroll horizontal di ponsel.
--}}
<div {{ $attributes->class(['grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4 lg:gap-5']) }}>
    @foreach ($kartu as $item)
        <article class="hasil-kartu" style="--h: {{ $item['warna'] }};">

            <div class="hasil-kartu__kepala">
                <span class="hasil-kartu__ikon" aria-hidden="true">
                    <svg class="h-[1.125rem] w-[1.125rem]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($item['ikon']) }}" />
                    </svg>
                </span>

                <h2 class="hasil-kartu__label">{{ $item['label'] }}</h2>
            </div>

            <p class="hasil-kartu__nilai">
                {{ $item['nilai'] }}

                @if ($item['satuan'] !== null)
                    <span class="hasil-kartu__satuan">{{ $item['satuan'] }}</span>
                @endif
            </p>

            <p class="hasil-kartu__catatan hasil-kartu__catatan--{{ $item['catatan']['arah'] }}">
                @if ($item['catatan']['arah'] === 'naik')
                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0-6.75 6.75M12 4.5l6.75 6.75" />
                    </svg>
                @elseif ($item['catatan']['arah'] === 'turun')
                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0 6.75-6.75M12 19.5l-6.75-6.75" />
                    </svg>
                @endif

                <span class="truncate">{{ $item['catatan']['teks'] }}</span>
            </p>
        </article>
    @endforeach
</div>
