@props([
    'judul',
    'subjudul' => null,
    // Tautan di kanan kepala, mis. "Lihat Semua".
    'aksiTeks' => null,
    'aksiHref' => null,
    /*
     * Tombol kembali, khusus layar kecil.
     *
     * Hanya dipakai Pengaturan. Alasannya ada di navigasi, bukan di kepala
     * halaman: di bawah 768px sidebar disembunyikan, jadi satu-satunya jalan
     * kembali dari Pengaturan ke Dashboard adalah logo di header — dan logo
     * itu terbaca sebagai merek, bukan sebagai tombol "kembali". Di desktop
     * tombol ini tidak ditampilkan karena sidebar sudah menyediakan Dashboard
     * dan Verifikasi secara terbuka.
     */
    'kembaliHref' => null,
    'kembaliTeks' => 'Kembali ke Dashboard',
    // Slot "ikon": lingkaran pastel di sebelah judul.
    'ikon' => null,
    'ikonNada' => null,
])

{{--
    Kepala halaman admin: judul besar, subjudul, dan tautan aksi di kanan.

    Satu komponen untuk semua halaman admin supaya jarak antara judul,
    subjudul, dan aksi selalu sama. Lebarnya dibuat penuh supaya
    elemen berikutnya bisa menentukan jaraknya sendiri.
--}}

<div {{ $attributes->class(['ad-kepala']) }}>
    <div class="ad-kepala__teks">
        <h1 class="ad-kepala__judul">{{ $judul }}</h1>

        @if (filled($subjudul))
            <p class="ad-kepala__sub">{{ $subjudul }}</p>
        @endif
    </div>

    @if (filled($aksiHref))
        <a href="{{ $aksiHref }}" class="ad-tautan">
            {{ $aksiTeks ?? 'Lihat Semua' }}

            <x-admin.ikon nama="panah-kanan" />
        </a>
    @elseif (filled($aksiTeks))
        <span class="ad-tautan" aria-hidden="true">{{ $aksiTeks }}</span>
    @endif

    @if (filled($kembaliHref))
        {{--
            md:hidden, bukan hidden md:inline-flex. Urutannya penting dan
            terbalik berarti tombol yang justru hilang di layar yang
            dibutuhkannya:

              - md:hidden        = tampil di bawah 768px, hilang di atasnya.
                                   Ini yang benar: tombol ini untuk ponsel,
                                   tempat sidebar tidak ada.
              - hidden md:...    = hilang di bawah 768px, tampil di atasnya.
                                   Persis kebalikannya.

            .ad-tombol sudah display: inline-flex, jadi kelas ini tidak perlu
            battled display sendiri. Batas 768px milik Tailwind (md) sama
            dengan .ad-hp dan .ad-bawah, jadi tombolnya muncul persis saat
            sidebar hilang.
        --}}
        <a href="{{ $kembaliHref }}" class="ad-tombol ad-tombol--garis shrink-0 md:hidden">
            <x-admin.ikon nama="panah-kiri" />

            {{ $kembaliTeks }}
        </a>
    @endif
</div>
