@props([
    // Daftar notifikasi, sudah dipetakan jadi array polos oleh
    // App\Support\NotifikasiKonten::daftar(). Bentuknya:
    //   id, jenis, judul, pesan, tautan, dibaca, waktu, waktu_label
    'daftar' => [],
    'sisa' => 0,
])

{{--
    Lonceng notifikasi di top bar halaman user.

    Dua bagian yang berbeda tugasnya, jadi sengaja tidak digabung: loncengnya
    membuka daftar, dan isinya sudah dirender sejak halaman dimuat.

    Yang ditambahkan JavaScript dua hal. Pertama, begitu panel dibuka,
    semua notifikasi yang ada ditandai terbaca — pembaca yang membuka
    lonceng memang sedang melihat daftarnya, jadi titiknya tidak perlu
    menunggu setiap baris diklik satu per satu. Kedua, tiap baris yang
    diklik ikut menandai dirinya sendiri, supaya tidakifikasi yang baru
    datang setelah panel dibuka tetap langsung terasa sudah dibaca.

    Setiap baris menuju kontennya (halaman detail materi atau halaman detail
    quiz). Navigasi ditahan sampai penandaan sampai ke server, karena
    halaman tujuan dirender ulang dari database: kalau navigasi berjalan
    duluan, titik lonceng masih menyala di halaman yang baru dibuka.

    Notifikasi yang kontennya sudah dihapus tetap ditampilkan sebagai teks
    biasa, tanpa tautan: pesannya masih benar sebagai kabar, dan tidak ada
    lagi halaman yang bisa dituju.
--}}

<details class="notif" data-notif data-notif-semua="{{ route('user.notifikasi.baca-semua') }}">
    <summary class="notif__tombol" aria-label="Notifikasi">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($sisa > 0)
            <span class="notif__titik" data-notif-titik aria-hidden="true"></span>
            <span class="sr-only">{{ $sisa }} notifikasi belum dibaca</span>
        @endif
    </summary>

    <div class="notif__panel" data-notif-panel>
        <p class="notif__kepala">Notifikasi</p>

        <div class="notif__daftar" data-notif-daftar>
            @if ($daftar === [])
                <p class="notif__kosong">Belum ada notifikasi.</p>
            @endif

            @foreach ($daftar as $item)
                @php
                    /*
                     * Notifikasi yang kontenh-nya sudah dihapus tidak punya
                     * tautan, jadi barisnya dirender sebagai <div> biasa. Pakai
                     * <a> dengan href kosong akan membuat pembaca landung di
                     * halaman yang sama.
                     */
                    $tag = filled($item['tautan']) ? 'a' : 'div';
                @endphp

                <{{ $tag }} @if ($item['tautan']) href="{{ $item['tautan'] }}" @endif
                    class="notif__item @if (! $item['dibaca']) is-belum @endif"
                    data-notif-item data-notif-baca="{{ route('user.notifikasi.baca', $item['id']) }}">
                    <span class="notif__item-kiri" aria-hidden="true">
                        <span class="notif__item-ikon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="{{ \App\Support\Ikon::path(\App\Models\Notifikasi::ikon($item['jenis'])) }}" />
                            </svg>
                        </span>
                    </span>

                    <span class="notif__item-isi">
                        <span class="notif__item-judul">{{ $item['judul'] }}</span>
                        <span class="notif__item-pesan">{{ $item['pesan'] }}</span>
                        <span class="notif__item-waktu">{{ $item['waktu_label'] }}</span>
                    </span>
                </{{ $tag }}>
            @endforeach
        </div>
    </div>
</details>
