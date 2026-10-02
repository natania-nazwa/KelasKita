@extends('layouts.admin')

@section('title', ($quiz ? 'Edit' : 'Tambah').' Quiz | KelasKita')

@section('content')
{{--
        /*
         * Form quiz untuk admin, sekaligus untuk tambah dan untuk edit.
         *
         * Tiga tahapnya sama persis dengan form Quiz milik pengguna
         * (user/quiz-tambah.blade.php): Informasi Dasar, Buat Soal, lalu
         * Pengaturan. Form ini memakai komponen wizard yang sama
         * (x-quiz.stepper, .wizard-informasi, .wizard-soal, .wizard-pengaturan,
         * .wizard-navigasi) dan JavaScript yang sama
         * (resources/js/quiz-tambah.js, resources/js/quiz-builder.js),
         * termasuk pratinjau thumbnail, pratinjau gambar di dalam editor, dan
         * pemeriksaan isian per langkah. Kalau formnya dibuat terpisah, aturan
         * gambar thumbnail-nya dan pemeriksaannya pasti akan menyimpang pada
         * satu versi.
         *
         * Yang berbeda dari form pemilik, semuanya soal siapa yang
         * menerbitkan:
         *
         *   1. Blok "Ajukan Persetujuan Admin" tidak dirender sama sekali
         *      (prop $admin pada x-quiz.wizard-pengaturan). Admin adalah pihak
         *      yang menerbitkan, jadi tidak ada yang perlu diajukan ke siapa
         *      pun — dan status quiz tidak pernah diturunkan ke "menunggu".
         *   2. Baris tombol langkah terakhir tidak memakai "Selesai & Simpan"
         *      milik form pemilik, melainkan Simpan Draft dan Publish Sekarang
         *      (prop $admin pada x-quiz.wizard-navigasi). Status konten di
         *      ruang kerja ini hanya "draft" dan "published", jadi kedua arah
         *      itu harus bisa dipilih dari form.
         *
         * ISIANNYA tidak berbeda sama sekali dari form pemilik: tidak ada
         * field admin saja. Field yang dulu hanya ada di sini (kelas tujuan)
         * sudah dihapus dari seluruh aplikasi.
         *
         * Karena tidak ada kode yang perlu dibagikan, cara publikasi juga
         * tidak bisa dipilih di sini: komponen x-quiz.wizard-pengaturan
         * mengirim "public" sebagai input tersembunyi (prop $admin), dan
         * controller memaksa nilainya lagi saat menyimpan.
         */
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

    <div data-wizard-quiz data-langkah="{{ $langkahAwal }}"
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
             Tiga tahap yang sama dengan form Quiz milik pengguna. --}}
        <div class="ad-seksi">
            <x-quiz.stepper :aktif="$langkahAwal" />
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
             Satu <form> untuk ketiga tahap, jadi hanya ada satu tombol kirim.
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
                /*
                 * Niat simpan. Nilainya diisi wizard (resources/js/quiz-tambah.js)
                 * tepat sebelum form dikirim: "draft" dari tombol Simpan Draft,
                 * "publish" dari tombol Publish Sekarang.
                 *
                 * Sengaja input tersembunyi, bukan name pada tombol: tombolnya
                 * bertipe button supaya pengirimannya bisa diperiksa langkah demi
                 * langkah lebih dulu, dan name-nya tidak ikut terkirim kalau
                 * tombolnya diklik lewat keyboard tanpa masuk ke sana.
                 */
            --}}
            <input type="hidden" name="aksi" value="draft" data-konten-aksi>

            {{-- =========================
                 TAHAP 1 — INFORMASI DASAR
            ==========================
                 Komponen yang sama dengan form pemilik, tanpa isian tambahan
                 apa pun.
            ========================== --}}
            <x-quiz.wizard-informasi :kategori="$kategori" :quiz="$quiz" />

            {{-- =========================
                 TAHAP 2 — BUAT SOAL
            ==========================
                 Builder-nya sama persis dengan form pemilik, termasuk lima
                 tipe soal, pilihan dinamis, pembahasan, dan tingkat kesulitan per
                 soal. Yang berubah hanya teks tombol yang mengirim form lebih
                 awal: "Draft" menjadi "Simpan Draft", karena di ruang kerja
                 admin setiap isian bisa langsung disimpan sebagai draft.
            ========================== --}}
            <x-quiz.wizard-soal :baris="$baris" :terlihat="$langkahAwal === 2"
                :batal="route('admin.konten', ['tab' => 'quiz'])" teks-draft="Simpan Draft" />

            {{-- =========================
                 TAHAP 3 — PENGATURAN
            ==========================
                 :admin="true" mematikan dua isian milik alur pemilik: blok
                 "Ajukan Persetujuan Admin" (admin adalah pihak yang menyetujui,
                 jadi tidak ada yang perlu diajukan dan status quiz tidak pernah
                 diturunkan ke "menunggu") serta pilihan cara publikasi dan kolom
                 kode aksesnya. Yang tersisa di sini sama dengan form pemilik:
                 durasi, saklar "Tampilkan Jawaban Setelah Selesai", dan
                 ringkasan isi quiz sebelum disimpan.
            ========================== --}}
            <x-quiz.wizard-pengaturan :quiz="$quiz" :kode-awal="$kodeAwal"
                :terlihat="$langkahAwal === 3" :admin="true" />

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
                 Dipakai tahap 1 dan 3. Selama tahap 2 terbuka seluruh blok ini
                 disembunyikan, karena tahap itu punya kartu aksinya sendiri
                 berisi Kembali, Batal, Simpan Draft, dan Lanjut.

                 :admin="true" mengganti tombol "Selesai & Simpan" milik form
                 pemilik dengan Simpan Draft dan Publish Sekarang. Keduanya
                 hanya muncul di tahap terakhir, dan Publish Sekarang memakai
                 tombol utama karena menerbitkannya memang tujuan akhir dari
                 form ini.
            ========================== --}}
            <div class="ad-seksi" data-wizard-nav>
                <x-quiz.wizard-navigasi :batal="route('admin.konten', ['tab' => 'quiz'])" :admin="true" />
            </div>
        </form>
    </div>

    {{--
        Dialog konfirmasi terbitan. Dipakai tombol "Publish Sekarang" di baris
        navigasi tahap 3. Wizard mendaftarkan funcinya di
        window.kelasKitaKontenKirim (lihat resources/js/quiz-tambah.js), dan
        dialog ini memanggilnya.
    --}}
    <x-admin.dialog-terbit />

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />
@endsection
