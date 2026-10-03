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
         PESAN

         Muncul di sini kalau admin baru saja menyimpan perubahan dari form
         edit, karena halaman detail adalah tujuan balik setelah simpan
         (Admin\MateriKelolaController::update). Tanpa blok ini flash-nya
         hilang begitu saja: redirect-nya memang ke sini, bukan ke daftar.
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
         KEPALA MATERI
    ====================== --}}
    <div class="ad-seksi">
        <x-materi.detail-kepala :detail="$detail" :simpan="false" />
    </div>

    {{--
        =====================
             ISI MATERI
        ======================

        Grid dan Daftar Isi ada di view terpisah
        (resources/views/admin/materi-detail-isi.blade.php) karena potongan yang
        sama dipakai lagi oleh pratinjau pada form Tambah/Edit Materi. Satu
        definisi, dua pemanggil — jadi pratinjau tidak mungkin terlihat seperti
        halaman ini sekarang lalu berbeda lagi nanti.
    --}}
    <div class="ad-seksi">
        @include('admin.materi-detail-isi')
    </div>
@endsection
