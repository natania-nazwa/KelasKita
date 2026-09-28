@props([
    // Array dari App\Support\DaftarJadwal::ringkasan().
    'ringkasan' => [],
])

{{--
    Panel ringkasan di sidebar: pelajaran yang sedang berlangsung dan
    pelajaran berikutnya pada tanggal yang sedang dibaca.

    Dua hal ini yang paling sering dicari pengguna ("sekarang belajar apa"
    dan "segera apa"), jadi keduanya dipakai juga sebagai penanda visual di
    daftar, bukan hanya sebagai angka.

    Kalau tidak ada pelajaran yang sedang berlangsung, blok itu disembunyikan
    sepenuhnya: kalimat "sedang tidak ada pelajaran" hanya menambah suara
    kosong di layar yang sudah sunyi.
--}}

<section class="jadwal-kartu" aria-label="Ringkasan jadwal"
    style="--k: var(--color-primary); --k-gelap: var(--color-primary-dark);">

    <div class="jadwal-kartu__kepala">
        <span class="jadwal-kartu__ikon" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
            </svg>
        </span>

        <h2 class="jadwal-kartu__judul">Ringkasan {{ $ringkasan['nama_hari'] }}</h2>
    </div>

    @if ($ringkasan['sedang'] !== null)
        <div class="jadwal-sekarang" style="--k: {{ $ringkasan['sedang']['warna'] }}; --k-gelap: {{ $ringkasan['sedang']['warna_gelap'] }};">
            <span class="jadwal-sekarang__label">
                <span class="jadwal-sekarang__titik" aria-hidden="true"></span>
                Sedang Berlangsung
            </span>

            <p class="jadwal-sekarang__judul">{{ $ringkasan['sedang']['judul'] }}</p>

            <p class="jadwal-sekarang__waktu">
                {{ $ringkasan['sedang']['mulai'] }} &ndash; {{ $ringkasan['sedang']['selesai'] }}
                &bull; {{ $ringkasan['sedang']['ruang'] }}
            </p>
        </div>
    @endif

    <dl class="jadwal-angka">
        <div>
            <dt>Jam pelajaran</dt>
            <dd>{{ $ringkasan['jam_label'] }} jam</dd>
        </div>

        <div>
            <dt>Sudah lewat</dt>
            <dd>{{ $ringkasan['selesai'] }}</dd>
        </div>

        <div>
            <dt>Belum dimulai</dt>
            <dd>{{ $ringkasan['akan_datang'] }}</dd>
        </div>
    </dl>

    @if ($ringkasan['berikut'] !== null)
        <p class="jadwal-kartu__catatan">
            Berikutnya: <strong>{{ $ringkasan['berikut']['judul'] }}</strong> pukul
            {{ $ringkasan['berikut']['mulai'] }} di {{ $ringkasan['berikut']['ruang'] }}.
        </p>
    @endif
</section>
