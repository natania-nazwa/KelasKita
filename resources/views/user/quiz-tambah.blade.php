@extends('layouts.app')

@section('title', ($quiz ?? null ? 'Edit' : 'Buat').' Quiz | KelasKita')

@section('content')
    {{--
        Wizard "Buat Quiz" sekaligus "Edit Quiz".

        Tiga langkahnya: Informasi Dasar, Buat Soal, lalu Pengaturan.
        Ketiganya adalah satu <form> supaya hanya ada satu tombol kirim,
        yaitu "Selesai & Simpan" di langkah terakhir. Isian langkah
        sebelumnya tetap utuh karena semuanya ada di dalam form yang sama
        dan tidak pernah di-unmount, cuma disembunyikan.

        resources/js/quiz-tambah.js yang menjalankan seluruhnya:
        berpindah langkah, menambah / mengubah / menghapus / mengurutkan
        soal, pratinjau thumbnail, membuat kode, dan saklar jawaban.
        Tanpa JavaScript, langkah pertama tetap bisa diisi dan dikirim
        (server akan menolak karena belum ada soal), jadi halaman tidak
        pernah rusak.
    --}}
    @php
        $quiz = $quiz ?? null;
        $modeEdit = $quiz !== null;
        $soal = $soal ?? collect();

        /*
         * Isian awal tiap langkah.
         *
         * old() selalu menang supaya submit yang gagal validasi tidak
         * membiarkan isian yang sudah diklik pengguna hilang. Setelah
         * itu: data quiz yang sedang diedit, dan untuk quiz baru
         * koleksi kosong.
         *
         * "langkahAwal" menentukan langkah mana yang langsung dibuka
         * setelah validasi gagal. Tanpa ini validate selalu melempar ke
         * langkah pertama walau masalahnya ada di Pengaturan.
         */
        $baris = old(
            'soal',
            $modeEdit
                ? $soal->map(fn ($s) => [
                    'pertanyaan' => $s->pertanyaan,
                    'tipe' => $s->tipe(),
                    'pilihan' => array_values(array_map(
                        fn ($huruf, $teks) => ['huruf' => $huruf, 'teks' => $teks],
                        array_keys($s->pilihan()),
                        array_values($s->pilihan()),
                    )),
                    'benar' => $s->hurufBenar(),
                    'kunciTeks' => $s->kunciTeks(),
                    'ceklis' => $s->tococok_persis,
                    'pembahasan' => (string) $s->pembahasan,
                    'tingkat' => $s->tingkat_kesulitan ?? \App\Models\Quiz::TINGKAT_MUDAH,
                ])->all()
                : []
        );

        /*
         * Langkah tempat validasi server berhenti. Kalau galatnya milik
         * soal, buka langkah 2; selain itu buka langkah 1 karena itu
         * tempat semua isian lainnya dikumpulkan.
         */
        $langkahAwal = $errors->has('soal') || collect($errors->keys())->contains(
            fn ($kunci) => str_starts_with((string) $kunci, 'soal.')
        ) ? 2 : 1;
    @endphp

    <div data-wizard-quiz data-langkah="{{ $langkahAwal }}" data-abjad="{{ \App\Support\KodeQuiz::ABJAD }}"
        data-panjang="{{ \App\Support\KodeQuiz::PANJANG }}"
        class="kanvas-halaman -m-6 min-h-[100dvh] p-6 pb-28 lg:-m-10 lg:p-10 lg:pb-28">

        {{-- =========================
             HEADER HALAMAN
        ==========================
             Breadcrumb "Home / Karya Saya / Buat Quiz" tidak dipakai lagi:
             top bar halaman ini sudah disembunyikan (lihat $sembunyiTopbar
             di layouts/app), jadi "Kembali" menggantikan cara pulang biasa —
             tombolnya duduk di kanan atas, lurus dengan judul. --}}
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                    {{ $modeEdit ? 'Edit Quiz' : 'Buat Quiz Baru' }}
                </h1>

                <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-muted" data-wizard-subjudul>
                    Tentukan informasi dasar untuk kuis yang akan kamu buat.
                </p>
            </div>

            <a href="{{ route('user.karya-saya', ['tab' => 'quiz']) }}"
                class="tombol-garis ml-auto shrink-0 self-start py-2">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>

                Kembali
            </a>
        </header>

        @if ($modeEdit)
            {{-- Status quiz saat ini, supaya pemilik tahu apakah soal yang
                 diperbaiki sudah tayang atau masih menunggu admin. --}}
            <p class="karya-status karya-status--{{ $quiz->warnaStatus() }} mt-3" data-wizard-status>
                <span class="karya-status__titik" aria-hidden="true"></span>
                Status saat ini: {{ $quiz->labelStatus() }}
            </p>
        @endif

        {{-- =========================
             STEPPER
        ========================== --}}
        <div class="mt-5">
            <x-quiz.stepper :aktif="$langkahAwal" />
        </div>

        {{-- Ringkasan galat dari server. Field yang bermasalah sendiri juga
             diberi tanda merah oleh masing-masing komponen, blok ini hanya
             supaya galat tidak pernah hilang tanpa penjelasan. --}}
        @if ($errors->any())
            <div role="alert" class="galat-kotak mt-5" data-wizard-galat-kotak>
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>

                <div class="min-w-0">
                    <p class="font-bold">Periksa lagi isianmu:</p>

                    <ul class="mt-1 list-disc space-y-0.5 pl-4">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" class="mt-5" data-wizard-form
            action="{{ $modeEdit ? route('user.quiz.update', $quiz) : route('user.quiz.tambah.store') }}"
            novalidate>
            @csrf

            @if ($modeEdit)
                @method('PUT')
            @endif

            {{-- =========================
                 LANGKAH 1 — INFORMASI DASAR
            ========================== --}}
            <x-quiz.wizard-informasi :kategori="$kategori" :quiz="$quiz" />

            {{-- =========================
                 LANGKAH 2 — BUAT SOAL
            ========================== --}}
            <x-quiz.wizard-soal :baris="$baris" :terlihat="$langkahAwal === 2" />

            {{-- =========================
                 LANGKAH 3 — PENGATURAN
            ========================== --}}
            <x-quiz.wizard-pengaturan :quiz="$quiz" :kode-awal="$kodeAwal"
                :terlihat="$langkahAwal === 3" />

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
                 aksinya sendiri berisi Kembali, Batal, Draft, dan Lanjut. --}}
            <div class="mt-5" data-wizard-nav>
                <x-quiz.wizard-navigasi />
            </div>
        </form>
    </div>
@endsection
