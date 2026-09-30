@extends('layouts.admin')

@section('title', 'Pengaturan | KelasKita')

@section('content')

    {{--
        Halaman "Pengaturan" area admin.

        Halaman ini menampilkan akun admin yang sedang login, dan tidak
        menyimpan apa pun.

        Form ubah nama, email, dan password tidak diduplikasi di sini.
        Kemampuan itu sudah ada dan sudah lengkap validasinya di halaman
        Profil (/user/profil) milik akun yang sama, dengan aturan
        password, verifikasi email, dan penanganan kata sandi lama.
        Menyalin form yang sama ke tempat kedua berarti menyalin aturan
        itu juga, dan dua tempat itu pasti akan berbeda suatu saat.

        Karena itu isian di bawah sengaja dibuat disabled, bukan form
        yang bisa diketik lalu diam-diam tidak tersimpan. Kotaknya tetap
        ada supaya admin tahu persis apa yang dibutuhkan, dan tombolnya
        mengarah ke halaman yang benar-benar menyimpan perubahannya.
    --}}

    <x-admin.kepala judul="Pengaturan" subjudul="Akun admin dan keamanan KelasKita." />

    <section class="ad-seksi ad-grid ad-grid--dua">
        <div class="space-y-5">

            {{-- ==================== PROFIL ADMIN ==================== --}}
            <div class="ad-kartu">
                <header class="ad-kartu__kepala">
                    <div class="ad-kartu__kepala-titik">
                        <span class="ad-cepat__ikon" aria-hidden="true">
                            <x-admin.ikon nama="pengguna" ukuran="w-5 h-5" />
                        </span>

                        <h2 class="ad-kartu__kepala-judul">Profil Admin</h2>
                    </div>
                </header>

                <div class="ad-kartu__badan">
                    <div class="flex items-center gap-4">
                        <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="besar" />

                        <div class="min-w-0">
                            <p class="text-base font-bold text-[#29245C]">{{ $admin?->nama ?? 'Admin' }}</p>
                            <p class="mt-0.5 break-all text-xs text-[#77739A]">{{ $admin?->email }}</p>

                            <span class="ad-lencana ad-lencana--ungu mt-1.5">
                                <x-admin.ikon nama="perisai" ukuran="w-3 h-3" />
                                Administrator
                            </span>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <label class="ad-field">
                            <span class="ad-field__label">Nama</span>

                            <input class="ad-input" type="text" value="{{ $admin?->nama }}" readonly>
                        </label>

                        <label class="ad-field">
                            <span class="ad-field__label">Email</span>

                            <input class="ad-input" type="email" value="{{ $admin?->email }}" readonly>
                        </label>
                    </div>

                    <div class="ad-alert mt-4">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                        </span>

                        <p class="min-w-0">
                            Nama dan email diubah di halaman Profil, supaya aturannya
                            hanya ada di satu tempat dan tidak bisa berbeda antara
                            halaman ini dan halaman Profil.
                        </p>
                    </div>
                </div>

                <footer class="ad-kartu__kaki">
                    <a href="{{ route('user.profil') }}" class="ad-tombol ad-tombol--utama">
                        <x-admin.ikon nama="pena" />
                        Simpan Perubahan
                    </a>
                </footer>
            </div>

            {{-- ==================== KEAMANAN ==================== --}}
            <div class="ad-kartu">
                <header class="ad-kartu__kepala">
                    <div class="ad-kartu__kepala-titik">
                        <span class="ad-cepat__ikon" aria-hidden="true">
                            <x-admin.ikon nama="gembok" ukuran="w-5 h-5" />
                        </span>

                        <h2 class="ad-kartu__kepala-judul">Keamanan</h2>
                    </div>
                </header>

                <div class="ad-kartu__badan">
                    <div class="space-y-4">
                        <label class="ad-field">
                            <span class="ad-field__label">Password lama</span>

                            <input class="ad-input" type="password" placeholder="••••••••" disabled>
                        </label>

                        <label class="ad-field">
                            <span class="ad-field__label">Password baru</span>

                            <input class="ad-input" type="password" placeholder="Minimal 8 karakter" disabled>

                            <span class="ad-field__petunjuk">Minimal 8 karakter.</span>
                        </label>

                        <label class="ad-field">
                            <span class="ad-field__label">Konfirmasi password</span>

                            <input class="ad-input" type="password" placeholder="Ulangi password baru" disabled>
                        </label>
                    </div>

                    <div class="ad-alert mt-4">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                        </span>

                        <p class="min-w-0">
                            Isian di atas belum bisa diklik: password hanya bisa diubah
                            di halaman Profil, yang juga memeriksa password lama kamu
                            sebelum menyimpan.
                        </p>
                    </div>
                </div>

                <footer class="ad-kartu__kaki">
                    <a href="{{ route('user.profil') }}" class="ad-tombol ad-tombol--garis">
                        <x-admin.ikon nama="gembok" />
                        Ubah Password
                    </a>
                </footer>
            </div>
        </div>

        {{-- ==================== INFORMASI AKUN ==================== --}}
        <div class="ad-kartu h-fit">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="kalender" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="ad-kartu__kepala-judul">Informasi Akun</h2>
                </div>
            </header>

            <div class="ad-kartu__badan">
                <dl>
                    <div class="flex items-center justify-between gap-3 border-b border-[#E8E4F5] py-2.5">
                        <dt class="text-xs text-[#77739A]">Bergabung</dt>
                        <dd class="shrink-0 text-xs font-bold text-[#29245C]">
                            {{ $bergabung?->translatedFormat('d F Y') ?? '-' }}
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-b border-[#E8E4F5] py-2.5">
                        <dt class="text-xs text-[#77739A]">Peran</dt>
                        <dd>
                            <span class="ad-lencana ad-lencana--ungu">Admin</span>
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-b border-[#E8E4F5] py-2.5">
                        <dt class="text-xs text-[#77739A]">Status</dt>
                        <dd>
                            @if ($admin?->isAktif())
                                <span class="ad-lencana ad-lencana--sukses">
                                    <span class="ad-lencana__titik" aria-hidden="true"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="ad-lencana ad-lencana--abu">
                                    <span class="ad-lencana__titik" aria-hidden="true"></span>
                                    Nonaktif
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <p class="ad-seksi__judul mt-6 !text-base">Ringkasan Platform</p>

                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach ([
                        ['Pengguna', $ringkasan['pengguna'], 'grup'],
                        ['Materi', $ringkasan['materi'], 'buku'],
                        ['Quiz', $ringkasan['quiz'], 'soal'],
                    ] as $item)
                        <div class="rounded-xl border border-[#E8E4F5] bg-[#F7F5FF] px-2 py-3 text-center">
                            <x-admin.ikon :nama="$item[2]" class="mx-auto mb-1.5 w-4 h-4 text-[#6D4AFF]" />

                            <p class="text-lg font-extrabold tabular-nums text-[#29245C]">{{ $item[1] }}</p>
                            <p class="text-[0.625rem] text-[#77739A]">{{ $item[0] }}</p>
                        </div>
                    @endforeach
                </div>

                <p class="ad-teks-2 mt-5 !text-xs">
                    Halaman ini hanya menampilkan. Semua perubahan disimpan oleh
                    halaman Profil, bukan di sini.
                </p>
            </div>
        </div>
    </section>

@endsection
