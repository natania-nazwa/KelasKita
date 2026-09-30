@extends('layouts.admin')

@section('title', 'Edit '.$materi->nama.' | KelasKita')

@section('content')
    {{--
        Form edit materi untuk admin.

        Isian dan editornya bukan ditulis ulang di sini: form ini memakai
        komponen yang sama persis dengan halaman Tambah Materi milik pengguna
        (x-materi.informasi, x-materi.bab, x-materi.editor) dan JavaScript
        yang sama (resources/js/materi-tambah.js), supaya admin menulis dan
        menyusun bab dengan alat yang persis sama seperti pemiliknya. Kalau
        formnya dibuat terpisah, pemeriksaannya, toolbar-nya, dan aturan
        gambar thumbnail-nya pasti akan menyimpang pada satu versi.

        Yang berbeda dari form pemilik ada tiga, dan semuanya disengaja:

          1. Tujuan simpan dan tombol pulang: ke admin.materi.update dan
             admin.materi, bukan Karya Saya.
          2. Tidak ada tombol "Ajukan Persetujuan". Form milik pemilik punya
             tombol itu karena revisinya wajib diinjau ulang; admin adalah
             pihak yang menyetujui, jadi materinya tetap tayang setelah
             diperbarui (lihat Admin\MateriKelolaController::update).
          3. Tidak ada alasan penolakan / catatan pengajuan: dua-duanya milik
             alur pemilik menuju admin, dan di sini tidak ada yang menunggu
             keputusan.
    --}}

    <div data-tambah-materi>
        {{-- =========================
             HEAD HALAMAN
        ========================== --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                <span class="ad-hero-materi__ikon mt-0.5" aria-hidden="true">
                    <x-admin.ikon nama="pena" ukuran="w-6 h-6" />
                </span>

                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                        Edit Materi
                    </h1>

                    <p class="mt-1 text-sm leading-relaxed text-dark/60">
                        Perbaiki isi materi <strong class="font-bold text-dark">{{ $materi->nama }}</strong>.
                        Materi ini sudah tayang dan tetap tayang setelah disimpan.
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.materi') }}" class="ad-tombol ad-tombol--garis shrink-0 self-start">
                <x-admin.ikon nama="panah-kiri" />

                Kembali ke Materi
            </a>
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
        ==========================
             Satu kolom kartu, sama seperti halaman Tambah Materi. Panel
             Preview di dalam kartu Daftar Bab memakai x-materi.detail-kepala
             juga, jadi admin bisa melihat hasil akhirnya sebelum
             menyimpan.
        ========================== --}}
        <form id="form-tambah-materi" action="{{ route('admin.materi.update', $materi->slug) }}"
            method="POST" enctype="multipart/form-data" class="ad-seksi">
            @csrf

            @method('PUT')

            <div class="grid min-w-0 gap-4">
                <x-materi.informasi :kategori="$kategori" :materi="$materi" />

                <x-materi.bab :kategori="$kategori" :materi="$materi" />

                <x-materi.editor :isi="$materi->isi" />
            </div>

            {{-- Data yang disusun JavaScript sebelum form dikirim. --}}
            <input type="hidden" name="isi" value="{{ old('isi', $materi->isi) }}" data-input-isi>

            <input type="hidden" name="bab" value="{{ old('bab', json_encode($bab, JSON_UNESCAPED_UNICODE)) }}"
                data-input-bab>

            {{--
                Catatan pengajuan sengaja tidak pernah dikirim dari form ini.
                Field-nya milik alur pemilik, dan membiarkannya kosong membuat
                MateriIsianRequest::diajukanUlang() bernilai false, sehingga
                validasinya tidak pernah menolak penyimpanan dari admin.
            --}}

            <div class="ad-seksi flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.materi') }}" class="ad-tombol ad-tombol--garis justify-center">
                    Batal
                </a>

                <button type="submit" class="ad-tombol ad-tombol--utama justify-center">
                    <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" />

                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
