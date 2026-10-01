@extends('layouts.admin')

@section('title', 'Informasi Sistem | KelasKita')

@section('content')

    {{--
        "Informasi Sistem" di dalam area Pengaturan admin.

        Halaman ini hanya menampilkan. Tidak ada satu pun isian yang bisa
        diubah dari sini, dan tidak ada yang ditulis ke database.

        Semuanya dibaca dari App\Support\Aplikasi, satu sumber untuk semua
        fakta aplikasi:
          - nama dan versi dari config (APP_VERSION di .env);
          - status database diukur dengan satu koneksi dan SELECT 1;
          - versi PHP, Laravel, dan driver database dari runtime.

        Status Server dan Database sengaja tidak ditulis sebagai "Online".
        Request yang sedang dilayani sudah sampai ke controller ini kalau
        halaman ini sampai terender, jadi "Online" berarti permintaan ini
        sendiri berhasil. Database diuji sungguhan, sehingga ketika
        koneksinya putus, halamannya menunjukkan keadaan yang sebenarnya.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Informasi Sistem" ikon="info"
            subjudul="Informasi aplikasi dan versi sistem."
            :aksi-teks="'Kembali ke Pengaturan'"
            :aksi-href="route('admin.pengaturan')" />
    </div>

    <div class="ad-atur-lebar">
        <div class="grid gap-5 md:grid-cols-2">

            {{-- ---------- APLIKASI ---------- --}}
            <div class="ad-kartu h-fit">
                <header class="ad-kartu__kepala">
                    <div class="ad-kartu__kepala-titik">
                        <span class="ad-cepat__ikon" aria-hidden="true">
                            <x-admin.ikon nama="roda" ukuran="w-5 h-5" />
                        </span>

                        <h2 class="ad-kartu__kepala-judul">Aplikasi</h2>
                    </div>
                </header>

                <div class="ad-kartu__badan">
                    <dl>
                        @foreach ([
                            'Nama Aplikasi' => \App\Support\Aplikasi::NAMA,
                            'Versi' => $aplikasi->versi(),
                            'Tahun' => (string) $aplikasi->tahun(),
                            'Lingkungan' => (string) config('app.env'),
                        ] as $label => $nilai)
                            <div class="ad-fakta">
                                <dt class="ad-fakta__label">{{ $label }}</dt>
                                <dd class="ad-fakta__nilai">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            {{-- ---------- TEKNOLOGI ---------- --}}
            <div class="ad-kartu h-fit">
                <header class="ad-kartu__kepala">
                    <div class="ad-kartu__kepala-titik">
                        <span class="ad-cepat__ikon" aria-hidden="true">
                            <x-admin.ikon nama="kode" ukuran="w-5 h-5" />
                        </span>

                        <h2 class="ad-kartu__kepala-judul">Teknologi</h2>
                    </div>
                </header>

                <div class="ad-kartu__badan">
                    <dl>
                        @foreach ([
                            'Framework' => 'Laravel '.$aplikasi->versiLaravel(),
                            'PHP' => $aplikasi->versiPhp(),
                            'Basis Data' => strtoupper($aplikasi->basisData()),
                            'Driver Sesi' => (string) config('session.driver'),
                        ] as $label => $nilai)
                            <div class="ad-fakta">
                                <dt class="ad-fakta__label">{{ $label }}</dt>
                                <dd class="ad-fakta__nilai">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>

        {{-- ---------- STATUS ---------- --}}
        <div class="ad-kartu mt-5">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="grafik" ukuran="w-5 h-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="ad-kartu__kepala-judul">Status Sistem</h2>
                        <p class="ad-kartu__subjudul">Diperiksa saat halaman ini dimuat.</p>
                    </div>
                </div>
            </header>

            <div class="ad-kartu__badan">
                <div class="ad-atur-status ad-atur-status--{{ $statusServer['ok'] ? 'oke' : 'gagal' }}">
                    <div>
                        <p class="ad-atur-status__nama">Server</p>
                        <p class="ad-atur-status__keterangan">Permintaan yang sedang dilayani.</p>
                    </div>

                    <span class="ad-atur-status__nilai">
                        <span class="ad-atur-status__titik" aria-hidden="true"></span>
                        {{ $statusServer['label'] }}
                    </span>
                </div>

                <div class="ad-atur-status ad-atur-status--{{ $statusBasisData['ok'] ? 'oke' : 'gagal' }}">
                    <div>
                        <p class="ad-atur-status__nama">Database</p>
                        <p class="ad-atur-status__keterangan">
                            Koneksi ke {{ strtoupper($aplikasi->basisData()) }} diuji dengan satu
                            pernyataan SELECT 1.
                        </p>
                    </div>

                    <span class="ad-atur-status__nilai">
                        <span class="ad-atur-status__titik" aria-hidden="true"></span>
                        {{ $statusBasisData['label'] }}
                    </span>
                </div>

                <div class="ad-atur-status ad-atur-status--oke">
                    <div>
                        <p class="ad-atur-status__nama">API</p>
                        <p class="ad-atur-status__keterangan">
                            Aplikasi ini memakai form biasa, jadi tidak ada endpoint API terpisah
                            yang perlu diperiksa.
                        </p>
                    </div>

                    <span class="ad-atur-status__nilai">
                        <span class="ad-atur-status__titik" aria-hidden="true"></span>
                        Tidak ada API terpisah
                    </span>
                </div>
            </div>
        </div>
    </div>

@endsection