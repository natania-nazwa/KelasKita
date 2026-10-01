/**
 * Form Konten Pembelajaran untuk materi, dan toast hasil aksi.
 *
 * Dua hal yang ditangani, keduanya berhenti sendiri kalau elemennya tidak ada
 * di halaman:
 *
 *   1. Pindah tahap pada form materi. Form admin punya dua tahap (Informasi
 *      Dasar, Isi Materi) dan butuh cara berpindah di antara keduanya.
 *      Panel tidak pernah dilepas dari DOM, cuma disembunyikan — persis
 *      seperti wizard quiz — jadi isian tahap pertama tetap utuh saat admin
 *      turun ke tahap kedua. Atribut hidden-nya sudah ada di markup, jadi
 *      tanpa JavaScript tahap kedua hanya terlihat di bawah tahap pertama dan
 *      seluruh isiannya tetap bisa dikirim.
 *
 *   2. Toast hasil aksi. Muncul dari flash session, jadi hanya ada setelah
 *      simpan, terbitkan, duplikat, atau hapus. Menutup dirinya sendiri
 *      supaya tidak menutupi isi daftar; tombol tutup selalu ada untuk
 *      menutupnya lebih awal.
 *
 * Modul ini sengaja tidak menyentuh form quiz: perpindahan tahapnya sudah
 * diurus resources/js/quiz-tambah.js, yang juga dipakai form Quiz milik
 * pengguna.
 */

/* ---------- 1. Tahap form materi ---------- */
const AKAR_MATERI = document.querySelector("[data-konten-materi]");

if (AKAR_MATERI) {
    initTahapMateri(AKAR_MATERI);
}

function initTahapMateri(akar) {
    const $ = (pilih, induk = akar) => induk.querySelector(pilih);
    const $$ = (pilih, induk = akar) =>
        Array.from(induk.querySelectorAll(pilih));

    const panel = $$("[data-konten-tahap]");
    const stepper = document.querySelector("[data-konten-stepper]");
    const form = $("#form-tambah-materi");

    if (!panel.length) {
        return;
    }

    /*
     * Jumlah tahap dihitung dari panel yang ada, bukan ditulis tetap: kalau
     * suatu hari tahap ketiga ditambahkan, navigasinya menyesuaikan tanpa
     * menyentuh JavaScript ini.
     */
    const total = panel.length;

    let tahap = 1;

    const jepit = (nilai) => Math.min(total, Math.max(1, nilai));

    /**
     * Buka satu tahap.
     *
     * Panel lain tetap di DOM, cuma disembunyikan. Itulah yang membuat isian
     * admin tidak hilang saat berpindah tahap: isian yang sedang tidak
     * terlihat masih ada di dalam form, dan tetap ikut terkirim.
     */
    const keTahap = (baru) => {
        tahap = jepit(baru);

        panel.forEach((bagian) => {
            if (Number(bagian.dataset.kontenTahap) === tahap) {
                bagian.removeAttribute("hidden");
            } else {
                bagian.setAttribute("hidden", "");
            }
        });

        /*
         * Stepper memakai kelas yang sama dengan wizard quiz, dan statusnya
         * sudah dipasang server untuk tahap pertama. Yang dilakukan di sini
         * hanya memperbaruinya, sama seperti yang dilakukan wizard.
         */
        stepper?.querySelectorAll("[data-step]").forEach((item) => {
            const nomor = Number(item.dataset.step);

            item.classList.toggle("is-aktif", nomor === tahap);
            item.classList.toggle("is-selesai", nomor < tahap);
        });

        window.scrollTo({ top: 0, behavior: "auto" });
    };

    /**
     * Isian wajib tahap pertama harus sudah terisi sebelum admin turun ke
     * tahap kedua.
     *
     * Hanya dua yang dicek di sini, dua-duanya yang memang diminta server:
     * nama dan kategori. Tanpa penjaga ini, admin bisa mencapai tahap kedua,
     * menekan Publish, dan baru setelah itu tahu judul dan kategorinya belum
     * diisi — padahal keduanya terlihat sekali di tahap pertama. Aturan
     * lengkapnya tetap milik server, yang dilalui setiap kali form dikirim.
     */
    const siapLanjut = () => {
        const nama = form?.querySelector("[name='nama']");
        const kategori = form?.querySelector("[name='pelajaran_id']");

        let siap = true;

        if (nama && !nama.value.trim()) {
            nama.setAttribute("aria-invalid", "true");
            nama.classList.add("kolom-form--salah");
            siap = false;
        }

        if (kategori && !kategori.value) {
            kategori.setAttribute("aria-invalid", "true");
            siap = false;
        }

        if (!siap) {
            keTahap(1);
            nama?.focus();
        }

        return siap;
    };

    $$("[data-konten-lanjut]").forEach((tombol) => {
        tombol.addEventListener("click", (event) => {
            if (!siapLanjut()) {
                event.preventDefault();
                return;
            }

            keTahap(tahap + 1);
        });
    });

    $$("[data-konten-kembali]").forEach((tombol) => {
        tombol.addEventListener("click", () => keTahap(tahap - 1));
    });

    /*
     * Form yang sedang dikirim diberi tanda supaya dua klik berurutan tidak
     * mengirim dua kali. Yang dinonaktifkan hanya tombol simpan-nya, bukan
     * seluruh isian — admin masih harus bisa mengoreksi sebelum menekan lagi.
     */
    form?.addEventListener("submit", () => {
        $$("[data-konten-kirim]").forEach((tombol) => {
            tombol.disabled = true;
        });
    });

    keTahap(tahap);
}

/* ---------- 2. Toast ---------- */
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
