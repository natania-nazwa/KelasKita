@extends('layouts.app')

@section('title', 'Jadwal | KelasKita')

@section('content')
    {{--
        Halaman Jadwal: tujuan tombol "Lihat Semua" di panel "Jadwal
        Hari Ini" milik dashboard.

        Latar halaman memakai .kanvas-jadwal, padanan .kanvas-halaman yang
        warnanya digeser ke ungu soft. Alasannya: kartu di dalam halaman ini
        sudah berwarna putih penuh, jadi latar halaman tidak boleh putih
        juga, kalau tidak batas kartunya hilang.

        Desktop (xl ke atas): dua kolom, daftar pelajaran di kiri dan
        ringkasan + kalender di kanan.
        Tablet dan mobile: sidebar turun ke bawah, jadi urutan membacanya
        tetap banner -> daftar -> ringkasan -> kalender.

        min-h-[100dvh], bukan 100dvh dikurangi 4rem seperti halaman lain:
        halaman ini tidak memakai top bar, jadi tinggi yang tersedia
        memang satu layar penuh.
    --}}
    <div class="kanvas-jadwal -m-6 min-h-[100dvh] p-5 sm:p-6 lg:-m-10 lg:p-6 xl:p-8">
        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">

            {{-- ======================= KOLOM UTAMA ======================= --}}
            <div class="min-w-0 [&>section:first-child]:mt-0">

                {{-- Kepala halaman: tanggal, angka ringkas, dan strip
                     tujuh hari. --}}
                <x-jadwal.kepala :ringkasan="$ringkasan" :minggu="$minggu" />

                {{-- Panel daftar pelajaran. --}}
                <section class="jadwal-panel mt-4">

                    <header class="jadwal-panel__kepala">
                        <div class="jadwal-panel__kepala-judul">
                            <span class="jadwal-panel__ikon" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                                </svg>
                            </span>

                            <h2 class="jadwal-panel__judul">
                                {{ $ringkasan['nama_hari'] }}, {{ $ringkasan['tanggal_label'] }}
                            </h2>

                            <span class="jadwal-panel__lencana">{{ $ringkasan['jumlah'] }} pelajaran</span>
                        </div>

                        <div class="jadwal-panel__aksi">
                            {{-- Pencarian + filter pelajaran. Satu form GET,
                                 tanggal yang sedang dibaca ikut dibawa sebagai
                                 hidden supaya tidak memantul ke hari ini. --}}
                            <x-jadwal.cari :tanggal="$tanggal" :kategori="$kategori"
                                :kategori-aktif="$kategoriAktif" :kata-kunci="$kataKunci" />

                            <x-jadwal.tambah :tanggal="$tanggal" />
                        </div>
                    </header>

                    <div class="jadwal-panel__badan">
                        <x-jadwal.daftar :daftar="$jadwal"
                            :alasan-kosong="$alasanKosong"
                            :tanggal="$tanggal" :kategori="$kategoriAktif" :kata-kunci="$kataKunci" />

                        @if ($ringkasan['sedang'] !== null)
                            <p class="jadwal-panel__catatan">
                                Kelas sedang berlangsung. Selesaikan tugasnya, lalu cek lagi setelah jam pelajaran berikutnya.
                            </p>
                        @endif
                    </div>
                </section>
            </div>

            {{-- ======================= SIDEBAR =========================== --}}
            <aside class="grid min-w-0 items-start gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-1">
                <x-jadwal.ringkasan :ringkasan="$ringkasan" />

                <x-jadwal.kalender :kalender="$kalender" :tanggal="$tanggal" />
            </aside>
        </div>
    </div>

    {{-- Pesan sukses dari tambah, ubah, atau hapus jadwal. --}}
    @if (session('sukses'))
        <div role="status"
            class="jadwal-sukses">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>

            <span>{{ session('sukses') }}</span>
        </div>
    @endif

    {{-- Dialog konfirmasi hapus jadwal (dikerjakan initKonfirmasi() di
         resources/js/app.js). --}}
    <x-app.konfirmasi />
@endsection
