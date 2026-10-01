@props([
    'judul' => 'Hapus konten?',
    'pesan' => 'Konten ini akan dihapus dan tidak lagi tersedia untuk pengguna. Tindakan ini tidak dapat dibatalkan.',
    'tombol' => 'Hapus',
])

{{--
    Dialog konfirmasi hapus, dipakai ulang untuk semua baris di daftar.

    Satu dialog untuk seluruh halaman: judul, keterangan, dan form yang
    dijalankan diambil dari atribut data-* tombol yang ditekan, bukan dari
    server (lihat resources/js/admin.js). Daftar panjang tetap hanya punya
    satu kotak konfirmasi.

    Form-nya disembunyikan di dalam dialog dan dipasang lewat atribut form=
    pada tombolnya, jadi tetap satu form HTML biasa yang membawa CSRF
    token-nya sendiri. Tanpa JavaScript tidak ada yang terkirim sama sekali —
    konten baru terhapus setelah admin menekan tombol di dalam dialog.
--}}

<div class="ad-dialog" data-dialog-hapus role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="dialog-hapus-konten-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="dialog-hapus-konten-judul" data-hapus-judul>{{ $judul }}</h2>

                <p class="ad-teks-2 mt-0.5 !text-xs" data-hapus-meta></p>
            </div>

            <button type="button" class="ad-dialog__tutup" data-hapus-tutup aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <div class="ad-dialog__badan">
            {{--
                Pesannya boleh ditimpa per baris lewat data-hapus-pesan pada
                tombol pemicu (resources/js/admin.js). Yang paling perlu
                dibedakan: konten yang sudah tayang lebih berbahaya dihapus,
                karena ikut hilang dari halaman pengguna — bukan hanya dari
                daftar admin.
            --}}
            <p class="ad-dialog__pesan" data-hapus-pesan>{{ $pesan }}</p>
        </div>

        <footer class="ad-dialog__kaki">
            <button type="button" class="ad-tombol ad-tombol--garis" data-hapus-tutup>Batal</button>

            <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-hapus-konten">
                <x-admin.ikon nama="sampah" />

                {{ $tombol }}
            </button>
        </footer>

        <form method="POST" action="" id="form-hapus-konten" data-hapus-form hidden>
            @csrf

            @method('DELETE')
        </form>
    </div>
</div>
