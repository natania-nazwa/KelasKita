@extends('layouts.admin')

@section('title', ($materi ? 'Edit' : 'Tambah').' Materi | KelasKita')

@section('content')
    {{--
        Form materi untuk admin, sekaligus untuk tambah dan untuk edit.

        Dua tahap: Informasi Dasar, lalu Isi Materi. Yang membuat form ini
        tidak menyimpang dari form pemilik adalah isian dan editornya sendiri
        — keduanya memakai komponen yang sama persis dengan halaman Tambah
        Materi milik pengguna (x-materi.informasi, x-materi.bab, x-materi.editor)
        dan JavaScript yang sama (resources/js/materi-tambah.js), jadi admin
        menulis materi dengan alat yang sudah dikenal. Kalau formnya dibuat
        terpisah, pemeriksaannya, toolbar-nya, dan aturan gambar thumbnail-nya
        pasti akan menyimpang pada satu versi.

        Yang berbeda dari form pemilik ada tiga, dan semuanya disengaja:

          1. Hanya dua tahap, bukan satu halaman panjang. Tahap pertama
             informasi dasar, tahap kedua seluruh alat tulis. Panel lain
             tidak pernah dilepas dari DOM, cuma disembunyikan, jadi isian
             tahap pertama tetap utuh saat admin turun ke tahap kedua.
          2. Tidak ada saklar "Ajukan Persetujuan". Admin adalah pihak yang
             menerbitkan, jadi tidak ada yang perlu diajukan ke siapa pun.
          3. Baris tombolnya punya dua pilihan: Simpan Draft dan Publish
             Sekarang. Keduanya menyimpan isi yang sama; yang membedakan cuma
             statusnya.

        Pindah tahap dan toast-nya dikerjakan oleh resources/js/konten-admin.js,
        konfirmasi terbitannya oleh resources/js/konten-publish.js. Tanpa
        JavaScript tahap kedua tetap terlihat (atribut hidden-nya dilepas di
        <noscript> di bawah) dan kedua tombol tetap mengirim form apa adanya.
    --}}

    @php
        $materi = $materi ?? null;
        $modeEdit = $materi !== null;
    @endphp

    <div data-tambah-materi data-konten-materi>
        {{-- =========================
             HEAD HALAMAN
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
             STEP PER
        ==========================
             Stepper yang sama dengan wizard quiz (x-quiz.stepper), karena
             bentuknya memang sama: daftar tahap, bukan navigasi. Yang
             berbeda cuma isinya, dua tahap bukan tiga. --}}
        <div class="ad-seksi" data-konten-stepper>
            <x-quiz.stepper :aktif="1" :langkah="[
                ['nama' => 'Informasi Dasar', 'keterangan' => 'Judul, kategori, thumbnail'],
                ['nama' => 'Isi Materi', 'keterangan' => 'Bab, editor, dan gambar'],
            ]" />
        </div>

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
                Tahap 1 — Informasi Dasar. Komponennya sama dengan form
                pemilik, termasuk area unggah thumbnail dan pemilih tingkat
                kesulitan.

                Kelas tujuan ada di bawahnya, bukan di dalam komponen itu: form
                pengguna tidak punya konsep kelas tujuan, jadi menyisipkannya
                ke x-materi.informasi akan ikut mengubah halaman pengguna.
            --}}
            <section data-konten-tahap="1" aria-labelledby="konten-tahap-1">
                <h2 id="konten-tahap-1" class="sr-only">Informasi Dasar</h2>

                <x-materi.informasi :kategori="$kategori" :materi="$materi" />

                <section class="kartu-form mt-4 overflow-hidden">
                    <header class="kartu-form__kepala">
                        <span class="kartu-form__ikon" aria-hidden="true">
                            <x-admin.ikon nama="grup" ukuran="w-5 h-5" />
                        </span>

                        <h2 class="kartu-form__judul">Kelas Tujuan</h2>
                    </header>

                    <div class="kartu-form__badan">
                        <x-admin.konten-kelas field="kelas" :pilihan="$kelas" :nilai="$materi?->kelas" />
                    </div>
                </section>

                {{--
                    Navigasi tahap. Tombolnya type="button", jadi tanpa
                    JavaScript ia tidak melakukan apa pun dan tahap kedua tetap
                    terlihat di bawah — isian admin tidak pernah terkunci hanya
                    karena satu tombol tidak bekerja.
                --}}
                <div class="mt-5 flex justify-end">
                    <button type="button" class="ad-tombol ad-tombol--utama" data-konten-lanjut>
                        Lanjut ke Isi Materi

                        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4" />
                    </button>
                </div>
            </section>

            {{--
                Tahap 2 — Isi Materi. Daftar bab, pratinjau, dan editor
                semuanya di dalam satu tahap, karena ketiganya saling
                membaca perubahan yang sama: bab yang sedang dipilih di chip
                juga yang dibuka editor-nya.
            --}}
            <section data-konten-tahap="2" aria-labelledby="konten-tahap-2" hidden>
                <h2 id="konten-tahap-2" class="sr-only">Isi Materi</h2>

                <div class="grid min-w-0 gap-4">
                    <x-materi.bab :kategori="$kategori" :materi="$materi" />

                    <x-materi.editor :isi="$modeEdit ? $materi->isi : null" />
                </div>

                <div class="mt-5">
                    <button type="button" class="ad-tombol ad-tombol--garis" data-konten-kembali>
                        <x-admin.ikon nama="panah-kiri" />

                        Kembali ke Informasi Dasar
                    </button>
                </div>
            </section>

            {{-- Data yang disusun JavaScript sebelum form dikirim. --}}
            <input type="hidden" name="isi" value="{{ old('isi', $modeEdit ? $materi->isi : '') }}" data-input-isi>

            <input type="hidden" name="bab" value="{{ old('bab', $modeEdit ? json_encode($bab ?? [], JSON_UNESCAPED_UNICODE) : '') }}"
                data-input-bab>

            {{-- =========================
                 BARIS TOMBOL
            ==========================
                 Simpan Draft dan Publish Sekarang ada di luar kedua tahap, jadi
                 admin tidak perlu naik ke tahap pertama untuk menyimpan
                 pekerjaan setengah jadi.

                 Keduanya tombol submit biasa: kalau JavaScript mati, tombolnya
                 tetap mengirim form apa adanya dan field "aksi" yang
                 menyertainya membawa maksudnya ke server.

                 Publish Sekarang juga type="submit" supaya tetap berfungsi tanpa
                 JavaScript; resources/js/konten-publish.js yang menahannya lebih
                 dulu untuk membuka dialog konfirmasi. --}}
            <div class="ad-seksi flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('admin.konten', ['tab' => 'materi']) }}" class="ad-tombol ad-tombol--garis justify-center">
                    Batal
                </a>

                <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center">
                    <button type="submit" name="aksi" value="draft" data-konten-kirim
                        class="ad-tombol ad-tombol--garis justify-center">
                        <x-admin.ikon nama="dokumen" ukuran="w-4 h-4" />

                        Simpan Draft
                    </button>

                    <button type="submit" name="aksi" value="publish" data-konten-publish="publish"
                        data-konten-kirim class="ad-tombol ad-tombol--sukses justify-center">
                        <x-admin.ikon nama="centang" ukuran="w-4 h-4" />

                        Publish Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

    {{--
        Dialog konfirmasi terbitan. Satu dialog untuk seluruh form; isinya
        diambil dari tombol yang ditekan, bukan dari server
        (resources/js/konten-publish.js).
    --}}
    <x-admin.dialog-terbitkan />

    {{-- =========================
         DIALOG HAPUS BAB
    ==========================
         Dipakai resources/js/materi-tambah.js, sama seperti di form
         pemilik.
    ========================== --}}
    <div class="dialog-bab" data-dialog-hapus role="dialog" aria-modal="true"
        aria-labelledby="judul-dialog-hapus">
        <div class="w-full max-w-sm rounded-2xl border border-ungu-line bg-white p-5 shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">
            <span class="ad-cucian-bahaya flex h-10 w-10 items-center justify-center rounded-xl text-[var(--ad-teks-bahaya)]"
                aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
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
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a]">
                    Ya, hapus
                </button>
            </div>
        </div>
    </div>
@endsection
