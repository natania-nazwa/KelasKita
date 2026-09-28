@props([
    'ringkasan' => [],
    'minggu' => [],
])

{{--
    Kepala halaman Jadwal: papan ungu pastel (soft) berisi lencana tanggal,
    judul, deskripsi, tiga angka ringkas, dan strip pemilih hari.

    Warnanya sengaja dibuat ungu soft: seluruh halaman jadwal berpijak pada
    latar itu, sementara kartu-kartunya tetap putih supaya isinya tetap
    menjadi fokus. Pola warnanya sama dengan kepala halaman Materi
    (components/materi/kepala) supaya keduanya terasa satu keluarga.

    Angka ringkas diambil dari daftar yang sudah difilter, jadi kartu dan
    daftar di bawahnya selalu bicara tentang hal yang sama.
--}}

<section data-reveal="zoom" class="jadwal-kepala p-5 sm:p-7">
    <div class="jadwal-kepala__isi">

        {{-- Baris atas: lencana tanggal di kiri, angka ringkas di kanan. --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <span class="jadwal-kepala__lencana">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalender') }}" />
                </svg>

                {{ $ringkasan['nama_hari'] }}
            </span>

            <div class="jadwal-kepala__statistik">
                <div>
                    <span class="jadwal-kepala__angka">{{ $ringkasan['jumlah'] }}</span>
                    <span class="jadwal-kepala__satuan">Pelajaran</span>
                </div>

                <div>
                    <span class="jadwal-kepala__angka">{{ $ringkasan['jam_label'] }}</span>
                    <span class="jadwal-kepala__satuan">Jam</span>
                </div>

                <div>
                    <span class="jadwal-kepala__angka">{{ $ringkasan['selesai'] }}</span>
                    <span class="jadwal-kepala__satuan">Selesai</span>
                </div>
            </div>
        </div>

        <h1 class="jadwal-kepala__judul">Jadwal Pelajaran</h1>

        <p class="jadwal-kepala__deskripsi">
            Lihat pelajaran yang akan kamu ikuti setiap hari, lengkap dengan jam, ruang, dan tugasnya.
        </p>

        {{-- Strip pemilih hari: tujuh hari dalam minggu yang sedang dibuka.
             Tiap kotaknya link biasa, jadi pindah hari tetap jalan walau
             JavaScript dimatikan. --}}
        <nav class="jadwal-hari" aria-label="Pilih hari">
            @foreach ($minggu as $hari)
                <a href="{{ $hari['tautan'] }}" data-jadwal-hari="{{ $hari['tanggal'] }}" @class([
                        'jadwal-hari__item',
                        'jadwal-hari__item--aktif' => $hari['aktif'],
                    ]) @if ($hari['aktif']) aria-current="date" @endif>
                    <span class="jadwal-hari__nama">{{ $hari['hari'] }}</span>
                    <span class="jadwal-hari__angka">{{ $hari['angka'] }}</span>

                    @if ($hari['jumlah'] > 0)
                        <span class="jadwal-hari__jumlah">{{ $hari['jumlah'] }} pelajaran</span>
                    @else
                        <span class="jadwal-hari__jumlah jadwal-hari__jumlah--kosong">Libur</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</section>
