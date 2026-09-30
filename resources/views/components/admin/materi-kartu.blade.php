@props([
    // Satu baris dari App\Support\DaftarMateriAdmin::petakan().
    'materi',
    // Slug materi yang sedang dipreview di panel kanan.
    'terpilih' => null,
    // Query string halaman yang sedang aktif, supaya memilih kartu lain tidak
    // mematikan pencarian atau filter yang sedang berjalan.
    'parameter' => [],
])

@php
    /*
     * Kartu materi di daftar kiri halaman "Materi" (admin).
     *
     * Bentuk array per kartu datang dari App\Support\DaftarMateriAdmin, jadi
     * komponen ini tidak terikat Eloquent. Yang dipakai di sini:
     *   judul, jumlah_bab, tanggal_label, tanggal_jam, status, status_label,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap],
     *   tautan_detail, tautan_edit, tautan_hapus
     *
     * Klik di mana saja pada kartu memilih materi itu untuk panel kanan,
     * sedangkan "Lihat" dan menu tiga titik tetap jadi tautan/form sendiri.
     * Itu dicapai tanpa <a> di dalam <a>: judulnya adalah satu-satunya tautan
     * di dalam kartu, dan ::after-nya (lihat .ad-materi-kartu__pilih di
     * admin.css) yang jadi area klik seluruh kartu. Tombol di dalam kartu
     * diberi z-index di atasnya, jadi tidak pernah tertimpa.
     */
    $dipilih = $terpilih === $materi['slug'];
    $pilih = route('admin.materi', array_merge($parameter, ['materi' => $materi['slug'], 'page' => null]));
@endphp

<article @class(['ad-materi-kartu', 'ad-materi-kartu--dipilih' => $dipilih])>
    <span class="ad-materi-kartu__ikon" aria-hidden="true">
        <x-admin.ikon nama="buku" ukuran="w-5 h-5" />
    </span>

    <div class="ad-materi-kartu__isi">
        <div class="ad-materi-kartu__kepala">
            <span class="ad-materi-kartu__tipe">Materi</span>

            <h3 class="ad-materi-kartu__judul">
                {{-- aria-current="true" supaya pembaca layar tahu kartu ini
                     yang sedang jadi isi panel kanan. --}}
                <a class="ad-materi-kartu__pilih" href="{{ $pilih }}"
                    @if ($dipilih) aria-current="true" @endif>
                    {{ $materi['judul'] }}
                </a>
            </h3>
        </div>

        <p class="ad-materi-kartu__pembuat">
            <span class="ad-materi-kartu__avatar"
                style="--a: {{ $materi['pembuat']['warna'] }}; --a-gelap: {{ $materi['pembuat']['warna_gelap'] }};"
                aria-hidden="true">{{ $materi['pembuat']['inisial'] }}</span>

            <span>Dibuat oleh: {{ $materi['pembuat']['nama'] }}</span>
        </p>

        <p class="ad-materi-kartu__meta">
            {{ $materi['kategori']['nama'] }} &middot; {{ $materi['jumlah_bab'] }} Bab
        </p>

        <p class="ad-materi-kartu__tanggal">
            {{ $materi['tanggal_label'] }}
            @if (filled($materi['tanggal_jam']))
                &middot; {{ $materi['tanggal_jam'] }}
            @endif
        </p>
    </div>

    <div class="ad-materi-kartu__kanan">
        <x-admin.lencana-materi :status="$materi['status']" :label="$materi['status_label']" />

        <div class="ad-materi-kartu__aksi">
            <a href="{{ $materi['tautan_detail'] }}" class="ad-tombol ad-tombol--halus ad-tombol--kecil">
                <x-admin.ikon nama="mata" ukuran="w-3.5 h-3.5" />

                Lihat
            </a>

            {{--
                Menu tiga titik memakai <details>, bukan tombol + <div>:
                buka/tutup-nya dari browser, jadi tetap jalan tanpa JavaScript
                dan tidak perlu satu blok IntersectionObserver cuma untuk
                menutup menu yang lagi terbuka.

                Isinya hanya aksi yang memang ada di aplikasi: Lihat, Edit,
                dan Hapus. Tidak ada "Arsipkan" karena tidak ada status arsip
                di database — menampilkan opsi yang pasti ditolak server
                hanya menambah satu klik sia-sia.
            --}}
            <details class="ad-titik" data-tutup-luar>
                <summary class="ad-titik__tombol" aria-label="Opsi lain untuk {{ $materi['judul'] }}">&vellip;</summary>

                <div class="ad-titik__menu" role="menu">
                    <a class="ad-titik__item" role="menuitem" href="{{ $materi['tautan_detail'] }}">
                        <x-admin.ikon nama="mata" />

                        Lihat
                    </a>

                    <a class="ad-titik__item" role="menuitem" href="{{ $materi['tautan_edit'] }}">
                        <x-admin.ikon nama="pena" />

                        Edit
                    </a>

                    <button type="button" class="ad-titik__item ad-titik__item--bahaya" role="menuitem"
                        data-hapus-buka
                        data-hapus-judul="Hapus Materi?"
                        data-hapus-meta="{{ $materi['kategori']['nama'] }} &middot; {{ $materi['jumlah_bab'] }} Bab &middot; oleh {{ $materi['pembuat']['nama'] }}"
                        data-hapus-aksi="{{ $materi['tautan_hapus'] }}">
                        <x-admin.ikon nama="sampah" />

                        Hapus
                    </button>
                </div>
            </details>
        </div>
    </div>
</article>
