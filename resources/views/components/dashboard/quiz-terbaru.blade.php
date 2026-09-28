@props([
    'daftar' => [],
    'tautan' => null,
])

{{--
    Section "Quiz Terbaru".

    Sama seperti "Materi Terbaru": satu kartu putih membungkus kepala seksi
    dan grid kartu quiz, jadi dua lapis (panel luar + kartu yang bisa diklik).

    Grid di dalam panel:
    - Desktop (xl / 1280px+): 4 kolom, satu baris
    - Tablet               : 2 kolom
    - Mobile               : 1 kolom
--}}

<section data-reveal
    class="mt-6 min-w-0 rounded-2xl border border-lavender bg-white p-4 shadow-[0_1px_2px_rgba(33,26,58,0.04)] sm:p-5"
    aria-label="Quiz Terbaru">

    <x-dashboard.kepala judul="Quiz Terbaru" ikon="benar" :tautan="$tautan" />

    <div data-reveal-stagger
        class="mt-4 grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse ($daftar as $quiz)
            <x-dashboard.quiz-kartu :quiz="$quiz" />
        @empty
            <p
                class="rounded-2xl border border-dashed border-lavender bg-brand-bg/60 p-6 text-center text-sm text-dark/50 sm:col-span-2 xl:col-span-4">
                Belum ada quiz terbaru. Akses halaman Quiz untuk memulai.
            </p>
        @endforelse
    </div>
</section>
