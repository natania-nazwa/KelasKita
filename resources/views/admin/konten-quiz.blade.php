@extends('layouts.admin')

@section('title', ($quiz ? 'Edit' : 'Tambah').' Quiz | KelasKita')

@section('content')
    {{--
        Form quiz untuk admin, sekaligus untuk tambah dan untuk edit.

        Dua tahap saja: Informasi Dasar, lalu Buat Soal. Yang tidak ikut
        adalah tahap "Pengaturan" milik form pemilik, karena isinya dua hal
        yang sama-sama tidak berlaku di sini: memakai kode (tidak ada kode
        yang perlu dibagikan dari konten yang tayang untuk semua) dan saklar
        "Ajukan Persetujuan" (tidak ada persetujuan).

        Semua isian dan builder soalnya bukan ditulis ulang. Form ini memakai
        komponen wizard yang sama dengan form Quiz milik pengguna
        (x-quiz.wizard-informasi, .wizard-soal) dan JavaScript yang sama
        (resources/js/quiz-tambah.js, resources/js/quiz-builder.js), termasuk
        pratinjau thumbnail dan pratinjau gambar di dalam editor. Kalau
        formnya dibuat terpisah, aturan gambar thumbnail-nya dan pemeriksaannya
        pasti akan menyimpang pada satu versi.

        Yang berbeda dari form pemilik:

          1. Jumlah tahap. resources/js/quiz-tambah.js membaca jumlah tahap
             dari atribut data-langkah-total, jadi yang berubah hanya angka
             itu, bukan logikanya.
          2. Visibilitasnya dipaksa "public" dan dikirim sebagai input
             tersembunyi, supaya aturan validasi yang sama dengan form
             pemilik tetap berlaku utuh tanpa perlu menolaknya di lapisan
             lain.
          3. Baris tombol di langkah "Buat Soal" berarti Simpan Draft dan
             Publish Sekarang, bukan Draft dan Lanjut ke Pengaturan. Itu
             diteruskan lewat prop $admin pada x-quiz.wizard-soal.
          4. Tidak ada tombol hapus thumbnail. Berkas lama justru dibuang di
             controller hanya kalau ada berkas baru yang menggantikannya,
             supaya gambar yang sedang dipakai tidak pernah hilang karena satu
             klik.

        Tanpa JavaScript tahap kedua tetap terlihat (atribut hidden-nya
        dilepas di <noscript> di bawah) dan "Simpan Draft" tetap mengirim form
        apa adanya, jadi isi form tidak pernah terkunci.
    --}}

    @php
        $quiz = $quiz ?? null;
        $modeEdit = $quiz !== null;

        /*
         * Isian awal dan tahap yang langsung dibuka, dihitung dengan pemecah
         * yang sama dengan form milik pengguna (App\Support\IsianSoalQuiz)
         * supaya quiz yang sama tidak punya dua bentuk isian berbeda
         * tergantung form mana yang membukanya.
         */
        $baris = old('soal', \App\Support\IsianSoalQuiz::baris($modeEdit ? $soal : null));
        $langkahAwal = \App\Support\IsianSoalQuiz::langkahAwal($errors);
    @endphp

    <div data-wizard-quiz data-langkah="{{ $langkahAwal }}" data-langkah-total="2"
        data-abjad="{{ \App\Support\KodeQuiz::ABJAD }}" data-panjang="{{ \App\Support\KodeQuiz::PANJANG }}">
        {{-- =========================
             HEAD HALAMAN
        ========================== --}}
        <header class="ad-seksi flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                <span class="ad-hero-konten__ikon mt-0.5" aria-hidden="true">
                    <x-admin.ikon nama="pena" ukuran="w-6 h-6" />
                </span>

                <div class="min-w-0">
                    <h1 class="ad-hero-konten__judul">{{ $modeEdit ? 'Edit Quiz' : 'Tambah Quiz' }}</h1>

                    <p class="mt-1 text-sm leading-relaxed text-dark/60" data-wizard-subjudul>
                        @if ($modeEdit)
                            Perbaiki isi quiz <strong class="font-bold text-dark">{{ $quiz->judul }}</strong>.
                        @else
                            Tentukan informasi dasar untuk quiz yang akan kamu buat.
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.konten', ['tab' => 'quiz']) }}"
                class="ad-tombol ad-tombol--garis shrink-0 self-start">
                <x-admin.ikon nama="panah-kiri" />

                Kembali
            </a>
        </header>

        {{-- =========================
             STEP PER
        ==========================
             Bentuknya sama persis dengan wizard quiz, hanya jumlah tahapnya
             dua: Informasi Dasar, lalu Buat Soal. --}}
        <div class="ad-seksi">
            <x-quiz.stepper :aktif="$langkahAwal" :langkah="[
                ['nama' => 'Informasi Dasar', 'keterangan' => 'Judul, kategori, durasi'],
                ['nama' => 'Buat Soal', 'keterangan' => 'Soal dan jawaban benar'],
            ]" />
        </div>

        {{-- Status quiz saat ini, supaya admin tahu apakah soal yang
             diperbaiki sudah tayang atau masih berupa draft. --}}
        @if ($modeEdit)
            <p class="ad-seksi ad-teks-2" data-wizard-status>
                Status saat ini: <strong class="font-bold text-dark">{{ $quiz->labelStatus() }}</strong>
            </p>
        @endif

        {{-- =========================
             GALAT VALIDASI
        ==========================
             Ringkasan dari server. Field yang bermasalah sendiri juga diberi
             tanda merah oleh masing-masing komponen; blok ini hanya supaya
             galat tidak pernah hilang tanpa penjelasan. Langkah yang dibuka
             sudah menyesuaikan diri lewat $langkahAwal di atas.
        ========================== --}}
        @if ($errors->any())
            <div class="ad-seksi ad-alert ad-alert--bahaya" role="alert" data-wizard-galat-kotak>
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
             Satu <form> untuk kedua tahap, jadi hanya ada satu tombol kirim.
             Isian tahap sebelumnya tetap utuh karena semuanya ada di dalam
             form yang sama dan tidak pernah di-unmount, cuma disembunyikan.
        ========================== --}}
        <form method="POST" enctype="multipart/form-data" class="ad-seksi" data-wizard-form novalidate
            action="{{ $modeEdit ? route('admin.konten.quiz.update', $quiz) : route('admin.konten.quiz.tambah.store') }}">
            @csrf

            @if ($modeEdit)
                @method('PUT')
            @endif

            {{--
                Konten dari form ini selalu tayang untuk semua pengguna, jadi
                visibilitasnya dikunci di "public". Field tetap dikirim supaya
                aturan validasi QuizIsianRequest — yang sama dengan form
                pemilik — tetap dijalankan utuh, termasuk aturan kode akses
                yang hanya berlaku untuk quiz mode kode.
            --}}
            <input type="hidden" name="visibilitas" value="{{ \App\Models\Quiz::VISIBILITAS_PUBLIK }}">

            {{--
                Niat simpan. Nilainya diisi wizard (resources/js/quiz-tambah.js)
                tepat sebelum form dikirim: "draft" dari tombol Simpan Draft,
                "publish" dari tombol Publish Sekarang.

                Sengaja input tersembunyi, bukan name pada tombol: tombolnya
                bertipe button supaya pengirimannya bisa diperiksa langkah demi
                langkah lebih dulu, dan name-nya tidak ikut terkirim kalau
                tombolnya diklik lewat keyboard tanpa masuk ke sana.
            --}}
            <input type="hidden" name="aksi" value="draft" data-konten-aksi>

            {{-- =========================
                 TAHAP 1 — INFORMASI DASAR
            ==========================
                 Komponen yang sama dengan form pemilik, ditambah durasi yang
                 di form pemilik diletakkan di langkah Pengaturan. Di sini tidak
                 ada langkah Pengaturan, jadi durasi ikut masuk ke tahap pertama
                 supaya tidak ada kolom yang hilang.
            ========================== --}}
            <x-quiz.wizard-informasi :kategori="$kategori" :quiz="$quiz" :durasi="true" />

            {{--
                Kelas tujuan, di panel terpisah setelah Informasi Dasar.

                Bukan field di dalam x-quiz.wizard-informasi: komponen itu
                dipakai bersama dengan form Quiz milik pengguna, dan halaman
                pengguna tidak punya konsep kelas tujuan. Memasangnya di sini
                membuat form admin berbeda dari form pemilik tanpa mengubah
                isi isian yang memang harus sama.
            --}}
            <div class="kartu-form mt-5 overflow-hidden">
                <header class="kartu-form__kepala">
                    <span class="kartu-form__ikon" aria-hidden="true">
                        <x-admin.ikon nama="grup" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="kartu-form__judul">Kelas Tujuan</h2>
                </header>

                <div class="kartu-form__badan">
                    <x-admin.konten-kelas field="kelas" :pilihan="$kelas" :nilai="$quiz?->kelas" />
                </div>
            </div>

            {{-- =========================
                 TAHAP 2 — BUAT SOAL
            ==========================
                 Builder-nya sama persis dengan form pemilik, termasuk lima
                 tipe soal, pilihan dinamis, pembahasan, dan tingkat kesulitan per
                 soal. Yang berubah hanya baris aksinya: Simpan Draft dan Publish
                 Sekarang menggantikan Draft dan Lanjut ke Pengaturan (prop
                 $admin).
            ========================== --}}
            <x-quiz.wizard-soal :baris="$baris" :terlihat="$langkahAwal === 2"
                :batal="route('admin.konten', ['tab' => 'quiz'])" :admin="true" />

            {{-- =========================
                 ISIAN SOAL
            ==========================
                 Satu input tersembunyi per soal dan per kolom, ditulis ulang
                 oleh quiz-builder.js setiap kali daftar soal berubah.
                 Satu-satunya sumber nomornya adalah urutan baris di daftar,
                 tidak ada nomor soal yang disimpan terpisah.
            ========================== --}}
            <div data-builder-input data-maksimal="{{ \App\Models\Soal::MAKSIMAL_PILIHAN }}" class="hidden"
                aria-hidden="true"></div>

            {{-- =========================
                 NAVIGASI
            ==========================
                 Dipakai tahap 1. Selama tahap 2 terbuka seluruh blok ini
                 disembunyikan, karena tahap itu punya kartu aksinya sendiri
                 berisi Kembali, Batal, Simpan Draft, dan Publish Sekarang.
            ========================== --}}
            <div class="ad-seksi" data-wizard-nav>
                <x-quiz.wizard-navigasi :batal="route('admin.konten', ['tab' => 'quiz'])" />
            </div>
        </form>
    </div>

    {{--
        Dialog konfirmasi terbitan. Dipakai tombol "Publish Sekarang" di baris
        aksi tahap 2. Wizard mendaftarkan funcinya di
        window.kelasKitaKontenKirim (lihat resources/js/quiz-tambah.js), dan
        dialog ini memanggilnya.
    --}}
    <x-admin.dialog-terbitkan />

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />
@endsection
