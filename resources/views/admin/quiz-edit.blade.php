@extends('layouts.admin')

@section('title', 'Edit '.$quiz->judul.' | KelasKita')

@section('content')
    {{--
        Form edit quiz untuk admin.

        Isian dan builder soalnya bukan ditulis ulang di sini: form ini
        memakai komponen wizard yang sama persis dengan halaman Tambah/Edit
        Quiz milik pengguna (x-quiz.wizard-informasi, .wizard-soal,
        .wizard-pengaturan) dan JavaScript yang sama
        (resources/js/quiz-tambah.js), supaya admin menyusun soal dengan alat
        yang persis sama seperti pemiliknya. Kalau formnya dibuat terpisah,
        pemeriksaannya, toolbar-nya, dan aturan gambar thumbnail-nya pasti
        akan menyimpang pada satu versi.

        Yang berbeda dari form pemilik ada empat, dan semuanya disengaja:

          1. Tujuan simpan dan tombol pulang: ke admin.quiz.update dan
             admin.quiz.show, bukan Karya Saya. Itu diteruskan lewat prop
             $batal pada wizard-navigasi dan wizard-soal.
          2. Tombol yang mengirim form lebih awal dari langkah "Buat Soal"
             berarti "simpan", bukan "simpan sebagai draft" — makanya teksnya
             dikirim lewat prop $teksDraft.
          3. Blok "Ajukan Persetujuan Admin" tidak dirender sama sekali
             (prop $admin pada wizard-pengaturan). Form milik pemilik punya
             blok itu karena revisinya wajib diinjau ulang; admin adalah pihak
             yang menyetujui, jadi quiznya tetap tayang setelah diperbarui
             (lihat Admin\QuizKelolaController::update).
          4. Sarana navigasi halaman: dipakai layout admin, jadi tidak ada
             padding negatif dan topbar yang disembunyikan seperti di form
             pemilik.

        Header halaman memakai kelas yang sama dengan halaman Edit Materi
        (.ad-hero-konten__ikon) supaya kedua form pengelolaan konten punya
        kepala yang sama.
    --}}

    @php
        /*
         * Isian awal dan langkah yang langsung dibuka, dihitung dengan
         * pemecah yang sama dengan form milik pengguna
         * (App\Support\IsianSoalQuiz) supaya quiz yang sama tidak punya dua
         * bentuk isian berbeda tergantung form mana yang membukanya.
         */
        $baris = old('soal', \App\Support\IsianSoalQuiz::baris($soal));
        $langkahAwal = \App\Support\IsianSoalQuiz::langkahAwal($errors);
    @endphp

    <div data-wizard-quiz data-langkah="{{ $langkahAwal }}" data-abjad="{{ \App\Support\KodeQuiz::ABJAD }}"
        data-panjang="{{ \App\Support\KodeQuiz::PANJANG }}">
        {{-- =========================
             HEAD HALAMAN
        ========================== --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                <span class="ad-hero-konten__ikon mt-0.5" aria-hidden="true">
                    <x-admin.ikon nama="pena" ukuran="w-6 h-6" />
                </span>

                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                        Edit Quiz
                    </h1>

                    <p class="mt-1 text-sm leading-relaxed text-dark/60">
                        Perbaiki isi quiz <strong class="font-bold text-dark">{{ $quiz->judul }}</strong>.
                        Quiz ini sudah tayang dan tetap tayang setelah disimpan.
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.quiz') }}" class="ad-tombol ad-tombol--garis shrink-0 self-start">
                <x-admin.ikon nama="panah-kiri" />

                Kembali ke Quiz
            </a>
        </div>

        {{-- =========================
             GALAT VALIDASI

             Ringkasan dari server. Field yang bermasalah sendiri juga diberi
             tanda merah oleh masing-masing komponen, blok ini hanya supaya
             galat tidak pernah hilang tanpa penjelasan. Langkah yang dibuka
             sudah menyesuaikan diri lewat $langkahAwal di bawah.
        ========================== --}}
        @if ($errors->any())
            <div class="ad-seksi ad-alert ad-alert--bahaya" role="alert">
                <span class="ad-alert__ikon" aria-hidden="true">
                    <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                </span>

                <div class="min-w-0">
                    <p class="font-semibold">Quiz belum bisa disimpan:</p>

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
        ==========================
             Satu <form> untuk ketiga langkah, jadi hanya ada satu tombol
             kirim, yaitu "Selesai & Simpan" di langkah terakhir. Isian
             langkah sebelumnya tetap utuh karena semuanya ada di dalam form
             yang sama dan tidak pernah di-unmount, cuma disembunyikan.
        ========================== --}}
        <form method="POST" action="{{ route('admin.quiz.update', $quiz) }}"
            enctype="multipart/form-data" class="ad-seksi" data-wizard-form novalidate>
            @csrf

            @method('PUT')

            {{-- =========================
                 LANGKAH 1 — INFORMASI DASAR
            ========================== --}}
            <x-quiz.wizard-informasi :kategori="$kategori" :quiz="$quiz" />

            {{-- =========================
                 LANGKAH 2 — BUAT SOAL
            ========================== --}}
            <x-quiz.wizard-soal :baris="$baris" :terlihat="$langkahAwal === 2"
                :batal="route('admin.quiz')" teks-draft="Simpan Perubahan" />

            {{-- =========================
                 LANGKAH 3 — PENGATURAN
            ==========================
                 :admin="true" mematikan blok "Ajukan Persetujuan Admin" —
                 admin adalah pihak yang menyetujui, jadi tidak ada yang perlu
                 diajukan, dan status quiz tidak pernah diturunkan ke "menunggu"
                 oleh simpanan ini. --}}
            <x-quiz.wizard-pengaturan :quiz="$quiz" :kode-awal="$kodeAwal"
                :terlihat="$langkahAwal === 3" :admin="true" />

            {{-- =========================
                 ISIAN SOAL
            ==========================
                 Satu input tersembunyi per soal dan per kolom, ditulis
                 ulang oleh quiz-builder.js setiap kali daftar soal berubah.
                 Satu-satunya sumber nomornya adalah urutan baris di
                 daftar, tidak ada nomor soal yang disimpan terpisah. --}}
            <div data-builder-input data-maksimal="{{ \App\Models\Soal::MAKSIMAL_PILIHAN }}" class="hidden"
                aria-hidden="true"></div>

            {{-- =========================
                 NAVIGASI
            ==========================
                 Dipakai langkah 1 dan 3. Selama langkah 2 terbuka seluruh
                 blok ini disembunyikan, karena langkah itu punya kartu
                 aksinya sendiri berisi Kembali, Batal, Simpan, dan Lanjut. --}}
            <div class="ad-seksi" data-wizard-nav>
                <x-quiz.wizard-navigasi :batal="route('admin.quiz')" />
            </div>
        </form>
    </div>
@endsection
