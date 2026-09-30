@extends('layouts.admin')

@section('title', 'Materi | KelasKita')

@section('content')
    {{--
        Halaman "Materi": papan admin untuk seluruh materi, dari semua status.

        Berbeda dengan halaman Verifikasi, di sini tidak ada tombol setujui
        atau tolak. Fungsinya memantau: berapa materi tayang, menunggu,
        ditolak, dan draft; lalu membuka isi materi apa pun lewat "Lihat".
    --}}

    {{-- =====================
         SPANDUK ATAS
    ====================== --}}
    <section class="ad-hero">
        <div class="ad-hero__susun">
            <div class="ad-hero__teks">
                <span class="ad-hero__lencana">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('daftar-cek') }}" />
                    </svg>

                    Manajemen konten
                </span>

                <h1 class="ad-hero__judul">Kelola Semua Materi</h1>

                <p class="ad-hero__sub">
                    Pantau semua materi dari satu tempat — mana yang sudah tayang,
                    mana yang masih menunggu keputusanmu, dan bagi yang ditolak,
                    pemiliknya perlu menyempurnakan ulang.
                </p>

                <div class="ad-hero__aksi">
                    <a href="{{ route('admin.verifikasi') }}" class="ad-tombol ad-tombol--garis">
                        <x-admin.ikon nama="daftar-cek" />

                        Buka Verifikasi
                    </a>
                </div>
            </div>

            <x-admin.ilustrasi-buku class="ad-hero__ilustrasi" />
        </div>
    </section>

    {{-- =====================
         RINGKASAN JUMLAH
    ====================== --}}
    <div class="ad-seksi ad-grid ad-grid--statistik">
        <x-admin.statistik ikon="buku" label="Total Materi"
            nilai="{{ array_sum($jumlahStatus) }}"
            keterangan="semua status"
            href="{{ route('admin.materi') }}" />

        <x-admin.statistik ikon="jam" label="Menunggu Verifikasi"
            nilai="{{ $jumlahStatus['pending'] ?? 0 }}"
            nada="peringatan"
            keterangan="perlu keputusanmu"
            href="{{ route('admin.materi', ['status' => 'pending']) }}" />

        <x-admin.statistik ikon="perisai" label="Dipublikasikan"
            nilai="{{ $jumlahStatus['published'] ?? 0 }}"
            nada="sukses"
            keterangan="materi yang tayang"
            href="{{ route('admin.materi', ['status' => 'published']) }}" />

        <x-admin.statistik ikon="silang-polos" label="Ditolak"
            nilai="{{ $jumlahStatus['rejected'] ?? 0 }}"
            keterangan="perlu diperbaiki pemilik"
            href="{{ route('admin.materi', ['status' => 'rejected']) }}" />
    </div>

    {{-- =====================
         CARI + FILTER
    ======================
         Filter memakai form GET supaya hasilnya bisa dibagikan lewat tautan.
         Field q dipakai juga oleh kolom pencarian di bagian atas layout. --}}
    <form method="GET" action="{{ route('admin.materi') }}" class="ad-seksi ad-alat">
        <div class="ad-alat__kiri">
            <label for="q" class="sr-only">Cari materi</label>

            <div class="ad-cari">
                <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                <input id="q" name="q" type="search" value="{{ $kataKunci }}"
                    placeholder="Cari judul, isi, atau pembuat..." autocomplete="off">
            </div>
        </div>

        <div class="ad-alat__kanan">
            <label for="status" class="sr-only">Filter status</label>

            <div class="ad-pilih__bungkus">
                <select id="status" name="status" class="ad-pilih">
                    <option value="">Semua Status</option>

                    @foreach ($pilihanStatus as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($statusAktif === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>

                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                </svg>
            </div>

            <label for="pelajaran" class="sr-only">Filter pelajaran</label>

            <div class="ad-pilih__bungkus">
                <select id="pelajaran" name="pelajaran" class="ad-pilih">
                    <option value="">Semua Pelajaran</option>

                    @foreach ($daftarPelajaran as $pelajaran)
                        <option value="{{ $pelajaran->slug }}" @selected($pelajaranAktif === $pelajaran->slug)>{{ $pelajaran->nama }}</option>
                    @endforeach
                </select>

                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
                </svg>
            </div>

            <button type="submit" class="ad-tombol ad-tombol--utama">Terapkan</button>

            @if ($kataKunci !== '' || $statusAktif !== '' || $pelajaranAktif !== '')
                <a href="{{ route('admin.materi') }}" class="ad-tombol ad-tombol--garis">Reset</a>
            @endif
        </div>
    </form>

    {{-- =====================
         DAFTAR MATERI
    ====================== --}}
    @if ($daftar === [])
        <div class="ad-seksi">
            @if ($kataKunci !== '' || $statusAktif !== '' || $pelajaranAktif !== '')
                <x-admin.kosong ikon="cari" judul="Tidak ada materi yang cocok"
                    teks="Coba kata kunci atau filter lain, atau reset filtrernya untuk melihat semua materi." />
            @else
                <x-admin.kosong judul="Belum ada materi"
                    teks="Materi yang dibuat pengguna akan muncul di sini begitu diajukan." />
            @endif
        </div>
    @else
        <div class="ad-seksi ad-tabel__bungkus hidden md:block">
            <table class="ad-tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Materi</th>
                        <th>Pelajaran</th>
                        <th>Dibuat oleh</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($daftar as $materi)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <div class="ad-tabel__nama">
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base font-extrabold text-white"
                                        style="background-color: {{ $materi['kategori']['warna'] }}"
                                        aria-hidden="true">
                                        {{ $materi['kategori']['ikon'] }}
                                    </span>

                                    <div class="ad-tabel__nama-teks">
                                        <p class="ad-tabel__judul">{{ $materi['judul'] }}</p>

                                        <p class="ad-tabel__sub">
                                            {{ $materi['tingkat_kesulitan'] }} &middot;
                                            {{ $materi['jumlah_bab'] }} Bab &middot;
                                            {{ $materi['waktu_baca'] }} menit baca
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                        style="background-color: {{ $materi['kategori']['warna'] }}" aria-hidden="true"></span>

                                    {{ $materi['kategori']['nama'] }}
                                </span>
                            </td>

                            <td>
                                <span class="inline-flex items-center gap-2">
                                    <x-admin.avatar :inisial="$materi['pembuat']['inisial']" :warna="$materi['pembuat']['warna']"
                                        :warnaGelap="$materi['pembuat']['warna_gelap']" />

                                    {{ $materi['pembuat']['nama'] }}
                                </span>
                            </td>

                            <td>
                                <x-admin.lencana-materi :status="$materi['status']" :label="$materi['status_label']" />
                            </td>

                            <td>
                                <span class="whitespace-nowrap">{{ $materi['tanggal_label'] }}</span>
                            </td>

                            <td>
                                <div class="ad-tabel__aksi">
                                    <a href="{{ $materi['tautan_detail'] }}" class="ad-tombol ad-tombol--halus">
                                        <x-admin.ikon nama="mata" />

                                        Lihat
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Kartu bertumpuk untuk layar kecil. --}}
        <div class="ad-seksi md:hidden space-y-4">
            @foreach ($daftar as $materi)
                <article class="rounded-2xl border border-lavender bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg font-extrabold text-white"
                                style="background-color: {{ $materi['kategori']['warna'] }}"
                                aria-hidden="true">
                                {{ $materi['kategori']['ikon'] }}
                            </span>

                            <div class="min-w-0">
                                <p class="text-xs font-bold text-dark/50">{{ $materi['kategori']['nama'] }}</p>

                                <p class="truncate text-base font-extrabold text-dark">{{ $materi['judul'] }}</p>
                            </div>
                        </div>

                        <x-admin.lencana-materi :status="$materi['status']" :label="$materi['status_label']" />
                    </div>

                    <p class="mt-2 text-xs font-semibold text-dark/50">
                        {{ $materi['tingkat_kesulitan'] }} &middot;
                        {{ $materi['jumlah_bab'] }} Bab
                    </p>

                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-dark/5 pt-3">
                        <span class="inline-flex min-w-0 items-center gap-2 text-xs font-semibold text-dark/60">
                            <x-admin.avatar :inisial="$materi['pembuat']['inisial']" :warna="$materi['pembuat']['warna']"
                                :warnaGelap="$materi['pembuat']['warna_gelap']" />

                            <span class="truncate">{{ $materi['pembuat']['nama'] }}</span>
                        </span>

                        <span class="shrink-0 text-xs text-dark/50">{{ $materi['tanggal_label'] }}</span>
                    </div>

                    <a href="{{ $materi['tautan_detail'] }}" class="ad-tombol ad-tombol--halus mt-4 w-full">
                        <x-admin.ikon nama="mata" />

                        Lihat Detail
                    </a>
                </article>
            @endforeach
        </div>

        <div class="ad-seksi flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-dark/60">
                Menampilkan {{ $paginasi->firstItem() ?? 0 }}–{{ $paginasi->lastItem() ?? 0 }}
                dari {{ $paginasi->total() }} materi
            </p>

            {{ $paginasi->links() }}
        </div>
    @endif
@endsection