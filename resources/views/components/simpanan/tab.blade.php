@props([
    // Tab aktif: 'materi' atau 'quiz'.
    'tab' => 'materi',
    'jumlahMateri' => 0,
    'jumlahQuiz' => 0,
    'kataKunci' => '',
])

{{--
    Tab "Materi" / "Quiz" untuk halaman Simpan.

    Tiap tab adalah tautan biasa, jadi kata kunci yang masih ada di URL
    ikut terbawa dan alamatnya tetap bisa disalin. Nilai kosong tidak
    perlu masuk URL supaya alamatnya tetap rapi.

    Bentuknya sama persis dengan tab di halaman Karya Saya dan Hasil, jadi
    initBookmark() di resources/js/app.js yang memperbarui angka setelah
    simpanan dilepas tidak perlu tahu halaman mana. Warnanya disesuaikan
    di .simpan-kepala (lihat app.css) supaya tab menyatu dengan papan
    kepala halaman, bukan terlihat ditempel di atasnya.

    Tab ini dikirim lewat slot components/simpanan/kepala supaya kepala
    halaman dan tabnya menjadi satu blok tanpa ruang putih di antaranya.

    Kolom pencarian tidak diulang di sini. Pencarian global di top bar
    sudah mengarah ke halaman ini, jadi dua kotak pencarian di satu
    halaman hanya membingungkan.
--}}

@php
    $url = function (?string $tabBaru = null) use ($tab, $kataKunci) {
        return route('user.simpanan', array_filter([
            'tab' => $tabBaru ?? $tab,
            'q' => $kataKunci,
        ], fn ($nilai) => filled($nilai)));
    };

    $daftarTab = [
        ['nilai' => 'materi', 'label' => 'Materi', 'jumlah' => $jumlahMateri],
        ['nilai' => 'quiz', 'label' => 'Quiz', 'jumlah' => $jumlahQuiz],
    ];
@endphp

{{-- Tab: aktif = ungu solid + teks putih, tidak aktif = lavender muda. --}}
<div class="tab-karya" role="tablist" aria-label="Pilih jenis simpan">
    @foreach ($daftarTab as $item)
        @php $aktif = $tab === $item['nilai']; @endphp

        <a href="{{ $url($item['nilai']) }}" role="tab" aria-selected="{{ $aktif ? 'true' : 'false' }}"
            @class(['tab-karya__item', 'tab-karya__item--aktif' => $aktif])>
            {{ $item['label'] }}

            <span class="tab-karya__jumlah">{{ $item['jumlah'] }}</span>
        </a>
    @endforeach
</div>
