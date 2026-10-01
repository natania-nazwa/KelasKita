@props([
    // Satu baris dari App\Support\DaftarKonten::petikan().
    'kartu',
])

{{--
    Satu baris di daftar "Konten Pembelajaran".

    Bentuknya baris, bukan kartu. Alasannya isi yang harus dipindai admin di
    setiap entri cuma empat: judul, jenis dan jumlahnya, kelas, dan status.
    Empat hal itu jauh lebih cepat dibaca dalam satu kolom yang sejajar
    daripada di dalam kartu yang harus dibaca satu per satu.

    Tiga bagian, dari kiri ke kanan:

      1. Thumbnail 52px, atau ikon jenis konten kalau tidak punya thumbnail.
      2. Judul di atas, metadata di bawahnya ("Materi • 3 bab • Pemrograman").
      3. Lencana kelas, tanggal, lencana status, lalu menu aksi.

    Aksi tidak lagi berupa lima tombol yang selalu terbuka. Semuanya masuk ke
    satu menu di kanan, karena di layar laptop lima tombol per baris membuat
    daftar jadi penuh tombol dan judulnya yang penting jadi tenggelam. Isi
    menu Depends status: draft mendapat "Publish", konten yang sudah terbit
    mendapat "Batalkan Publikasi" — jadi "Publish" tidak pernah muncul pada
    konten yang sudah tayang.

    Dua aksi berat (Publish dan Hapus) tetap lewat dialog konfirmasi lebih
    dulu, keduanya mewarisi mekanisme yang sudah ada:
    resources/js/konten-publish.js untuk terbit, resources/js/admin.js untuk
    hapus.

    Tanpa JavaScript: menu aksi tidak bisa dibuka, tapi tautan "Lihat" pada
    judul dan seluruh isi baris tetap terbaca dan bisa dipakai. Isi menu
    memang disembunyikan lewat display, bukan opacity, jadi tidak pernah
    bisa difokus keyboard sebelum benar-benar dibuka.
--}}

@php
    $kategori = $kartu['kategori'];
    $materi = $kartu['jenis'] === 'materi';
    $nama = $materi ? 'Materi' : 'Quiz';
    $terbit = $kartu['status'] === \App\Models\Materi::STATUS_PUBLISHED;
    $kelas = $kartu['kelas'] ?? null;

    /*
     * Ikon thumbnail. Kalau materi, dokumen; kalau quiz, papan periksa.
     * Bedanya disengaja supaya admin bisa membedakan jenis konten dari
     * kotak gambarnya saja, tanpa membaca teksnya.
     */
    $ikon = $materi ? 'dokumen' : 'buku-centang';
@endphp

<article class="ad-konten-baris">
    {{-- 1. Thumbnail --}}
    <span class="ad-konten-baris__gambar" aria-hidden="true">
        @if (filled($kartu['thumbnail'] ?? null))
            <img src="{{ $kartu['thumbnail'] }}" alt="" loading="lazy" class="ad-konten-baris__foto">
        @else
            <x-admin.ikon :nama="$ikon" ukuran="w-5 h-5" :tebal="1.7" />
        @endif
    </span>

    {{-- 2. Judul + metadata --}}
    <div class="ad-konten-baris__isi">
        <h2 class="ad-konten-baris__judul">
            <a href="{{ $kartu['tautan']['lihat'] }}">{{ $kartu['judul'] }}</a>
        </h2>

        <div class="ad-konten-baris__meta">
            <span class="ad-konten-baris__meta-item">
                <x-admin.ikon :nama="$materi ? 'buku' : 'soal'" ukuran="w-3.5 h-3.5" />

                {{ $nama }}
            </span>

            <span class="ad-konten-baris__meta-item">{{ $kartu['jumlah'] }} {{ $kartu['satuan'] }}</span>

            @if ((int) $kartu['menit'] > 0)
                <span class="ad-konten-baris__meta-item">
                    <x-admin.ikon nama="jam" ukuran="w-3.5 h-3.5" />

                    {{ $kartu['menit'] }} menit
                </span>
            @endif

            <span class="ad-konten-baris__meta-item">
                <x-admin.ikon nama="dokumen" ukuran="w-3.5 h-3.5" />

                {{ $kategori['nama'] }}
            </span>
        </div>
    </div>

    {{-- 3. Kelas, tanggal, status --}}
    <div class="ad-konten-baris__kanan">
        @if (filled($kelas))
            <span class="ad-konten-lencana">{{ $kelas }}</span>
        @else
            {{-- Konten yang belum ditentukkan kelasnya tidak diberi lencana
                 kosong: kotak kosong di kanan baris hanya menambah ruang
                 kosong tanpa memberi informasi apa pun. --}}
            <span class="sr-only">Kelas belum ditentukan</span>
        @endif

        <span class="ad-konten-baris__tanggal">
            <time datetime="{{ $kartu['terbit']?->toDateString() }}">{{ $kartu['tanggal_label'] }}</time>
        </span>

        <span class="ad-konten-status ad-konten-status--{{ $terbit ? 'terbit' : 'draft' }}">
            <span class="ad-konten-status__titik" aria-hidden="true"></span>

            {{ $kartu['status_ringkas'] }}
        </span>
    </div>

    {{-- Menu aksi --}}
    <div class="ad-konten-menu" data-konten-menu>
        <button type="button" class="ad-konten-menu__tombol" data-konten-menu-tombol
            aria-expanded="false" aria-haspopup="menu"
            aria-label="Aksi untuk {{ $nama }} {{ $kartu['judul'] }}">
            <x-admin.ikon nama="titik-tiga" ukuran="w-4 h-4" :tebal="2.2" />
        </button>

        <div class="ad-konten-menu__isi" data-konten-menu-isi role="menu"
            aria-label="Aksi untuk {{ $nama }} {{ $kartu['judul'] }}">

            <a href="{{ $kartu['tautan']['lihat'] }}" class="ad-konten-menu__aksi" role="menuitem">
                <x-admin.ikon nama="mata" ukuran="w-4 h-4" />

                Lihat
            </a>

            <a href="{{ $kartu['tautan']['edit'] }}" class="ad-konten-menu__aksi" role="menuitem">
                <x-admin.ikon nama="pena" ukuran="w-4 h-4" />

                Edit
            </a>

            {{-- Duplikat dan Publish/Hapus lewat form: keduanya mengubah
                 database, jadi bukan tautan. --}}
            <form method="POST" action="{{ $kartu['tautan']['duplikat'] }}">
                @csrf

                <button type="submit" class="ad-konten-menu__aksi" role="menuitem">
                    <x-admin.ikon nama="salin" ukuran="w-4 h-4" />

                    Duplikat
                </button>
            </form>

            {{--
                Satu tombol untuk dua arah, jadi yang ditampilkan tergantung
                status: draft mendapat "Publish", yang sudah terbit mendapat
                "Batalkan Publikasi". "Publish" karena itu tidak pernah muncul
                pada konten yang sudah tayang.

                Atribut data-konten-terbit-* dibaca
                resources/js/konten-publish.js untuk mengisi judul, pesan, dan
                nama tombol dialog — jadi kalimatnya berbeda antara
                menerbitkan dan menarik kembali.
            --}}
            <button type="button" class="ad-konten-menu__aksi" data-konten-publish="publish"
                data-konten-terbit-buka
                data-konten-aksi="{{ $kartu['tautan']['publish'] }}"
                data-konten-nama="{{ $nama }} &quot;{{ $kartu['judul'] }}&quot;"
                data-konten-terbit-judul="{{ $terbit ? 'Batalkan publikasi?' : 'Publish konten?' }}"
                data-konten-terbit-pesan="{{ $terbit
                    ? $nama . ' ini akan ditarik dari halaman pengguna dan kembali menjadi draft.'
                    : 'Konten ini akan langsung tersedia untuk pengguna dan notifikasi akan dikirim.' }}"
                data-konten-terbit-tombol="{{ $terbit ? 'Batalkan Publikasi' : 'Publish Sekarang' }}"
                data-konten-terbit-bahaya="{{ $terbit ? '1' : '0' }}"
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
</article>
