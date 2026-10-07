{{--
    Konfirmasi hapus akun.

    Sekarang dialog ini benar-benar mengirim form ke
    route('user.profil.hapus'), jadi isinya bukan lagi tombol mati: formnya
    ada di slot "isi" (supaya kolom password ikut di dalam kotak dialog),
    sedangkan tombol kirim di slot "aksi" menunjuk form itu lewat atribut
    form="form-hapus-akun" — pola yang sama persis dengan kata-sandi.

    Password akun wajib diisi dan dicek di server oleh HapusAkunRequest.
    Isinya tidak pernah ditulis di markup, dibaca, atau dibandingkan di
    peramban; satu-satunya yang dilakukan tombol mata adalah mengganti
    atribut type kolomnya.

    Kalau passwordnya salah, halaman dimuat ulang dan dialog ini dibuka
    lagi oleh profil.js (lihat initDialog), sehingga pesan kesalahan di
    bawah kolom langsung terlihat tanpa perlu diklik apa-apa.
--}}
<x-profil.dialog
    id="dialog-hapus-akun"
    judul="Hapus akun?"
    bahaya
    pesan="Akun dan seluruh isinya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan."
    :ikon="\App\Support\Ikon::path('sampah')"
>
    <x-slot:isi>
        <form
            id="form-hapus-akun"
            method="POST"
            action="{{ route('user.profil.hapus') }}"
        >
            @csrf
            @method ('DELETE')

            <div>
                <label for="hapus-akun-kata-sandi" class="label-form">Password Akun</label>

                <div class="relative mt-1.5">
                    <input
                        id="hapus-akun-kata-sandi"
                        name="kata_sandi"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="kolom-form pr-11"
                        placeholder="••••••••"
                    />

                    {{--
                        Tombol mata yang sama persis dengan dialog Ubah
                        Password, jadi initLihatSandi() di profil.js bisa
                        dipakai tanpa aturan tambahan.
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

                @error ('kata_sandi')
                    <p class="profil-galat" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <p class="mt-4 text-xs leading-relaxed text-dark/50">
                Riwayat aktivitas, nilai quiz, sesi, dan bookmark ikut terhapus.
                Materi yang sudah kamu terbitkan tetap ada, tapi namanya tidak lagi
                tertaut ke akun mana pun.
            </p>
        </form>
    </x-slot:isi>

    <x-slot:aksi>
        <div class="modal__aksi">
            <button type="button" data-dialog-batal class="tombol-garis">
                Batal
            </button>

            <button
                type="submit"
                form="form-hapus-akun"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a]"
            >
                Ya, Hapus Akun
            </button>
        </div>
    </x-slot:aksi>
</x-profil.dialog>