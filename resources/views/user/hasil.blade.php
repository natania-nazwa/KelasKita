@extends('layouts.app')

@section('title', ($quiz !== null ? 'Hasil ' . $quiz->judul : 'Hasil') . ' | KelasKita')

@section('content')
    {{--
        Layout dashboard hasil quiz.

        Desktop (xl dan lebih lebar): dua kolom, konten utama di kiri
        dan sidebar statistik di kanan. Sidebar dibatasi 19rem supaya
        tidak terlalu lebar, jadi konten utama tetap dapat bagiannya
        yang lebih besar.

        Tablet: sidebar turun ke bawah konten utama. Kolom statistik
        jadi dua kolom dulu, baru empat di layar besar.

        Mobile: satu kolom, urutannya banner -> statistik -> riwayat ->
        statistik belajar -> quiz terpopuler -> motivasi. min-w-0
        dipakai di kedua kolom supaya tidak ada scroll horizontal.
    --}}
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-5 sm:p-6 lg:-m-10 lg:p-6 xl:p-8">
        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_19rem]">

            {{-- ======================= KOLOM UTAMA ======================= --}}
            <div class="min-w-0">

                <x-hasil.kepala :quiz="$quiz" />

                {{-- Empat kartu statistik. Selalu dirender: kalau belum
                     ada hasil, angkanya 0 sesuai database, bukan data
                     dummy. --}}
                <x-hasil.statistik :kartu="$ringkasan['kartu']" class="mt-4" />

                {{-- Riwayat hasil quiz. --}}
                <section class="hasil-panel mt-4">

                    <header class="hasil-panel__kepala hasil-panel__kepala--riwayat">
                        <div class="hasil-panel__kepala-judul">
                            <span class="hasil-panel__ikon" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('dokumen') }}" />
                                </svg>
                            </span>

                            <h2 class="hasil-panel__judul">Riwayat Hasil Quiz</h2>

                            <span class="hasil-panel__lencana">{{ $paginasi->total() }} hasil</span>
                        </div>

                        {{-- Pencarian + sorting di kanan header. Keduanya
                             satu form GET, jadi status aktif tidak perlu
                             ikut dibawa. --}}
                        <x-hasil.pencarian :status-aktif="$statusAktif" :daftar-urutan="$daftarUrutan"
                            :urut-aktif="$urutAktif" :kata-kunci="$kataKunci"
                            :aksi="$quiz !== null ? 'user.hasil.daftar' : 'user.hasil'"
                            :param="$quiz !== null ? ['quiz' => $quiz->getKey()] : []" />
                    </header>

                    <div class="hasil-panel__badan">
                        {{-- Tab filter status, melintang penuh di bawah
                             header. --}}
                        <x-hasil.tab :status-aktif="$statusAktif" :jumlah-status="$jumlahStatus"
                            :kata-kunci="$kataKunci" :urut-aktif="$urutAktif"
                            :aksi="$quiz !== null ? 'user.hasil.daftar' : 'user.hasil'"
                            :param="$quiz !== null ? ['quiz' => $quiz->getKey()] : []" />

                        <x-hasil.riwayat :daftar="$riwayat" :kosong="! $pernahMengerjakan"
                            :aksi="$quiz !== null ? 'user.hasil.daftar' : 'user.hasil'"
                            :param="$quiz !== null ? ['quiz' => $quiz->getKey()] : []" />

                        @if ($paginasi->hasPages())
                            <div class="mt-5">
                                {{ $paginasi->links() }}
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            {{-- ======================= SIDEBAR =========================== --}}
            <aside class="grid min-w-0 items-start gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-1">
                <x-hasil.ringkasan :ringkasan="$ringkasan" class="{{ $pernahMengerjakan ? '' : 'sm:col-span-2 xl:col-span-1' }}" />

                <x-hasil.terpopuler :daftar="$ringkasan['terpopuler']" class="{{ $pernahMengerjakan ? '' : 'sm:col-span-2 xl:col-span-1' }}" />

                <x-hasil.motivasi class="{{ $pernahMengerjakan ? '' : 'sm:col-span-2 xl:col-span-1' }}" />
            </aside>
        </div>
    </div>
@endsection
