@extends('layouts.admin')

@section('title', 'Konten Pembelajaran | KelasKita')

@section('content')
    {{--
        Halaman "Konten Pembelajaran".

        Ruang kerja admin untuk materinya dan quiz-nya sendiri: tambah,
        sunting, gandakan, terbitkan, dan tarik kembali. Bedanya dari menu
        "Materi" dan "Quiz" di sidebar yang lain adalah keduanya berisi
        katalog konten yang sudah tayang (siapa pun yang membuatnya),
        sedangkan di sini admin mengelola karyanya dari draft sampai terbit.
        Menu "Verifikasi" juga berbeda: itu antrean keputusan untuk karya
        pengguna, dan tidak diubah oleh halaman ini.

        Yang tampil hanya karya admin yang sedang login, bukan seluruh isi
        database — sama seperti "Karya Saya" milik pengguna. Karya pengguna
        tetap managing lewat Verifikasi dan lewat katalog Materi / Quiz.

        Urutan halaman ini mengikuti urutan Zulu_admin dari bawah: kepala,
        dua kartu aksi, tab, baris alat, lalu daftar. Setiap bagian punya
        satu wrapper .ad-seksi supaya jaraknya seragam dan tidak perlu
        ditulis ulang di view.

        Tab, pencarian, dan filter tetap tautan biasa ke URL yang sama, jadi
        semua ikut bekerja tanpa JavaScript dan tombol "kembali" di browser
        mengembalikan keadaan sebelumnya.
    --}}

    @php
        $adaFilter = filled($kategoriAktif) || filled($kelasAktif) || filled($statusAktif)
            || $urutAktif !== 'terbaru';

        $bahanKonten = $tab === 'quiz' ? 'kuis' : 'materi';
        $jumlahBaris = $daftar->count();
    @endphp

    {{-- =====================
         KEPALA HALAMAN
    ======================
         Ikon di kotak soft purple, judul, dan satu kalimat penjelas.
         Jumlah isi per jenis di sebelah kanan sebagai penanda tab, bukan
         sebagai angka yang harus dibaca. --}}
    <header class="ad-seksi">
        <div class="ad-konten-kepala">
            <div class="ad-konten-kepala__kiri">
                <span class="ad-konten-kepala__kotak" aria-hidden="true">
                    <x-admin.ikon nama="buku" ukuran="w-6 h-6" />
                </span>

                <div class="ad-konten-kepala__teks">
                    <h1 class="ad-konten-kepala__judul">Konten Pembelajaran</h1>

                    <p class="ad-konten-kepala__sub">
                        Kelola materi dan kuis untuk mendukung proses pembelajaran di Kelas Kita.
                    </p>
                </div>
            </div>

            {{--
                Dua lencana jumlah. Dihitung sebagai dua query kecil di
                controller, bukan lewat kartu statistik: di halaman ini angka
                ini cuma penanda tab, bukan angka yang harus dibaca.
            --}}
            <div class="ad-konten-kepala__jumlah">
                <span class="ad-lencana ad-lencana--ungu">
                    <span class="ad-lencana__titik" aria-hidden="true"></span>
                    {{ $jumlahMateri }} Materi
                </span>

                <span class="ad-lencana ad-lencana--ungu">
                    <span class="ad-lencana__titik" aria-hidden="true"></span>
                    {{ $jumlahQuiz }} Quiz
                </span>
            </div>
        </div>
    </header>

    {{-- =====================
         KARTU AKSI
    ======================
         Dua kartu dengan lebar sama: dua kolom di desktop, satu kolom di
         mobile. Tautan dan kalimatnya ditulis di komponen supaya daftar di
         bawah dan form yang dibuka dari sini tidak pernah berbeda. --}}
    <div class="ad-seksi">
        <div class="ad-konten-aksi">
            <div class="ad-konten-aksi__kartu">
                <span class="ad-konten-aksi__ikon" aria-hidden="true">
                    <x-admin.ikon nama="dokumen" ukuran="w-6 h-6" />
                </span>

                <div class="ad-konten-aksi__isi">
                    <h2 class="ad-konten-aksi__judul">Tambah Materi</h2>

                    <p class="ad-konten-aksi__pesan">
                        Buat materi pembelajaran untuk peserta didik.
                    </p>

                    <a href="{{ route('admin.konten.materi.tambah') }}"
                        class="ad-tombol ad-tombol--utama ad-tombol--kecil ad-konten-aksi__tombol">
                        <x-admin.ikon nama="tambah" ukuran="w-4 h-4" :tebal="2.4" />

                        Tambah Materi
                    </a>
                </div>
            </div>

            <div class="ad-konten-aksi__kartu">
                <span class="ad-konten-aksi__ikon" aria-hidden="true">
                    <x-admin.ikon nama="buku-centang" ukuran="w-6 h-6" />
                </span>

                <div class="ad-konten-aksi__isi">
                    <h2 class="ad-konten-aksi__judul">Tambah Kuis</h2>

                    <p class="ad-konten-aksi__pesan">
                        Buat kuis untuk menguji pemahaman peserta didik.
                    </p>

                    <a href="{{ route('admin.konten.quiz.tambah') }}"
                        class="ad-tombol ad-tombol--utama ad-tombol--kecil ad-konten-aksi__tombol">
                        <x-admin.ikon nama="tambah" ukuran="w-4 h-4" :tebal="2.4" />

                        Tambah Kuis
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- =====================
         TOAST
    ======================
         Hasil aksi setelah redirect: simpan, terbitkan, duplikat, atau
         hapus. Muncul mengambang di pojok kanan bawah dan menutup dirinya
         sendiri setelah beberapa detik (resources/js/konten-admin.js). --}}
    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

    {{-- =====================
         TAB DAN BARIS ALAT
    ======================
         Tab dan alatnya satu blok karena keduanya selalu menuju halaman yang
         sama: ganti tab tidak berarti mengganti halaman, dan filter yang
         aktif ikut terbawa ke tab berikutnya.
    --}}
    <div class="ad-seksi">
        <nav class="ad-konten-tab" aria-label="Jenis konten" data-konten-navigasi>
            <a href="{{ route('admin.konten', ['tab' => 'materi']) }}"
                class="{{ $tab === 'materi' ? 'ad-konten-tab__item ad-konten-tab__item--aktif' : 'ad-konten-tab__item' }}"
                @if ($tab === 'materi') aria-current="page" @endif>
                Materi

                <span class="ad-konten-tab__jumlah">{{ $jumlahMateri }}</span>
            </a>

            <a href="{{ route('admin.konten', ['tab' => 'quiz']) }}"
                class="{{ $tab === 'quiz' ? 'ad-konten-tab__item ad-konten-tab__item--aktif' : 'ad-konten-tab__item' }}"
                @if ($tab === 'quiz') aria-current="page" @endif>
                Kuis

                <span class="ad-konten-tab__jumlah">{{ $jumlahQuiz }}</span>
            </a>
        </nav>
    </div>

    {{-- =====================
         PENCARIAN DAN FILTER
    ======================
         Satu baris lurus di layar lebar: kotak cari, kelas, kategori,
         status, urutan, dan "Terapkan". Di bawah 1024px pembungkusnya boleh
         membungkus dan setiap kolom mengambil lebar penuh, jadi tidak ada
         yang terpotong dan tidak ada gulir horizontal.

         Dua form, bukan satu: "Terapkan" mengirim form yang berisi semua
         field, sedangkan "Hapus filter" memakai form sendiri yang isinya
         cuma tab. Kalau keduanya satu form, tombol Hapus filter ikut
         mengirim nilai filter yang sedang aktif, jadi tidak menghapus apa
         pun.

         Select ganti langsung mengirim form, jadi admin tidak perlu menekan
         "Terapkan" lagi setelah memilih kelas atau status. Kotak cari
         tetap butuh tombol karena isinya belum selesai diketik. --}}
    <div class="ad-seksi">
        <div class="ad-kartu ad-alat-kotak">
            <form method="GET" action="{{ route('admin.konten') }}" class="ad-konten-alat"
                data-konten-saring>
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="ad-konten-alat__cari">
                    <div class="ad-cari">
                        <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                        <label class="sr-only" for="cari-konten">Cari {{ $bahanKonten }}</label>

                        <input id="cari-konten" name="q" type="search" value="{{ $kataKunci }}"
                            placeholder="Cari {{ $bahanKonten }}..." autocomplete="off">
                    </div>
                </div>

                <div class="ad-konten-alat__field">
                    <label class="sr-only" for="saring-kelas">Saring menurut kelas</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-kelas" name="kelas" class="ad-pilih" data-konten-saring-pilih>
                            <option value="">Semua Kelas</option>

                            @foreach ($daftarKelas as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($kelasAktif === $nilai)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                        </svg>
                    </div>
                </div>

                <div class="ad-konten-alat__field">
                    <label class="sr-only" for="saring-kategori-konten">Saring menurut kategori</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-kategori-konten" name="kategori" class="ad-pilih"
                            data-konten-saring-pilih>
                            <option value="">Semua Kategori</option>

                            @foreach ($daftarKategori as $kategori)
                                <option value="{{ $kategori->slug }}" @selected($kategoriAktif === $kategori->slug)>
                                    {{ $kategori->nama }}
                                </option>
                            @endforeach
                        </select>

                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                        </svg>
                    </div>
                </div>

                <div class="ad-konten-alat__field">
                    <label class="sr-only" for="saring-status">Saring menurut status</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-status" name="status" class="ad-pilih" data-konten-saring-pilih>
                            <option value="">Semua Status</option>

                            @foreach ($pilihanStatus as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($statusAktif === $nilai)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                        </svg>
                    </div>
                </div>

                <div class="ad-konten-alat__field">
                    <label class="sr-only" for="saring-urut-konten">Urutkan daftar</label>

                    <div class="ad-pilih__bungkus">
                        <select id="saring-urut-konten" name="urut" class="ad-pilih" data-konten-saring-pilih>
                            @foreach ($pilihanUrut as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($urutAktif === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                        </svg>
                    </div>
                </div>

                <button type="submit" class="ad-tombol ad-tombol--utama">Terapkan</button>
            </form>

            <form method="GET" action="{{ route('admin.konten') }}" class="ad-konten-alat__aksi">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <button type="submit" class="ad-tombol ad-tombol--garis">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />

                    Hapus filter
                </button>
            </form>

            {{--
                Ringkasan filter yang aktif.

                Tanpa ini, admin yang memakai filter lalu mengetik ulang di kotak
                cari tidak bisa tahu filter mana yang masih menyala, dan "Hapus
                filter" terlihat seperti tidak sedang menghapus apa pun.
            --}}
            @if ($adaFilter || $kataKunci !== '')
                <div class="ad-alat-baris__simpul">
                    @if ($kataKunci !== '')
                        <span class="ad-simpul">
                            <span class="ad-simpul__label">Kata kunci</span>
                            <span class="ad-simpul__nilai">"{{ $kataKunci }}"</span>
                        </span>
                    @endif

                    @if (filled($kelasAktif))
                        <span class="ad-simpul">
                            <span class="ad-simpul__label">Kelas</span>
                            <span class="ad-simpul__nilai">{{ $kelasAktif }}</span>
                        </span>
                    @endif

                    @if (filled($statusAktif))
                        <span class="ad-simpul">
                            <span class="ad-simpul__label">Status</span>
                            <span class="ad-simpul__nilai">{{ $pilihanStatus[$statusAktif] ?? $statusAktif }}</span>
                        </span>
                    @endif

                    @if (filled($kategoriAktif))
                        <span class="ad-simpul">
                            <span class="ad-simpul__label">Kategori</span>
                            <span class="ad-simpul__nilai">
                                {{ $daftarKategori->firstWhere('slug', $kategoriAktif)->nama ?? $kategoriAktif }}
                            </span>
                        </span>
                    @endif

                    @if ($urutAktif !== 'terbaru')
                        <span class="ad-simpul">
                            <span class="ad-simpul__label">Urutan</span>
                            <span class="ad-simpul__nilai">{{ $pilihanUrut[$urutAktif] ?? $urutAktif }}</span>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- =====================
         DAFTAR KONTEN
    ======================
         Tiga state yang berbeda, dan ketiganya wajib ada. Tanpa loading
         state, admin akan sempat melihat "Belum ada materi" padahal
         halamannya sedang memuat. Tanpa error state, kegagalan apa pun
         akan tampil sebagai daftar kosong yang terlihat seperti "belum
         ada konten". Tanpa empty state, daftar yang benar-benar kosong
         terlihat seperti gagal dimuat.

         State-nya diurutkan dari yang paling tidak mungkin salah (gagal),
         ke yang paling mungkin (berisi). --}}
    <div class="ad-seksi">
        {{-- GAGAL: query-nya melempar. Pesan errornya sendiri tidak pernah
             ditampilkan; yang muncul hanya kalimat yang bisa ditindaklanjuti
             beserta tombol Coba Lagi. --}}
        @if ($gagal)
            <div class="ad-konten-kotak">
                <div class="ad-konten-gagal">
                    <span class="ad-konten-gagal__ikon" aria-hidden="true">
                        <x-admin.ikon nama="peringatan" ukuran="w-6 h-6" />
                    </span>

                    <p class="ad-konten-gagal__judul">Gagal memuat konten</p>

                    <p class="ad-konten-gagal__teks">
                        Terjadi masalah saat mengambil data pembelajaran.
                    </p>

                    <a href="{{ request()->fullUrlWithQuery($request->except('page')) }}"
                        class="ad-tombol ad-tombol--utama">
                        <x-admin.ikon nama="roda" ukuran="w-4 h-4" />

                        Coba Lagi
                    </a>
                </div>
            </div>
        @else
            {{--
                MEMUAT: kerangka disembunyikan di markup dan ditampilkan
                JavaScript tepat sebelum browser meninggalkan halaman ini
                (resources/js/konten-daftar.js). Tanpa JavaScript ia tetap
                tersembunyi dan daftar di bawahnya tetap terbaca utuh.
            --}}
            <div class="ad-konten-kotak" data-konten-rangka hidden aria-hidden="true">
                @for ($i = 0; $i < 5; $i++)
                    <div class="ad-konten-kerangka">
                        <span class="ad-konten-kerangka__kotak"></span>

                        <div class="ad-konten-kerangka__teks">
                            <span class="ad-konten-kerangka__garis ad-konten-kerangka__garis--judul"></span>
                            <span class="ad-konten-kerangka__garis ad-konten-kerangka__garis--meta"></span>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- KOSONG: dua-duanya perlu penjelasan dan jalan keluar. Yang
                 bedanya cuma jalannya: daftar yang tersaring meminta
                 "coba filter lain", daftar yang benar-benar kosong
                 meminta "mulai buat". --}}
            @if ($daftar->isEmpty())
                <div class="ad-konten-kotak">
                    @if ($adaFilter || $kataKunci !== '')
                        <div class="ad-konten-kosong">
                            <span class="ad-konten-gagal__ikon" aria-hidden="true"
                                style="background-color: var(--ad-cucian-ungu); color: var(--ad-teks-ungu-terang)">
                                <x-admin.ikon nama="cari" ukuran="w-6 h-6" />
                            </span>

                            <p class="ad-konten-gagal__judul">Konten tidak ditemukan</p>

                            <p class="ad-konten-gagal__teks">
                                Coba gunakan kata kunci atau filter yang lain.
                            </p>

                            <a href="{{ route('admin.konten', ['tab' => $tab]) }}" class="ad-tombol ad-tombol--garis">
                                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />

                                Hapus filter
                            </a>
                        </div>
                    @elseif ($tab === 'quiz')
                        <div class="ad-konten-kosong">
                            <span class="ad-konten-gagal__ikon" aria-hidden="true"
                                style="background-color: var(--ad-cucian-ungu); color: var(--ad-teks-ungu-terang)">
                                <x-admin.ikon nama="buku-centang" ukuran="w-6 h-6" />
                            </span>

                            <p class="ad-konten-gagal__judul">Belum ada kuis</p>

                            <p class="ad-konten-gagal__teks">
                                Belum ada kuis yang dibuat.
                            </p>

                            <a href="{{ route('admin.konten.quiz.tambah') }}" class="ad-tombol ad-tombol--utama">
                                <x-admin.ikon nama="tambah" ukuran="w-4 h-4" :tebal="2.4" />

                                Tambah Kuis
                            </a>
                        </div>
                    @else
                        <div class="ad-konten-kosong">
                            <span class="ad-konten-gagal__ikon" aria-hidden="true"
                                style="background-color: var(--ad-cucian-ungu); color: var(--ad-teks-ungu-terang)">
                                <x-admin.ikon nama="buku" ukuran="w-6 h-6" />
                            </span>

                            <p class="ad-konten-gagal__judul">Belum ada materi</p>

                            <p class="ad-konten-gagal__teks">
                                Belum ada materi pembelajaran yang dibuat.
                            </p>

                            <a href="{{ route('admin.konten.materi.tambah') }}" class="ad-tombol ad-tombol--utama">
                                <x-admin.ikon nama="tambah" ukuran="w-4 h-4" :tebal="2.4" />

                                Tambah Materi
                            </a>
                        </div>
                    @endif
                </div>
            @else
                {{-- ADA ISI: baris per konten, bukan kartu per konten. --}}
                <div class="ad-konten-kotak">
                    <div class="ad-konten-info">
                        <p class="ad-konten-info__jumlah">
                            Menampilkan <strong>{{ $jumlahBaris }}</strong>
                            {{ $bahanKonten }}
                            @if ($paginasi && $paginasi->total() > $jumlahBaris)
                                dari <strong>{{ $paginasi->total() }}</strong>
                            @endif
                        </p>

                        <p class="ad-konten-info__jumlah">
                            <x-admin.ikon nama="jam" ukuran="w-3.5 h-3.5" class="inline" />
                            Terakhir diperbarui sesuai tanggal di setiap baris
                        </p>
                    </div>

                    <div class="ad-konten-daftar" data-konten-daftar>
                        @foreach ($daftar as $baris)
                            <x-admin.konten-baris :kartu="$baris" />
                        @endforeach
                    </div>
                </div>

                {{-- PAGINASI: tombol halaman saja, tanpa kartu putih. --}}
                @if ($paginasi && $paginasi->hasPages())
                    <div class="ad-seksi ad-paginasi" data-konten-navigasi>
                        {{ $paginasi->links() }}
                    </div>
                @endif
            @endif
        @endif
    </div>

    {{-- =====================
         DIALOG
    ======================
         Dua dialog dipakai bersama untuk semua baris, jadi daftar panjang
         tetap hanya punya satu kotak konfirmasi di layar.

         Keduanya diisi dari data-* tombol yang ditekan
         (resources/js/konten-publish.js dan resources/js/admin.js), bukan
         dari server. --}}
    <x-admin.dialog-terbitan />

    <x-admin.dialog-hapus judul="Hapus konten?" tombol="Hapus Konten" />
@endsection
