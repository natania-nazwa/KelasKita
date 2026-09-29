{{--
    Kepala halaman "Karya Saya": papan ungu berisi lencana, judul, dan
    deskripsi.

    Bentuknya mengikuti kepala halaman Materi dan Quiz (papan ungu dengan
    lencana, judul besar, lalu deskripsi) supaya berpindah menu tidak
    terasa seperti halaman yang berbeda. Warnanya lebih dalam daripada
    kepala Quiz karena halaman ini adalah tempat mengelola karya sendiri,
    bukan tempat menjelajah pustaka.

    Padding bawahnya lebih besar dari biasanya: deret kartu ringkasan di
    bawah halaman ini naik INTO papan ini, jadi ruang ekstra itu dipakai
    sebagai tempat kartu itu bertumpuk.

    Isinya sengaja tidak ditambah apa pun: hanya lencana, judul, dan
    subtitle yang sama dengan sebelumnya, dipindah ke dalam papan.
--}}

<section data-reveal="zoom" class="karya-kepala p-5 pb-16 sm:p-7 sm:pb-20">
    <div class="karya-kepala__isi">

        <span class="karya-kepala__lencana">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>

            Karyaku
        </span>

        <h1 class="karya-kepala__judul">Karya Saya</h1>

        <p class="karya-kepala__deskripsi">
            Kelola materi dan quiz yang kamu buat sendiri.
        </p>
    </div>
</section>
