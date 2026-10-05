@props([
    'daftar' => [],
    // Halaman tujuan tombol "Lihat Semua". Kalau kosong, aksi ditampilkan
    // redup (lihat components/dashboard/kepala).
    'tautan' => null,
    // Berapa baris yang boleh tampil di panel kecil ini.
    'maksimal' => 4,
])

@php
    /*
     * Panel ini berada di sidebar selebar 20rem, jadi tidak bisa memuat semua
     * jam pelajaran tanpa jadi jauh lebih tinggi daripada panel lain di
     * sebelahnya. Karena itu barisnya dipotong di sini, bukan di controller:
     * pemotongan ini murni urusan tampilan, dan halaman /user/jadwal tetap
     * menampilkan jadwal utuh.
     *
     * Daftar sudah terurut berdasarkan jam mulai, jadi yang dipotong selalu
     * pelajaran paling pagi.
     */
    $tampil = array_slice($daftar, 0, $maksimal);
    $sisa = max(0, count($daftar) - count($tampil));
@endphp

{{--
    Panel "Jadwal Hari Ini" di sidebar.

    Tiap baris: jam mulai/selesai, indikator warna tipis, judul pelajaran,
    dan kelas. Warna diambil dari katalog Pelajaran lewat data, baris yang
    statusnya "Selesai" tampil redup.

    Hanya $maksimal baris pertama yang ditampilkan. Kalau masih ada sisanya,
    jumlahnya disebut di kaki panel supaya pengguna tidak mengira jadwalnya
    hari ini cuma segitu.

    Datanya dibaca dari App\Support\DaftarJadwal, sama seperti halaman
    /user/jadwal, jadi baris di sini dijamin identik dengan baris di sana
    untuk tanggal yang sama.
--}}

<section class="dash-kartu dash-panel min-w-0" aria-label="Jadwal hari ini"
    style="--k: #6c4de6; --k-gelap: #5a3fd4;">

    <x-dashboard.kepala judul="Jadwal Hari Ini" ikon="jam" label="Lihat Semua" :tautan="$tautan" />

    <div data-reveal-stagger class="grid gap-0.5">
        @forelse ($tampil as $item)
            <div style="--k: {{ $item['warna'] }}; --k-gelap: {{ $item['warna_gelap'] }};"
                @class([
                    'dash-jadwal',
                    'dash-jadwal--lewat' => $item['lewat'],
                ])
                title="{{ $item['judul'] }} - {{ $item['kelas'] }} ({{ $item['status_label'] }})">

                <span class="dash-jadwal__waktu">
                    <span class="dash-jadwal__mulai">{{ $item['mulai'] }}</span>
                    <span class="dash-jadwal__selesai">{{ $item['selesai'] }}</span>
                </span>

                <span class="dash-jadwal__indikator" aria-hidden="true"></span>

                <span class="min-w-0 flex-1">
                    <span class="dash-jadwal__judul">{{ $item['judul'] }}</span>
                    <span class="dash-jadwal__kelas">{{ $item['kelas'] }}</span>
                </span>

                <span class="dash-ikon-kotak" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                    </svg>
                </span>
            </div>
        @empty
            {{--
                Pengguna yang belum punya jadwal — termasuk akun baru yang
                baru mendaftar — mendapat empty state mini di sini, bukan
                hanya satu baris teks. Bentuknya sengaja ringkas: kartu ini
                cuma selebar 20rem dan berdiri di samping kartu lain yang
                padat, jadi ilustrasi penuh seperti halaman Karya Saya atau
                Simpan akan membuatnya jatuh dari tinggi sidebar.

                Tombolnya memakai komponen yang sama dengan tombol di
                kepala panel maupun di halaman /user/jadwal.
            --}}
            <div class="flex flex-col items-center gap-2 px-4 py-6 text-center">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-lavender/70 text-dark/30"
                    aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                    </svg>
                </span>

                <p class="text-xs font-semibold text-dark/70">Belum ada jadwal hari ini</p>

                <p class="max-w-[15rem] text-[11px] leading-relaxed text-dark/45">
                    Tambahkan jadwal pelajaranmu, lalu kartu ini akan langsung terisi.
                </p>

                <x-jadwal.tambah class="mt-1" />
            </div>
        @endforelse
    </div>

    @if ($sisa > 0)
        {{-- Sisa jadwal tidak disembunyikan diam-diam: jumlahnya disebut, dan
             tombol "Lihat Semua" di kepala panel sudah membawa ke daftar
             lengkap. --}}
        <p class="dash-jadwal__sisa">
            <span>+{{ $sisa }} pelajaran lainnya</span>

            @if (filled($tautan))
                <a href="{{ $tautan }}" class="dash-jadwal__sisa-tautan">Lihat semua</a>
            @endif
        </p>
    @endif
</section>
