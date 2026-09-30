@props([
    'inisial',
    'warna' => null,
    'warnaGelap' => null,
    'judul',
    'detail' => null,
    'waktu' => null,
    'ikon' => 'buku',
])

{{--
    Satu baris "Aktivitas Terbaru".

    Avatar di kiri menunjukkan siapa yang pelaku kegiatannya, dan ikon
    kecil di atasnya menunjukkan jenisnya (materi atau quiz). Waktu
    ditulis di kanan dan disembunyikan di layar sangat sempit supaya
    judul aktivitas tidak pernah terpotong.
--}}

<div {{ $attributes->class(['ad-aktivitas__baris']) }}>
    <span class="relative shrink-0">
        <x-admin.avatar :inisial="$inisial" :warna="$warna" :warna-gelap="$warnaGelap" ukuran="sedang" />

        <span class="ad-aktivitas__jenis" aria-hidden="true">
            <x-admin.ikon :nama="$ikon" ukuran="w-2.5 h-2.5" :tebal="2.4" />
        </span>
    </span>

    <div class="ad-aktivitas__isi">
        <p class="ad-aktivitas__judul">{{ $judul }}</p>

        @if (filled($detail))
            <p class="ad-aktivitas__detail">{{ $detail }}</p>
        @endif
    </div>

    @if (filled($waktu))
        <span class="ad-aktivitas__waktu">{{ $waktu }}</span>
    @endif
</div>
