@extends('layouts.admin')

@section('title', 'Materi | KelasKita')

@section('content')
    {{--
        Halaman "Materi": daftar materi yang sudah dipublikasikan.

        Batas halaman ini disengaja dan tidak boleh kabur: yang tampil hanya
        materi berstatus "published". Materi yang masih menunggu keputusan
        ditinjau di menu Verifikasi, dan yang ditolak atau masih draft
        dikelola pemiliknya di "Karya Saya". Karena itu halaman ini tidak punya
        tombol Setujui/Tolak dan tidak punya tab status — dengan daftar yang
        sudah published-only, tab seperti itu hanya mengulang daftar yang sama.

        Isinya: cari, saring, baca detail, ubah, hapus. Daftar ini satu kolom
        selebar penuh: kartu materi tinggi hanya sebentar, jadi panel preview di
        sebelah kanan lebih banyak mengambil ruang daripada yang terpakai.

        Tombol "Lihat Materi" membuka halaman detail yang memakai komponen
        tampilan milik pengguna, jadi yang dibaca admin persis sama dengan
        yang dibaca user.
    --}}

    @php
        /*
         * Berapa saringan yang sedang aktif selain kata kunci. "urut" tidak
         * ikut dihitung kalau masih bawaan, karena mengurutkan ulang daftar
         * bukan hal yang perlu disorot sebagai filter aktif.
         */
        $jumlahFilter = count(array_filter([
            'kategori' => $kategoriAktif,
            'urut' => $urutAktif === 'terbaru' ? null : $urutAktif,
        ], fn ($nilai) => filled($nilai)));

        $adaFilter = $jumlahFilter > 0;
    @endphp

    {{-- =====================
         HERO BANNER

         Dua kolom: teks di kiri, gambar buku di kanan. Ikon dan kutipan
         tidak dipakai di sini — visual dengan logo dan slogan itu milik hero
         Dashboard, sedangkan di halaman ini yang dibutuhkan cuma penanda
         materi.

         Memakai .ad-seksi juga supaya jarak ke kartu filter di bawahnya
         datang dari aturan .ad-seksi + .ad-seksi.
    ====================== --}}
    <section class="ad-seksi ad-hero ad-hero--konten">
        <div class="ad-hero-konten__susun">
            <div class="ad-hero-konten__teks">
                <h1 class="ad-hero-konten__judul">Materi</h1>

                <p class="ad-hero-konten__sub">
                    Kelola materi pembelajaran yang telah dipublikasikan untuk pengguna KelasKita.
                </p>
            </div>

            {{--
                Ilustrasi buku. Murni dekoratif, jadi alt-nya kosong: tidak ada
                informasi di dalamnya yang perlu dibaca pembaca layar, dan teks
                yang penting sudah ada di sebelah kiri.
            --}}
            <img class="ad-hero-konten__gambar" src="{{ asset('images/buku.png') }}"
                alt="" aria-hidden="true" loading="lazy" decoding="async">
        </div>
    </section>

    {{-- =====================
         PESAN
    ====================== --}}
    @if (session('sukses'))
        <div class="ad-seksi ad-alert ad-alert--sukses" role="status">
            <span class="ad-alert__ikon" aria-hidden="true">
                <x-admin.ikon nama="tanda-centang" ukuran="w-3.5 h-3.5" :tebal="2.6" />
            </span>

            <p class="min-w-0 font-medium">{{ session('sukses') }}</p>
        </div>
    @endif

    {{-- =====================
         FILTER

         Satu form GET untuk pencarian dan kedua filter sekaligus, jadi
         mengganti filter tidak mematikan filter lain yang sedang aktif, dan
         sebaliknya. Tidak ada tombol buka/tutup: isinya cuma dua, dan
         menyembunyikannya di balik popover hanya menambah satu klik untuk
         sesuatu yang selalu dipakai.

         Kolom "Cari materi" ada di sini, di dalam form filter. Kolom cari
         di topbar sudah dihapus (lihat layouts/admin), jadi kolom inilah
         satu-satunya tempat mencari — sekaligus alasannya harus terlihat
         jelas di halaman tempat admin sedang menyaring, bukan disembunyikan
         di layar atas.

         Karena kolomnya ikut di dalam form ini, tidak ada lagi input tersembunyi
         untuk membawa kata kunci: input yang terlihat itulah yang mengirim
         "q". Enter di dalam kolom ini mengirim form, jadi tidak butuh tombol
         cari terpisah.

         Barisnya flex-wrap, jadi saat layar tidak cukup lebar isinya turun
         sendiri — itu perilaku responsif, bukan dua baris yang sengaja
         dirancang begitu.

         Label select disembunyikan karena teks di dalamnya sudah menyebut apa
         yang disaring ("Semua kategori"), jadi tidak ada yang perlu dibaca
         dua kali. Label kolom cari juga disembunyikan, tapi hanya karena
         teks placeholder-nya sudah menyebut apa yang dicari.

         Tidak ada filter pembuat di sini. Dropdown "Semua pembuat" pernah
         ada, dan dropdown itu bisa jadi tidak punya satu pun pilihan: materi
         yang sudah tayang tidak selalu punya dibuat_oleh yang terisi, jadi
         daftar pembuat yang diambil dari materi yang punya pembuat saja bisa
         kosong — filter yang kelihatan ada tapi tidak bisa dipakai. Nama
         pembuat tetap bisa dicari lewat kolom cari, jadi tidak ada yang
         hilang. Kartu di daftar tetap menampilkan siapa pembuatnya.
    ====================== --}}
    <div class="ad-seksi ad-kartu ad-alat-kotak">
        <form method="GET" action="{{ route('admin.materi') }}">
            <div class="ad-alat-baris">
                <div class="ad-cari">
                    <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                    <label class="sr-only" for="cari-materi">Cari materi</label>

                    <input id="cari-materi" name="q" type="search" value="{{ $kataKunci }}"
                        placeholder="Cari materi..." autocomplete="off">
                </div>

                <div class="ad-alat-baris__field">
                    <label class="sr-only" for="saring-kategori">Saring menurut kategori</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-kategori" name="kategori" class="ad-pilih">
                            <option value="">Semua kategori ({{ $totalMateri }})</option>

                            @foreach ($daftarKategori as $kategori)
                                <option value="{{ $kategori['slug'] }}" @selected($kategoriAktif === $kategori['slug'])>
                                    {{ $kategori['nama'] }} ({{ $kategori['jumlah'] }})
                                </option>
                            @endforeach
                        </select>

                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                        </svg>
                    </div>
                </div>

                <div class="ad-alat-baris__field">
                    <label class="sr-only" for="saring-urut">Urutkan daftar</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-urut" name="urut" class="ad-pilih">
                            @foreach ($pilihanUrut as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($urutAktif === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                        </svg>
                    </div>
                </div>

                <div class="ad-alat-baris__aksi">
                    <button type="submit" class="ad-tombol ad-tombol--utama">Terapkan</button>

                    {{--
                        Selalu hidup, tidak pernah dimatikan.

                        Dulu tautan ini diberi pointer-events-none dan
                        aria-disabled ketika tidak ada saringan yang aktif,
                        dengan alasan "tidak ada yang perlu dihapus".
                        Akibatnya tombol yang tetap kelihatan seperti tombol
                        justru tidak bereaksi apa pun saat diklik, dan admin
                        menyimpulkan tombolnya rusak.

                        Menghemat satu klik itu tidak balancing dengan
                        tombol yang terlihat bisa diklik tapi mati: tautan
                        ini tetap menuju URL polos, jadi diklik saat daftar
                        sudah bersih hanya memuat ulang daftar yang sama.

                        Karena itu tidak ada lagi keadaan mati di sini. Yang
                        dihapus adalah kata kunci, kategori, urutan, dan
                        pembuat sekaligus, karena tautannya menuju route
                        tanpa query string sama sekali.
                    --}}
                    <a href="{{ route('admin.materi') }}" class="ad-tombol ad-tombol--garis">
                        <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />

                        Hapus filter
                    </a>
                </div>
            </div>
        </form>
    </div>
    {{-- =====================
         DAFTAR MATERI
    ====================== --}}
    <div class="ad-seksi ad-daftar-kartu">
        @if ($daftar === [])
            @if ($kataKunci !== '' || $adaFilter)
                <x-admin.kosong ikon="cari" judul="Materi tidak ditemukan"
                    teks="Coba gunakan kata kunci yang berbeda." />
            @else
                <x-admin.kosong ikon="buku" judul="Belum ada materi"
                    teks="Materi yang telah disetujui akan muncul di sini." />
            @endif
        @else
            @foreach ($daftar as $materi)
                <x-admin.materi-kartu :materi="$materi" />
            @endforeach
        @endif
    </div>

    {{-- =====================
         PAGINASI

         Cuma tombol halaman, di tengah dan tanpa kartu putih di belakang.
         Jumlah data ("Menampilkan 1-8 dari 10 data") sengaja dihapus: dengan
         empat kolom kartu, posisi kartu sudah memberitahu sedang berada di
         halaman berapa.
    ====================== --}}
    @if ($daftar !== [])
        <div class="ad-seksi ad-paginasi">
            {{ $paginasi->links() }}
        </div>
    @endif

    {{--
        =====================
             DIALOG HAPUS MATERI

        Satu dialog untuk semua kartu, dipakai dari komponen bersama
        x-admin.dialog-hapus, dan isinya diisi dari data-* tombol yang
        ditekan oleh admin.js, jadi daftar panjang tetap hanya punya satu
        kotak konfirmasi.

        Form-nya memakai @method('DELETE') ke admin.materi.destroy, sama
        seperti form hapus milik pengguna. Tidak ada form yang langsung
        terkirim: materinya baru dihapus setelah tombol "Hapus Materi"
        ditekan.
    ====================== --}}
    <x-admin.dialog-hapus
        judul="Hapus Materi?"
        pesan="Materi ini akan dihapus dan tidak lagi tersedia untuk pengguna. Tindakan ini tidak dapat dibatalkan."
        tombol="Hapus Materi"
        id-dialog="dialog-hapus-judul"
        id-form="form-hapus-materi"
        kelas-pesan="text-sm leading-relaxed text-dark/70" />
@endsection
