@props([
    // Satu baris dari App\Support\DaftarQuizAdmin::petikan().
    'quiz',
])

@php
    /*
     * Kartu quiz di daftar halaman "Quiz" (admin).
     *
     * Kerangkanya sama persis dengan kartu materi
     * (resources/views/components/admin/materi-kartu.blade.php) dan memakai
     * kelas CSS yang sama: gambar di atas, isi di bawah, pembatas aksen, lalu
     * baris informasi. Bukan kartu horizontal, supaya satu baris kartu tidak
     * hanya berisi empat baris teks yang saling salting.
     *
     * Yang berbeda hanya isi bodynya. Kartu materi memakai baris meta untuk
     * "kategori · jumlah bab"; di sini baris itu menjadi "kategori · jumlah
     * soal", karena bagi admin yang paling menentukan adalah berapa soal yang
     * harus diperiksa dan berapa lama quiz itu dikerjakan.
     *
     * Mode publik atau kode sengaja tidak jadi baris tersendiri: statusnya
     * sudah terbaca dari lencana di kaki kartu, dan baris tambahan hanya
     * membuat kartu lebih tinggi tanpa menambah informasi baru.
     *
     * Bentuk array per kartu datang dari App\Support\DaftarQuizAdmin, jadi
     * komponen ini tidak terikat Eloquent:
     *   judul, thumbnail, tingkat_kesulitan, durasi_label, jumlah_soal,
     *   pakai_kode, tanggal_label, tanggal_jam, status, status_label,
     *   boleh_edit, kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat => [nama, inisial, warna, warna_gelap],
     *   tautan_detail, tautan_edit, tautan_hapus
     *
     * Kartu tidak bisa diklik seluruhnya. Satu-satunya jalan ke quiz adalah
     * tombol Lihat, supaya menu tiga titik tidak ikut jadi bagian dari area
     * klik yang sama.
     *
     * Modifier --kuis dipakai di kartu ini saja: warna kategori merambat ke
     * isi kartu (cucian di atas badan, titik di baris meta, tombol Lihat,
     * menu tiga titik), nama pembuat memakai warna avatarnya sendiri, dan
     * lencana kesulitan dibedakan warnanya. Semuanya warna, bayangan, dan
     * dekorasi — tidak ada yang menambah, mengurangi, atau memindahkan
     * elemen, jadi bentuk kartu di halaman ini sama persis dengan kartunya
     * di halaman Materi.
     */

    $kategori = $quiz['kategori'];
    $pembuat = $quiz['pembuat'];

    /*
     * Tingkat kesulitan dipakai sebagai kelas modifier, supaya lencana di
     * pojok gambar bisa dibedakan: hijau untuk Mudah, oranye untuk Sedang,
     * merah untuk Sulit. Nilai dari model sudah berupa "Mudah", "Sedang",
     * atau "Sulit", jadi hanya huruf kecilnya yang dipakai di kelas.
     *
     * Nilai lain tetap dapat lencana — kelas dasarnya tidak bergantung pada
     * modifier ini, jadi data yang tidak dikenal tidak membuat kartu rusak.
     */
    $kelasKesulitan = 'ad-kartu-daftar__kesulitan--'.strtolower((string) $quiz['tingkat_kesulitan']);
@endphp

<article class="ad-kartu-daftar ad-kartu-daftar--kuis"
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }}; --p: {{ $pembuat['warna'] }}; --p-gelap: {{ $pembuat['warna_gelap'] }};">

    {{--
        A. Blok gambar. Selalu berisi sesuatu: kalau quiznya tidak punya
        thumbnail, gradasi warna kategori + ikon mapel menggantikannya —
        persis seperti kartu quiz milik pengguna, bukan kotak kosong.
    --}}
    <div class="ad-kartu-daftar__gambar">
        @if (filled($quiz['thumbnail']))
            <img class="ad-kartu-daftar__foto" src="{{ $quiz['thumbnail'] }}"
                alt="Thumbnail quiz {{ $quiz['judul'] }}" loading="lazy">
        @else
            <span class="ad-kartu-daftar__ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{--
            Tingkat kesulitan melayang di pojok kiri. Di kartu admin ini satu
            -satunya tempat informasi tentang tingkat kesulitan, dan tidak
            beradu dengan kategori yang sudah tampil di baris meta.

            Warnanya ikut membawa warna tingkatnya lewat $kelasKesulitan,
            supaya lencana ini tidak selalu abu-abu.
        --}}
        @if (filled($quiz['tingkat_kesulitan']))
            <span @class(['ad-kartu-daftar__kesulitan', 'capitalize', $kelasKesulitan])>{{ $quiz['tingkat_kesulitan'] }}</span>
        @endif
    </div>

    {{-- B. Isi kartu. --}}
    <div class="ad-kartu-daftar__badan">
        <span class="ad-kartu-daftar__aksen" aria-hidden="true"></span>

        <h3 class="ad-kartu-daftar__judul">{{ $quiz['judul'] }}</h3>

        <p class="ad-kartu-daftar__pembuat">
            <span>Dibuat oleh:</span>

            <span class="ad-kartu-daftar__truncate">{{ $pembuat['nama'] }}</span>
        </p>

        {{--
            Kategori dan jumlah soal. Titik warna kategorinya datang dari
            ::before .ad-kartu-daftar__meta, jadi teksnya sendiri tetap
            persis seperti sebelumnya.
        --}}
        <p class="ad-kartu-daftar__meta">
            {{ $kategori['nama'] }} &middot; {{ $quiz['jumlah_soal'] }} Soal
        </p>

        {{--
            Tanggal dan menu tiga titik berbagi satu baris: tanggal di kiri,
            menu menempel di kanan. Baris pembungkus ini hanya flex; jarak ke
            atas tetap datang dari margin-top .ad-kartu-daftar__tanggal
            supaya baris ini punya jarak yang sama dengan "Dibuat oleh" dan
            baris meta di atasnya.
        --}}
        <div class="ad-kartu-daftar__tanggal-baris">
            <p class="ad-kartu-daftar__tanggal">
                {{ $quiz['tanggal_label'] }}
                @if (filled($quiz['tanggal_jam']))
                    &middot; {{ $quiz['tanggal_jam'] }}
                @endif
            </p>

            {{--
                Menu tiga titik memakai <details>, bukan tombol + <div>:
                buka/tutup-nya dari browser, jadi tetap jalan tanpa
                JavaScript.

                Isinya hanya aksi yang memang ada di aplikasi: Lihat,
                Edit, dan Hapus. Tidak ada "Arsipkan" karena tidak ada
                status arsip di database.

                Edit hanya untuk quiz yang dibuat admin yang sedang login
                (boleh_edit). Quiz buatan pengguna lain tetap punya Lihat
                dan Hapus; soalnya bukan hak admin untuk diubah, dan
                Admin\QuizKelolaController akan menolak 403 kalau URL-nya
                diketik manual.
            --}}
            <details class="ad-titik" data-tutup-luar>
                <summary class="ad-titik__tombol" aria-label="Opsi lain untuk {{ $quiz['judul'] }}">&vellip;</summary>

                <div class="ad-titik__menu" role="menu">
                    <a class="ad-titik__item" role="menuitem" href="{{ $quiz['tautan_detail'] }}">
                        <x-admin.ikon nama="mata" />

                        Lihat
                    </a>

                    @if ($quiz['boleh_edit'])
                        <a class="ad-titik__item" role="menuitem" href="{{ $quiz['tautan_edit'] }}">
                            <x-admin.ikon nama="pena" />

                            Edit
                        </a>
                    @endif

                    <button type="button" class="ad-titik__item ad-titik__item--bahaya" role="menuitem"
                        data-hapus-buka
                        data-hapus-judul="Hapus Quiz?"
                        data-hapus-meta="{{ $kategori['nama'] }} &middot; {{ $quiz['jumlah_soal'] }} soal &middot; {{ $quiz['durasi_label'] }} &middot; oleh {{ $quiz['pembuat']['nama'] }}"
                        data-hapus-aksi="{{ $quiz['tautan_hapus'] }}">
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

            Edit berdiri sebagai tombol, bukan hanya isi menu: quiz milik
            admin yang sedang login adalah hal yang paling sering diubah
            setelah dibaca, dan mencarinya di dalam menu tiga titik menambah
            satu langkah tanpa alasan. Syaratnya tetap boleh_edit yang sama
            seperti di dalam menu.
        --}}
        <div class="ad-kartu-daftar__kaki">
            <x-admin.lencana-materi :status="$quiz['status']" :label="$quiz['status_label']" />

            <div class="ad-kartu-daftar__aksi">
                <a href="{{ $quiz['tautan_detail'] }}" class="ad-tombol ad-tombol--halus ad-tombol--kecil">
                    <x-admin.ikon nama="mata" ukuran="w-3.5 h-3.5" />

                    Lihat
                </a>

                @if ($quiz['boleh_edit'])
                    <a href="{{ $quiz['tautan_edit'] }}" class="ad-tombol ad-tombol--halus ad-tombol--kecil">
                        <x-admin.ikon nama="pena" ukuran="w-3.5 h-3.5" />

                        Edit
                    </a>
                @endif
            </div>
        </div>
    </div>
</article>
