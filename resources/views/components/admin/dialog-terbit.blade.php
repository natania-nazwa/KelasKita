@props([
    /*
     * Dialog konfirmasi sebelum menerbitkan konten.
     *
     * Dipakai oleh form Konten Pembelajaran (tombol "Publish Sekarang") dan
     * oleh daftar konten (aksi "Publikasikan" di menu tiga titik). Satu
     * dialog cukup untuk keduanya: isinya sama, dan yang berubah cuma
     * kalimat penutup yang menyebut nama konten — kalimat itu dikirim lewat
     * data-konten-nama oleh pemicunya.
     *
     * Tanpa JavaScript tombol "Publish Sekarang" tetap mengirim form apa
     * adanya, hanya tanpa konfirmasi. Itu urutan yang benar: lebih baik konten
     * terbit tanpa tanya daripada tidak bisa terbit sama sekali.
     */
    'judul' => 'Publish konten?',
    'pesan' => 'Konten ini akan langsung tersedia untuk pengguna dan notifikasi akan dikirim.',
])

<div class="ad-dialog" data-konten-publish-dialog role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="dialog-konten-publish-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="dialog-konten-publish-judul" data-konten-terbit-judul>{{ $judul }}</h2>

                <p class="ad-teks-2 mt-0.5 !text-xs" data-konten-nama></p>
            </div>

            <button type="button" class="ad-dialog__tutup" data-konten-publish-batal aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <div class="ad-dialog__badan">
            <p class="ad-dialog__pesan" data-konten-terbit-pesan>{{ $pesan }}</p>
        </div>

        <footer class="ad-dialog__kaki">
            <button type="button" class="ad-tombol ad-tombol--garis" data-konten-publish-batal>
                Batal
            </button>

            <button type="submit" class="ad-tombol ad-tombol--sukses" data-konten-publish-konfirmasi
                form="form-konten-publish">
                <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />

                <span data-konten-terbit-tombol>Publish Sekarang</span>
            </button>
        </footer>

        <form method="POST" id="form-konten-publish" data-konten-publish-form hidden>
            @csrf
        </form>
    </div>
</div>
