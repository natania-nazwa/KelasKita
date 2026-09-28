@props([
    // Sama dengan DaftarQuiz::perHalaman(), supaya kerangka dan grid
    // yang menggantikannya punya tinggi yang sama.
    'jumlah' => 12,
])

{{--
    Kerangka kartu quiz untuk keadaan memuat.

    Sengaja disembunyikan dari awal: markup-nya sudah ada di halaman, lalu
    initQuizMuat() di resources/js/app.js menampilkannya hanya saat pengguna
    mengetik atau berpindah halaman. Dengan begitu tidak ada kedipan layout,
    dan skeleton tetap muncul untuk navigasi antar halaman yang memang
    butuh waktu.

    Strukturnya mengikuti .kartu-quiz: thumbnail, lencana, judul, deskripsi,
    avatar, dan jumlah soal. Kelas .rangka yang menjalankan animasi
    shimmer-nya (lihat app.css).
--}}

<div data-quiz-rangka hidden aria-hidden="true"
    class="mt-5 grid grid-cols-2 gap-4 min-w-0 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
    @for ($i = 0; $i < $jumlah; $i++)
        <div class="kartu-quiz kartu-quiz--rangka min-w-0">
            <div class="kartu-quiz__gambar">
                <span class="rangka"></span>
            </div>

            <div class="kartu-quiz__badan">
                <span class="rangka kartu-quiz__rangka-pil mt-0.5"></span>

                <span class="rangka mt-3 h-4 w-4/5"></span>
                <span class="rangka mt-2 h-4 w-2/3"></span>

                <span class="rangka mt-3 h-2.5 w-full"></span>
                <span class="rangka mt-2 h-2.5 w-3/4"></span>

                <div class="kartu-quiz__meta">
                    <span class="flex items-center gap-2">
                        <span class="rangka h-7 w-7 rounded-full"></span>
                        <span class="rangka h-2.5 w-16"></span>
                    </span>

                    <span class="rangka h-5 w-16 rounded-full"></span>
                </div>
            </div>
        </div>
    @endfor
</div>
