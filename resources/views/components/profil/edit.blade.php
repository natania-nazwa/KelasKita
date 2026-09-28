@props ([
    'pengguna',
])

@php
    /*
     * Satu form untuk dua pemicu: tombol "Edit Profil" di kartu kepala,
     * dan tombol "Ganti Foto" di dalam dialog ini sendiri. Keduanya
     * mengirim field yang sama persis, jadi cukup satu form dan satu
     * endpoint (user.profil.update).
     */
    $adaFoto = filled($pengguna->foto_profil);
@endphp

<x-profil.dialog
    id="dialog-edit-profil"
    judul="Edit Profil"
    pesan="Perbarui nama, email, dan foto profil akunmu."
    :ikon="\App\Support\Ikon::path('pena')"
>
    <x-slot:isi>
        {{--
            File diunggah lewat form biasa, bukan lewat JavaScript fetch:
            begitu tombol ditekan, halaman selesai dimuat ulang dan kartu
            kepala, top bar, serta semua avatar ikut memakai data terbaru.
            Foto lama hanya diganti kalau ada file baru yang benar-benar
            terkirim, jadi mengoreksi email saja tidak membuat foto hilang.
        --}}
        <form
            id="form-edit-profil"
            method="POST"
            action="{{ route('user.profil.update') }}"
            enctype="multipart/form-data"
            class="space-y-4"
        >
            @csrf
            @method ('PUT')

            <div>
                <label for="profil-nama" class="label-form">Nama Lengkap</label>
                <input
                    id="profil-nama"
                    name="nama"
                    type="text"
                    required
                    maxlength="120"
                    autocomplete="name"
                    value="{{ old('nama', $pengguna->nama) }}"
                    class="kolom-form mt-1.5"
                    placeholder="Nama lengkap kamu"
                />

                @error ('nama')
                    <p class="profil-galat" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profil-email" class="label-form">Email</label>
                <input
                    id="profil-email"
                    name="email"
                    type="email"
                    required
                    maxlength="255"
                    autocomplete="email"
                    value="{{ old('email', $pengguna->email) }}"
                    class="kolom-form mt-1.5"
                    placeholder="email@contoh.com"
                />

                @error ('email')
                    <p class="profil-galat" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{--
                Foto profil. Kolom file disembunyikan karena gaya checkbox
                aslinya tidak cocok, lalu diganti tombol biasa; yang
                sebenarnya diklik tetap <input type="file">, jadi
                keyboard dan pembaca layar tetap bekerja.
            --}}
            <div>
                <span class="label-form">Foto Profil</span>

                <div class="mt-1.5 flex flex-wrap items-center gap-3">
                    <x-profil.avatar :pengguna="$pengguna" ukuran="kecil" />

                    <div class="min-w-0 flex-1">
                        <input
                            id="profil-foto"
                            name="foto_profil"
                            type="file"
                            class="sr-only"
                            accept="image/jpeg,image/png,image/webp"
                            data-profil-pilih-foto
                        />

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                data-profil-pilih-foto-tombol
                                class="tombol-garis py-2 text-xs"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="{{ \App\Support\Ikon::path('kamera') }}"
                                    />
                                </svg>

                                Ganti Foto
                            </button>

                            {{-- Hanya muncul kalau memang ada foto yang terpasang. --}}
                            <button
                                type="button"
                                data-profil-buka-dialog="dialog-hapus-foto"
                                @disabled (! $adaFoto)
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-bold text-[#c2414a] transition hover:bg-[#fdecee] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="{{ \App\Support\Ikon::path('sampah') }}"
                                    />
                                </svg>

                                Hapus Foto
                            </button>
                        </div>

                        {{-- Nama berkas baru, hanya muncul setelah ada file dipilih. --}}
                        <p
                            class="mt-1.5 hidden truncate text-xs text-dark/55"
                            data-profil-nama-berkas
                            aria-live="polite"
                        ></p>

                        <p class="mt-1 text-xs text-dark/45">JPG, PNG, atau WEBP. Maksimal 2MB.</p>

                        @error ('foto_profil')
                            <p class="profil-galat" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Kirim error validasi yang tidak menempel ke field tertentu. --}}
            @error ('form')
                <p class="profil-galat" role="alert">{{ $message }}</p>
            @enderror
        </form>
    </x-slot:isi>

    <x-slot:aksi>
        <div class="modal__aksi">
            <button type="button" data-dialog-batal class="tombol-garis">
                Batal
            </button>

            {{--
                type="submit" + form="...": tombolnya berada di luar <form>,
                tapi tetap menunjuk form yang benar supaya tidak perlu
                menyalin field ke tempat lain.
            --}}
            <button type="submit" form="form-edit-profil" class="tombol-utama">
                Simpan Perubahan
            </button>
        </div>
    </x-slot:aksi>
</x-profil.dialog>
