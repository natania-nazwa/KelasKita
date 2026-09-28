@props([
    // Array dari App\Support\StatistikHasil::ringkas().
    'ringkasan' => [],
])

{{--
    Kartu "Statistik Belajar" untuk kolom kanan: donat rata-rata nilai
    di kiri, legend Benar / Salah / Total Soal di kanannya.

    Angka legend semuanya dari agregasi di server. Kalau belum ada quiz
    selesai, donat menampilkan 0% dan catatan di bawahnya menjelaskan
    supaya kartu tidak terlihat rusak.
--}}

@php
    // Busur donat dihitung sekali di sini, bukan dua kali di dalam
    // markup, supaya lingkaran dan angkanya dijamin konsisten.
    $lingkaran = \App\Support\Angka::cincin((float) $ringkasan['rata_rata']);
    $persen = \App\Support\Angka::teks((float) $ringkasan['rata_rata']);
@endphp

<section {{ $attributes->class(['hasil-panel']) }}>
    <header class="hasil-panel__kepala">
        <h2 class="hasil-panel__judul">Statistik Belajar</h2>

        <span class="hasil-panel__lencana">{{ $ringkasan['total_selesai'] }} selesai</span>
    </header>

    <div class="hasil-panel__badan">

        {{-- Donat + legend. md:flex-row supaya di layar sempit legend
             turun ke bawah donat, bukan memaksanya sempit. --}}
        <div class="hasil-donat">

            <div class="hasil-donat__lingkaran">
                <svg viewBox="0 0 120 120" class="hasil-donat__svg" role="img"
                    aria-label="Rata-rata nilai: {{ $persen }} persen">

                    <circle cx="60" cy="60" r="54" fill="none" stroke="var(--color-lavender)" stroke-width="13" />

                    @if ($ringkasan['total_selesai'] > 0)
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#6c4de6" stroke-width="13"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $lingkaran['panjang'] }} {{ $lingkaran['keliling'] }}" />
                    @else
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e2e0ee" stroke-width="13" />
                    @endif
                </svg>

                <span class="hasil-donat__tengah">
                    <span class="hasil-donat__angka">{{ $persen }}<i>%</i></span>

                    <span class="hasil-donat__label">Rata-rata Nilai</span>
                </span>
            </div>

            <dl class="hasil-donat__keterangan">
                <div class="hasil-donat__baris">
                    <dt>
                        <span class="hasil-donat__titik hasil-donat__titik--benar" aria-hidden="true"></span>
                        Benar
                    </dt>

                    <dd>{{ $ringkasan['total_benar'] }}</dd>
                </div>

                <div class="hasil-donat__baris">
                    <dt>
                        <span class="hasil-donat__titik hasil-donat__titik--salah" aria-hidden="true"></span>
                        Salah
                    </dt>

                    <dd>{{ $ringkasan['total_salah'] }}</dd>
                </div>

                <div class="hasil-donat__baris">
                    <dt>
                        <span class="hasil-donat__titik hasil-donat__titik--soal" aria-hidden="true"></span>
                        Total Soal
                    </dt>

                    <dd>{{ $ringkasan['total_soal'] }}</dd>
                </div>
            </dl>
        </div>

        <p class="hasil-panel__catatan">
            @if ($ringkasan['total_selesai'] > 0)
                {{ $ringkasan['total_soal'] }} soal dari {{ $ringkasan['total_selesai'] }} quiz selesai
                &middot; {{ max(0, $ringkasan['total_soal'] - $ringkasan['total_dijawab']) }} soal dilewati
            @else
                Donat akan terisi begitu quiz pertamamu selesai.
            @endif
        </p>
    </div>
</section>
