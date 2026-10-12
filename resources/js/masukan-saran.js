/**
 * Halaman "Masukan & Saran".
 *
 * Tiga hal kecil, semuanya memperbaiki yang sudah bekerja tanpa JavaScript:
 *
 *   1. penghitung karakter detail (0/500) yang ikut berubah saat mengetik,
 *      dan berubah warna saat mendekati batas;
 *   2. nama berkas lampiran yang dipilih, supaya pengguna tahu berkas mana
 *      yang akan terkirim tanpa membuka dialog berkas lagi;
 *   3. keadaan "mengirim" pada tombol kirim begitu form dikirim, supaya
 *      tombol tidak bisa ditekan dua kali dan pengguna tahu kirimannya
 *      sedang berjalan.
 *
 * Tanpa JavaScript, formnya tetap terkirim seperti biasa: penghitung dan
 * nama berkas hanya tambahan, sedangkan tombolnya tetap tombol submit biasa.
 *
 * Modul ini berhenti langsung kalau halamannya tidak dibuka, jadi aman
 * diimpor dari resources/js/app.js.
 */
const AKAR = document.querySelector("[data-masukan-form]");

if (AKAR) {
    initMasukanSaran(AKAR);
}

function initMasukanSaran(form) {
    const detail = form.querySelector("[data-masukan-detail]");
    const hitung = form.querySelector("[data-masukan-hitung]");

    /**
     * Penghitung karakter.
     *
     * Ambang "penuh" 90% dipakai supaya peringatannya datang sebelum benar-
     * benar mentok, bukan tepat saat sudah tidak bisa menambah karakter lagi.
     */
    if (detail && hitung) {
        const maks = detail.maxLength > 0 ? detail.maxLength : 500;

        const perbarui = () => {
            const jumlah = detail.value.length;
            const penuh = jumlah >= maks * 0.9;

            hitung.textContent = `${jumlah}/${maks}`;
            hitung.classList.toggle("text-[#c2414a]", penuh);
            hitung.classList.toggle("text-muted", !penuh);
        };

        perbarui();
        detail.addEventListener("input", perbarui);
    }

    const lampiran = form.querySelector("[data-masukan-lampiran]");
    const lampiranNama = form.querySelector("[data-masukan-lampiran-nama]");
    const lampiranNamaTeks = form.querySelector("[data-masukan-lampiran-nama-teks]");

    if (lampiran && lampiranNama) {
        lampiran.addEventListener("change", () => {
            const berkas = lampiran.files?.[0];

            if (!berkas) {
                lampiranNama.hidden = true;
                return;
            }

            lampiranNama.hidden = false;

            if (lampiranNamaTeks) {
                lampiranNamaTeks.textContent = berkas.name;
            }
        });
    }

    /*
     * Keadaan "mengirim".
     *
     * Tombolnya ada di luar form (dihubungkan lewat atribut form=), jadi
     * yang dipasangi listener adalah form-nya. event submit hanya jalan
     * setelah validasi HTML5 lolos, jadi tombol tidak pernah macet dalam
     * keadaan "mengirim" hanya karena ada isian yang belum diisi.
     */
    const tombol = document.querySelector("[data-masukan-kirim]");
    const label = tombol?.querySelector("[data-masukan-label]");
    const ikon = tombol?.querySelector("[data-masukan-ikon]");
    const ikonMuat = tombol?.querySelector("[data-masukan-ikon-muat]");

    form.addEventListener("submit", (event) => {
        if (form.dataset.terkirim === "1") {
            event.preventDefault();
            return;
        }

        form.dataset.terkirim = "1";

        if (tombol) {
            tombol.disabled = true;
        }

        if (label) {
            label.textContent = "Mengirim...";
        }

        if (ikon) {
            ikon.hidden = true;
        }

        if (ikonMuat) {
            ikonMuat.hidden = false;
        }
    });
}
