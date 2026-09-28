{{--
    Kartu motivasi di dasar kolom kanan.

    Murni elemen tampilan: tidak ada angka dan tidak ada query, jadi
    tidak menerima props apa pun. Latarnya lavender dengan pita trophy
    di kiri bawah supaya tidak terasa kosong.
--}}

<section {{ $attributes->class(['hasil-motivasi']) }}>

    <span class="hasil-motivasi__pita" aria-hidden="true">
        <svg class="h-full w-full" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('piala') }}" />
        </svg>
    </span>

    <span class="hasil-motivasi__bintang" aria-hidden="true">
        <svg class="h-full w-full" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('bintang') }}" />
        </svg>
    </span>

    <p class="hasil-motivasi__teks">
        Teruslah belajar, karena setiap usaha pasti membuahkan hasil!
    </p>
</section>
