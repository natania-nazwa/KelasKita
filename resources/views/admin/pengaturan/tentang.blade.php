@extends('layouts.admin')

@section('title', 'Tentang Kelas Kita | KelasKita')

@section('content')

    {{--
        "Tentang Kelas Kita" di dalam area Pengaturan admin.

        Halaman penutup dari area Pengaturan: nama aplikasi, versi, tahun, dan
        singkatannya. Semuanya dari App\Support\Aplikasi, satu sumber dengan
        halaman Informasi Sistem, jadi nomor versi yang tampil di dua halaman
        itu tidak mungkin berbeda.

        Baris yang datanya memang belum ada tidak ditampilkan sama sekali.
        APP_PENGEMBANG di .env masih kosong, jadi baris "Developer" dilewati
        dan tidak menampilkan tanda hubung sebagai pengganti: halaman ini
        tidak pernah menebak informasi yang belum ada.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Tentang Kelas Kita" ikon="buku"
            subjudul="Lihat informasi aplikasi, versi, dan pembaruan."
            :aksi-teks="'Kembali ke Pengaturan'"
            :aksi-href="route('admin.pengaturan')" />
    </div>

    <div class="ad-atur-lebar">
        <div class="ad-kartu">
            <div class="ad-kartu__badan !py-8 text-center">
                <span class="ad-cucian-ungu mx-auto grid h-16 w-16 place-items-center rounded-2xl">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo KelasKita"
                        class="h-full w-full object-contain">
                </span>

                <h2 class="mt-4 font-display text-xl font-extrabold tracking-tight text-[var(--ad-teks)]">
                    {{ \App\Support\Aplikasi::NAMA }}
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[var(--ad-teks-2)]">
                    {{ \App\Support\Aplikasi::DESKRIPSI }}
                </p>

                <dl class="mx-auto mt-6 flex max-w-sm flex-wrap items-center justify-center gap-x-6 gap-y-3">
                    <div>
                        <dt class="text-[0.625rem] font-semibold uppercase tracking-wider text-[var(--ad-teks-2)]">
                            Versi
                        </dt>
                        <dd class="mt-0.5 text-sm font-bold text-[var(--ad-teks)]">{{ $aplikasi->versi() }}</dd>
                    </div>

                    <div>
                        <dt class="text-[0.625rem] font-semibold uppercase tracking-wider text-[var(--ad-teks-2)]">
                            Tahun
                        </dt>
                        <dd class="mt-0.5 text-sm font-bold text-[var(--ad-teks)]">{{ $aplikasi->tahun() }}</dd>
                    </div>

                    @if (filled($aplikasi->pengembang()))
                        <div>
                            <dt class="text-[0.625rem] font-semibold uppercase tracking-wider text-[var(--ad-teks-2)]">
                                Developer
                            </dt>
                            <dd class="mt-0.5 text-sm font-bold text-[var(--ad-teks)]">
                                {{ $aplikasi->pengembang() }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <footer class="ad-kartu__kaki text-center">
                <p class="ad-teks-2 !text-xs">{{ $aplikasi->hakCipta() }}</p>
            </footer>
        </div>
    </div>

@endsection