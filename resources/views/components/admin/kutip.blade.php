{{--
    Kartu kutipan (kartu branding) di sebelah kanan "Perlu Ditinjau".

    Murni dekoratif: tidak ada tautan, tombol, atau angka di dalamnya.

    Teksnya dicek dengan $slot->isNotEmpty(), bukan dengan "$slot ?:".
    <x-admin.kutip /> yang dipanggil tanpa slot tetap punya $slot, hanya
    objek ComponentSlot yang kosong. Objek kosong itu bernilai truthy,
    jadi kalau memakai "?:" teks fallback-nya tidak akan pernah dipakai
    dan kartu ikut tampil kosong.

    Ilustrasi memakai public/images/buku.png, berkas 552x552 berlatar
    transparan, diletakkan di sebelah kanan teks (di bawahnya di layar
    sempit). Atribut width/height-nya sengaja ditulis supaya browser tahu
    rasio gambarnya sebelum berkas selesai diunduh, jadi kartu tidak
    melompat saat gambar dimuat.

    Kutipannya ditutup dengan tanda petik supaya utuhnya jadi satu
    kutipan. Penutupnya memakai span sendiri supaya ukurannya bisa diatur
    terpisah dari tanda pembuka yang jauh lebih besar.
--}}

@php
    $teks = $slot->isNotEmpty() ? $slot : 'Konten berkualitas, untuk pembelajaran yang lebih baik.';
@endphp

<section {{ $attributes->class(['ad-seksi', 'ad-kutip']) }}>
    <p class="ad-kutip__teks">
        <span class="ad-kutip__tanda" aria-hidden="true">&ldquo;</span>

        {{ $teks }}<span class="ad-kutip__tutup" aria-hidden="true">&rdquo;</span>
    </p>

    <div class="ad-kutip__ilustrasi">
        <img src="{{ asset('images/buku.png') }}" width="552" height="552" alt="">
    </div>
</section>
