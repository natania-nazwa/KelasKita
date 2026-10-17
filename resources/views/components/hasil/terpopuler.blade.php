@props([
    // Array dari App\Support\StatistikHasil::ringkas()['terpopuler'].
    'daftar' => [],
])

{{--
    Kartu "Quiz Terpopuler" untuk kolom kanan.

    Angkanya dihitung dari pengerjaan seluruh pengguna, jadi quiz yang
    tampil memang yang paling sering dikerjakan di platform. Tidak ada
    jumlah ribuan yang dikarang.
--}}

<section {{ $attributes->class(['hasil-panel']) }}>
    <header class="hasil-panel__kepala">
        <h2 class="hasil-panel__judul">Quiz Terpopuler</h2>

        @if (filled($daftar))
            <a href="{{ route('user.quiz') }}" class="hasil-panel__aksi">Semua Quiz</a>
        @endif
    </header>

    <div class="hasil-panel__badan">
        @forelse ($daftar as $item)
            {{-- Separator tipis antar item, bukan di item terakhir. --}}
            <a href="{{ $item['tautan'] }}" class="hasil-top"
                @if (! $loop->last) data-belum-terakhir="true" @endif>

                <span class="hasil-top__peringkat" @if ($item['peringkat'] <= 3) data-medali="{{ $item['peringkat'] }}" @endif>
                    {{ $item['peringkat'] }}
                </span>

                {{-- Thumbnail quiz. Kalau quiz belum punya gambar, dipakai
                     ikon kategori (emoji dari Pelajaran::KATALOG) supaya
                     kotaknya tidak kosong. --}}
                <span class="hasil-top__ikon" style="--k: {{ $item['kategori']['warna'] }}; --k-gelap: {{ $item['kategori']['warna_gelap'] }};"
                    aria-hidden="true">
                    @if (filled($item['thumbnail']))
                        <img src="{{ $item['thumbnail'] }}" alt="" class="hasil-top__foto"
                            loading="lazy" decoding="async">
                    @else
                        <span class="hasil-top__ikon-emoji">{{ $item['kategori']['ikon'] }}</span>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="hasil-top__judul" title="{{ $item['judul'] }}">{{ $item['judul'] }}</span>

                    <span class="hasil-top__kategori">{{ $item['kategori']['nama'] }}</span>

                    <span class="hasil-top__meta">
                        {{ $item['jumlah_pengerjaan'] }} kali dikerjakan
                    </span>
                </span>

                <span class="hasil-top__nilai" title="Rata-rata nilai {{ $item['rata_nilai'] }}">
                    {{ $item['rata_nilai'] }}%
                </span>

                <svg class="hasil-top__panah" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kanan') }}" />
                </svg>
            </a>
        @empty
            <p class="hasil-panel__kosong">
                Quiz yang paling sering dikerjakan akan muncul di sini.
            </p>
        @endforelse
    </div>
</section>
