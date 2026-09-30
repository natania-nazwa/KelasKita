@props([
    // Inisial nama, mis. "NA" untuk Natania.
    'inisial',
    // Sepasang warna avatar dari User::warnaAvatar(). Diset inline
    // supaya satu komponen bisa dipakai untuk semua pengguna tanpa
    // membuat kelas baru di CSS.
    'warna' => null,
    'warnaGelap' => null,
    // Ukuran: kecil | sedang | besar.
    'ukuran' => 'kecil',
])

{{--
    Avatar bulat berisi inisial.

    Kalau warna tidak diberikan, komponen pakai gradien ungu bawaan.
    Semua admin memakai warna yang sama, jadi warna tidak wajib dikirim.
--}}

@php
    $gaya = $warna && $warnaGelap
        ? 'style="--a: '.$warna.'; --a-gelap: '.$warnaGelap.';"'
        : '';
@endphp

<span {{ $attributes->class(['ad-avatar', 'ad-avatar--'.$ukuran]) }} {!! $gaya !!} aria-hidden="true">
    {{ $inisial }}
</span>
