{{--
    Konfirmasi hapus akun.

    PENTING: endpoint hapus akun belum ada di aplikasi ini, jadi dialog
    ini sengaja TIDAK memakai <form> dan TIDAK mengirim apa pun. Yang
    sudah ada baru lapisan konfirmasinya; tombol "Ya, Hapus Akun" hanya
    memunculkan toast yang menyebutkan Endpoint-nya belum tersedia.

    Alasannya menghapus akun adalah keputusan yang tidak bisa dibatalkan,
    jadi tidak boleh ada aksi yang terpicu hanya karena satu klik.

    Saat endpoint-nya sudah tersedia, langkah peacanya cuma mengganti
    blok <x-slot:aksi> di bawah dengan <form> yang menunjuk
    route('user.profil.hapus'), persis seperti hapus-foto.blade.php.
--}}
<x-profil.dialog
    id="dialog-hapus-akun"
    judul="Hapus akun?"
    bahaya
    pesan="Apakah kamu yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan."
    :ikon="\App\Support\Ikon::path('sampah')"
>
    <x-slot:aksi>
        <div class="modal__aksi">
            <button type="button" data-dialog-batal class="tombol-garis">
                Batal
            </button>

            {{--
                data-belum-ada-endpoint menandai tombol ini sebagai tempat
                nanti endpoint hapus akun dipasang, supaya tidak ada yang
                mengira tombolnya sudah terhubung ke server.
            --}}
            <button
                type="button"
                data-belum-ada-endpoint
                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a]"
            >
                Ya, Hapus Akun
            </button>
        </div>
    </x-slot:aksi>
</x-profil.dialog>
