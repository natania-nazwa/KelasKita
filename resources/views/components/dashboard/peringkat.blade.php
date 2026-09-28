@props([
    'daftar' => [],
    'tautan' => null,
])

{{--
    Panel "Leaderboard" di sidebar.

    Tiga peringkat teratas memakai lencana angka berwarna medali, peringkat
    satu disimpan mahkotanya. Baris milik pengguna yang sedang login
    (data["saya"]) diberi sorotan supaya mudah ditemukan.
--}}

<section class="dash-kartu dash-panel min-w-0" aria-label="Leaderboard"
    style="--k: #6c4de6; --k-gelap: #5a3fd4;">

    <x-dashboard.kepala judul="Leaderboard" ikon="mahkota" :tautan="$tautan" />

    <div data-reveal-stagger class="grid gap-0.5">
        @forelse ($daftar as $baris)
            <div @class([
                'dash-peringkat',
                'dash-peringkat--saya' => $baris['saya'],
            ])>
                <span @class([
                    'dash-peringkat__no',
                    'dash-peringkat__no--emas' => $baris['medali'] === 'emas',
                    'dash-peringkat__no--perak' => $baris['medali'] === 'perak',
                    'dash-peringkat__no--perunggu' => $baris['medali'] === 'perunggu',
                ])>{{ $baris['peringkat'] }}</span>

                <span class="dash-avatar"
                    style="--a: {{ $baris['warna'] }}; --a-gelap: {{ $baris['warna_gelap'] }};"
                    aria-hidden="true">{{ $baris['inisial'] }}</span>

                <span class="min-w-0 flex-1">
                    <span class="dash-peringkat__nama">
                        {{ $baris['nama'] }}
                        @if ($baris['saya'])
                            <span class="dash-peringkat__saya">Kamu</span>
                        @endif
                    </span>
                </span>

                @if ($baris['medali'] === 'emas')
                    <svg class="h-4 w-4 shrink-0 text-[#dfa114]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Peringkat pertama">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('mahkota') }}" />
                    </svg>
                @endif

                <span class="dash-peringkat__skor">{{ $baris['skor'] }}</span>
            </div>
        @empty
            <p class="py-2 text-center text-xs text-dark/45">Belum ada peringkat.</p>
        @endforelse
    </div>
</section>
