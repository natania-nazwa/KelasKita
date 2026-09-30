@extends('layouts.admin')

@section('title', $quiz->judul.' | KelasKita')

@section('content')
    {{--
        Detail quiz di area admin.

        Yang di dalam area konten ini BUKAN versi admin dari quiz. Semua
        elemen di bawah — kartu utama, kartu informasi, dan Daftar Soal —
        memakai komponen yang sama dengan halaman detail milik pengguna
        (resources/views/components/quiz/detail-*.blade.php), dengan data
        dari pemecah yang sama (App\Support\DaftarQuiz dan DaftarSoal). Jadi
        admin membaca judul, deskripsi, thumbnail, metadata, dan soal-soalnya
        persis seperti membacanya pengguna, termasuk hal yang sama: kunci
        jawaban dan pembahasan tidak ikut tampil, karena keduanya memang belum
        boleh bocor sebelum quiz dikerjakan.

        Yang membedakan hanya kerangka: baris aksi di paling atas, dan
        tombol-tombol milik pembaca. "Mulai Quiz", "Bagikan", dan form buka
        sesi disembunyikan lewat prop $aksi=false, karena ketiganya adalah
        milik orang yang akan mengerjakan quiz — bukan milik admin yang sedang
        memeriksa isinya. Blok "Ajukan Persetujuan" juga tidak ikut: halaman
        ini bukan tempat memutuskan apa pun, keputusan itu ada di menu
        Verifikasi.

        Layout admin tidak memakai kanvas penuh seperti halaman pengguna:
        sidebar dan topbar tetap terlihat supaya admin tidak kehilangan akses
        kembali ke panelnya.
    --}}

    {{-- =====================
         BARIS AKSI ADMIN
         Berada di luar area konten quiz, jadi tidak menyentuh isi yang
         harus identik dengan versi pengguna.
    ====================== --}}
    <div class="ad-seksi flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.quiz') }}" class="ad-tautan">
            <x-admin.ikon nama="panah-kiri" />

            Kembali ke Quiz
        </a>

        <a href="{{ $tautanEdit }}" class="ad-tombol ad-tombol--halus">
            <x-admin.ikon nama="pena" />

            Edit Quiz
        </a>
    </div>

    {{-- =====================
         PESAN

         Muncul di sini kalau admin baru saja menyimpan perubahan dari form
         edit, karena halaman detail adalah tujuan balik setelah simpan.
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
         KEPALA QUIZ
    ====================== --}}
    <div class="ad-seksi grid min-w-0 items-stretch gap-4 sm:gap-5 lg:grid-cols-[minmax(0,68fr)_minmax(0,32fr)]">
        <x-quiz.detail-kartu :kartu="$kartu" :jumlah-soal="$jumlahSoal" :aksi="false" />

        <x-quiz.detail-informasi :kartu="$kartu" :jumlah-soal="$jumlahSoal" />
    </div>

    {{--
        =====================
             DAFTAR SOAL
        ======================
        Tanpa prop $tautan: tautan "Lihat semua" milik halaman pengguna, dan
        admin/quiz disaring published-only tanpa kategori yang selalu
        tersedia, jadi tidak ada halaman daftar yang bisa ditunjuknya.
    --}}
    <div class="ad-seksi">
        <x-quiz.detail-daftar-soal :soal="$soal" />
    </div>
@endsection
