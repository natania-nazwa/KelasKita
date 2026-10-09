@props([
    // Satu kartu dari App\Support\DaftarKonten::petikan().
    'kartu',
])

{{--
    Satu kartu di daftar "Konten Pembelajaran".

    Tampilan kartu ini sengaja sama persis dengan kartu di halaman "Karya Saya"
    milik pengguna: kelas yang dipakai (kartu-materi, karya-kartu, karya-status,
    karya-info, karya-aksi) semuanya milik kartu pengguna, jadi satu konten punya
    tampilan yang sama di kedua tempat. Yang membedakan hanya isi dan tombolnya.

    Bedanya dari kartu pengguna ada di tiga hal:

      - kartu di sini menampilkan "Batalkan Publikasi" dan "Duplikat". Dua aksi
        itu hanya ada di sisi admin: pemilik tidak menerbitkan karyanya sendiri,
        dan duplikat adalah keputusan kerja admin, bukan pilihan pengguna.
      - tombol "Lihat" dan judulnya selalu jadi tautan. Kartu pengguna
        menyembunyikan tautannya untuk yang belum tayang supaya tidak
        menuliskan URL yang berakhir 404; di sini justru halaman detailnya
        sudah bisa membaca materi maupun quiz dari status apa pun, jadi
        tautannya selalu berguna.
      - tidak ada alasan ditolak dan tidak ada sisa pengajuan. Dua hal itu
        hanya berlaku untuk konten yang menunggu keputusan admin, dan konten
        di daftar ini memang milik admin sendiri.

    Aksi di kartu dibagi tiga tingkat supaya tiap baris punya satu jenis
    informasi. Baris paling bawah ("kaki") hanya berisi "Lihat" dan "Edit" —
    dua hal yang paling sering dilakukan, jadi keduanya dibiarkan berupa
    tombol yang kelihatan, tidak disembunyikan di balik menu. Baris
    informasi menutup dengan tanggal, dan menu tiga titik berdiri tepat di
    sebelah kanannya: Duplikat, Publish/Batalkan, dan Hapus tetap di dalam
    menu, karena ketiganya jarang dipakai dan Hapus tidak sebaiknya selalu
    satu klik saja dari kartu. Menu itu dikendalikan
    resources/js/konten-daftar.js, jadi tidak ada perilaku baru yang harus
    dibuat untuknya.

    Pengganti baris mendatar yang pernah dipakai di sini. Baris itu memang
    bisa memindai empat hal sekaligus tanpa membaca satu per satu, tapi daftar
    di halaman ini biasanya berisi lebih dari satu halaman isinya, dan grid
    kartu memberi tempat untuk thumbnail yang tidak pernah bisa ditunjukkan
    baris.
--}}

@php
    $kategori = $kartu['kategori'];
    $nama = $kartu['jenis'] === 'materi' ? 'Materi' : 'Quiz';
    $terbit = $kartu['status'] === \App\Models\Materi::STATUS_PUBLISHED;
@endphp

<article style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-materi karya-kartu kartu-konten group min-w-0">

    {{-- A. Thumbnail: tinggi seragam (16:9), sama seperti kartu pengguna. --}}
    <div class="kartu-materi__gambar">
        @if (filled($kartu['thumbnail'] ?? null))
            <img src="{{ $kartu['thumbnail'] }}" alt="" loading="lazy" class="kartu-materi__foto">
        @else
            <span class="kartu-materi__gambar-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Lencana kategori, melayang di pojok kiri bawah thumbnail. --}}
        <span class="kartu-materi__lencana">{{ $kategori['nama'] }}</span>

        {{-- Status di pojok kanan thumbnail. --}}
        <span class="karya-status karya-status--{{ $kartu['warna_status'] }} karya-pojok">
            <span class="karya-status__titik" aria-hidden="true"></span>

            {{ $kartu['status_label'] }}
        </span>
    </div>

    {{-- B-E. Aksen, judul, deskripsi, informasi, dan baris aksi. --}}
    <div class="kartu-materi__badan">
        {{-- Garis warna kategori, menyambung ke thumbnail tanpa celah. --}}
        <span class="kartu-materi__aksen -mx-4 -mt-3.5 mb-3 block" aria-hidden="true"></span>

        <h2 class="kartu-materi__judul karya-judul">
            <a href="{{ $kartu['tautan']['lihat'] }}">{{ $kartu['judul'] }}</a>
        </h2>

        {{--
            Deskripsi hanya ditulis kalau pengarangnya memang mengisinya.
            Materi yang sudah terbit sebelum form punya isian deskripsi belum
            memilikinya — dan menampilkan baris kosong hanya akan menyisakan
            ruang kosong di antara judul dan baris informasi.
        --}}
        @if (filled($kartu['deskripsi']))
            <p class="kartu-materi__deskripsi">{{ $kartu['deskripsi'] }}</p>
        @endif

        {{-- Jumlah bab atau soal, perkiraan waktu, dan tanggal. --}}
        <div class="karya-info mt-3.5">
            <span class="karya-info__butir">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>

                {{ $kartu['jumlah'] }} {{ $kartu['satuan'] }}
            </span>

            @if ((int) $kartu['menit'] > 0)
                <span class="karya-info__butir">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                    {{ $kartu['menit'] }} menit
                </span>
            @endif

            {{--
                Tanggal menyusul jumlah dan durasi, jadi ketiganya terbaca
                sebagai satu baris dari kiri ke kanan. Dulu tanggal ikut
                dorongan ke kanan baris itu — dan baris yang paling penting
                (kapan konten ini diperbarui) justru jadi satu-satunya yang
                paling jauh dari judulnya.
            --}}
            <span class="karya-info__butir karya-info__butir--sepuh">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>

                {{ $kartu['tanggal_label'] }}
            </span>

            {{--
                Menu tiga titik, satu-satunya hal di baris ini yang bukan
                informasi: deshalb ia yang didorong ke ujung kanan, supaya
                tidak berebut tempat dengan baris tanggal di sebelah kirinya.

                .ad-konten-menu sudah position: relative, jadi tidak perlu
                pembungkus lain untuk menjadikannya jangkar popover. Isinya
                dibaca resources/js/konten-daftar.js: menu yang terbuka
                ditutup lagi saat tetikus menekan di luar .ad-konten-menu.
            --}}
            <div class="kartu-konten__akhir">
                <div class="ad-konten-menu" data-konten-menu>
                    <button type="button" class="ad-konten-menu__tombol" data-konten-menu-tombol
                        aria-expanded="false" aria-haspopup="menu"
                        aria-label="Aksi untuk {{ $nama }} {{ $kartu['judul'] }}">
                        <x-admin.ikon nama="titik-tiga" ukuran="w-4 h-4" :tebal="2.2" />
                    </button>

                    <div class="ad-konten-menu__isi" data-konten-menu-isi role="menu"
                        aria-label="Aksi untuk {{ $nama }} {{ $kartu['judul'] }}">

                        {{-- Duplikat lewat form: ia mengubah database, jadi bukan tautan. --}}
                        <form method="POST" action="{{ $kartu['tautan']['duplikat'] }}">
                            @csrf

                            <button type="submit" class="ad-konten-menu__aksi" role="menuitem">
                                <x-admin.ikon nama="salin" ukuran="w-4 h-4" />

                                Duplikat
                            </button>
                        </form>

                        {{--
                            Satu tombol untuk dua arah, jadi yang ditampilkan
                            tergantung status: draft mendapat "Publish", yang sudah
                            terbit mendapat "Batalkan Publikasi" — sehingga
                            "Publish" tidak pernah muncul pada konten yang sudah
                            tayang.

                            Atribut data-konten-terbit-* dibaca
                            resources/js/konten-publish.js untuk mengisi judul,
                            pesan, dan nama tombol dialog — jadi kalimatnya berbeda
                            antara menerbitkan dan menarik kembali.
                        --}}
                        <button type="button" class="ad-konten-menu__aksi" data-konten-publish="publish"
                            data-konten-aksi="{{ $kartu['tautan']['publish'] }}"
                            data-konten-nama="{{ $nama }} &quot;{{ $kartu['judul'] }}&quot;"
                            data-konten-terbit-judul="{{ $terbit ? 'Batalkan publikasi?' : 'Publish konten?' }}"
                            data-konten-terbit-pesan="{{ $terbit
                                ? $nama . ' ini akan ditarik dari halaman pengguna dan kembali menjadi draft.'
                                : 'Konten ini akan langsung tersedia untuk pengguna dan notifikasi akan dikirim.' }}"
                            data-konten-terbit-tombol="{{ $terbit ? 'Batalkan Publikasi' : 'Publish Sekarang' }}"
                            role="menuitem">
                            <x-admin.ikon :nama="$terbit ? 'silang-polos' : 'unggah'" ukuran="w-4 h-4" />

                            {{ $terbit ? 'Batalkan Publikasi' : 'Publish' }}
                        </button>

                        <span class="ad-konten-menu__pisah" role="separator"></span>

                        <button type="button" class="ad-konten-menu__aksi ad-konten-menu__aksi--bahaya"
                            data-hapus-buka
                            data-hapus-judul="Hapus {{ strtolower($nama) }}?"
                            data-hapus-meta="{{ $kategori['nama'] }} &middot; {{ $kartu['ringkasan'] }} &middot; {{ $kartu['tanggal_label'] }}"
                            data-hapus-aksi="{{ $kartu['tautan']['hapus'] }}"
                            data-hapus-pesan="{{ $terbit
                                ? $nama . ' ini sudah tayang untuk pengguna. Menghapusnya akan menaruhnya dari halaman pengguna dan tidak dapat dikembalikan.'
                                : $nama . ' ini akan dihapus dan tidak dapat dikembalikan.' }}"
                            role="menuitem">
                            <x-admin.ikon nama="sampah" ukuran="w-4 h-4" />

                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{--
            Kaki kartu. Hanya "Lihat" dan "Edit" — dua hal yang paling sering
            dilakukan, jadi keduanya dibiarkan berupa tombol yang kelihatan.
            Publish dan hapus lewat dialog konfirmasi lebih dulu (dikerjakan di
            resources/js/konten-publish.js dan .konten-admin.js).
        --}}
        <div class="karya-aksi">
            <a href="{{ $kartu['tautan']['lihat'] }}"
                class="karya-aksi__tombol karya-aksi__tombol--lihat karya-aksi__tombol--utama"
                title="Buka halaman {{ strtolower($nama) }} ini">
                Lihat

                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>

            <a href="{{ $kartu['tautan']['edit'] }}" class="karya-aksi__tombol"
                title="Ubah {{ strtolower($nama) }} ini">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                </svg>

                Edit
            </a>
        </div>
    </div>
</article>