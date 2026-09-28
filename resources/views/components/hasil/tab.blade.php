@props([
    // Tab status yang sedang aktif: null = semua.
    'statusAktif' => null,
    // Jumlah tiap status, sudah memperhitungkan kata kunci pencarian.
    'jumlahStatus' => [],
    'kataKunci' => '',
    'urutAktif' => 'terbaru',
    'aksi' => 'user.hasil',
    'param' => [],
])

{{--
    Tab filter status: Semua, Selesai, Dalam Proses, Gagal.

    Tiap tab adalah tautan biasa, jadi kata kunci dan urutan ikut
    terbawa dan alamatnya tetap bisa disalin. Nilai kosong tidak masuk
    URL supaya alamatnya tetap rapi.

    Gaya pill-nya memakai .tab-karya yang sudah dipakai halaman
    "Karya Saya": aktif ungu solid + teks putih, tidak aktif di atas
    dasar lavender.
--}}

@php
    $url = function (?string $statusBaru) use ($aksi, $param, $statusAktif, $urutAktif, $kataKunci) {
        return route($aksi, array_merge(
            $param,
            array_filter([
                'status' => $statusBaru ?? $statusAktif,
                'urut' => $urutAktif !== 'terbaru' ? $urutAktif : null,
                'q' => $kataKunci,
            ], fn ($nilai) => filled($nilai)),
        ));
    };

    $tab = [
        ['nilai' => null, 'label' => 'Semua', 'jumlah' => $jumlahStatus['semua'] ?? 0],
        ['nilai' => 'selesai', 'label' => 'Selesai', 'jumlah' => $jumlahStatus['selesai'] ?? 0],
        ['nilai' => 'proses', 'label' => 'Dalam Proses', 'jumlah' => $jumlahStatus['proses'] ?? 0],
        ['nilai' => 'gagal', 'label' => 'Gagal', 'jumlah' => $jumlahStatus['gagal'] ?? 0],
    ];
@endphp

{{-- overflow-x-auto supaya tab keempat tidak memaksa halaman melebar di
     ponsel; min-w-0 menjaga induknya tetap boleh menyusut. --}}
<div class="tab-karya" role="tablist" aria-label="Saring hasil quiz">
    @foreach ($tab as $item)
        @php $aktif = $statusAktif === $item['nilai']; @endphp

        <a href="{{ $url($item['nilai']) }}" role="tab" aria-selected="{{ $aktif ? 'true' : 'false' }}"
            @class(['tab-karya__item', 'tab-karya__item--aktif' => $aktif])>
            {{ $item['label'] }}

            <span class="tab-karya__jumlah">{{ $item['jumlah'] }}</span>
        </a>
    @endforeach
</div>
