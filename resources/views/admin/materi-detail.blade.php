@extends('layouts.admin')

@section('title', $detail['judul'].' | KelasKita')

@section('content')
    {{--
        Detail materi di area admin.

        Yang di dalam area konten ini BUKAN versi admin dari materi. Semua
        elemen di bawah — kepala, Daftar Isi, kartu bab, isi materi, blok
        kode, dan navigasi Sebelumnya/Berikutnya — memakai komponen yang sama
        dengan halaman detail milik pengguna
        (resources/views/components/materi/detail-*.blade.php), dengan data
        dari pemecah yang sama (App\Support\DetailMateri). Jadi admin membaca
        judul, deskripsi, thumbnail, daftar bab, isi, dan kode persis seperti
        membacanya pengguna.

        Yang membedakan hanya kerangka: baris aksi di paling atas. Tombol
        Simpan disembunyikan lewat prop $simpan=false karena bookmark adalah
        urusan pembaca, dan Recommendasi "Materi lain" milik area pengguna
        tidak ikut karena bukan bagian dari isi materi.

        Layout admin tidak memakai kanvas penuh seperti halaman pengguna:
        sidebar dan topbar tetap terlihat supaya admin tidak kehilangan
        akses kembali ke panelnya.
    --}}

    {{--
        Tanpa JavaScript, resources/js/materi-detail.js tidak pernah
        membatasi satu bab per layar, jadi setiap seksi materi tetap tampil
        penuh. Atribut hidden dilepas di sini supaya isi materi tidak pernah
        hilang — persis seperti yang dilakukan <noscript> di layouts/app
        untuk halaman detail milik pengguna.
    --}}
    <noscript>
        <style>
            .materi-seksi[hidden] {
                display: block !important;
            }
        </style>
    </noscript>

    {{-- =====================
         BARIS AKSI ADMIN
         Berada di luar area konten materi, jadi tidak menyentuh isi yang
         harus identik dengan versi pengguna.

         Hanya ada tombol kembali. Tombol Edit Materi sengaja tidak ada di
         sini: perubahan materi disalakan dari menu tiga titik pada kartu di
         daftar Materi, dan di sana letaknya berdampingan dengan Hapus —
         jadi satu tempat untuk mengelola, bukan dua.

         Wrapper-nya tetap dipakai walau isinya cuma satu tautan: kelas
         justify-between dengan satu anak hasilnya sama dengan
         justify-start, jadi tautannya tetap di kiri seperti sebelumnya.
    ====================== --}}
    <div class="ad-seksi flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $detail['tautan_daftar'] }}" class="ad-tautan">
            <x-admin.ikon nama="panah-kiri" />

            Kembali ke Materi
        </a>
    </div>

    {{-- =====================
         KEPALA MATERI
    ====================== --}}
    <div class="ad-seksi">
        <x-materi.detail-kepala :detail="$detail" :simpan="false" />
    </div>

    {{--
        =====================
             ISI MATERI
        ======================

        Struktur grid dan syarat menampilkan Daftar Isi disalin apa adanya
        dari user/materi-detail.blade.php: proporsi 27 : 73, min-w-0 di
        kedua kolom supaya blok kode yang lebar tidak mendorong halaman, dan
        Daftar Isi baru muncul kalau materi punya lebih dari satu seksi.
    --}}
    @if (count($detail['seksi']) > 1)
        <div class="ad-seksi grid items-start gap-6 lg:grid-cols-[minmax(0,27fr)_minmax(0,73fr)]">

            <div class="min-w-0">
                <x-materi.detail-daftar-isi :seksi="$detail['seksi']" />
            </div>

            <div class="min-w-0">
                <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
            </div>
        </div>
    @else
        <div class="ad-seksi min-w-0">
            <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
        </div>
    @endif
@endsection
