/**
 * Toast hasil aksi di seluruh area admin.
 *
 * Muncul dari flash session, jadi hanya ada setelah simpan, terbitkan,
 * duplikat, atau hapus. Menutup dirinya sendiri supaya tidak menutupi isi
 * daftar; tombol tutup selalu ada untuk menutupnya lebih awal.
 *
 * Modul ini dipakai di delapan halaman admin (Konten, Konten Quiz,
 * Pengguna, dan Pengaturan beserta sub-halamannya), bukan hanya di
 * "Konten Pembelajaran" seperti nama filenya. Namanya tidak diubah supaya
 * referensi file di dalam blade tetap benar; yang diubah hanya isi
 * modulnya.
 *
 * Yang TIDAK ada di modul ini adalah perpindahan tahap form materi.
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

/*
 * Berapa lama toast dibiarkan tampil.
 *
 * Lima detik cukup untuk membaca judul dan satu baris detail, dan tidak
 * terlalu lama sampai menutupi daftar yang baru saja berubah karena aksi
 * tadi. Toast di halaman Verifikasi (data-vf-toast di admin.js) memakai
 * 6 detik; angka ini sedikit lebih pendek karena toast di sini muncul
 * setelah halaman dimuat ulang, bukan di atas halaman yang sedang dibaca.
 */
const BATAS_TOAST_MS = 5000;

function initToast() {
    const toast = document.querySelector("[data-konten-toast]");

    if (!toast) {
        return;
    }

    /*
     * Timer disimpan supaya bisa dibatalkan ketika admin menekan tombol
     * tutup lebih dulu. Tanpa itu, timer tetap hidup atas toast yang sudah
     * hilang; toast.remove() di atas node yang sudah tidak ada tidak
     * melempar error, jadi akibatnya tidak kelihatan, hanya satu timer
     * sia-sia yang ditahan lima detik. Cara membatalkannya sama dengan
     * toast Verifikasi di admin.js.
     */
    let jam = null;

    const tutup = () => {
        if (jam !== null) {
            window.clearTimeout(jam);
            jam = null;
        }

        toast.remove();
    };

    toast.querySelectorAll("[data-konten-toast-tutup]").forEach((tombol) => {
        tombol.addEventListener("click", tutup);
    });

    jam = window.setTimeout(tutup, BATAS_TOAST_MS);
}

initToast();
