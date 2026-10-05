{{--
    Kepala halaman Materi: papan ungu pastel (soft) berisi lencana, judul,
    deskripsi, tiga angka ringkas, lalu filter kategori.

    Angka ringkas diambil dari seluruh materi yang tayang, bukan dari hasil
    pencarian atau filter yang sedang aktif, supaya infonya menjadi "seberapa
    besar pustaka materi ini", bukan "berapa yang kebetulan muncul".

    Kolom pencarian tidak ada di halaman ini lagi: pencarian memakai kolom
    cari di top bar (desktop) dan di baris kedua header mobile, jadi tidak
    ada lagi dua kotak yang menulis "Cari materi..." dalam satu layar.
    Filter kategorinya tetap ditaruh di dalam papan ini, bukan di bawahnya,
    supaya area putih di antara kepala dan daftar materi hilang dan seluruh
    bagian atas halaman terbaca sebagai satu blok.

    Bentuk lencana, judul, dan deskripsinya sengaja mirip dengan kepala
    halaman Quiz supaya berpindah menu tidak terasa seperti halaman yang
    berbeda; yang membedakan hanya warnanya (Quiz ungu pekat dengan teks
    putih, Materi ungu muda dengan teks ungu tua).
--}}

@props([
    'totalMateri' => 0,
    'totalPembuat' => 0,
    'kategori' => [],
    'kategoriAktif' => '',
    'kataKunci' => '',
])

<section data-reveal="zoom" class="materi-kepala p-5 sm:p-7">
    <div class="materi-kepala__isi">

        {{-- Baris atas: lencana di kiri, tiga angka di kanan. Di layar
             sempit angka turun ke bawah lencana karena flex-wrap. --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <span class="materi-kepala__lencana">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>

                Pustaka Belajar
            </span>

            <div class="materi-kepala__statistik">
                <div>
                    <span class="materi-kepala__angka">{{ $totalMateri }}</span>
                    <span class="materi-kepala__satuan">Materi</span>
                </div>

                <div>
                    <span class="materi-kepala__angka">{{ count($kategori) }}</span>
                    <span class="materi-kepala__satuan">Kategori</span>
                </div>

                <div>
                    <span class="materi-kepala__angka">{{ $totalPembuat }}</span>
                    <span class="materi-kepala__satuan">Pembuat</span>
                </div>
            </div>
        </div>

        <h1 class="materi-kepala__judul">Materi Pembelajaran</h1>

        {{-- Deskripsi dan filter kategori dibungkus satu baris. Di layar
             lebih kecil blok ini tetap blok biasa, jadi susunannya persis
             seperti semula: teks lalu filter bertumpuk di bawahnya. Hanya
             di desktop (≥1024px) blok .materi-kepala__baris di app.css yang
             mengubahnya menjadi flex dan menarik filter ke sebelah kanan
             teks. --}}
        <div class="materi-kepala__baris">
            <p class="materi-kepala__deskripsi">
                Temukan dan pelajari berbagai materi yang dibuat oleh guru maupun teman-temanmu.
            </p>

            {{-- Filter kategori. Masih berbentuk form, dan kata kunci dari
                 kolom cari top bar ikut dikirim sebagai input tersembunyi,
                 supaya menyaring kategori tidak menghapus hasil pencarian. --}}
            <div class="materi-kepala__filter mt-5">
                <x-materi.cari :kategori="$kategori" :kategori-aktif="$kategoriAktif"
                    :kata-kunci="$kataKunci" :total-materi="$totalMateri" />
            </div>
        </div>
    </div>
</section>
