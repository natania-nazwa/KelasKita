@props([
    // Array dari App\Support\DaftarMateri atau DaftarQuiz.
    'daftar' => [],
    // 'materi' atau 'quiz', menentukan kartu mana yang dirender.
    'jenis' => 'materi',
    'kataKunci' => '',
])

{{--
    Grid daftar simpanan pada halaman "Simpan".

    Kartunya sama persis dengan yang dipakai di halaman Materi dan Quiz
    (thumbnail 16:9, lencana, judul, deskripsi, baris meta), jadi perpindahan
    dari menu itu ke halaman ini tidak terasa seperti halaman yang berbeda.

    Hanya jumlah kolomnya yang lebih padat: lima kolom di layar besar, tiga di
    tablet, dua di ponsel. Lima kolom dipilih karena grid lima kali lima
    (lihat SimpananController::perHalaman) selalu penuh di setiap ukuran
    layar, jadi tidak pernah ada kartu yatim sendirian di baris terakhir.

    min-w-0 pada tiap kartu membuat kolom menyusut mengikuti ruang yang
    tersedia di samping sidebar, jadi tidak pernah melebar atau membuat
    halaman scroll horizontal. Nama pembuat di baris bawah memakai
    ellipsis, jadi kartu tetap rapi walau kolomnya sempit.
--}}

<div data-reveal-stagger data-simpan-grid
    {{ $attributes->class(['grid min-w-0 grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-3 xl:grid-cols-5']) }}>
    @forelse ($daftar as $item)
        @if ($jenis === 'quiz')
            <x-quiz.kartu :quiz="$item" :kata-kunci="$kataKunci" />
        @else
            <x-materi.kartu :materi="$item" :kata-kunci="$kataKunci" />
        @endif
    @empty
        {{-- Slot kosong; halaman merender pesan "belum ada simpan"
             sendiri di luar grid ini. --}}
    @endforelse
</div>
