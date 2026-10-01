@extends('layouts.admin')

@section('title', 'Quiz | KelasKita')

@section('content')
    {{--
        Halaman "Quiz": daftar quiz yang sudah dipublikasikan.

        Sengaja ditiru seluruhnya dari halaman "Materi": hero yang sama, kartu
        filter yang sama, grid kartu yang sama, paginasi yang sama, dan dialog
        hapus yang sama. Yang berbeda hanya isi kartu dan gambarnya. Jadi
        berpindah antara Materi dan Quiz di sidebar admin tidak terasa seperti
        berpindah aplikasi, dan siapa pun yang sudah paham satu halaman tidak
        perlu belajar ulang halaman yang lain.

        Batas halaman ini juga sama dan disengaja: yang tampil hanya quiz
        berstatus "published". Quiz yang masih menunggu keputusan ditinjau di
        menu Verifikasi — halaman yang sama untuk materi dan quiz — dan yang
        ditolak atau masih draft dikelola pemiliknya di "Karya Saya". Karena
        itu halaman ini tidak punya tombol Setujui/Tolak dan tidak punya tab
        status: dengan daftar yang sudah published-only, tab seperti itu hanya
        mengulang daftar yang sama.

        Isinya: cari, saring, baca detail, ubah, hapus. Daftar ini satu kolom
        selebar penuh: kartu quiz tinggi hanya sebentar, jadi panel preview di
        sebelah kanan lebih banyak mengambil ruang daripada yang terpakai.

        Tombol "Lihat Quiz" membuka halaman detail yang memakai komponen
        tampilan milik pengguna, jadi yang dibaca admin persis sama dengan
        yang dibaca user.

        Yang membedakan halaman ini dari halaman Materi hanya warnanya:
        kelas --kuis pada hero, kartu filter, dan kartu daftar. Semua aturan
        CSS untuk kelas itu hanya berlaku di halaman ini, jadi Materi tetap
        seperti sebelumnya. Kerangka, urutan, dan fungsinya tidak berubah —
        kelas --kuis tidak menambah, mengurangi, atau memindahkan elemen apa
        pun, hanya warna, bayangan, dan dekorasi yang ditumpuk di atasnya.
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

         Dua kolom: teks di kiri, gambar di kanan. Ikon dan kutipan tidak
         dipakai di sini — visual dengan logo dan slogan itu milik hero
         Dashboard, sedangkan di halaman ini yang dibutuhkan cuma penanda
         quiz.

         Memakai .ad-seksi juga supaya jarak ke kartu filter di bawahnya
         datang dari aturan .ad-seksi + .ad-seksi.

         Modifier --kuis hanya untuk halaman ini: pola titik dua warna,
         cincin cahaya di belakang gambar, dan garis aksen di bawah judul.
         Semuanya lapisan ::before/::after yang absolute, jadi hero tetap
         setinggi dan selebar yang sebelumnya.
    ====================== --}}
    <section class="ad-seksi ad-hero ad-hero--konten ad-hero--kuis">
        <div class="ad-hero-konten__susun">
            <div class="ad-hero-konten__teks">
                <h1 class="ad-hero-konten__judul">Quiz</h1>

                <p class="ad-hero-konten__sub">
                    Kelola quiz yang telah dipublikasikan untuk pengguna KelasKita.
                </p>
            </div>

            {{--
                Ilustrasi siswa di laptop. Murni dekoratif, jadi alt-nya kosong:
                tidak ada informasi di dalamnya yang perlu dibaca pembaca layar,
                dan teks yang penting sudah ada di sebelah kiri.

                Modifier --penuh karena gambarnya lebih lebar dari kotak hero
                (828x552 di kotak bujur sangkar), bukan bujur sangkar seperti
                buku.png di halaman Materi. Tanpa itu gambarnya hanya mengisi
                dua pertiga kotak dan terlihat jauh lebih kecil dibanding
                halaman sebelah — meski hero-nya sama persis.
            --}}
            <img class="ad-hero-konten__gambar ad-hero-konten__gambar--penuh" src="{{ asset('images/cover.png') }}"
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

         Kolom "Cari quiz" ada di sini, bukan cuma di topbar. Identik dengan
         halaman Materi: pencarian di halaman ini mencari judul, kategori, dan
         pembuat, persis seperti yang dilakukan halaman Materi.

         Karena kolomnya ikut di dalam form ini, tidak ada lagi input tersembunyi
         untuk membawa kata kunci: input yang terlihat itulah yang mengirim
         "q", dan Enter di dalamnya mengirim form.

         Barisnya flex-wrap, jadi saat layar tidak cukup lebar isinya turun
         sendiri — itu perilaku responsif, bukan dua baris yang sengaja
         dirancang begitu.

         Label select disembunyikan karena teks di dalamnya sudah menyebut apa
         yang disaring ("Semua kategori"), jadi tidak ada yang perlu dibaca
         dua kali.

         Tidak ada filter pembuat di sini, sama seperti halaman Materi, dan
         alasannya sama: nama pembuat sudah bisa dicari lewat kolom cari.
         Kartu di daftar tetap menampilkan siapa pembuatnya.

         Modifier --kuis hanya untuk halaman ini: kolom cari dan kedua
         select diberi cucian ungu muda, tombol "Terapkan" diberi gradasi,
         dan "Hapus filter" memerah saat dilewati. Warnanya saja — isian,
         label, dan urutan tombolnya tetap sama.
    ====================== --}}
    <div class="ad-seksi ad-kartu ad-alat-kotak ad-alat-kotak--kuis">
        <form method="GET" action="{{ route('admin.quiz') }}">
            <div class="ad-alat-baris">
                <div class="ad-cari">
                    <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                    <label class="sr-only" for="cari-quiz">Cari quiz</label>

                    <input id="cari-quiz" name="q" type="search" value="{{ $kataKunci }}"
                        placeholder="Cari quiz..." autocomplete="off">
                </div>

                <div class="ad-alat-baris__field">
                    <label class="sr-only" for="saring-kategori">Saring menurut kategori</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-kategori" name="kategori" class="ad-pilih">
                            <option value="">Semua kategori ({{ $totalQuiz }})</option>

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
                    <a href="{{ route('admin.quiz') }}" class="ad-tombol ad-tombol--garis">
                        <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />

                        Hapus filter
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- =====================
         DAFTAR QUIZ
    ====================== --}}
    <div class="ad-seksi ad-daftar-kartu">
        @if ($daftar === [])
            @if ($kataKunci !== '' || $adaFilter)
                <x-admin.kosong ikon="cari" judul="Quiz tidak ditemukan"
                    teks="Coba gunakan kata kunci yang berbeda." />
            @else
                <x-admin.kosong ikon="soal" judul="Belum ada quiz"
                    teks="Quiz yang telah disetujui akan muncul di sini." />
            @endif
        @else
            @foreach ($daftar as $quiz)
                <x-admin.quiz-kartu :quiz="$quiz" />
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
             DIALOG HAPUS QUIZ

        Satu dialog untuk semua kartu, diisi dari data-* tombol yang ditekan
        oleh admin.js, jadi daftar panjang tetap hanya punya satu kotak
        konfirmasi.

        Form-nya memakai @method('DELETE') ke admin.quiz.destroy. Tidak ada
        form yang langsung terkirim: quiznya baru dihapus setelah tombol
        "Hapus Quiz" ditekan.
    ====================== --}}
    <div class="ad-dialog" data-dialog-hapus role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="dialog-hapus-judul">
        <div class="ad-dialog__kartu">
            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="dialog-hapus-judul" data-hapus-judul>Hapus Quiz?</h2>

                    <p class="ad-teks-2 mt-0.5 !text-xs" data-hapus-meta></p>
                </div>

                <button type="button" class="ad-dialog__tutup" data-hapus-tutup aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            <div class="ad-dialog__badan">
                <p class="text-sm leading-relaxed text-dark/70">
                    Quiz ini akan dihapus beserta seluruh soalnya, dan tidak lagi
                    tersedia untuk pengguna. Riwayat pengerjaan yang sudah ada ikut
                    terhapus. Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-hapus-tutup>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-hapus-quiz">
                    <x-admin.ikon nama="sampah" />

                    Hapus Quiz
                </button>
            </footer>

            {{--
                Form tidak terlihat, tapi tetap ada di DOM supaya tombolnya
                bisa memakai atribut form=.
            --}}
            <form method="POST" action="" id="form-hapus-quiz" data-hapus-form hidden>
                @csrf

                @method('DELETE')
            </form>
        </div>
    </div>
@endsection
