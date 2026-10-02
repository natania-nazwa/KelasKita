@props([
    // Satu baris dari App\Support\DaftarMateriAdmin::petikan().
    'materi',
])

@php
    /*
     * Kartu materi di daftar halaman "Materi" (admin).
     *
     * Bentuknya mengikuti kartu materi milik pengguna
     * (resources/views/components/materi/kartu.blade.php): gambar di atas,
     * isi di bawah, pembatas aksen, lalu baris informasi. Bukan kartu
     * horizontal, supaya satu baris kartu tidak hanya berisi empat baris
     * teks yang saling salting.
     *
     * Yang berbeda hanya isi bodynya. Kartu user memuat deskripsi materi;
     * kartu admin tidak membutuhkannya — admin sudah tahu isinya dari
     * detail, dan yang perlu dilihat di sini justru siapa yang menulis dan
     * kategorinya. Jadi baris yang di kartu user dipakai deskripsi, di sini
     * dipakai kategori dan jumlah bab.
     *
     * Bentuk array per kartu datang dari App\Support\DaftarMateriAdmin, jadi
     * komponen ini tidak terikat Eloquent:
     *   judul, thumbnail, tingkat_kesulitan, jumlah_bab, tanggal_label,
     *   tanggal_jam, status, status_label, boleh_edit,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap],
     *   tautan_detail, tautan_edit, tautan_hapus
     *
     * Kartu tidak bisa diklik seluruhnya. Dulu ada pola "stretched link" di
     * sini supaya sekali klik bisa mengisi panel detail; panel itu sudah
     * dihapus, jadi satu-satunya jalan ke materi adalah tombol Lihat.
     * Membuatkan tautan besar di sini juga tidak menguntungkan: menu tiga
     * titik lalu ikut jadi bagian dari area klik yang sama.
     */

    $kategori = $materi['kategori'];
    $kesulitan = strtolower((string) $materi['tingkat_kesulitan']);
@endphp

<article class="ad-kartu-daftar"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};">

    {{--
        A. Blok gambar. Selalu berisi sesuatu: kalau materinya tidak punya
        thumbnail, gradasi warna kategori + ikon mapel menggantikannya —
        persis seperti kartu materi milik pengguna, bukan kotak kosong.
    --}}
    <div class="ad-kartu-daftar__gambar">
        @if (filled($materi['thumbnail']))
            <img class="ad-kartu-daftar__foto" src="{{ $materi['thumbnail'] }}"
                alt="Thumbnail materi {{ $materi['judul'] }}" loading="lazy">
        @else
            <span class="ad-kartu-daftar__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{--
            Tingkat kesulitan melayang di pojok kiri. Di kartu admin ini satu
           -satunya tempat informasi tentang tingkat kesulitan, dan tidak
            beradu dengan kategori yang sudah tampil di baris meta.
        --}}
        @if (filled($materi['tingkat_kesulitan']))
            <span class="ad-kartu-daftar__kesulitan capitalize">{{ $materi['tingkat_kesulitan'] }}</span>
        @endif
    </div>

    {{-- B. Isi kartu. --}}
    <div class="ad-kartu-daftar__badan">
        <span class="ad-kartu-daftar__aksen" aria-hidden="true"></span>

        <h3 class="ad-kartu-daftar__judul">{{ $materi['judul'] }}</h3>

        <p class="ad-kartu-daftar__pembuat">
            <span>Dibuat oleh:</span>

            <span class="ad-kartu-daftar__truncate">{{ $materi['pembuat']['nama'] }}</span>
        </p>

<p class="ad-kartu-daftar__meta">
            {{ $kategori['nama'] }} &middot; {{ $materi['jumlah_bab'] }} Bab
        </p>

        {{--
            Tanggal dan menu tiga titik berbagi satu baris: tanggal di kiri,
            menu menempel di kanan. Dulu menunya berdiri di kaki kartu,
            terpisah dari tanggal oleh tombol Lihat — jadi tanggal jadi
            baris info terakhir yang sendirian, dan menuaksinya terlihat
            seperti tombol ketiga di baris tombol, bukan bagian dari
            informasi kartu.

            Baris pembungkus ini hanya flex; jarak ke atas tetap datang
            dari margin-top .ad-kartu-daftar__tanggal supaya ketiganya
            (pembuat, meta, tanggal) tetap punya jarak yang sama.
        --}}
        <div class="ad-kartu-daftar__tanggal-baris">
            <p class="ad-kartu-daftar__tanggal">
                {{ $materi['tanggal_label'] }}
                @if (filled($materi['tanggal_jam']))
                    &middot; {{ $materi['tanggal_jam'] }}
                @endif
            </p>

            {{--
                Menu tiga titik memakai <details>, bukan tombol + <div>:
                buka/tutup-nya dari browser, jadi tetap jalan tanpa
                JavaScript.

                Isinya hanya aksi yang memang ada di aplikasi: Lihat,
                Edit, dan Hapus. Tidak ada "Arsipkan" karena tidak ada
                status arsip di database.

                Edit hanya untuk materi yang dibuat admin yang sedang
                login (boleh_edit). Materi buatan pengguna lain tetap
                punya Lihat dan Hapus; isinya bukan hak admin untuk
                diubah, dan Admin\MateriKelolaController akan menolak
                403 kalau URL-nya diketik manual.
            --}}
            <details class="ad-titik" data-tutup-luar>
                <summary class="ad-titik__tombol" aria-label="Opsi lain untuk {{ $materi['judul'] }}">&vellip;</summary>

                <div class="ad-titik__menu" role="menu">
                    <a class="ad-titik__item" role="menuitem" href="{{ $materi['tautan_detail'] }}">
                        <x-admin.ikon nama="mata" />

                        Lihat
                    </a>

                    @if ($materi['boleh_edit'])
                        <a class="ad-titik__item" role="menuitem" href="{{ $materi['tautan_edit'] }}">
                            <x-admin.ikon nama="pena" />

                            Edit
                        </a>
                    @endif

                    <button type="button" class="ad-titik__item ad-titik__item--bahaya" role="menuitem"
                        data-hapus-buka
                        data-hapus-judul="Hapus Materi?"
                        data-hapus-meta="{{ $kategori['nama'] }} &middot; {{ $materi['jumlah_bab'] }} Bab &middot; oleh {{ $materi['pembuat']['nama'] }}"
                        data-hapus-aksi="{{ $materi['tautan_hapus'] }}">
                        <x-admin.ikon nama="sampah" />

                        Hapus
                    </button>
                </div>
            </details>
        </div>

        {{--
            Status + aksi. Tombolnya terdorong ke bawah kartu lewat
            margin-top: auto pada .ad-kartu-daftar__kaki, jadi semua kartu
            dalam satu baris tetap sejajar walaupun judulnya beda panjang.

Edit berdiri sebagai tombol, bukan hanya isi menu: materinya
            milik admin yang sedang login adalah hal yang paling sering
            diubah setelah dibaca, dan mencarinya di dalam menu tiga titik
            menambah satu langkah tanpa alasan. Syaratnya tetap boleh_edit
            yang sama seperti di dalam menu.
        --}}
        <div class="ad-kartu-daftar__kaki">
            <x-admin.lencana-materi :status="$materi['status']" :label="$materi['status_label']" />

            <div class="ad-kartu-daftar__aksi">
                <a href="{{ $materi['tautan_detail'] }}" class="ad-tombol ad-tombol--halus ad-tombol--kecil">
                    <x-admin.ikon nama="mata" ukuran="w-3.5 h-3.5" />

                    Lihat
                </a>

                @if ($materi['boleh_edit'])
                    <a href="{{ $materi['tautan_edit'] }}" class="ad-tombol ad-tombol--halus ad-tombol--kecil">
                        <x-admin.ikon nama="pena" ukuran="w-3.5 h-3.5" />

                        Edit
                    </a>
                @endif
            </div>
        </div>
    </div>
</article>
