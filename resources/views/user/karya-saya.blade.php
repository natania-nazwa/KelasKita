@extends('layouts.app')

@section('title', 'Karya Saya | KelasKita')

@section('content')
    {{--
        Halaman "Karya Saya": pusat pengelolaan materi dan quiz milik
        pengguna yang sedang login.

        Dua tab (Materi Saya / Quiz Saya) memakai query string ?tab=, jadi
        perpindahan tab tetap jalan walau JavaScript dimatikan dan alamatnya
        bisa disalin. Daftar hanya berisi karya pengguna sendiri: filter
        dibuat oleh scope milik() di model, bukan oleh Blade.
    --}}
    {{--
        min-h-[100dvh], bukan min-h-[calc(100dvh-4rem)]: tinggi halaman ini
        dihitung tanpa top bar karena top bar-nya disembunyikan (lihat
        $sembunyiTopbar di layouts/app.blade.php). Kalau heights lama dipakai,
        akan ada 4rem ruang kosong yang tidak pernah terisi di bawah.
    --}}
    <div class="kanvas-halaman -m-6 min-h-[100dvh] p-5 sm:p-6 lg:-m-10 lg:p-8 xl:p-10">

        {{-- Toast hasil aksi dipindahkan ke layouts/app.blade.php. --}}

        {{-- =========================
             JUDUL HALAMAN
        ==========================
             Papan ungu di paling atas: lencana, judul, dan subtitle. --}}
        <x-karya.kepala />

        {{-- =========================
             RINGKASAN KARYA
        ==========================
             Empat angka besar di bawah kepala halaman: berapa materi, berapa
             quiz, berapa soal, dan berapa yang masih menunggu persetujuan
             admin. Angkanya mencakup seluruh karya, jadi tetap sama walau
             berpindah tab atau memuat halaman berikutnya.

             Deret kartu ini naik sedikit ke dalam papan kepala supaya
             keduanya terbaca sebagai satu blok utuh, bukan dua bagian
             yang dipisahkan celah putih. --}}
        <div class="-mt-10 sm:-mt-12">
            <x-karya.ringkasan :materi="$ringkasan['materi']" :quiz="$ringkasan['quiz']"
                :soal="$ringkasan['soal']" :menunggu="$ringkasan['menunggu']" />
        </div>

        {{-- =========================
             TAB + PENCARIAN + TOMBOL TAMBAH
        ==========================
             Ketiganya dilingkari satu panel putih (karya-alat) supaya
             terbaca sebagai satu kendali halaman. --}}
        <section data-reveal class="mt-6">
            <x-karya.tab :tab="$tab" :jumlah-materi="$jumlahPerTab['materi']"
                :jumlah-quiz="$jumlahPerTab['quiz']" :kata-kunci="$kataKunci" />
        </section>

        {{-- =========================
             DAFTAR KARYA
        ==========================
             Tiga kolom di layar besar: kartu di halaman ini lebih banyak
             isinya (lencana status, tiga baris informasi, tiga tombol),
             jadi tiga kolom membuatnya lega. Dua kolom di tablet, satu di
             ponsel. --}}
        @if ($daftar === [])
            <div data-reveal class="mt-5">
                <x-karya.kosong :alasan="$alasanKosong" :tab="$tab" />
            </div>
        @else
            <div data-reveal-stagger
                class="mt-5 grid grid-cols-1 gap-5 min-w-0 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($daftar as $kartu)
                    @if ($tab === 'quiz')
                        <x-karya.kartu-quiz :quiz="$kartu" :prioritas="$loop->index < 9" />
                    @else
                        <x-karya.kartu-materi :materi="$kartu" :prioritas="$loop->index < 9" />
                    @endif
                @endforeach
            </div>

            <div class="karya-hal mt-8">
                {{ $paginasi->links() }}
            </div>
        @endif

        {{-- =========================
             DIALOG KONFIRMASI HAPUS
        ==========================
             Satu dialog untuk semua kartu; isi dan form yang dijalankan
             diambil dari kartu yang tombol "Hapus"-nya ditekan. --}}
        <x-app.konfirmasi />
    </div>
@endsection
