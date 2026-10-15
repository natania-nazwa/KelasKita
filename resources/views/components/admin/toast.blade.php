@props([
    'judul' => null,
    'pesan' => null,
    'ikon' => null,

    // "sukses" (bawaan) untuk kabar hasil aksi yang berhasil, "galat"
    // untuk pesan yang gagal — isian belum lengkap, aksi ditolak, dan
    // sejenisnya. Menentukan warna ikon dan tepi kartunya, sekaligus
    // memilih ikon bawaan kalau pemanggil tidak menentukan sendiri.
    'jenis' => 'sukses',
])

@php
    $galat = $jenis === 'galat';
    $ikon ??= $galat ? 'peringatan' : 'tanda-centang';
@endphp

{{--
    Toast hasil aksi: "Berhasil dipublikasikan", "Draft berhasil disimpan",
    dan pesan sejenis setelah simpan, hapus, atau duplikat.

    Sumbernya selalu flash session "sukses" + "suksesDetail" (atau "galat"),
    jadi toast ini hanya muncul kalau ada aksi yang benar-benar baru saja
    terjadi — bukan tiap kali halaman dibuka.

    Letaknya di pojok kanan bawah, menumpuk di atas sidebar yang tetap
    terlihat, dan menutup dirinya sendiri setelah lima detik
    (resources/js/konten-admin.js). Tombol tutup selalu ada, jadi membaca
    pesannya tidak bergantung pada JavaScript.
--}}

@if (filled($judul))
    <div @class(['ad-toast', 'ad-toast--galat' => $galat]) data-konten-toast role="status" aria-live="polite">
        <span class="ad-toast__ikon" aria-hidden="true">
            <x-admin.ikon :nama="$ikon" ukuran="w-4 h-4" :tebal="2.4" />
        </span>

        <div class="ad-toast__teks">
            <p class="ad-toast__judul">{{ $judul }}</p>

            @if (filled($pesan))
                <p class="ad-toast__pesan">{{ $pesan }}</p>
            @endif
        </div>

        <button type="button" class="ad-toast__tutup" data-konten-toast-tutup aria-label="Tutup pesan">
            <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" />
        </button>
    </div>
@endif
