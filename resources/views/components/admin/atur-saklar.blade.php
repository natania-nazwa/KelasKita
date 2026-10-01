@props([
    // Nama pengaturan, dipakai sebagai aria-label saklar.
    'judul',
    // Kalau diisi, saklar menunjuk keterangan yang tampil di sebelahnya,
    // di beri id oleh pemanggil.
    'deskripsi' => null,
    // Nilai sekarang, ditulis ke aria-checked.
    'aktif' => false,
])

{{--
    Saklar nyala / mati untuk satu pengaturan.

    Tombol dengan role="switch", bukan input[type=checkbox] yang disembunyikan
    lalu diganti: bentuknya memang saklar, dan aria-checked langsung
    diterima pembaca layar tanpa perlu tebakan tambahan.

    Kolom aslinya tetap ada di form sebagai field tersembunyi dengan nilai 0
    tepat sebelum saklarnya, karena checkbox yang tidak dicentang tidak pernah
    terkirim. Tanpa field tersembunyi itu, mematikan saklar akan berarti
    field-nya hilang dan nilainya tidak akan pernah tersimpan.
--}}

<button type="button"
    class="ad-atur-saklar"
    role="switch"
    aria-checked="{{ $aktif ? 'true' : 'false' }}"
    aria-label="{{ $judul }}"
    @if (filled($deskripsi)) aria-describedby="{{ $deskripsi }}" @endif
    data-atur-saklar
>
    <span class="ad-atur-saklar__titik" aria-hidden="true"></span>
</button>