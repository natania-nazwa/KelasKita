@props([
    'peserta' => [],
])

@php
    /*
     * Daftar nama peserta di lobby.
     *
     * Isinya datang dari App\Support\DaftarPeserta, jadi komponen ini
     * hanya menerima array polos: tidak ada kueri database di dalam
     * markup. Shape satu baris:
     *
     *   id, pengguna_id, nama, inisial, warna, warna_gelap, status,
     *   status_label, bergabung_pada
     *
     * Baris punya data-lobi-peserta berisi id peserta. Polling di
     * resources/js/quiz-lobby.js membandingkan daftar lama dengan daftar
     * baru, lalu menandai baris yang baru saja muncul dengan kelas
     * "--baru" supaya animasi masuknya hanya diputar sekali.
     */
@endphp

<div class="lobi-daftar mt-5" data-lobi-daftar role="list">
    @forelse ($peserta as $orang)
        <div class="lobi-daftar__item" data-lobi-peserta="{{ $orang['id'] }}" role="listitem">
            <span class="lobi-daftar__avatar" style="--a: {{ $orang['warna'] }}; --a-gelap: {{ $orang['warna_gelap'] }};"
                aria-hidden="true">{{ $orang['inisial'] }}</span>

            <span class="lobi-daftar__nama" title="{{ $orang['nama'] }}">{{ $orang['nama'] }}</span>

            <span class="lobi-daftar__status {{ $orang['status'] === 'mengerjakan' ? 'lobi-daftar__status--mengerjakan' : '' }} {{ $orang['status'] === 'selesai' ? 'lobi-daftar__status--selesai' : '' }}"
                data-lobi-peserta-status>{{ $orang['status_label'] }}</span>

            @if ($orang['bergabung_pada'])
                <span class="lobi-daftar__waktu" data-lobi-daftar-waktu>{{ $orang['bergabung_pada'] }}</span>
            @endif
        </div>
    @empty
        <p class="lobi-daftar__kosong" data-lobi-daftar-kosong>
            Belum ada peserta yang bergabung.
        </p>
    @endforelse
</div>
