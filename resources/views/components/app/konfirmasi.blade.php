{{--
    Dialog konfirmasi hapus (materi / quiz).

    Satu dialog untuk semua kartu di halaman "Karya Saya": judul dan
    pesannya diambil dari atribut data-* pada form hapus yang memanggilnya,
    jadi tiap kartu bisa punya kalimat sendiri (lihat initKonfirmasi() di
    resources/js/app.js).

    Tombol hapus di kartu sengaja type="button", bukan submit: kalau
    JavaScript tidak berjalan, tidak ada yang terkirim (menggagal dengan
    aman) alih-alih terhapus tanpa konfirmasi.

    Styling memakai .dialog-bab yang sama dengan dialog hapus bab di form
    Tambah Materi, jadi tampilannya tidak terasa seperti dialog lain.
--}}

<div class="dialog-bab" data-konfirmasi-dialog role="dialog" aria-modal="true"
    aria-labelledby="judul-dialog-konfirmasi" aria-describedby="pesan-dialog-konfirmasi">
    <div class="w-full max-w-sm rounded-2xl border border-ungu-line bg-white p-5 shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#fdecee] text-[#c2414a]"
            aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </span>

        <h2 id="judul-dialog-konfirmasi" class="mt-4 text-base font-extrabold text-dark" data-konfirmasi-judul>
            Hapus karya ini?
        </h2>

        <p id="pesan-dialog-konfirmasi" class="mt-1.5 text-sm leading-relaxed text-muted" data-konfirmasi-pesan>
            Karya ini akan dihapus dan tidak dapat dikembalikan.
        </p>

        <div class="mt-5 flex flex-col gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" data-konfirmasi-batal class="tombol-garis justify-center">
                Batal
            </button>

            <button type="button" data-konfirmasi-ya
                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a]">
                Hapus
            </button>
        </div>
    </div>
</div>
