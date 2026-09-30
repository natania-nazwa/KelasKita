@props([
    'status',
    'label',
])

{{--
    Lencana status konten (materi / quiz).

    Warnanya ditentukan dari nilai status di model, bukan dari nama
    kelas yang ditulis manual di view, supaya warna dan labelnya tidak
    bisa terpisah: satu sumber kebenaran, yaitu model.

    Peta warna:
      published  hijau   sudah tayang untuk semua pengguna
      pending    oranye  menunggu keputusan admin
      draft      abu     belum diajukan, hanya terlihat pembuatnya
      rejected   pink    ditolak, dan alasannya dibaca pemilik
--}}

@php
    $warna = match ($status) {
        'published' => 'sukses',
        'pending' => 'peringatan',
        'rejected' => 'bahaya',
        default => 'abu',
    };
@endphp

<span {{ $attributes->class(['ad-lencana', 'ad-lencana--'.$warna]) }}>
    <span class="ad-lencana__titik" aria-hidden="true"></span>

    {{ $label }}
</span>
