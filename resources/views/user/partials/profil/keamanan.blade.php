{{--
    Kartu "Keamanan": satu-satunya tempat mengubah password.

    Isinya cuma satu baris Password plus tombolnya. Formnya tidak ada di
    sini, tapi di dialog (x-profil.kata-sandi) supaya halaman ini tetap
    tenang dan tidak penuh form yang belum diisi.
--}}

<section
    data-reveal
    style="--reveal-delay: 220ms"
    class="profil-kartu p-5 sm:p-6"
    aria-labelledby="profil-judul-keamanan"
>
    <x-profil.kepala judul="Keamanan" ikon="perisai" />

    <div class="mt-4 flex items-start gap-3">
        <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-lavender/60 text-primary"
            aria-hidden="true"
        >
            <svg
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="{{ \App\Support\Ikon::path('gembok') }}"
                />
            </svg>
        </span>

        <div class="min-w-0">
            <h3 class="text-sm font-bold text-dark">Password</h3>

            <p class="mt-1 text-sm leading-relaxed text-dark/55">Perbarui password akunmu secara berkala untuk menjaga keamanan akun.</p>
        </div>
    </div>

    {{-- w-full di mobile, tombol otomatis di layar lebar. --}}
    <button
        type="button"
        data-dialog-buka="dialog-kata-sandi"
        class="tombol-utama mt-5 w-full sm:w-auto"
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
                d="{{ \App\Support\Ikon::path('gembok') }}"
            />
        </svg>

        Ubah Password
    </button>
</section>
