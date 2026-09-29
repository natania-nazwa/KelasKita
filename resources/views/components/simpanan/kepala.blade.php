@props([
    // Jumlah simpanan materi dan quiz milik pengguna yang sedang login.
    'jumlahMateri' => 0,
    'jumlahQuiz' => 0,
])

{{--
    Kepala halaman "Simpan": papan hero berisi lencana, dua angka ringkas,
    judul, deskripsi, lalu tab Materi / Quiz.

    Bentuknya sengaja mengikuti kepala halaman Materi dan Quiz
    (components/materi/kepala dan components/quiz/kepala): papan berwarna
    yang mengisi bagian atas halaman, lencana dan judul di dalamnya, dan
    tidak ada area putih kosong di antara kepala dan daftar. Berpindah dari
    menu Materi atau Quiz ke sini karena itu tidak terasa seperti halaman
    yang berbeda.

    Berbeda dengan kedua halaman itu, papan ini tidak memuat kolom cari
    dan filter: pencarian sudah ada di top bar dan mengarah ke halaman ini
    juga, jadi mengulangnya hanya menghasilkan dua kotak pencarian dalam
    satu halaman. Slot dipakai untuk tab, yang memang tidak ada di halaman
    Materi dan Quiz.
--}}

<section data-reveal="zoom" class="simpan-kepala p-5 sm:p-7">
    <div class="simpan-kepala__isi">

        {{-- Baris atas: lencana di kiri, dua angka di kanan. Di layar
             sempit angka turun ke bawah lencana karena flex-wrap. --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <span class="simpan-kepala__lencana">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                </svg>

                Koleksiku
            </span>

            <div class="simpan-kepala__statistik">
                <div>
                    <span class="simpan-kepala__angka">{{ $jumlahMateri }}</span>
                    <span class="simpan-kepala__satuan">Materi</span>
                </div>

                <div>
                    <span class="simpan-kepala__angka">{{ $jumlahQuiz }}</span>
                    <span class="simpan-kepala__satuan">Quiz</span>
                </div>
            </div>
        </div>

        <h1 class="simpan-kepala__judul">Simpan</h1>

        <p class="simpan-kepala__deskripsi">
            Materi dan quiz yang kamu simpan lewat tombol bookmark di pojok kanan atas kartunya.
        </p>

        {{-- Tab Materi / Quiz, dikirim lewat slot supaya tab ini satu
             blok dengan kepala halaman dan tidak menambah jarak putih
             di antara keduanya. --}}
        <div class="mt-5">
            {{ $slot }}
        </div>
    </div>
</section>
