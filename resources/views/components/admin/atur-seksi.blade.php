@props([
    // Judul dan ikon boleh dikosongkan. Kartu tanpa judul dipakai untuk satu
    // baris yang tidak perlu kelompok: yang menandainya sudah ikon dan
    // kalimatnya sendiri, jadi kepala kartu hanya akan mengulang.
    'judul' => null,
    'subjudul' => null,
    'ikon' => null,
    // Menandai kartu yang isinya tindakan berbahaya (Keluar dari Akun).
    'bahaya' => false,
])

{{--
    Satu kartu kelompok pengaturan di area admin: kepala kecil berisi ikon,
    judul, dan satu kalimat penjelas, lalu isinya lewat slot.

    Kepala kartu hanya dirender kalau ada judulnya. Kartu "Keluar dari Akun"
    sengaja tanpa judul dan ikon: baris di dalamnya sudah punya ikon pintu
    keluar dan kalimatnya sendiri, jadi kepala kartu hanya akan mengulang.

    Kartu ini sengaja tidak punya tinggi minimum dan kepala kartu berisi
    tombol aksi: isinya cuma baris-baris pengaturan, jadi tinggi kartanya
    mengikuti isi, bukan dipaksa memenuhi layar.
--}}

<section @class([
    'ad-atur-seksi',
    'ad-atur-seksi--bahaya' => $bahaya,
]) {{ $attributes }}>

    @if (filled($judul))
        <header class="ad-atur-seksi__kepala">
            @if (filled($ikon))
                <span class="ad-atur-seksi__ikon" aria-hidden="true">
                    <x-admin.ikon :nama="$ikon" ukuran="w-4 h-4" />
                </span>
            @endif

            <div class="ad-atur-seksi__teks">
                <h2 class="ad-atur-seksi__judul">{{ $judul }}</h2>

                @if (filled($subjudul))
                    <p class="ad-atur-seksi__sub">{{ $subjudul }}</p>
                @endif
            </div>
        </header>
    @endif

    {{ $slot }}
</section>
