@props([
    'daftar' => [],
    'kataKunci' => '',
])

{{--
    Grid daftar materi.
    - Desktop: 4 kolom
    - Tablet : 2 kolom
    - Ponsel : 2 kolom (padat, supaya 20 kartu per halaman tidak
                terasa seperti daftar yang tidak berujung)

    Pembagiannya sengaja 2 dan 4: jumlah materi per halaman (lihat
    DaftarMateri::perHalaman) = 20, jadi tidak pernah ada kartu yatim
    di baris terakhir, baik di ponsel maupun desktop.

    min-w-0 pada tiap kartu membuat kolom menyusut mengikuti ruang yang
    tersedia di samping sidebar, jadi tidak pernah melebar atau membuat
    halaman scroll horizontal. Nama pembuat di baris bawah memakai
    ellipsis, jadi kartu tetap rapi walau kolomnya sempit.
--}}

<div data-reveal-stagger {{ $attributes->class(['grid grid-cols-2 gap-4 min-w-0 sm:gap-5 lg:grid-cols-4']) }}>
    @forelse ($daftar as $materi)
        <x-materi.kartu :materi="$materi" :kata-kunci="$kataKunci" />
    @empty
        {{-- Slot kosong; halaman biasanya merender pesan "belum ada
             materi" sendiri di luar grid ini. --}}
    @endforelse
</div>
