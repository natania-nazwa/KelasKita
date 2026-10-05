@props([
    // Kenapa daftar kosong: 'kosong' = belum ada jadwal untuk hari ini,
    // 'libur' = hari tanpa pelajaran (Minggu),
    // 'cari' = ada pelajaran tapi tidak cocok dengan filter/pencarian.
    'alasan' => 'cari',
    // Route tombol aksi, supaya tetap benar di tanggal yang sedang dibuka.
    'tanggal' => null,
    'kategori' => '',
    'kataKunci' => '',
])

{{--
    Empty state di dalam panel daftar pelajaran.

    Tiga kondisi punya pesan berbeda:
      kosong -> pengguna belum menambah jadwal sama sekali untuk hari itu
      libur  -> hari tersebut memang tidak ada pelajaran
      cari   -> ada pelajaran, tapi tidak cocok dengan filter atau kata kunci

    Angka nol sengaja tidak diulang di sini: kartu ringkasan di kepala
    halaman sudah menampilkannya, jadi mengulangnya hanya membuat "0"
    muncul dua kali.
--}}

@php
    // Tombol "Reset Filter" menghapus kategori dan kata kunci, tapi tetap
    // menahan tanggal yang sedang dibuka: mereset filter bukan berarti
    // lompat balik ke hari ini.
    $reset = route('user.jadwal', array_filter([
        'tanggal' => $tanggal?->toDateString(),
    ], fn ($nilai) => filled($nilai)));

    $isi = match ($alasan) {
        'kosong' => [
            'judul' => 'Belum Ada Jadwal',
            'deskripsi' => 'Kamu belum menambahkan jadwal pelajaran untuk hari ini. Tambahkan jadwal harianmu supaya kartu di dashboard ikut terisi.',
            'ikon' => \App\Support\Ikon::path('jam'),
        ],
        'libur' => [
            'judul' => 'Hari Libur',
            'deskripsi' => 'Tidak ada jam pelajaran yang tercatat di hari ini. Nikmati istirahatnya, atau lihat jadwal hari lain lewat kalender di samping.',
            'ikon' => \App\Support\Ikon::path('kalender'),
        ],
        default => [
            'judul' => 'Jadwal Tidak Ditemukan',
            'deskripsi' => 'Tidak ada pelajaran yang cocok dengan filter atau kata kunci itu. Coba kata yang lebih umum, atau ganti pilihan filternya.',
            'ikon' => \App\Support\Ikon::path('jam'),
        ],
    };
@endphp

<div {{ $attributes->class(['jadwal-kosong']) }}>

    <span class="jadwal-kosong__ikon" aria-hidden="true">
        <svg class="h-full w-full" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $isi['ikon'] }}" />
        </svg>
    </span>

    <h3 class="jadwal-kosong__judul">{{ $isi['judul'] }}</h3>

    <p class="jadwal-kosong__deskripsi">{{ $isi['deskripsi'] }}</p>

    @if ($alasan === 'kosong')
        {{-- Tombol yang sama dengan tombol di kepala panel, jadi pengguna
             mengenali satu tombol "Tambah Jadwal" di dua tempat. --}}
        <x-jadwal.tambah :tanggal="$tanggal" class="mt-5" />
    @elseif ($alasan === 'libur')
        <a href="{{ route('user.jadwal') }}" class="tombol-utama mt-5">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>

            Kembali ke Hari Ini
        </a>
    @else
        <a href="{{ $reset }}" class="tombol-utama mt-5">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>

            Reset Filter
        </a>
    @endif
</div>
