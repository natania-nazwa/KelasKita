@props([
    // Daftar soal dari App\Support\DaftarSoal.
    'soal' => [],
    // Tautan "Lihat semua": daftar quiz lain di kategori yang sama.
    'tautan' => null,
])

{{--
    Seksi "Daftar Soal" di bawah kepala halaman detail quiz.

    Setiap soal adalah satu baris yang bisa dilipat: pertanyaannya selalu
    terlihat, isinya baru dilihat setelah baris diklik. Isinya hanya
    pertanyaannya dan keempat pilihannya — jawaban benar dan pembahasan
    sengaja tidak ikut (lihat App\Support\DaftarSoal), supaya halaman ini
    tidak membocorkan kunci soal sebelum quiz dikerjakan.

    Barisnya dibuat sebagai tombol di dalam <h3>, pola yang sama dengan
    Database WAI untuk akordion, supaya pembaca layar tetap tahu baris ini
    bisa dibuka dan sedang terbuka atau tertutup lewat aria-expanded.
--}}

<section {{ $attributes->class(['mt-6 sm:mt-7']) }} aria-labelledby="judul-daftar-soal">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <h2 id="judul-daftar-soal" class="text-base font-extrabold tracking-tight text-dark sm:text-lg">
            Daftar Soal
        </h2>

        @if (filled($tautan))
            <a href="{{ $tautan }}" class="text-xs font-semibold text-primary transition hover:text-primary-dark">
                Lihat semua
            </a>
        @endif
    </div>

    @if (count($soal) === 0)
        {{-- Quiz tanpa soal: kartu putih dengan garis putus-putus, supaya
             jelas memang belum ada isinya, bukan daftar yang gagal memuat
             data. --}}
        <div class="rounded-2xl border border-dashed border-lavender bg-white px-5 py-10 text-center">
            <h3 class="text-sm font-bold text-dark">Belum ada soal</h3>

            <p class="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-dark/55">
                Quiz ini belum memiliki soal. Pembuatnya bisa menambahkannya lewat form tambah quiz.
            </p>
        </div>
    @else
        <ol class="daftar-soal kartu-detail">
            @foreach ($soal as $item)
                @php
                    $sasar = 'soal-'.$item['nomor'].'-isi';
                @endphp

                <li class="daftar-soal__baris">
                    <h3>
                        <button type="button" class="daftar-soal__tombol" data-soal-lipat
                            aria-expanded="false" aria-controls="{{ $sasar }}">
                            <span class="daftar-soal__nomor" aria-hidden="true">{{ $item['nomor'] }}</span>

                            <span class="daftar-soal__teks">{{ $item['pertanyaan'] }}</span>

                            {{-- Berputar 180 derajat saat baris dibuka, jadi
                                 ikon turun berubah jadi ikon naik tanpa
                                 menukar SVG di markup. --}}
                            <svg class="daftar-soal__panah" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </h3>

                    {{--
                        Atribut hidden dipakai JS untuk menutup baris yang
                        belum diklik. Tanpa JavaScript aturannya dilepas lagi
                        oleh blok @media (scripting: none) di app.css, jadi
                        pilihan jawabannya tetap terbaca.
                    --}}
                    <div class="daftar-soal__isi" id="{{ $sasar }}" data-soal-isi hidden>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            @foreach ($item['pilihan'] as $huruf => $isi)
                                <li class="daftar-soal__pilihan">
                                    <span class="daftar-soal__huruf" aria-hidden="true">{{ $huruf }}</span>

                                    <span class="min-w-0">{{ $isi }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
