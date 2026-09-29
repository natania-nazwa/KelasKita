// Halaman detail quiz: baris soal yang bisa dilipat, tombol Bagikan, dan
// pemotretan banner kategori kalau fotonya gagal dimuat.
// Semua fungsi berhenti sendiri kalau elemennya tidak ada di halaman ini.

const KELAS_TERBUKA = "is-buka";

/**
 * Daftar soal: klik baris untuk membuka atau menutup pilihan jawabannya.
 *
 * Panel memakai atribut hidden, bukan kelas, jadi markup-nya tetap benar
 * tanpa JavaScript: semua baris tertutup. Aturan CSS di
 * @media (scripting: none) membukanya kembali kalau JavaScript mati.
 *
 * Panel dicari lewat aria-controls yang sudah ada di markup, bukan dari
 * nomor urut, supaya komponen ini tidak perlu tahu bentuk array soal.
 */
function initSoalLipat() {
    const tombol = document.querySelectorAll("[data-soal-lipat]");

    if (!tombol.length) {
        return;
    }

    tombol.forEach((item) => {
        const panel = document.getElementById(
            item.getAttribute("aria-controls") || "",
        );

        if (!panel) {
            return;
        }

        item.addEventListener("click", () => {
            const terbuka = item.getAttribute("aria-expanded") === "true";

            item.setAttribute("aria-expanded", terbuka ? "false" : "true");
            panel.hidden = terbuka;
        });
    });
}

/**
 * Tombol "Bagikan".
 *
 * Kalau browser punya Web Share API (ponsel dan sebagian browser desktop),
 * عنها langsung diserahkan ke sana supaya pengguna bisa memilih aplikasi
 * tujuan. Kalau tidak ada, dialog kecil berisi tautannya yang dibuka, dan
 * di situ ada tombol salin.
 *
 * Menutup share sheet di aplikasi tujuan menghasilkan AbortError. Itu
 * pilihan pengguna, bukan kegagalan, jadi tidak ada pesan apa pun yang
 * perlu ditampilkan.
 */
function initBagikan() {
    const tombol = document.querySelector("[data-bagikan-buka]");

    if (!tombol) {
        return;
    }

    const dialog = document.querySelector("[data-bagikan-dialog]");
    const kolom = dialog?.querySelector("[data-bagikan-tautan]");
    const status = dialog?.querySelector("[data-bagikan-status]");
    const tombolSalin = dialog?.querySelector("[data-bagikan-salin]");
    const tombolTutup = dialog?.querySelector("[data-bagitutup]");

    const url = window.location.href;
    const judul = document.title;

    const buka = () => {
        if (!dialog) {
            return;
        }

        if (kolom) {
            kolom.value = url;
        }

        dialog.classList.add(KELAS_TERBUKA);
        tombolTutup?.focus();
    };

    const tutup = () => {
        dialog?.classList.remove(KELAS_TERBUKA);
    };

    tombol.addEventListener("click", async () => {
        if (typeof navigator.share === "function") {
            try {
                await navigator.share({ title: judul, text: judul, url });
            } catch (e) {
                if (e?.name !== "AbortError") {
                    buka();
                }
            }

            return;
        }

        buka();
    });

    tombolTutup?.addEventListener("click", tutup);

    // Klik pada latar gelap (bukan panelnya) menutup dialog.
    dialog?.addEventListener("click", (event) => {
        if (event.target === dialog) {
            tutup();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (
            event.key === "Escape" &&
            dialog?.classList.contains(KELAS_TERBUKA)
        ) {
            tutup();
            tombol.focus();
        }
    });

    tombolSalin?.addEventListener("click", async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            } else {
                // Browser lama atau halaman non-HTTPS: pakai perintah
                // salin bawaan lewat kolom yang sedang disorot.
                kolom.select();
                document.execCommand("copy");
            }
        } catch {
            // Izin papan klip ditolak. Tautan tetap selectable di kolom,
            // jadi pengguna masih bisa menyalin sendiri; yang perlu
            // dijaga hanya supaya jangan sampai tampil seolah berhasil.
            if (status) {
                status.textContent =
                    "Tidak bisa menyalin otomatis. Salin tautannya manual.";
            }

            kolom?.select();

            return;
        }

        if (status) {
            status.textContent = "Link berhasil disalin.";
        }
    });
}

/**
 * Banner kategori memakai foto dari luar (Unsplash) kalau quiz punya
 * thumbnail. Di perangkat tanpa internet fotonya gagal dimuat; tanpa
 * penanganan apa pun yang tampil cuma kotak abu-abu, padahal di belakangnya
 * sudah ada gradasi warna kategori. Jadi fotonya dilepas saja supaya
 * gradasi itu yang terlihat.
 */
function initBanner() {
    document.querySelectorAll("img[data-detail-banner]").forEach((foto) => {
        foto.addEventListener("error", () => {
            foto.remove();
        });
    });
}

initSoalLipat();
initBagikan();
initBanner();
