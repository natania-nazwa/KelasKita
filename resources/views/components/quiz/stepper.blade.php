@props([
    // Tahap yang sedang dibuka (1, 2, atau 3).
    'aktif' => 1,
])

@php
    /*
     * Langkah wizard "Buat Quiz": Informasi Dasar, Buat Soal, Pengaturan.
     *
     *     Status langkah sudah dipasang di sini, bukan hanya oleh
     * resources/js/quiz-tambah.js. Alasannya: stepper adalah penanda
     * posisi pengguna, jadi harus langsung benar sejak HTML pertama
     * tiba — termasuk saat JavaScript belum (atau gagal) berjalan.
     * JavaScript lalu hanya perlu memperbaruinya saat langkah berganti.
     *
     * Nomor langkah yang sudah dilewati diganti tanda centang, persis
     * seperti rancangan, jadi urutan yang sudah selesai selalu terbaca.
     */
    $langkah = [
        ['nama' => 'Informasi Dasar', 'keterangan' => 'Judul, kategori, thumbnail'],
        ['nama' => 'Buat Soal', 'keterangan' => 'Soal dan jawaban benar'],
        ['nama' => 'Pengaturan', 'keterangan' => 'Publikasi dan jawaban'],
    ];
@endphp

<nav data-wizard-stepper data-aktif="{{ $aktif }}" aria-label="Tahap pembuatan quiz" class="kartu-wizard">
    <ol class="flex items-stretch gap-1.5 sm:gap-2">
        @foreach ($langkah as $index => $item)
            @php
                $nomor = $index + 1;
                $kelas = match (true) {
                    $nomor === $aktif => 'stepper-item is-aktif',
                    $nomor < $aktif => 'stepper-item is-selesai',
                    default => 'stepper-item',
                };
            @endphp

            <li class="{{ $kelas }} min-w-0 flex-1" data-step="{{ $nomor }}">
                <span class="stepper-item__lencana" aria-hidden="true">
                    <svg class="stepper-item__centang h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>

                    <span class="stepper-item__nomor">{{ $nomor }}</span>
                </span>

                <span class="stepper-item__teks min-w-0">
                    <span class="stepper-item__nama">{{ $item['nama'] }}</span>
                    <span class="stepper-item__keterangan">{{ $item['keterangan'] }}</span>
                </span>
            </li>

            @unless ($loop->last)
                <li class="stepper-garis" aria-hidden="true"></li>
            @endunless
        @endforeach
    </ol>
</nav>
