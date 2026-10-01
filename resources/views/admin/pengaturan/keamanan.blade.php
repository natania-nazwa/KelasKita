@extends('layouts.admin')

@section('title', 'Keamanan | KelasKita')

@section('content')

    {{--
        Halaman "Keamanan" di dalam area Pengaturan admin.

        Dua hal di sini: mengubah kata sandi, dan memperpendek jarak antar
       -login dengan mencabut sesi di perangkat lain.

        Formnya satu kartu penuh, bukan dialog seperti di halaman Pengaturan.
        Alasannya isi formnya tiga kolom bertumpuk, dan dialog setinggi itu
        di HP membuat kolom ketiganya keluar layar. Halaman ini juga yang
        opened lewat tombol "Ubah Password" di footer, jadi admin yang sudah
        sampai sini bisa langsung isi.

        Aturan passwordnya bukan ditulis di sini: form request-nya
        KataSandiRequest yang sama dengan halaman Profil milik pengguna.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Keamanan" subjudul="Ubah password dan kelola keamanan akun."
            ikon="perisai" />
    </div>

    <div class="ad-atur-lebar">
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="gembok" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="ad-kartu__kepala-judul">Ubah Password</h2>
                </div>
            </header>

            <form method="POST" action="{{ route('admin.pengaturan.keamanan.kata-sandi') }}">
                @csrf
                @method('PUT')

                <div class="ad-kartu__badan">
                    @if ($errors->any())
                        <div class="ad-alert ad-alert--bahaya mb-5" role="alert">
                            <span class="ad-alert__ikon" aria-hidden="true">
                                <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                            </span>

                            <div class="min-w-0">
                                <p class="font-semibold">Password belum bisa diganti:</p>
                                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                    @foreach ($errors->all() as $pesan)
                                        <li>{{ $pesan }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        @foreach ([
                            'kata_sandi_lama' => ['Password saat ini', 'Masukkan password yang sekarang dipakai', 'current-password'],
                            'kata_sandi_baru' => ['Password baru', 'Minimal 8 karakter', 'new-password'],
                            'kata_sandi_baru_konfirmasi' => ['Konfirmasi password baru', 'Ulangi password baru', 'new-password'],
                        ] as $kolom => [$label, $petunjuk, $autocomplete])
                            <div class="ad-field">
                                <label class="ad-field__label" for="keamanan-{{ $kolom }}">{{ $label }}</label>

                                <div class="relative">
                                    <input class="ad-input ad-input-ikon" type="password" name="{{ $kolom }}"
                                        id="keamanan-{{ $kolom }}"
                                        value="{{ old($kolom) }}"
                                        placeholder="{{ $petunjuk }}"
                                        autocomplete="{{ $autocomplete }}" required>

                                    {{--
                                        Tombol mata hanya mengubah atribut type
                                        milik kolom di sebelahnya. Isi password
                                        tidak pernah dibaca, disalin, atau
                                        disimpan di mana pun; pemeriksaan
                                        password lama tetap di server.
                                    --}}
                                    <button type="button"
                                        class="ad-kolom-ikon"
                                        data-atur-lihat-sandi aria-pressed="false"
                                        aria-label="Tampilkan {{ strtolower($label) }}">
                                        <x-admin.ikon nama="mata" ukuran="w-4 h-4"
                                            data-atur-mata-tertutup />
                                        <x-admin.ikon nama="mata-tutup" ukuran="w-4 h-4" class="hidden"
                                            data-atur-mata-terbuka />
                                    </button>
                                </div>

                                @error($kolom)
                                    <span class="ad-field__error">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="ad-alert mt-5">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                        </span>

                        <p class="min-w-0">
                            Setelah diganti, sesi di perangkat lain ikut diakhiri.
                            Sesi di perangkat ini tetap aktif. Daftar lengkapnya
                            ada di
                            <a class="ad-tautan !text-xs" href="{{ route('admin.pengaturan.sesi') }}">Sesi Login</a>.
                        </p>
                    </div>
                </div>

                <footer class="ad-kartu__kaki flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('admin.pengaturan') }}" class="ad-tombol ad-tombol--halus justify-center">
                        <x-admin.ikon nama="panah-kiri" />
                        Kembali ke Pengaturan
                    </a>

                    <button type="submit" class="ad-tombol ad-tombol--utama justify-center">
                        <x-admin.ikon nama="gembok" />
                        Ubah Password
                    </button>
                </footer>
            </form>
        </div>
    </div>

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

@endsection