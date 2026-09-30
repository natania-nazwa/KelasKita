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

        Isinya: cari, saring, pilih, baca detail, ubah, hapus. Panel di kanan
        menampilkan ringkasan materi yang dipilih; tombol "Lihat Materi" dari
        sana membuka halaman detail yang memakai komponen tampilan milik
        pengguna, jadi yang dibaca admin persis sama dengan yang dibaca user.
    --}}

    @php
        /*
         * Query string yang sedang aktif, dipakai ulang untuk tautan kartu.
         *
         * 'page' sengaja dikosongkan: memilih materi lain harus kembali ke
         * halaman pertama, karena materi yang dipilih belum tentu ada di
         * nomor halaman yang sedang dibuka.
         */
        $parameter = array_filter([
            'q' => $kataKunci,
            'kategori' => $kategoriAktif,
            'pembuat' => $pembuatAktif,
            'urut' => $urutAktif === 'terbaru' ? null : $urutAktif,
        ], fn ($nilai) => filled($nilai));

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
        $slugTerpilih = $terpilih['petikan']['slug'] ?? null;
    @endphp

    {{-- =====================
         HERO BANNER
    ====================== --}}
    <section class="ad-hero ad-hero--materi">
        <div class="ad-hero-materi__susun">
            <div class="ad-hero-materi__kiri">
                <span class="ad-hero-materi__ikon" aria-hidden="true">
                    <x-admin.ikon nama="buku" ukuran="w-7 h-7" />
                </span>

                <div class="min-w-0">
                    <h1 class="ad-hero-materi__judul">Materi</h1>

                    <p class="ad-hero-materi__sub">
                        Kelola materi pembelajaran yang telah dipublikasikan untuk pengguna KelasKita.
                    </p>
                </div>
            </div>

            <div class="ad-hero-materi__kanan">
                <p class="ad-hero-materi__kutip">
                    &ldquo;Ilmu hari ini,<br>masa depan esok&rdquo;
                </p>

                <div class="ad-hero-materi__ilustrasi">
                    <x-admin.ilustrasi-admin />
                </div>
            </div>
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
         FILTER + PENCARIAN

         Satu form GET untuk filter dan pencarian sekaligus, supaya mengetik
         kata kunci tidak mematikan saringan yang sedang aktif, dan sebaliknya.

         Panel filter memakai <details>: buka/tutup-nya dari browser, jadi
         filter tetap bisa dipakai tanpa JavaScript. admin.js hanya menambah
         satu hal: menutupnya dari luar dan dari tombol Escape.
    ====================== --}}
    <div class="ad-seksi ad-kartu">
        <form method="GET" action="{{ route('admin.materi') }}" class="ad-alat">
            <div class="ad-alat__kiri">
                <details class="ad-saring" data-tutup-luar @if ($adaFilter) open @endif>
                    <summary @class(['ad-saring__tombol', 'ad-saring__tombol--ada' => $adaFilter])>
                        <x-admin.ikon nama="kotak" ukuran="w-4 h-4" />

                        Filter

                        @if ($adaFilter)
                            <span class="ad-lencana ad-lencana--ungu">{{ $jumlahFilter }}</span>
                        @endif
                    </summary>

                    <div class="ad-saring__panel">
                        <div class="ad-saring__grup">
                            <label class="ad-saring__label" for="saring-kategori">Kategori</label>

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

                        <div class="ad-saring__grup">
                            <label class="ad-saring__label" for="saring-pembuat">Pembuat</label>

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

                        <div class="ad-saring__grup">
                            <label class="ad-saring__label" for="saring-urut">Urutkan</label>

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

                        <div class="ad-saring__grup flex flex-wrap gap-2">
                            <button type="submit" class="ad-tombol ad-tombol--utama ad-tombol--kecil">Terapkan</button>

                            @if ($adaFilter || $kataKunci !== '')
                                <a href="{{ route('admin.materi') }}" class="ad-tombol ad-tombol--garis ad-tombol--kecil">Reset</a>
                            @endif
                        </div>
                    </div>
                </details>
            </div>

            <div class="ad-alat__kanan">
                <div class="ad-cari">
                    <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                    <label class="sr-only" for="q">Cari materi</label>

                    <input id="q" name="q" type="search" value="{{ $kataKunci }}"
                        placeholder="Cari materi..." autocomplete="off">
                </div>

                <button type="submit" class="ad-tombol ad-tombol--garis shrink-0">Cari</button>
            </div>
        </form>
    </div>

    {{-- =====================
         DAFTAR + PANEL DETAIL
    ====================== --}}
    <div class="ad-seksi ad-materi">
        <div class="ad-materi__daftar">
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
                    <x-admin.materi-kartu :materi="$materi" :terpilih="$slugTerpilih" :parameter="$parameter" />
                @endforeach
            @endif
        </div>

        <div class="ad-materi__panel">
            <x-admin.materi-panel :terpilih="$terpilih" />
        </div>
    </div>

    {{-- =====================
         PAGINASI
    ====================== --}}
    @if ($daftar !== [])
        <div class="ad-seksi flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-dark/60">
                Menampilkan {{ $paginasi->firstItem() ?? 0 }}–{{ $paginasi->lastItem() ?? 0 }}
                dari {{ $paginasi->total() }} data
            </p>

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
