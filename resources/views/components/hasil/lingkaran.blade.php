@props([
    // Persentase 0-100.
    'persen' => 0,
    'warna' => '#6c4de6',
    'ukuran' => 'h-14 w-14',
    'label' => 'Nilai',
])

{{--
    Progress indicator kecil berbentuk lingkaran, dipakai di baris
    riwayat hasil quiz.

    Rumus busurnya dihitung di App\Support\Angka::cincin() supaya
    donat besar di sidebar memakai perhitungan yang sama persis.

    Guard "persen > 0" penting: busur lingkaran penuh dengan
    stroke-linecap round akan terlihat seperti lingkaran utuh kalau
    panjangnya nol, jadi nilainya 0 diganti lingkaran kosong.
--}}

@php
    $lingkaran = \App\Support\Angka::cincin((float) $persen, 54.0);
    $teks = $persen > 0 ? $lingkaran['teks'] : '--';
@endphp

<span {{ $attributes->class(['relative shrink-0', $ukuran]) }} role="img"
    aria-label="{{ $label }}: {{ $persen > 0 ? $lingkaran['teks'].' persen' : 'belum dinilai' }}">

    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" aria-hidden="true">
        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--color-lavender)" stroke-width="12" />

        @if ($persen > 0)
            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $warna }}" stroke-width="12"
                stroke-linecap="round"
                stroke-dasharray="{{ $lingkaran['panjang'] }} {{ $lingkaran['keliling'] }}" />
        @else
            <circle cx="60" cy="60" r="54" fill="none" stroke="#e2e0ee" stroke-width="12" />
        @endif
    </svg>

    <span class="hasil-lingkaran__teks">{{ $teks }}@if ($persen > 0)<i>%</i>@endif</span>
</span>
