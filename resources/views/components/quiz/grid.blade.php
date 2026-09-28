@props([
    'daftar' => [],
    'kataKunci' => '',
])

{{--
    Grid daftar quiz.
    - Desktop: 4 kolom
    - Tablet : 2 kolom
    - Mobile : 1 kolom

    min-w-0 pada tiap kartu membuat kolom menyusut mengikuti ruang yang
    tersedia di samping sidebar, jadi tidak pernah melebar atau membuat
    halaman scroll horizontal. Nama pembuat di baris bawah memakai
    ellipsis, jadi kartu tetap rapi walau kolomnya sempit.
--}}

<div data-reveal-stagger data-quiz-daftar
    class="grid grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 lg:grid-cols-4">
    @forelse ($daftar as $quiz)
        <x-quiz.kartu :quiz="$quiz" :kata-kunci="$kataKunci" />
    @empty
        {{-- Slot kosong; halaman biasanya merender pesan "belum ada quiz"
             sendiri di luar grid ini. --}}
    @endforelse
</div>
