@extends('layouts.admin')

@section('title', 'Profil Admin | KelasKita')

@section('content')

    {{--
        Halaman "Profil Admin" di dalam area Pengaturan.

        Satu kartu, isinya satu form. Form-nya di sini, bukan di dialog,
        karena ada unggah berkas foto: dialog yang memuatkan pemilih berkas
        dan tombol hapus foto jadi sempit sekali di layar HP, dan foto yang
        baru dipilih sulit diperiksa sebelum disimpan kalau tidak terlihat
        penuh.

        Aturan validasinya bukan ditulis di sini: form request-nya
        ProfilIsianRequest yang sama dengan halaman Profil milik pengguna,
        dan tempat fotonya App\Support\BerkasProfil. Satu tempat untuk aturan
        nama dan email, supaya halaman ini dan halaman Profil tidak mungkin
        berbeda.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Profil Admin" subjudul="Kelola foto profil, nama, dan email."
            ikon="pengguna" />
    </div>

    <div class="ad-atur-lebar">
        <div class="ad-kartu">
            <form method="POST" action="{{ route('admin.pengaturan.profil.update') }}"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="ad-kartu__badan">
                    @if ($errors->any())
                        <div class="ad-alert ad-alert--bahaya mb-5" role="alert">
                            <span class="ad-alert__ikon" aria-hidden="true">
                                <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                            </span>

                            <div class="min-w-0">
                                <p class="font-semibold">Profil belum bisa diperbarui:</p>
                                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                    @foreach ($errors->all() as $pesan)
                                        <li>{{ $pesan }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    {{--
                        Foto profil. Kalau admin belum memasang foto, avatar
                        memakai inisial nama depan seperti di sidebar dan
                        topbar — bukan gambar pengganti, supaya yang tampil
                        selalu sama dengan yang dilihat di tempat lain.
                    --}}
                    <div class="flex items-center gap-4">
                        {{--
                            Avatar dibungkus satu span supaya pratinjau
                            foto yang baru dipilih bisa menggantinya tanpa
                            menulis ulang komponen avatarnya.
                        --}}
                        <span data-atur-avatar>
                            <x-admin.avatar :inisial="$admin->inisial()" ukuran="besar"
                                :warna="$admin->warnaAvatar()['warna']"
                                :warna-gelap="$admin->warnaAvatar()['warna_gelap']"
                                :foto="$admin->fotoProfilUrl()" />
                        </span>

                        <div class="min-w-0">
                            {{--
                                Kolom berkas disembunyikan lalu diganti tombol
                                biasa, karena gaya bawaan kolom berkas tidak
                                cocok dengan tombol lain di halaman ini. Yang
                                benar-benar diklik tetap <input
                                type="file">, jadi papan ketik dan pembaca
                                layar tetap bisa memakainya.
                            --}}
                            <input class="sr-only" type="file" name="foto_profil"
                                id="atur-foto-profil" accept="image/jpeg,image/png,image/webp"
                                data-atur-foto>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="ad-tombol ad-tombol--garis"
                                    data-atur-foto-tombol>
                                    <x-admin.ikon nama="kamera" />
                                    Ganti Foto
                                </button>

                                @if (filled($admin->foto_profil))
                                    <button type="button" class="ad-tombol ad-tombol--halus"
                                        data-atur-dialog-buka="atur-hapus-foto">
                                        <x-admin.ikon nama="sampah" />
                                        Hapus Foto
                                    </button>
                                @endif
                            </div>

                            <p class="ad-field__petunjuk mt-2">
                                JPG, PNG, atau WEBP. Maksimal 2MB.
                            </p>

                            <p class="ad-field__petunjuk mt-1 hidden" data-atur-foto-nama></p>
                        </div>
                    </div>

                    <div class="ad-seksi mt-6 space-y-4">
                        <label class="ad-field">
                            <span class="ad-field__label">Nama lengkap</span>

                            <input class="ad-input" type="text" name="nama"
                                value="{{ old('nama', $admin->nama) }}" maxlength="120"
                                autocomplete="name" required>
                        </label>

                        <label class="ad-field">
                            <span class="ad-field__label">Email</span>

                            <input class="ad-input" type="email" name="email"
                                value="{{ old('email', $admin->email) }}" maxlength="255"
                                autocomplete="email" required>

                            <span class="ad-field__petunjuk">
                                Email ini dipakai untuk login, jadi tidak boleh sama dengan akun lain.
                            </span>
                        </label>

                        {{--
                            Peran tidak punya kolom di form. Akun ini adalah
                            satu-satunya admin pengelola, jadi peran tidak
                            bisa diubah dari mana pun, termasuk dari halaman
                            ini.
                        --}}
                        <div class="ad-field">
                            <span class="ad-field__label">Peran</span>

                            <p class="ad-input !bg-transparent !text-[var(--ad-teks-2)]" aria-readonly="true">
                                Administrator
                            </p>
                        </div>
                    </div>
                </div>

                <footer class="ad-kartu__kaki flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('admin.pengaturan') }}" class="ad-tombol ad-tombol--halus justify-center">
                        <x-admin.ikon nama="panah-kiri" />
                        Kembali ke Pengaturan
                    </a>

                    <button type="submit" class="ad-tombol ad-tombol--utama justify-center">
                        <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />
                        Simpan Perubahan
                    </button>
                </footer>
            </form>
        </div>
    </div>

    {{--
        Hapus foto. Dialog terpisah supaya tombol di dalam form utama tidak
        ikut mengirim isian lain, dan supaya pengonfirmasi tidak ikut hilang
        bersama halaman.
    --}}
    <div class="ad-dialog" data-atur-dialog="atur-hapus-foto" role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="atur-hapus-foto-judul">
        <div class="ad-dialog__kartu">
            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="atur-hapus-foto-judul">Hapus foto profil?</h2>
                </div>

                <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            <div class="ad-dialog__badan">
                <p class="ad-dialog__pesan">
                    Nama dan email tetap utuh. Avatar akan kembali memakai inisial
                    nama depanmu.
                </p>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                <form method="POST" action="{{ route('admin.pengaturan.profil.foto.destroy') }}"
                    id="form-atur-hapus-foto">
                    @csrf
                    @method('DELETE')
                </form>

                <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-atur-hapus-foto">
                    <x-admin.ikon nama="sampah" />
                    Ya, Hapus
                </button>
            </footer>
        </div>
    </div>

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

@endsection