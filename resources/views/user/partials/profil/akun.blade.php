{{--
    Kartu "Informasi Akun".

    Isinya dibaca semua dari $pengguna (pengguna yang sedang login),
    tidak ada satu pun nama atau email yang ditulis langsung di sini.
    Nilainya sengaja tidak bisa diklik: cara mengubahnya adalah tombol
    "Edit Profil" di kartu kepala.
--}}

@php
    $bergabung = $pengguna->created_at?->translatedFormat('d F Y') ?? '-';
@endphp

<section
    data-reveal
    style="--reveal-delay: 160ms"
    class="profil-kartu p-5 sm:p-6"
    aria-labelledby="profil-judul-akun"
>
    <x-profil.kepala judul="Informasi Akun" ikon="pengguna" />

    <dl class="mt-4">
        <x-profil.info
            ikon="pengguna"
            label="Nama Lengkap"
            :nilai="$pengguna->nama"
        />

        <x-profil.info ikon="surel" label="Email" :nilai="$pengguna->email" />

        <x-profil.info
            ikon="kalender"
            label="Tanggal Bergabung"
            :nilai="$bergabung"
        />
    </dl>
</section>
