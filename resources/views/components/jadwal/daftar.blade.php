@props([
    // Array dari App\Support\DaftarJadwal::hari().
    'daftar' => [],
    // Kenapa daftar kosong, diteruskan ke components/jadwal/kosong:
    // 'libur' = hari tanpa pelajaran, 'cari' = tidak cocok dengan filter.
    'alasanKosong' => 'cari',
    'tanggal' => null,
    'kategori' => '',
    'kataKunci' => '',
])

{{--
    Daftar pelajaran pada tanggal terpilih, ditampilkan sebagai lini masa
    (timeline): jam di kiri, kartu pelajaran di kanan.

    Setiap baris bisa punya satu PR, yang ditampilkan di bawah nama
    pelajarannya beserta tenggatnya.

    Penandanya adalah titik warna di tiap baris, bukan garis penghubung,
    jadi urutan waktunya sudah terbaca tanpa menambah elemen dekoratif.
    Baris yang sudah lewat tampil redup, sama seperti di panel dashboard.

    Warna tiap baris mengikuti katalog Pelajaran lewat --k / --k-gelap,
    sehingga kartu pelajaran, kartu materi, dan kartu hasil quiz memakai
    warna yang sama untuk pelajaran yang sama.

    Empty state dirender di dalam komponen ini, bukan lewat slot named,
    karena komponen jadwal/kosong butuh props yang sama persis dengan yang
    ada di sini.
--}}

<div {{ $attributes->class(['jadwal-daftar']) }}>
    @forelse ($daftar as $item)
        <article data-jadwal-id="{{ $item['id'] }}" @class([
                'jadwal-item',
                'jadwal-item--lewat' => $item['lewat'],
                'jadwal-item--sedang' => $item['sedang'],
            ]) style="--k: {{ $item['warna'] }}; --k-gelap: {{ $item['warna_gelap'] }};">

            {{-- Jam: kotak berisi jam mulai dan jam selesai, sama seperti
                 di panel dashboard, supaya kartu kecil dan daftar besar
                 langsung terbaca sebagai hal yang sama. --}}
            <div class="jadwal-item__waktu">
                <span class="jadwal-item__mulai">{{ $item['mulai'] }}</span>
                <span class="jadwal-item__selesai">{{ $item['selesai'] }}</span>
            </div>

            {{-- Badge status, hanya untuk pelajaran yang sedang berlangsung
                 atau sudah selesai. Pelajaran yang masih akan datang tidak
                 perlu badge karena sudah jelas dari jamnya. --}}
            <div class="jadwal-item__isi">
                <div class="jadwal-item__kepala">
                    <h3 class="jadwal-item__judul" title="{{ $item['judul'] }}">{{ $item['judul'] }}</h3>

                    @if ($item['sedang'])
                        <span class="jadwal-status jadwal-status--sedang">
                            <span class="jadwal-status__titik" aria-hidden="true"></span>
                            {{ $item['status_label'] }}
                        </span>
                    @elseif ($item['lewat'])
                        <span class="jadwal-status jadwal-status--selesai">{{ $item['status_label'] }}</span>
                    @endif
                </div>

                <p class="jadwal-item__kategori">{{ $item['kategori']['nama'] }}</p>

                {{-- PR. Hanya muncul kalau jam pelajaran ini memang punya
                     tugas, jadi daftar yang bersih tetap padat. Nada
                     tenggat berubah kalau sudah lewat, karena itu yang
                     paling perlu kelihatan cepat. --}}
                @if ($item['punya_pr'])
                    <p class="jadwal-pr">
                        <span class="jadwal-pr__label" aria-hidden="true">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            PR
                        </span>

                        <span class="jadwal-pr__isi">{{ $item['pr'] }}</span>

                        @if ($item['pr_keterangan'] !== null)
                            <span class="jadwal-pr__tempo jadwal-pr__tempo--{{ $item['pr_keterangan']['nada'] }}">{{ $item['pr_keterangan']['teks'] }}</span>
                        @endif
                    </p>
                @endif

                {{-- Rincian di bawah judul: kelas, ruang, durasi. Isinya
                     sudah disiapkan di DaftarJadwal dan hanya memuat yang
                     benar-benar diisi, jadi field yang dikosongkan di form
                     tidak muncul sama sekali di sini. --}}
                <dl class="jadwal-item__meta">
                    @foreach ($item['meta'] as $label => $nilai)
                        <div data-jadwal-meta="{{ $label }}">
                            <dt>{{ $label }}</dt>
                            <dd>{{ $nilai }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <span class="jadwal-ikon-kotak" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                </svg>
            </span>

            {{-- Aksi edit dan hapus. Hanya untuk baris yang benar-benar
                 ada di tb_jadwal: contoh jadwal tidak punya baris di
                 database, jadi tidak bisa diedit atau dihapus.

                 Tombol hapus membuka dialog konfirmasi lebih dulu
                 (dikerjakan initKonfirmasi() di resources/js/app.js) dan
                 sengaja type="button" supaya kalau JavaScript tidak jalan
                 tidak ada yang terhapus tanpa konfirmasi. --}}
            @if ($item['bisa_diubah'])
                <div class="jadwal-item__aksi">
                    <a href="{{ $item['tautan_edit'] }}" title="Ubah jadwal {{ $item['judul'] }}">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                        </svg>

                        <span class="sr-only">Ubah jadwal {{ $item['judul'] }}</span>
                    </a>

                    <form method="POST" action="{{ $item['tautan_hapus'] }}" data-konfirmasi-form
                        data-konfirmasi-judul="Hapus Jadwal?"
                        data-konfirmasi-pesan="Jadwal &quot;{{ $item['judul'] }}&quot; hari {{ $item['nama_hari'] }} akan dihapus dan tidak dapat dikembalikan.">
                        @csrf
                        @method('DELETE')

                        <button type="button" data-konfirmasi title="Hapus jadwal {{ $item['judul'] }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>

                            <span class="sr-only">Hapus jadwal {{ $item['judul'] }}</span>
                        </button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <x-jadwal.kosong :alasan="$alasanKosong" :tanggal="$tanggal" :kategori="$kategori"
            :kata-kunci="$kataKunci" />
    @endforelse
</div>
