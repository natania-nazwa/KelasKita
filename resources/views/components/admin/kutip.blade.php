{{--
    Kartu kutipan (kartu branding) di sebelah kanan "Perlu Ditinjau".

    Murni dekoratif: tidak ada tautan, tombol, atau angka di dalamnya.
    Dipisah jadi komponen supaya dashboard tidak perlu menyalin ulang
    blok yang sama, dan supaya teks kutipan bisa diganti dari satu tempat.

    Ilustrasi memakai public/images/buku.png. Sebelumnya kartu ini memakai
    SVG ilustrasi-buku; berkas PNG sekarang dipakai supaya gaya visualnya
    sama soft dengan banner, dan supaya ilustrasi SVG itu tidak lagi dipakai
    dua halaman sekaligus.
--}}

<section {{ $attributes->class(['ad-seksi', 'ad-kutip']) }}>
    <p class="ad-kutip__teks">
        <span class="ad-kutip__tanda" aria-hidden="true">&ldquo;</span>

        {{ $slot ?: 'Konten berkualitas, untuk pembelajaran yang lebih baik.' }}
    </p>

    <div class="ad-kutip__ilustrasi" aria-hidden="true">
        <img src="{{ asset('images/buku.png') }}" width="552" height="552" alt="" loading="lazy">
    </div>
</section>
