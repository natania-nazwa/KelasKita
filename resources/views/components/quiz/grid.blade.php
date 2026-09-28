@props([
    'daftar' => [],
    'kataKunci' => '',
])

{{--
    Grid daftar quiz.
    - Desktop : 4 kolom
    - Tablet  : 3 kolom
    - Ponsel  : 2 kolom, tetap dua agar daftar terasa padat

    Jumlah per halaman (12) habis dibagi 2, 3, dan 4, jadi baris terakhir
    tidak pernah menyisakan kartu yatim di ukuran layar mana pun.

    min-w-0 pada tiap kartu membuat kolom menyusut mengikuti ruang yang
    tersedia di samping sidebar, jadi tidak pernah melebar atau membuat
    halaman scroll horizontal. Nama pembuat di baris bawah memakai
    ellipsis, jadi kartu tetap rapi walau kolomnya sempit.
--}}

<div data-reveal-stagger data-quiz-daftar
    class="grid grid-cols-2 gap-4 min-w-0 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
    @forelse ($daftar as $quiz)
        <x-quiz.kartu :quiz="$quiz" :kata-kunci="$kataKunci" />
    @empty
        {{-- Slot kosong; halaman biasanya merender pesan "belum ada quiz"
             sendiri di luar grid ini. --}}
    @endforelse
</div>
