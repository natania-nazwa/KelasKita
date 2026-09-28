{{--
    Kartu "Zona Berbahaya".

    Ini satu-satunya tempat warna merah muncul di halaman Profil, jadi
    kartu ini sengaja terpisah dari kartu lain dan diberi tanda/class
    .profil-bahaya.

    Tombol "Hapus Akun" hanya membuka dialog konfirmasi. Endpoint hapus
    akun belum ada di aplikasi ini, jadi tidak ada <form> yang dikirim:
    lihat components/profil/hapus-akun.blade.php untuk catatan cara
    menyambungkannya ke endpoint nanti.
--}}

<section
    data-reveal
    style="--reveal-delay: 340ms"
    class="profil-kartu profil-bahaya mt-5 border-[#f4c9c9] p-5 sm:p-6"
    aria-labelledby="profil-judul-bahaya"
>
    <div
        class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#fdecee] text-[#c2414a]"
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
                            d="{{ \App\Support\Ikon::path('sampah') }}"
                        />
                    </svg>
                </span>

                <h2 id="profil-judul-bahaya" class="profil-kartu__judul">
                    Zona Berbahaya
                </h2>
            </div>

            <p class="profil-kartu__deskripsi">Penghapusan akun bersifat permanen dan data akun tidak dapat dipulihkan.</p>
        </div>

        {{-- type="button" supaya tidak ada form yang ikut terkirim. --}}
        <button
            type="button"
            data-dialog-buka="dialog-hapus-akun"
            class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a] sm:w-auto"
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

            Hapus Akun
        </button>
    </div>
</section>
