@props([
    'status',
    'label' => null,
])

{{--
    Lencana status untuk daftar Verifikasi.

    Berbeda dari x-admin.lencana yang hanya menampilkan titik berwarna,
    komponen ini ikut membawa ikon supaya statusnya terbaca sekilas di
    daftar padat: jam untuk yang masih menunggu, centang untuk yang
    sudah tayang, silang untuk yang ditolak.

    Peta warna mengikuti x-admin.lencana, jadi satu status tidak
    pernah punya dua warna di dua tempat berbeda.
--}}

@php
    $cocok = match ($status) {
        'pending' => ['ad-lencana--peringatan', 'jam', 'Menunggu Verifikasi'],
        'published' => ['ad-lencana--sukses', 'centang', 'Disetujui'],
        'rejected' => ['ad-lencana--bahaya', 'silang', 'Ditolak'],
        default => ['ad-lencana--abu', 'dokumen', ucfirst((string) $status)],
    };

    [$kelas, $ikon, $tulisan] = $cocok;
@endphp

<span {{ $attributes->class(['ad-lencana', $kelas]) }}>
    <x-admin.ikon :nama="$ikon" ukuran="w-3 h-3" />

    {{ $label ?? $tulisan }}
</span>
