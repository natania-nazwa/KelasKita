@props([
    // Tab aktif: 'materi' atau 'quiz'.
    'tab' => 'materi',
    'jumlahMateri' => 0,
    'jumlahQuiz' => 0,
    'kataKunci' => '',
])

{{--
    Tab "Materi Saya" / "Quiz Saya" + pencarian + tombol tambah.

    Tiap tab adalah tautan biasa, jadi kata kunci ikut terbawa dan
    alamatnya tetap bisa disalin. Nilai kosong tidak perlu masuk URL
    supaya alamatnya tetap rapi.

    Tombol tambah di sini mengikuti tab yang sedang aktif, jadi "+ Tambah
    Materi" hanya muncul di tab Materi Saya dan "+ Tambah Quiz" di tab
    Quiz Saya.
--}}

@php
    $url = function (?string $tabBaru = null) use ($tab, $kataKunci) {
        return route('user.karya-saya', array_filter([
            'tab' => $tabBaru ?? $tab,
            'q' => $kataKunci,
        ], fn ($nilai) => filled($nilai)));
    };

    $daftarTab = [
        ['nilai' => 'materi', 'label' => 'Materi Saya', 'jumlah' => $jumlahMateri],
        ['nilai' => 'quiz', 'label' => 'Quiz Saya', 'jumlah' => $jumlahQuiz],
    ];
@endphp

{{--
    Susunan: tab di kiri, cari + tombol di kanan. xl:flex-row karena tiga
    elemen ini muat side-by-side hanya di layar besar; di bawah itu
    membungkus ke bawah dan kolom cari melebar penuh supaya tidak ada
    scroll horizontal di ponsel.
--}}
<div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

    {{-- Tab: aktif = ungu solid + teks putih, tidak aktif = lavender muda. --}}
    <div class="tab-karya" role="tablist" aria-label="Pilih karya saya">
        @foreach ($daftarTab as $item)
            @php $aktif = $tab === $item['nilai']; @endphp

            <a href="{{ $url($item['nilai']) }}" role="tab" aria-selected="{{ $aktif ? 'true' : 'false' }}"
                @class(['tab-karya__item', 'tab-karya__item--aktif' => $aktif])>
                {{ $item['label'] }}

                <span class="tab-karya__jumlah">{{ $item['jumlah'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <x-karya.cari :tab="$tab" :kata-kunci="$kataKunci" class="min-w-0" />

        @if ($tab === 'quiz')
            {{-- Komponen tombol yang sama dengan halaman Quiz, hanya
                 tujuannya mengikuti tab aktif. --}}
            <x-quiz.tambah class="shrink-0" />
        @else
            <x-materi.tambah class="shrink-0" />
        @endif
    </div>
</div>
