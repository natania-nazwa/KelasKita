@props ([
    /*
     * Pengguna yang avatarnya sedang digambar. Wajib diisi, karena avatar
     * selalu milik orang yang sedang login.
     */
    'pengguna',

    /*
     * Ukuran avatar. "besar" untuk kartu kepala halaman Profil, "kecil"
     * untuk dialog edit profil dan top bar.
     */
    'ukuran' => 'besar',
])

@php
    $warna = $pengguna->warnaAvatar();
    $foto = $pengguna->fotoProfilUrl();
@endphp

{{--
    Dua sumber tampilan, dipilih dari User::fotoProfilUrl():

      1. Ada foto  -> <img> dari disk publik.
      2. Tidak ada -> satu huruf inisial nama depan di atas gradasi ungu.

    Tidak ada gambar placeholder sama sekali. Kalau foto dihapus, kolom
    foto_profil jadi kosong, fotoProfilUrl() mengembalikan null, dan
    cabang kedua otomatis dipakai kembali tanpa perlu JavaScript.

    Teks alternatif pada <img> memakai nama pengguna, jadi pembaca layar
    membacakan nama yang sama dengan teks di sebelah avatar.
--}}
<span
    @class ([
    'profil-avatar',
    'profil-avatar--kecil' => $ukuran === 'kecil',
])
    style="--a: {{ $warna['warna'] }}; --a-gelap: {{ $warna['warna_gelap'] }};"
>
    @if ($foto)
        <img
            src="{{ $foto }}"
            alt="Foto profil {{ $pengguna->nama }}"
            class="profil-avatar__foto"
            data-profil-foto
        />
    @else
        <span data-profil-inisial>{{ $pengguna->inisial() }}</span>
    @endif
</span>
