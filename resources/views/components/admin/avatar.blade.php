@props([
    // Inisial nama, mis. "NA" untuk Natania.
    'inisial',
    // Sepasang warna avatar dari User::warnaAvatar(). Diset inline
    // supaya satu komponen bisa dipakai untuk semua pengguna tanpa
    // membuat kelas baru di CSS.
    'warna' => null,
    'warnaGelap' => null,
    // URL foto profil, mis. User::fotoProfilUrl(). Null = pakai inisial.
    'foto' => null,
    // Ukuran: kecil | sedang | besar.
    'ukuran' => 'kecil',
])

{{--
    Avatar bulat berisi inisial, atau foto profil kalau ada.

    Kalau warna tidak diberikan, komponen pakai gradien ungu bawaan.
    Semua admin memakai warna yang sama, jadi warna tidak wajib dikirim.

    Foto profil tidak pernah punya nilai placeholder: kalau pengguna belum
    memasang foto, pemanggil harus mengirim null supaya yang tampil tetap
    inisial. Lihat User::fotoProfilUrl() yang mengembalikan null untuk
    kasus itu.
--}}

@php
    $gaya = $warna && $warnaGelap
        ? 'style="--a: '.$warna.'; --a-gelap: '.$warnaGelap.';"'
        : '';
@endphp

<span {{ $attributes->class(['ad-avatar', 'ad-avatar--'.$ukuran]) }} {!! $gaya !!} aria-hidden="true">
    @if (filled($foto))
        <img src="{{ $foto }}" alt="" width="52" height="52" loading="lazy" decoding="async">
    @else
        {{ $inisial }}
    @endif
</span>
