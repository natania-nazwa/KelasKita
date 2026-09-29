@props([
    // Kartu quiz dari App\Support\DaftarQuiz.
    'kartu',
    'jumlahSoal' => 0,
])

@php
    /*
     * Kartu "Informasi Quiz" di kanan kepala halaman detail.
     *
     * Isinya sengaja bukan ulang dari kartu utama: di sana identitas quiz
     * dibaca sebagai satu paragraf, sedangkan di sini empat hal yang paling
     * sering dicari siswa — kategori, kesulitan, jumlah soal, dan durasi —
     * dikumpulkan sebagai daftar pendek yang enak dipindai mata.
     */
    $kategori = $kartu['kategori'];
    $durasi = (int) ($kartu['durasi'] ?? 0);
    $kesulitan = filled($kartu['tingkat_kesulitan'] ?? null) ? $kartu['tingkat_kesulitan'] : 'Mudah';

    /*
     * Satu daftar, bukan empat blok yang ditulis manual, supaya jarak antar
     * item dan urutan ikonnya tidak bisa berbeda antara markup dan CSS.
     */
    $informasi = [
        ['ikon' => 'clipboard', 'label' => 'Kategori', 'nilai' => $kategori['nama']],
        ['ikon' => 'grafik', 'label' => 'Tingkat Kesulitan', 'nilai' => $kesulitan],
        ['ikon' => 'dokumen', 'label' => 'Jumlah Soal', 'nilai' => $jumlahSoal.' Soal'],
        ['ikon' => 'jam', 'label' => 'Durasi', 'nilai' => $durasi > 0 ? "± {$durasi} Menit" : 'Tidak dibatasi'],
    ];
@endphp

<section {{ $attributes->class(['info-quiz', 'kartu-detail']) }} aria-labelledby="judul-info-quiz">
    <h2 id="judul-info-quiz" class="info-quiz__judul">Informasi Quiz</h2>

    <dl class="info-quiz__list">
        @foreach ($informasi as $baris)
            <div class="info-quiz__item">
                <span class="info-quiz__ikon" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="{{ \App\Support\Ikon::path($baris['ikon']) }}" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <dt class="info-quiz__label">{{ $baris['label'] }}</dt>
                    <dd class="info-quiz__nilai">{{ $baris['nilai'] }}</dd>
                </div>
            </div>
        @endforeach
    </dl>
</section>
