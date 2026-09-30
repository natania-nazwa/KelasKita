@extends('layouts.admin')

@section('title', $materi['judul'].' | KelasKita')

@section('content')
    {{--
        Halaman detail "Kelola Materi": isi materi selengkapnya untuk ditinjau
        admin, tanpa membuka materi di sisi pengguna.

        Menampilkan semua seksi sekaligus — tidak ada tab bab yang
        disembunyikan — karena tugas admin membaca semuanya, termasuk materi
        yang masih draft atau sudah ditolak. Blok kode ditampilkan tanpa
        tombol salin: tombol itu butuh JavaScript halaman detail materi yang
        tidak dimuat di area admin.
    --}}

    {{-- Kembali ke daftar. --}}
    <a href="{{ $materi['tautan_daftar'] }}" class="ad-tautan">
        <x-admin.ikon nama="panah-kiri" />

        Kembali ke Materi
    </a>

    {{-- =====================
         KEPALA MATERI
    ====================== --}}
    <header class="ad-seksi rounded-2xl border border-lavender bg-white p-5 sm:p-6">
        <div class="flex flex-wrap items-start gap-4">
            <span
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-extrabold text-white"
                style="background-color: {{ $materi['kategori']['warna'] }}"
                aria-hidden="true">
                {{ $materi['kategori']['ikon'] }}
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-extrabold text-dark sm:text-2xl">{{ $materi['judul'] }}</h1>

                    <x-admin.lencana-materi :status="$materi['status']" :label="$materi['status_label']" />
                </div>

                <p class="mt-1 text-sm font-semibold text-dark/50">
                    {{ $materi['kategori']['nama'] }}
                    &middot; {{ $materi['tingkat_kesulitan'] }}
                    &middot; {{ $materi['jumlah_bab'] }} Bab
                    &middot; {{ $materi['waktu_baca'] }} menit baca
                </p>

                @if (filled($materi['deskripsi']))
                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-dark/70">{{ $materi['deskripsi'] }}</p>
                @endif
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-dark/5 pt-4 sm:grid-cols-3">
            <div>
                <dt class="text-xs font-bold text-dark/45">Dibuat oleh</dt>

                <dd class="mt-1 inline-flex items-center gap-2 text-sm font-semibold text-dark">
                    <x-admin.avatar :inisial="$materi['pembuat']['inisial']" :warna="$materi['pembuat']['warna']"
                        :warnaGelap="$materi['pembuat']['warna_gelap']" />

                    {{ $materi['pembuat']['nama'] }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-dark/45">Dibuat pada</dt>

                <dd class="mt-1 text-sm font-semibold text-dark">{{ $materi['tanggal_dibuat'] }}</dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-dark/45">Terbit pada</dt>

                <dd class="mt-1 text-sm font-semibold text-dark">
                    @if ($materi['status'] === 'published' && filled($materi['tanggal_terbit']))
                        {{ $materi['tanggal_terbit'] }}
                    @else
                        <span class="text-dark/45">Belum tayang</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if (filled($materi['catatan_pengajuan']))
            <div class="mt-4 rounded-2xl border border-lavender bg-brand-bg px-4 py-3.5">
                <p class="text-xs font-bold text-dark/50">Catatan pengajuan dari pemilik</p>

                <p class="mt-1 text-sm leading-relaxed text-dark/80">{{ $materi['catatan_pengajuan'] }}</p>
            </div>
        @endif

        @if (filled($materi['catatan_admin']))
            <div class="mt-4 rounded-2xl border border-[#f4c7cd] bg-[#fdecee] px-4 py-3.5">
                <p class="text-xs font-bold text-[#a33a46]">Alasan ditolak sebelumnya</p>

                <p class="mt-1 text-sm leading-relaxed text-[#a33a46]">{{ $materi['catatan_admin'] }}</p>
            </div>
        @endif
    </header>

    {{-- =====================
         ISI MATERI
    ====================== --}}
    <div class="ad-seksi">
        <div class="space-y-4">
            @foreach ($materi['seksi'] as $seksi)
                <section class="kartu-detail p-5 sm:p-6 lg:p-7">
                    <h2 class="materi-seksi__judul">{{ $seksi['nomor'] }}. {{ $seksi['judul'] }}</h2>

                    @if ($seksi['blok'] !== [])
                        <div class="mt-4 space-y-4">
                            @foreach ($seksi['blok'] as $blok)
                                @if ($blok['tipe'] === 'kode')
                                    <div class="kode-blok" data-blok-kode data-bahasa="{{ $blok['bahasa'] }}">
                                        <pre class="kode-blok__isi">
                                            <code class="font-mono">{!! $blok['sorot'] ?? e($blok['kode']) !!}</code>
                                        </pre>
                                    </div>
                                @else
                                    <x-materi.detail-blok :blok="$blok" />
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-dark/45">Bagian ini belum diisi.</p>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endsection