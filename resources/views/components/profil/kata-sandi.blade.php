@props ([
    /*
     * Error yang masih tertinggal setelah submit gagal. Dibaca di sini
     * supaya tetap menempel ke field yang benar meski halaman dimuat
     * ulang, sama seperti error form biasa.
     */
    'kunci' => 'kata-sandi',
])

{{--
    Form ubah password.

    Tiga field memakai komponen .kolom-form yang sama dengan form Materi,
    ditambah satu tombol mata di kanan tiap kolom untuk
    menampilkan/menyembunyikan isi teksnya. Password tidak pernah
    disimpan atau dibandingkan di sisi peramban: seluruh pengecekan
    (lama benar, konfirmasi sama, panjang minimal) dilakukan di server
    oleh KataSandiRequest.
--}}
<x-profil.dialog
    id="dialog-kata-sandi"
    judul="Ubah Password"
    pesan="Masukkan password lama untuk memastikan ini wirklich kamu."
    :ikon="\App\Support\Ikon::path('gembok')"
>
    <x-slot:isi>
        <form
            id="form-kata-sandi"
            method="POST"
            action="{{ route('user.profil.kata-sandi') }}"
            class="space-y-4"
            data-profil-form-sandi
        >
            @csrf
            @method ('PUT')

            @foreach ([
                'kata_sandi_lama' => 'Password Lama',
                'kata_sandi_baru' => 'Password Baru',
                'kata_sandi_baru_konfirmasi' => 'Konfirmasi Password Baru',
            ] as $nama => $label)
                <div>
                    <label
                        for="{{ $kunci }}-{{ $nama }}"
                        class="label-form"
                        >{{ $label }}</label
                    >

                    <div class="relative mt-1.5">
                        <input
                            id="{{ $kunci }}-{{ $nama }}"
                            name="{{ $nama }}"
                            type="password"
                            required
                            autocomplete="{{ $nama === 'kata_sandi_lama' ? 'current-password' : 'new-password' }}"
                            class="kolom-form pr-11"
                            placeholder="••••••••"
                        />

                        {{--
                            Tombol mata: type="button" supaya tidak ikut
                            mengirim form, dan aria-pressed memberi tahu
                            pembaca layar sedang menampilkan atau
                            menyembunyikan isi kolom.
                        --}}
                        <button
                            type="button"
                            data-profil-lihat-sandi
                            aria-pressed="false"
                            aria-label="Tampilkan password"
                            class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-dark/40 transition hover:bg-lavender/70 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                                data-profil-mata-terbuka
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="{{ \App\Support\Ikon::path('mata') }}"
                                />
                            </svg>

                            <svg
                                class="hidden h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                                data-profil-mata-tertutup
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="{{ \App\Support\Ikon::path('mata-tutup') }}"
                                />
                            </svg>
                        </button>
                    </div>

                    @error ($nama)
                        <p class="profil-galat" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <p class="text-xs leading-relaxed text-dark/50">Password baru minimal 8 karakter. Setelah diganti, gunakan password baru saat masuk berikutnya.</p>
        </form>
    </x-slot:isi>

    <x-slot:aksi>
        <div class="modal__aksi">
            <button type="button" data-dialog-batal class="tombol-garis">
                Batal
            </button>

            <button type="submit" form="form-kata-sandi" class="tombol-utama">
                Simpan Password
            </button>
        </div>
    </x-slot:aksi>
</x-profil.dialog>
