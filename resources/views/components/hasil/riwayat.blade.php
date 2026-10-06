@props([
    // Array dari App\Support\DaftarHasil::petakan().
    'daftar' => [],
    // True saat belum ada satu pun pengerjaan sama sekali, supaya empty
    // state-nya yang tampil di dalam daftar.
    'kosong' => false,
    // Route untuk tombol empty state.
    'aksi' => 'user.hasil',
    'param' => [],
])

{{--
    Daftar riwayat hasil quiz milik pengguna yang sedang login.

    Satu baris: thumbnail 60x60 di kiri, judul dan meta di tengah,
    lingkaran nilai, badge status, lalu tombol "Lihat Detail" di paling
    kanan. Di layar sempit baris ini berubah jadi susunan vertikal,
    jadi tidak ada scroll horizontal.

    Yang dipotong di ponsel ditangani di app.css pada blok
    @media (max-width: 639px) milik .hasil-item, bukan di sini: judul
    dilink ke dua baris dan tanggal dibiarkan membungkus. Span tanggal
    tetap memakai .truncate supaya tablet dan desktop tidak jadi dua
    baris.

    Nilai yang belum selesai ditampilkan sebagai "--", bukan persen,
    karena nilai itu memang belum final sampai pengerjaan ditutup.
--}}

<div {{ $attributes->class(['mt-4 space-y-3']) }}>
    @forelse ($daftar as $item)
        <article class="hasil-item">

            {{-- Thumbnail 60x60. Kalau quiz punya thumbnail dari
                 database, foto itu yang dipakai; kalau tidak, gradien
                 plus ikon kategori, sama seperti kartu quiz di halaman
                 Quiz. --}}
            <span class="hasil-item__thumb" style="--k: {{ $item['kategori']['warna'] }}; --k-gelap: {{ $item['kategori']['warna_gelap'] }};"
                aria-hidden="true">
                @if (filled($item['thumbnail']))
                    <img src="{{ $item['thumbnail'] }}" alt="" class="hasil-item__foto" loading="lazy" decoding="async">
                @else
                    <svg class="hasil-item__thumb-ikon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['kategori']['ikon'] }}" />
                    </svg>
                @endif
            </span>

            {{-- Judul, badge kategori, jumlah soal, durasi, dan tanggal. --}}
            <div class="hasil-item__teks">
                <h3 class="hasil-item__judul" title="{{ $item['judul'] }}">{{ $item['judul'] }}</h3>

                <span class="hasil-item__kategori">{{ $item['kategori']['nama'] }}</span>

                <p class="hasil-item__meta">
                    {{ $item['jumlah_soal'] }} soal
                    <span aria-hidden="true">&bull;</span>
                    {{ $item['durasi_label'] }}
                </p>

                <p class="hasil-item__tanggal">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalender') }}" />
                    </svg>

                    <span class="truncate">
                        {{ $item['sudah_selesai'] ? 'Dikerjakan pada' : 'Dimulai pada' }}: {{ $item['tanggal_label'] }}
                    </span>
                </p>
            </div>

            {{-- Ringkasan jawaban, disembunyikan di layar sempit supaya
                 baris tidak jadi tinggi sekali di ponsel. --}}
            <dl class="hasil-item__hitungan">
                <div class="hasil-item__hitungan-item">
                    <dt>Benar</dt>
                    <dd class="hasil-item__hitungan-nilai hasil-item__hitungan-nilai--benar">{{ $item['jumlah_benar'] }}</dd>
                </div>

                <div class="hasil-item__hitungan-item">
                    <dt>Salah</dt>
                    <dd class="hasil-item__hitungan-nilai hasil-item__hitungan-nilai--salah">{{ $item['jumlah_salah'] }}</dd>
                </div>
            </dl>

            {{-- Lingkaran nilai + badge status. --}}
            <div class="hasil-item__nilai">
                <x-hasil.lingkaran :persen="$item['persen']" :warna="$item['warna_nilai']"
                    :label="$item['judul']" />

                <span class="hasil-status hasil-status--{{ $item['status'] }}">
                    {{ $item['status_label'] }}
                </span>
            </div>

            <a href="{{ $item['tautan'] }}" class="hasil-item__tombol">
                Lihat Detail

                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kanan') }}" />
                </svg>
            </a>
        </article>
    @empty
        <x-hasil.kosong :alasan="$kosong ? 'saya' : 'cari'" :aksi="$aksi" :param="$param" class="mt-4" />
    @endforelse
</div>
