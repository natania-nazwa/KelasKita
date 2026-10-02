/**
 * Toast hasil aksi di halaman "Konten Pembelajaran".
 *
 * Muncul dari flash session, jadi hanya ada setelah simpan, terbitkan,
 * duplikat, atau hapus. Menutup dirinya sendiri supaya tidak menutupi isi
 * daftar; tombol tutup selalu ada untuk menutupnya lebih awal.
 *
 * Yang TIDAK ada di modul ini lagi adalah perpindahan tahap form materi.
 * Dulunya form admin punya dua tahap, jadi butuh cara berpindah di
 * antaranya. Sekarang form admin memakai satu halaman panjang yang sama
 * persis dengan form pemilik — persis seperti resources/js/materi-tambah.js
 * yang sudah menangani daftar bab, editor, dan baris tombol lengketnya.
 * Tidak ada tahap yang perlu dilewati, jadi tidak ada lagi yang perlu
 * disusun di sini.
 *
 * Modul ini berhenti sendiri kalau elemennya tidak ada di halaman, jadi
 * aman diimpor tanpa syarat dari mana saja.
 */

function initToast() {
    const toast = document.querySelector("[data-konten-toast]");

    if (!toast) {
        return;
    }

    const tutup = () => toast.remove();

    toast.querySelectorAll("[data-konten-toast-tutup]").forEach((tombol) => {
        tombol.addEventListener("click", tutup);
    });

    window.setTimeout(tutup, 8000);
}

initToast();
