@extends('layouts.admin')

@section('title', ($materi ?? null ? 'Edit' : 'Tambah').' Materi | KelasKita')

@section('content')
    {{--
        Halaman "Tambah Materi" dan "Edit Materi" di area admin.

        Form ini sengaja SAMA dengan halaman pemilik (user/materi-tambah.blade.php):
        satu halaman panjang, bukan dua tahap; komponen, urutan, dan isi
        isiannya persis sama; tombol simpan yang sama; baris tombol lengket
        yang sama. Bedanya hanya dua, semuanya soal siapa yang menerbitkan:

          1. Tidak ada saklar "Ajukan Persetujuan". Admin adalah pihak yang
             menerbitkan, jadi tidak ada yang perlu diajukan ke siapa pun —
             dan tidak ada catatan pengajuan maupun alasan penolakan, karena
             tidak ada proses yang menolaknya.
          2. Baris tombolnya punya dua pilihan: Simpan Draft dan Publish
             Sekarang. Keduanya menyimpan isi yang sama persis; yang
             membedakan cuma statusnya.

        FORM-nya benar-benar identik dengan form pemilik: tidak ada field
        admin saja. Field yang dulu hanya ada di sini (kelas tujuan) sudah
        dihapus dari seluruh aplikasi, jadi tidak ada lagi alasan form ini
        perlu BEDA di sini saja.

        Semua komponen formnya masih milik pemilik (x-materi.informasi,
        .bab, .editor) dan JavaScript-nya juga (resources/js/materi-tambah.js),
        termasuk baris tombol lengket dan aturan gambarnya. Kalau formnya
        ditulis ulang, pemeriksaannya, toolbar-nya, dan aturan gambar
        thumbnail-nya pasti akan menyimpang pada satu versi.

        Konfirmasi terbitan dikerjakan resources/js/konten-publish.js. Tanpa
        JavaScript tombolnya tetap mengirim form apa adanya — lebih baik
        konten terbit tanpa tanya daripada tidak bisa terbit sama sekali.
    --}}

    @php
        $materi = $materi ?? null;
        $modeEdit = $materi !== null;
    @endphp

    <div data-tambah-materi>
        {{-- =========================
             KEPALA HALAMAN
        ========================== --}}
        <header class="ad-seksi flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                <span class="ad-hero-konten__ikon mt-0.5" aria-hidden="true">
                    <x-admin.ikon nama="pena" ukuran="w-6 h-6" />
                </span>

                <div class="min-w-0">
                    <h1 class="ad-hero-konten__judul">{{ $modeEdit ? 'Edit Materi' : 'Tambah Materi' }}</h1>

                    <p class="mt-1 text-sm leading-relaxed text-dark/60">
                        @if ($modeEdit)
                            Perbaiki isi materi <strong class="font-bold text-dark">{{ $materi->nama }}</strong>.
                        @else
                            Buat materi pembelajaran untuk peserta didik.
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.konten', ['tab' => 'materi']) }}"
                class="ad-tombol ad-tombol--garis shrink-0 self-start">
                <x-admin.ikon nama="panah-kiri" />

                Kembali
            </a>
        </header>

        {{-- =========================
             GALAT VALIDASI
        ========================== --}}
        @if ($errors->any())
            <div class="ad-seksi ad-alert ad-alert--bahaya" role="alert">
                <span class="ad-alert__ikon" aria-hidden="true">
                    <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                </span>

                <div class="min-w-0">
                    <p class="font-semibold">Materi belum bisa disimpan:</p>

                    <ul class="mt-1 list-disc space-y-0.5 pl-5">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- =========================
             FORM
        ========================== --}}
        <form id="form-tambah-materi" method="POST" enctype="multipart/form-data" class="ad-seksi"
            action="{{ $modeEdit ? route('admin.konten.materi.update', $materi->slug) : route('admin.konten.materi.tambah.store') }}">
            @csrf

            @if ($modeEdit)
                @method('PUT')
            @endif

            {{--
                Satu kolom, komponen persis sama seperti form pemilik, dan
                urutan yang sama: informasi, daftar bab, editor isi. Tidak ada
                kartu atau field tambahan di antaranya — kalau ada, isian form
                ini akan berbeda dari form pemilik.
            --}}
            <div class="grid min-w-0 gap-4 lg:gap-5">
                <x-materi.informasi :kategori="$kategori" :materi="$materi" />

                <x-materi.bab :kategori="$kategori" :materi="$materi" />

                <x-materi.editor :isi="$modeEdit ? $materi->isi : null" />
            </div>

            {{-- Data yang disusun JavaScript sebelum form dikirim. --}}
            <input type="hidden" name="isi" value="{{ old('isi', $modeEdit ? $materi->isi : '') }}" data-input-isi>

            <input type="hidden" name="bab" value="{{ old('bab', $modeEdit ? json_encode($bab ?? [], JSON_UNESCAPED_UNICODE) : '') }}"
                data-input-bab>

            {{-- =========================
                 BARIS TOMBOL
            ==========================
                 Bentuknya sama dengan baris tombol di form Quiz milik admin:
                 satu kartu di akhir form, bukan baris tombol lengket yang
                 menyala dan mati saat form digulir. Alasannya, form ini satu
                 halaman panjang dan tiga tombolnya selalu relevan — membiarkan
                 mereka hilang sampai form habis digulir hanya menambah satu
                 langkah lagi sebelum admin bisa menyimpan.

                 Karena tidak lagi lengket, atribut data-action-bar dan
                 penandanya (data-action-bar-picu) sengaja tidak dipakai di
                 sini. resources/js/materi-tambah.js hanya menyalakan baris
                 seperti itu, jadi tanpa keduanya barisnya tampil begitu saja
                 dan halaman ini tidak lagi bergantung JavaScript untuk
                 menampilkan tombolnya. Form milik pengguna tetap memakai baris
                 lengketnya sendiri.

                 Tiga tombol, dua sisi. "Batal" berdiri sendiri di kiri karena
                 ia membatalkan, sedangkan "Simpan Draft" dan "Publish Sekarang"
                 adalah dua arah dari satu keputusan yang sama, jadi keduanya
                 diletakkan berurutan di kanan. Dua tombol menyimpan isi yang
                 sama persis — yang membedakan cuma field "aksi".

                 Keduanya type="submit" supaya tetap berfungsi tanpa
                 JavaScript; resources/js/konten-publish.js yang menahan
                 Publish Sekarang lebih dulu untuk membuka dialog konfirmasi. --}}
            <div class="panel-aksi">
                @if ($modeEdit)
                    <div class="flex w-full min-w-0 flex-wrap items-center gap-2">
                        <span class="ad-lencana ad-lencana--ungu">
                            <span class="ad-lencana__titik" aria-hidden="true"></span>
                            {{ $materi->labelStatus() }}
                        </span>

                        <p class="w-full text-sm leading-relaxed text-dark/60">
                            @if ($materi->status === \App\Models\Materi::STATUS_PUBLISHED)
                                Materi ini sudah tayang untuk pengguna. Menyunting isinya tidak menariknya dari
                                halaman Materi; statusnya hanya berubah lewat Publish atau Batalkan Publikasi di daftar.
                            @else
                                Materi ini masih draft, jadi belum muncul di halaman Materi.
                            @endif
                        </p>
                    </div>
                @endif

                {{--
                    Baris tombolnya harus selebar kartunya (w-full). Tanpa itu
                    baris ini hanya selebar isinya dan duduk di kiri kartu,
                    sehingga sm:ml-auto pada pasangannya tidak punya ruang
                    kosong untuk didorong — hasilnya tombol tetap berdesakan di
                    kiri, bukan di tepi kanan.
                --}}
                <div class="mt-4 flex w-full flex-col gap-2.5 sm:flex-row sm:items-center">
                    <a href="{{ route('admin.konten', ['tab' => 'materi']) }}" class="tombol-garis justify-center">
                        Batal
                    </a>

                    {{--
                        Dua tombol yang menjawab keputusan terbit. Dikelompokkan
                        supaya sm:ml-auto cukup mendorong satu wadah, bukan
                        satu tombol: kalau hanya tombol Publish yang didorong,
                        "Simpan Draft" akan tertinggal di tengah baris dan
                        kelihatan seperti tombol milik blok status di atasnya.
                    --}}
                    <div class="flex flex-col gap-2.5 sm:ml-auto sm:flex-row sm:items-center">
                        <button type="submit" name="aksi" value="draft" data-konten-kirim
                            class="tombol-garis justify-center">
                            <x-admin.ikon nama="dokumen" ukuran="w-4 h-4" />

                            Simpan Draft
                        </button>

                        <button type="submit" name="aksi" value="publish" data-konten-publish="publish"
                            data-konten-kirim class="tombol-utama justify-center">
                            <x-admin.ikon nama="centang" ukuran="w-4 h-4" />

                            Publish Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- =========================
         DIALOG
    ==========================
         Dialog konfirmasi terbitan dipakai seluruh form; isinya diambil
         dari tombol yang ditekan, bukan dari server
         (resources/js/konten-publish.js). --}}
    <x-admin.dialog-terbit />

    {{-- =========================
         DIALOG HAPUS BAB
    ==========================
         Dipakai resources/js/materi-tambah.js, sama seperti di form
         pemilik — termasuk letaknya: modul itu mencari elemen di dalam
         akar data-tambah-materi, jadi dialog harus ikut di dalam akar itu
         supaya tombol "Ya, hapus" benar-benar bekerja di sini. --}}
    <div data-tambah-materi>
        <div class="dialog-bab" data-dialog-hapus role="dialog" aria-modal="true"
            aria-labelledby="judul-dialog-hapus">
            <div class="w-full max-w-sm rounded-2xl border border-ungu-line bg-white p-5 shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">
                <span class="ad-cucian-bahaya flex h-10 w-10 items-center justify-center rounded-xl text-[var(--ad-teks-bahaya)]"
                    aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.166L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-.621.504-1.125 1.125-1.125H9.75c.621 0 1.125.504 1.125 1.125v.916" />
                    </svg>
                </span>

                <h2 id="judul-dialog-hapus" class="mt-4 text-base font-extrabold text-dark">
                    Hapus bab ini?
                </h2>

                <p class="mt-1.5 text-sm leading-relaxed text-muted" data-dialog-pesan>
                    Bab yang dihapus tidak dapat dikembalikan.
                </p>

                <div class="mt-5 flex flex-col gap-2.5 sm:flex-row sm:justify-end">
                    <button type="button" data-dialog-batal class="tombol-garis justify-center">
                        Batal
                    </button>

                    <button type="button" data-dialog-hapus-tombol
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover-[#c2414a]">
                        Ya, hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection