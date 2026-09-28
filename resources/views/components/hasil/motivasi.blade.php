{{--
    Kartu motivasi di dasar kolom kanan.

    Murni elemen tampilan: tidak ada angka dan tidak ada query, jadi
    tidak menerima props apa pun.

    Di atas teks kutipan tidak ada SVG trophy lagi, melainkan emoji
    piala. Aplikasi ini sudah memakai emoji di beberapa tempat (👋 dan ✨
    di banner dashboard, emoji kategori di landing page), jadi ini
    masih konsisten dengan bahasa visual yang ada.

    Bintang di kanan atas tetap SVG: itu dekorasi latar dengan opacity
    rendah, bukan "logo" di atas teks.
--}}

<section {{ $attributes->class(['hasil-motivasi']) }}>

    <span class="hasil-motivasi__piala" role="img" aria-label="Piala">🏆</span>

    <span class="hasil-motivasi__bintang" aria-hidden="true">
        <svg class="h-full w-full" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('bintang') }}" />
        </svg>
    </span>

    <p class="hasil-motivasi__teks">
        Teruslah belajar, karena setiap usaha pasti membuahkan hasil!
    </p>
</section>
