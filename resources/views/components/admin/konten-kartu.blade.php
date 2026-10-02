@props([
    // Satu kartu dari App\Support\DaftarKonten::petikan().
    'kartu',
])

{{--
    Satu kartu di daftar "Konten Pembelajaran".

    Tampilan kartu ini sengaja sama persis dengan kartu di halaman "Karya Saya"
    milik pengguna: kelas yang dipakai (kartu-materi, karya-kartu, karya-status,
    karya-info, karya-aksi) semuanya milik kartu pengguna, jadi satu konten punya
    tampilan yang sama di kedua tempat. Yang membedakan hanya isi dan tombolnya.

    Bedanya dari kartu pengguna ada di tiga hal:

      - kartu di sini menampilkan "Batalkan Publikasi" dan "Duplikat". Dua aksi
        itu hanya ada di sisi admin: pemilik tidak menerbitkan karyanya sendiri,
        dan duplikat adalah keputusan kerja admin, bukan-judgement pengguna.
      - tombol "Lihat" dan judulnya selalu jadi tautan. Kartu pengguna
        menyembunyikan tautannya untuk yang belum tayang supaya tidak
        menuliskan URL yang berakhir 404; di sini justru halaman detailnya
        sudah bisa membaca materi maupun quiz dari status apa pun, jadi
        tautannya selalu berguna.
      - tidak ada alasan ditolak dan tidak ada sisa pengajuan. Dua hal itu
        hanya berlaku untuk konten yang menunggu keputusan admin, dan konten
        di daftar ini memang milik admin sendiri.
--}}

@php
    $kategori = $kartu['kategori'];
    $nama = $kartu['jenis'] === 'materi' ? 'Materi' : 'Quiz';
    $terbit = $kartu['status'] === \App\Models\Materi::STATUS_PUBLISHED;
@endphp

<article style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};"
    class="kartu-materi karya-kartu kartu-konten group min-w-0">

    {{-- A. Thumbnail: tinggi seragam (16:9), sama seperti kartu pengguna. --}}
    <div class="kartu-materi__gambar">
        @if (filled($kartu['thumbnail'] ?? null))
            <img src="{{ $kartu['thumbnail'] }}" alt="" loading="lazy" class="kartu-materi__foto">
        @else
            <span class="kartu-materi__gambar-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif

        {{-- Lencana kategori, melayang di pojok kiri bawah thumbnail. --}}
        <span class="kartu-materi__lencana">{{ $kategori['nama'] }}</span>

        {{-- Status di pojok kanan thumbnail. --}}
        <span class="karya-status karya-status--{{ $kartu['warna_status'] }} karya-pojok">
            <span class="karya-status__titik" aria-hidden="true"></span>

            {{ $kartu['status_label'] }}
        </span>
    </div>

    {{-- B-E. Aksen, judul, deskripsi, informasi, dan baris aksi. --}}
    <div class="kartu-materi__badan">
        {{-- Garis warna kategori, menyambung ke thumbnail tanpa celah. --}}
        <span class="kartu-materi__aksen -mx-4 -mt-3.5 mb-3 block" aria-hidden="true"></span>

        <h2 class="kartu-materi__judul karya-judul">
            <a href="{{ $kartu['tautan']['lihat'] }}">{{ $kartu['judul'] }}</a>
        </h2>

        <p class="kartu-materi__deskripsi">{{ $kartu['deskripsi'] }}</p>

        {{-- Jumlah bab atau soal, perkiraan waktu, dan tanggal. --}}
        <div class="karya-info mt-3.5">
            <span class="karya-info__butir">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>

                {{ $kartu['jumlah'] }} {{ $kartu['satuan'] }}
            </span>

            @if ((int) $kartu['menit'] > 0)
                <span class="karya-info__butir">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                    {{ $kartu['menit'] }} menit
                </span>
            @endif

            <span class="karya-info__butir karya-info__butir--sepuh">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>

                {{ $kartu['tanggal_label'] }}
            </span>
        </div>

        {{-- Baris aksi. Publish dan hapus lewat dialog konfirmasi lebih dulu
             (dikerjakan di resources/js/konten-publish.js dan .konten-admin.js). --}}
        <div class="karya-aksi">
            <a href="{{ $kartu['tautan']['lihat'] }}"
                class="karya-aksi__tombol karya-aksi__tombol--lihat karya-aksi__tombol--utama"
                title="Buka halaman {{ strtolower($nama) }} ini">
                Lihat

                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>

            <a href="{{ $kartu['tautan']['edit'] }}" class="karya-aksi__tombol"
                title="Ubah {{ strtolower($nama) }} ini">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                </svg>

                Edit
            </a>

            <form method="POST" action="{{ $kartu['tautan']['duplikat'] }}">
                @csrf

                <button type="submit" class="karya-aksi__tombol"
                    title="Duplikat {{ strtolower($nama) }} ini sebagai draft baru">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m0 0h1.5a5.25 5.25 0 0 1 5.25-5.25H15" />
                    </svg>

                    Duplikat
                </button>
            </form>

            {{--
                Satu tombol untuk dua arah. Kalau kontennya sudah terbit, tombol
                yang sama membatalkan terbitkanya. Yang berubah hanya kalimatnya,
                supaya admin tidak perlu mencari tombol berbeda untuk hal yang
                sebenarnya satu keputusan.
            --}}
            <button type="button" class="karya-aksi__tombol" data-konten-terbit-buka
                data-konten-terbit-aksi="{{ $kartu['tautan']['publish'] }}"
                data-konten-nama="{{ $nama }} &quot;{{ $kartu['judul'] }}&quot;"
                data-konten-terbit-judul="{{ $terbit ? 'Batalkan publikasi?' : 'Publish konten?' }}"
                data-konten-terbit-pesan="{{ $terbit
                    ? $nama . ' ini akan ditarik dari halaman pengguna dan kembali menjadi draft.'
                    : 'Konten ini akan langsung tersedia untuk pengguna dan notifikasi akan dikirim.' }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    @if ($terbit)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    @endif
                </svg>

                {{ $terbit ? 'Batalkan' : 'Publish' }}
            </button>

            <button type="button" class="karya-aksi__tombol karya-aksi__tombol--hapus"
                data-hapus-buka
                data-hapus-judul="Hapus {{ $nama }}?"
                data-hapus-meta="{{ $kategori['nama'] }} &middot; {{ $kartu['ringkasan'] }} &middot; {{ $kartu['tanggal_label'] }}"
                data-hapus-aksi="{{ $kartu['tautan']['hapus'] }}"
                title="Hapus {{ strtolower($nama) }} ini" aria-label="Hapus {{ strtolower($nama) }} {{ $kartu['judul'] }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>

                Hapus
            </button>
        </div>
    </div>
</article>