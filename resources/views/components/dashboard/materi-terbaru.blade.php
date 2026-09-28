@props([
    'daftar' => [],
    'tautan' => null,
])

{{--
    Section "Materi Terbaru".

    Seluruh section dibungkus satu kartu putih (kepala seksi + isi), lalu di
    dalamnya grid kartu materi. Jadi ada dua lapis: panel luar yang
    memberi batas visual section, dan kartu materi di dalamnya yang bisa
    diklik dan di-hover sendiri.

    Kartu materi memakai x-dashboard.materi-kartu, bukan x-materi.kartu dari
    halaman Materi. Alasannya: kartu halaman Materi dirancang untuk grid
    tiga kolom (sekitar 330px per kartu), sedangkan di dashboard empat kartu
    dijajar satu baris sehingga hanya sekitar 200-300px. Bentuk datanya tetap
    sama dengan App\Support\DaftarMateri, jadi sumber datanya tetap bisa
    diganti ke API tanpa menyentuh komponen.

    Grid di dalam panel:
    - Desktop (xl / 1280px+): 4 kolom, satu baris
    - Tablet               : 2 kolom
    - Mobile               : 1 kolom

    min-w-0 di panel dan di grid mencegah kolom melebar mengikuti isi teks,
    jadi tidak pernah memicu scroll horizontal.
--}}

<section data-reveal
    class="mt-6 min-w-0 rounded-2xl border border-lavender bg-white p-4 shadow-[0_1px_2px_rgba(33,26,58,0.04)] sm:p-5"
    aria-label="Materi Terbaru">

    <x-dashboard.kepala judul="Materi Terbaru" ikon="buku" :tautan="$tautan" />

    <div data-reveal-stagger
        class="mt-4 grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse ($daftar as $materi)
            <x-dashboard.materi-kartu :materi="$materi" />
        @empty
            <p
                class="rounded-2xl border border-dashed border-lavender bg-brand-bg/60 p-6 text-center text-sm text-dark/50 sm:col-span-2 xl:col-span-4">
                Belum ada materi terbaru. Mulai dari Jelajahi Materi.
            </p>
        @endforelse
    </div>
</section>
