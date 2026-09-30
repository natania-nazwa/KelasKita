@props([
    // Nama ikon di App\Support\Ikon.
    'nama',
    // Ukuran lewat kelas Tailwind, mis. "w-4 h-4".
    'ukuran' => 'w-4 h-4',
    // Lebar garis ikon. Semua ikon di area admin memakai 1.8 supaya
    // garisnya konsisten dengan ikon yang sudah ada di halaman lain.
    'tebal' => 1.8,
])

{{--
    Ikon garis untuk area admin.

    Path-nya diambil dari App\Support\Ikon supaya satu ikon tidak ditulis
    ulang di beberapa file. Tanpa pustaka ikon: proyek ini memang belum
    adapinya, dan menambah dependency hanya untuk satu daftar ikon tidak
    sebanding.

    Atribut class dari luar digabung dengan ukuran bawaan, jadi
    <x-admin.ikon nama="cari" class="ad-atas__cari-ikon" /> tetap bisa
    diposisikan oleh kelas CSS-nya sendiri.
--}}

<svg {{ $attributes->class(['shrink-0', $ukuran]) }} fill="none" stroke="currentColor" stroke-width="{{ $tebal }}"
    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($nama) }}" />
</svg>
