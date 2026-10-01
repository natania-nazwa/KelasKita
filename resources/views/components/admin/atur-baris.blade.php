@props([
    // Judul baris, mis. "Profil Admin".
    'judul',
    // Kalimat kedua yang menjelaskan apa yang diaktifkan baris ini.
    'subjudul' => null,
    'ikon',
    // Halaman tujuan. Kalau null, elemennya dirender tanpa href supaya
    // pemanggil bisa menambahkannya lewat $attributes.
    'href' => null,
    // Teks kecil di kanan, mis. "5 kelas".
    'jumlah' => null,
    'bahaya' => false,
])

{{--
    Satu baris pengaturan yang membuka halaman lain.

    Elemennya <a>, jadi bisa dioperasikan dengan keyboard dan bisa menerima
    fokus tanpa tabindex tambahan. Chevron bukan tombol terpisah: kalau
    chevron jadi tombol sendiri, pembaca layar akan membacakan tombol yang
    sama dua kali.

    Baris yang tidak membuka halaman (yang membuka dialog, atau yang
    menjalankan aksi) ditulis langsung di view sebagai <button> dengan kelas
    yang sama, supaya tombolnya benar-benar tombol dan bukan tautan yang
    dipaksa jadi tombol.
--}}

<a @if (filled($href)) href="{{ $href }}" @endif
    {{ $attributes->class(['ad-atur-baris', 'ad-atur-baris--bahaya' => $bahaya]) }}>

    @if (filled($ikon))
        <span class="ad-atur-baris__ikon" aria-hidden="true">
            <x-admin.ikon :nama="$ikon" ukuran="w-4 h-4" />
        </span>
    @endif

    <span class="ad-atur-baris__teks">
        <span class="ad-atur-baris__judul">{{ $judul }}</span>

        @if (filled($subjudul))
            <span class="ad-atur-baris__sub">{{ $subjudul }}</span>
        @endif
    </span>

    <span class="ad-atur-baris__kanan">
        @if (filled($jumlah))
            <span class="ad-atur-baris__jumlah">{{ $jumlah }}</span>
        @endif

        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4" class="ad-atur-baris__chevron" />
    </span>
</a>