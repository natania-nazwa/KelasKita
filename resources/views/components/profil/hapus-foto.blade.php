{{--
    Konfirmasi hapus foto profil.

    Tombol "Hapus Foto" sengaja type="button": formnya baru dikirim
    setelah pengguna menekan "Ya, Hapus Foto" di dialog ini. Kalau
    JavaScript mati, tidak ada yang terkirim sama sekali, bukan foto
    yang terhapus tanpa konfirmasi.

    Yang dipanggil adalah route user.profil.foto.destroy yang sungguhan
    ada: berkas dihapus dari disk publik lalu kolom foto_profil
    dikosongkan, sehingga avatar di seluruh aplikasi otomatis kembali
    ke inisial nama depan.
--}}
<x-profil.dialog
    id="dialog-hapus-foto"
    judul="Hapus foto profil?"
    bahaya
    pesan="Foto profil akan dihapus dan avatar akan menggunakan inisial nama depan."
    :ikon="\App\Support\Ikon::path('kamera')"
>
    <x-slot:aksi>
        <form
            id="form-hapus-foto"
            method="POST"
            action="{{ route('user.profil.foto.destroy') }}"
        >
            @csrf
            @method ('DELETE')

            <div class="modal__aksi">
                <button type="button" data-dialog-batal class="tombol-garis">
                    Batal
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a]"
                >
                    Ya, Hapus Foto
                </button>
            </div>
        </form>
    </x-slot:aksi>
</x-profil.dialog>
