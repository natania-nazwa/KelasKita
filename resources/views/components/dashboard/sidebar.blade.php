@props([
    'aksesCepat' => [],
    'jadwal' => [],
    'tautanJadwal' => null,
    'peringkat' => [],
    'kalender' => [],
])

{{--
    Sidebar dashboard.

    Desktop (2xl ke atas): satu kolom di samping kolom utama, selebar 20rem.
    Tablet & mobile      : turun ke bawah kolom utama dan menjadi dua kolom,
                          supaya tiap panel tetap punya ruang yang nyaman.

    2xl (bukan xl) dipakai supaya materi dan quiz di kolom utama bisa tetap
    EMPAT KARTU SEJALAR dengan lebar yang layak. Di 1280px sidebar masih
    memakan ~320px dan kolom utama tinggal ~600px.

    items-start supaya tiap panel memakai tinggi alaminya sendiri, tidak
    ikut diregangkan mengikuti panel paling tinggi di baris yang sama.

    Keempat panelnya dipisah jadi komponen sendiri supaya halaman utama tetap
    ringkas dan tiap panel bisa dipakai ulang di halaman lain.
--}}

<aside class="grid min-w-0 items-start gap-4 sm:grid-cols-2 lg:gap-5 2xl:grid-cols-1">
    <x-dashboard.akses-cepat :daftar="$aksesCepat" />

    <x-dashboard.jadwal :daftar="$jadwal" :tautan="$tautanJadwal" />

    <x-dashboard.peringkat :daftar="$peringkat" />

    <x-dashboard.kalender :kalender="$kalender" />
</aside>
