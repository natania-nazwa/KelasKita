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
            'pembuat' => $pembuatAktif,
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
    <section class="ad-seksi ad-hero ad-hero--materi">
        <div class="ad-hero-materi__susun">
            <div class="ad-hero-materi__teks">
                <h1 class="ad-hero-materi__judul">Materi</h1>

                <p class="ad-hero-materi__sub">
                    Kelola materi pembelajaran yang telah dipublikasikan untuk pengguna KelasKita.
                </p>
            </div>

            {{--
                Ilustrasi buku. Murni dekoratif, jadi alt-nya kosong: tidak ada
                informasi di dalamnya yang perlu dibaca pembaca layar, dan teks
                yang penting sudah ada di sebelah kiri.
            --}}
            <img class="ad-hero-materi__gambar" src="{{ asset('images/buku.png') }}"
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

         Satu form GET untuk ketiga filter sekaligus, jadi mengganti filter
         tidak mematikan filter lain yang sedang aktif. Tidak ada tombol
         buka/tutup: kategorinya cuma tiga, dan menyembunyikannya di balik
         popover hanya menambah satu klik untuk sesuatu yang selalu dipakai.

         Kolom "Cari materi" tidak ada di sini. Pencarian tetap berfungsi
         lewat kotak pencarian di topbar, yang form-nya sudah mengarah ke
         halaman ini dengan field "q" — jadi tidak ada pencarian yang
         hilang, hanya satu kolom yang tidak lagi terduplikasi. Kata kunci
         yang sedang aktif ikut dibawa sebagai query string supaya saringan
         di bawah tidak hilang saat admin menyaring daftar.

         Barisnya flex-wrap, jadi saat layar tidak cukup lebar isinya turun
         sendiri — itu perilaku responsif, bukan dua baris yang sengaja
         dirancang begitu.

         Label select disembunyikan karena teks di dalamnya sudah menyebut apa
         yang disaring ("Semua kategori", "Semua pembuat"), jadi tidak ada
         yang perlu dibaca dua kali.
    ====================== --}}
    <div class="ad-seksi ad-kartu ad-alat-kotak">
        <form method="GET" action="{{ route('admin.materi') }}">
            <div class="ad-alat-baris">
                {{-- Pencarian yang sudah aktif ikut dibawa, supaya tidak hilang
                     dari URL saat admin menyaring daftar. --}}
                @if ($kataKunci !== '')
                    <input type="hidden" name="q" value="{{ $kataKunci }}">
                @endif

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

                <div class="ad-alat-baris__field">
                    <label class="sr-only" for="saring-pembuat">Saring menurut pembuat</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-pembuat" name="pembuat" class="ad-pilih">
                            <option value="">Semua pembuat</option>

                            @foreach ($daftarPembuat as $pembuat)
                                <option value="{{ $pembuat['id'] }}" @selected($pembuatAktif === (string) $pembuat['id'])>
                                    {{ $pembuat['nama'] }}
                                </option>
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
                        Selalu ikut dirender supaya posisinya tidak bergeser
                        saat filter dipakai dan dilepas. Tanpa saringan yang
                        aktif tautannya dimatikan: tidak ada yang perlu
                        dihapus, jadi tidak boleh terlihat bisa diklik.
                    --}}
                    <a href="{{ route('admin.materi') }}" @class([
                        'ad-tombol',
                        'ad-tombol--garis',
                        'pointer-events-none opacity-40' => ! $adaFilter && $kataKunci === '',
                    ]) @if (! $adaFilter && $kataKunci === '') aria-disabled="true" tabindex="-1" @endif>
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
    <div class="ad-seksi ad-materi__daftar">
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

        Satu dialog untuk semua kartu, diisi dari data-* tombol yang ditekan
        oleh admin.js, jadi daftar panjang tetap hanya punya satu kotak
        konfirmasi.

        Form-nya memakai @method('DELETE') ke admin.materi.destroy, sama
        seperti form hapus milik pengguna. Tidak ada form yang langsung
        terkirim: materinya baru dihapus setelah tombol "Hapus Materi" ditekan.
    ====================== --}}
    <div class="ad-dialog" data-dialog-hapus role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="dialog-hapus-judul">
        <div class="ad-dialog__kartu">
            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="dialog-hapus-judul" data-hapus-judul>Hapus Materi?</h2>

                    <p class="ad-teks-2 mt-0.5 !text-xs" data-hapus-meta></p>
                </div>

                <button type="button" class="ad-dialog__tutup" data-hapus-tutup aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            <div class="ad-dialog__badan">
                <p class="text-sm leading-relaxed text-dark/70">
                    Materi ini akan dihapus dan tidak lagi tersedia untuk pengguna.
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-hapus-tutup>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-hapus-materi">
                    <x-admin.ikon nama="sampah" />

                    Hapus Materi
                </button>
            </footer>

            {{--
                Form tidak terlihat, tapi tetap ada di DOM supaya tombolnya
                bisa memakai atribut form=.
            --}}
            <form method="POST" action="" id="form-hapus-materi" data-hapus-form hidden>
                @csrf

                @method('DELETE')
            </form>
        </div>
    </div>
@endsection
