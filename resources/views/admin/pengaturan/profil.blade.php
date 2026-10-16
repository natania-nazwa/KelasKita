@extends('layouts.admin')

@section('title', 'Profil Admin | KelasKita')

@section('content')

    {{--
        Halaman "Profil Admin" di dalam area Pengaturan.

        Bentuknya mengikuti halaman Profil milik pengguna: kartu kepala
        berisi avatar, nama, email, dan tombol Edit Profil; kartu
        Informasi Akun berisi nilai yang hanya bisa dibaca; dan seluruh
        isian (nama, email, foto) pindah ke dalam dialog. Peran tetap
        tampil sebagai nilai baca saja, karena akun ini adalah satu-
        satunya admin pengelola dan perannya tidak bisa diubah dari
        mana pun.

        Aturan validasinya bukan ditulis di sini: form request-nya
        ProfilIsianRequest yang sama dengan halaman Profil milik
        pengguna, dan tempat fotonya App\Support\BerkasProfil. Satu
        tempat untuk aturan nama dan email, supaya halaman ini dan
        halaman Profil tidak mungkin berbeda.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Profil Admin" subjudul="Kelola foto profil, nama, dan email."
            ikon="pengguna"
            :aksi-href="route('admin.pengaturan')"
            aksi-teks="Kembali ke Pengaturan" />
    </div>

    <div class="ad-atur-lebar space-y-5">
        {{--
            Kartu kepala. Avatar memakai data-atur-avatar langsung di
            komponen avatarnya, supaya pratinjau foto yang baru dipilih
            bisa mengganti isinya tanpa menulis ulang komponen — sama
            seperti halaman Profil milik pengguna.
        --}}
        <div class="ad-kartu">
            <div class="ad-kartu__badan">
                <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:gap-5 sm:text-left">
                    <x-admin.avatar :inisial="$admin->inisial()" ukuran="besar"
                        :warna="$admin->warnaAvatar()['warna']"
                        :warna-gelap="$admin->warnaAvatar()['warna_gelap']"
                        :foto="$admin->fotoProfilUrl()"
                        data-atur-avatar />

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-[var(--ad-teks)]">
                            {{ $admin->nama }}
                        </h2>

                        <p class="mt-1 flex items-center justify-center gap-1.5 text-sm text-[var(--ad-teks-2)] sm:justify-start">
                            <x-admin.ikon nama="surel" ukuran="w-4 h-4" />
                            <span class="truncate">{{ $admin->email }}</span>
                        </p>
                    </div>

                    {{--
                        Dua aksi, ditumpuk di mobile dan berdampingan di
                        layar lebar — susunan yang sama dengan kartu
                        kepala halaman Profil milik pengguna.
                    --}}
                    <div class="flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row">
                        <button type="button" class="ad-tombol ad-tombol--utama justify-center"
                            data-atur-dialog-buka="atur-edit-profil">
                            <x-admin.ikon nama="pena" />
                            Edit Profil
                        </button>

                        {{-- Hanya muncul kalau memang ada foto yang terpasang. --}}
                        @if (filled($admin->foto_profil))
                            <button type="button" class="ad-tombol ad-tombol--halus justify-center"
                                data-atur-dialog-buka="atur-hapus-foto">
                                <x-admin.ikon nama="sampah" />
                                Hapus Foto
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{--
            Informasi Akun. Nilainya sengaja tidak bisa diklik: cara
            mengubahnya adalah tombol "Edit Profil" di kartu kepala,
            persis seperti halaman Profil milik pengguna.
        --}}
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="pengguna" ukuran="w-5 h-5" />
                    </span>

                    <h2 class="ad-kartu__kepala-judul">Informasi Akun</h2>
                </div>
            </header>

            <div class="ad-kartu__badan">
                <div class="space-y-4">
                    <div class="ad-field">
                        <span class="ad-field__label">Nama lengkap</span>

                        <p class="ad-input !bg-transparent !text-[var(--ad-teks-2)]" aria-readonly="true">
                            {{ $admin->nama }}
                        </p>
                    </div>

                    <div class="ad-field">
                        <span class="ad-field__label">Email</span>

                        <p class="ad-input !bg-transparent !text-[var(--ad-teks-2)]" aria-readonly="true">
                            {{ $admin->email }}
                        </p>
                    </div>

                    <div class="ad-field">
                        <span class="ad-field__label">Peran</span>

                        <p class="ad-input !bg-transparent !text-[var(--ad-teks-2)]" aria-readonly="true">
                            Administrator
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--
        Dialog edit. Kalau validasi server menolak isian, Laravel
        memuat ulang halaman dengan $errors terisi dan dialog ini harus
        ikut terbuka — kalau tidak, pesan kesalahan terkunci di dalam
        kotak yang tertutup dan admin tidak pernah melihatnya. Polanya
        sama dengan dialog tambah/ubah mata pelajaran.
    --}}
    @php($bukaEdit = $errors->any())

    <div @class(['ad-dialog', 'is-buka' => $bukaEdit])
        data-atur-dialog="atur-edit-profil" role="dialog" aria-modal="true"
        aria-hidden="{{ $bukaEdit ? 'false' : 'true' }}"
        aria-labelledby="atur-edit-profil-judul">
        <div class="ad-dialog__kartu">
            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="atur-edit-profil-judul">Edit Profil</h2>
                </div>

                <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            {{--
                File diunggah lewat form biasa, bukan lewat fetch: begitu
                tombol ditekan, halaman selesai dimuat ulang dan kartu
                kepala, topbar, serta semua avatar ikut memakai data
                terbaru. Foto lama hanya diganti kalau ada file baru yang
                benar-benar terkirim, jadi mengoreksi email saja tidak
                membuat foto hilang.
            --}}
            <form method="POST" action="{{ route('admin.pengaturan.profil.update') }}"
                enctype="multipart/form-data" id="form-atur-profil">
                @csrf
                @method('PUT')

                <div class="ad-dialog__badan">
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

                    <div class="space-y-4">
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
                            Foto profil. Kolom berkas disembunyikan lalu
                            diganti tombol biasa, karena gaya bawaan kolom
                            berkas tidak cocok dengan tombol lain di
                            halaman ini. Yang benar-benar diklik tetap
                            <input type="file">, jadi papan ketik dan
                            pembaca layar tetap bisa memakainya.
                        --}}
                        <div class="ad-field">
                            <span class="ad-field__label">Foto Profil</span>

                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <x-admin.avatar :inisial="$admin->inisial()" ukuran="kecil"
                                    :warna="$admin->warnaAvatar()['warna']"
                                    :warna-gelap="$admin->warnaAvatar()['warna_gelap']"
                                    :foto="$admin->fotoProfilUrl()"
                                    data-atur-avatar />

                                <div class="min-w-0 flex-1">
                                    <input class="sr-only" type="file" name="foto_profil"
                                        id="atur-foto-profil" accept="image/jpeg,image/png,image/webp"
                                        data-atur-foto>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="ad-tombol ad-tombol--garis"
                                            data-atur-foto-tombol>
                                            <x-admin.ikon nama="kamera" />
                                            Ganti Foto
                                        </button>

                                        <button type="button" class="ad-tombol ad-tombol--halus"
                                            data-atur-dialog-buka="atur-hapus-foto"
                                            @disabled(! filled($admin->foto_profil))>
                                            <x-admin.ikon nama="sampah" />
                                            Hapus Foto
                                        </button>
                                    </div>

                                    <p class="ad-field__petunjuk mt-2">
                                        JPG, PNG, atau WEBP. Maksimal 2MB.
                                    </p>

                                    <p class="ad-field__petunjuk mt-1 hidden" data-atur-foto-nama></p>
                                </div>
                            </div>
                        </div>

                        {{--
                            Peran tidak punya kolom di form. Akun ini
                            adalah satu-satunya admin pengelola, jadi
                            peran tidak bisa diubah dari mana pun,
                            termasuk dari dialog ini.
                        --}}
                        <div class="ad-field">
                            <span class="ad-field__label">Peran</span>

                            <p class="ad-input !bg-transparent !text-[var(--ad-teks-2)]" aria-readonly="true">
                                Administrator
                            </p>
                        </div>
                    </div>
                </div>

                <footer class="ad-dialog__kaki">
                    <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                    <button type="submit" class="ad-tombol ad-tombol--utama">
                        <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />
                        Simpan Perubahan
                    </button>
                </footer>
            </form>
        </div>
    </div>

    {{--
        Hapus foto. Dialog terpisah supaya tombol di dalam form utama
        tidak ikut mengirim isian lain, dan supaya pengonfirmasi tidak
        ikut hilang bersama halaman.
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
