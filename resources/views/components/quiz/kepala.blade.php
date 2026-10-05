{{--
    Kepala halaman Quiz: papan ungu berisi lencana, judul, deskripsi, tiga
    angka ringkas, lalu filter kategori.

    Angka ringkas sengaja diambil dari seluruh quiz yang tayang, bukan dari
    hasil pencarian atau filter yang sedang aktif, supaya infonya menjadi
    "seberapa besar pustaka quiz ini", bukan "berapa yang kebetulan
    muncul".

    Kolom pencarian tidak ada di halaman ini lagi: pencarian memakai kolom
    cari di top bar (desktop) dan di baris kedua header mobile, jadi tidak
    ada lagi dua kotak yang menulis "Cari quiz..." dalam satu layar.
    Filter kategorinya tetap ditaruh di dalam papan ini, bukan di bawahnya,
    supaya area putih di antara kepala dan daftar quiz hilang dan seluruh
    bagian atas halaman terbaca sebagai satu blok.

    Bentuk lencana, judul, dan deskripsinya sengaja mirip dengan kepala
    halaman Materi supaya berpindah menu tidak terasa seperti halaman
    yang berbeda; yang membedakan hanya warnanya.
--}}

@props([
    'totalQuiz' => 0,
    'totalSoal' => 0,
    'jumlahKategori' => 0,
    'kategori' => [],
    'kategoriAktif' => '',
    'kataKunci' => '',
])

<section data-reveal="zoom" class="quiz-kepala p-5 sm:p-7">
    <div class="quiz-kepala__isi">

        {{-- Baris atas: lencana di kiri, tiga angka di kanan. Di layar
             sempit angka turun ke bawah lencana karena flex-wrap. --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="quiz-kepala__lencana">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 0 1 18 0Z" />
                    </svg>

                    Latihan
                </span>

                {{--
                    Pintasan "Masukkan Kode". Alurnya sama persis dengan
                    tombol yang sama di dashboard: sekali klik langsung ke
                    halaman Gabung Quiz untuk mengetik kode, bukan ke daftar
                    quiz dulu. Quiz mode kode milik orang lain tidak pernah
                    muncul di daftar ini, jadi tanpa pintasan ini fitur itu
                    hanya bisa dicapai lewat dashboard.
                --}}
                <a href="{{ route('user.sesi.gabung') }}" class="quiz-kepala__aksi">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kode') }}" />
                    </svg>

                    Masukkan Kode
                </a>
            </div>

            <div class="quiz-kepala__statistik">
                <div>
                    <span class="quiz-kepala__angka">{{ $totalQuiz }}</span>
                    <span class="quiz-kepala__satuan">Quiz</span>
                </div>

                <div>
                    <span class="quiz-kepala__angka">{{ $totalSoal }}</span>
                    <span class="quiz-kepala__satuan">Soal</span>
                </div>

                <div>
                    <span class="quiz-kepala__angka">{{ $jumlahKategori }}</span>
                    <span class="quiz-kepala__satuan">Kategori</span>
                </div>
            </div>
        </div>

        <h1 class="quiz-kepala__judul">Quiz</h1>

        {{-- Deskripsi dan filter kategori dibungkus satu baris. Di layar
             lebih kecil blok ini tetap blok biasa, jadi susunannya persis
             seperti semula: teks lalu filter bertumpuk di bawahnya. Hanya
             di desktop (≥1024px) blok .quiz-kepala__baris di app.css yang
             mengubahnya menjadi flex dan menarik filter ke sebelah kanan
             teks. --}}
        <div class="quiz-kepala__baris">
            <p class="quiz-kepala__deskripsi">
                Uji pemahamanmu dengan berbagai kuis menarik yang dibuat oleh guru maupun teman-temanmu.
            </p>

            {{-- Filter kategori. Masih berbentuk form, dan kata kunci dari
                 kolom cari top bar ikut dikirim sebagai input tersembunyi,
                 supaya menyaring kategori tidak menghapus hasil pencarian. --}}
            <div class="quiz-kepala__filter mt-5">
                <x-quiz.cari :kategori="$kategori" :kategori-aktif="$kategoriAktif"
                    :kata-kunci="$kataKunci" :total-quiz="$totalQuiz" />
            </div>
        </div>
    </div>
</section>
