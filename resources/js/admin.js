/*
 * Interaksi area admin.
 *
 * Semua modul berhenti sendiri kalau elemen yang mereka cari tidak ada
 * di halaman, jadi modul ini bisa diimpor untuk semua halaman admin
 * tanpa perlu tahu halaman mana yang sedang dibuka.
 *
 * Tiga hal yang ditangani:
 *   1. Drawer sidebar di bawah 1024px.
 *   2. Dropdown akun di topbar.
 *   3. Dialog peninjauan konten.
 *
 * Tanpa JavaScript: sidebar tetap tampil di desktop, tombol Keluar di
 * sidebar tetap ada, dan setiap keputusan Setujui / Tolak tetap punya
 * form-nya sendiri di markup. Tidak ada satu pun aksi yang hilang.
 */

/* ---------- 1. Drawer sidebar ---------- */
function initDrawer() {
    const sisi = document.getElementById("sisi-admin");
    const buka = document.querySelector("[data-sisi-buka]");

    if (!sisi || !buka) {
        return;
    }

    const tirai = document.querySelector(".ad-sisi__tirai");

    const tutupDrawer = () => {
        sisi.classList.remove("is-buka");
        tirai?.classList.remove("is-buka");
        buka.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
    };

    const bukaDrawer = () => {
        sisi.classList.add("is-buka");
        tirai?.classList.add("is-buka");
        buka.setAttribute("aria-expanded", "true");
        document.body.style.overflow = "hidden";
    };

    const terbuka = () => sisi.classList.contains("is-buka");

    buka.addEventListener("click", bukaDrawer);

    // Menutup lewat tombol X, lewat tirai, dan lewat Escape. Ketiganya
    // mengembalikan scroll halaman kalau sebelumnya terkunci.
    document.querySelectorAll("[data-sisi-tutup]").forEach((el) => {
        el.addEventListener("click", tutupDrawer);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && terbuka()) {
            tutupDrawer();
        }
    });

    // Memperlebar jendela ke desktop mengembalikan keadaan semula,
    // supaya overflow body tidak tertinggal terkunci.
    const desktop = window.matchMedia("(min-width: 1024px)");

    desktop.addEventListener("change", (event) => {
        if (event.matches) {
            tutupDrawer();
        }
    });

    // Mengklik tautan di dalam drawer menutupnya, supaya halaman
    // berikutnya tidak dibuka dengan menu masih menumpuk di atas.
    sisi.querySelectorAll("a[href]").forEach((tautan) => {
        tautan.addEventListener("click", tutupDrawer);
    });
}

/* ---------- 2. Dropdown akun ---------- */
function initDropdownAkun() {
    const tombol = document.querySelector("[data-akun-tombol]");
    const menu = document.querySelector("[data-akun-menu]");

    if (!tombol || !menu) {
        return;
    }

    const tutup = () => {
        menu.classList.remove("is-buka");
        tombol.setAttribute("aria-expanded", "false");
    };

    tombol.addEventListener("click", (event) => {
        event.stopPropagation();

        const akanBuka = !menu.classList.contains("is-buka");

        menu.classList.toggle("is-buka", akanBuka);
        tombol.setAttribute("aria-expanded", akanBuka ? "true" : "false");
    });

    // Klik di luar menutup menu. Tombolnya dikecualikan supaya klik
    // kedua tidak langsung membukanya lagi setelah dokumen ini menutupnya.
    document.addEventListener("click", (event) => {
        if (!menu.contains(event.target) && event.target !== tombol) {
            tutup();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            tutup();
        }
    });
}

/* ---------- 3. Dialog peninjauan ---------- */
/*
 * Satu dialog dipakai ulang untuk semua baris yang sedang ditinjau:
 * judul, ringkasan, dan URL keputusan diambil dari data-* tombol yang
 * ditekan, bukan dari server. Jadi halaman dengan banyak baris tetap
 * hanya punya satu dialog.
 *
 * Dua form di kaki dialog mengirim POST ke URL yang sudah ada
 * (admin.materi.setujui / admin.materi.tolak, atau padanannya untuk
 * quiz) dengan field "status" yang sama seperti form yang sebelumnya
 * tertanam langsung di tiap baris. Logika persetujuan di server tidak
 * tersentuh.
 *
 * Kalau tombol tidak membawa URL keputusan (konten yang statusnya bukan
 * "menunggu"), bagian keputusan disembunyikan supaya tidak ada form
 * yang bisa terkirim ke URL kosong.
 */
function initDialog() {
    const dialog = document.querySelector("[data-dialog]");

    if (!dialog) {
        return;
    }

    const judul = dialog.querySelector("[data-dialog-judul]");
    const meta = dialog.querySelector("[data-dialog-meta]");
    const isi = dialog.querySelector("[data-dialog-isi]");
    const formTolak = dialog.querySelector("[data-dialog-form-tolak]");
    const alasan = dialog.querySelector("[data-dialog-alasan]");
    const bagianTolak = dialog.querySelector("[data-dialog-bagian-tolak]");
    const tombolTolak = dialog.querySelector("[data-dialog-tolak-tombol]");
    const formSetujui = dialog.querySelector("[data-dialog-form-setujui]");
    const tombolSetujui = dialog.querySelector("[data-dialog-setujui]");
    const tombolTutup = dialog.querySelector("[data-dialog-tutup]");

    let pemicu = null;

    /*
     * Isian alasan yang gagal divalidasi dikembalikan ke kotak ini.
     * Nilainya hanya dipakai sekali, saat dialog dibuka pertama kali:
     * kalau dipakai terus, alasan dari baris sebelumnya akan ikut
     * terbawa ke baris lain yang dibuka setelahnya.
     */
    let alasanSudahDipakai = false;

    const tutup = () => {
        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");

        // Fokus dikembalikan ke tombol pemicu supaya navigasi keyboard
        // tidak melompat ke awal halaman.
        pemicu?.focus();
        pemicu = null;
    };

    document.querySelectorAll("[data-dialog-buka]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            pemicu = tombol;

            if (judul) {
                judul.textContent = tombol.dataset.dialogJudul || "";
            }

            if (meta) {
                meta.textContent = tombol.dataset.dialogMeta || "";
            }

            if (isi) {
                isi.textContent = tombol.dataset.dialogIsi || "";
            }

            const urlTolak = tombol.dataset.dialogTolak || "";
            const urlSetujui = tombol.dataset.dialogSetujui || "";
            const bolehPutuskan = urlTolak !== "" || urlSetujui !== "";

            if (formTolak) {
                formTolak.hidden = urlTolak === "";
                formTolak.setAttribute("action", urlTolak);
            }

            if (bagianTolak) {
                bagianTolak.hidden = urlTolak === "";
            }

            if (tombolTolak) {
                tombolTolak.hidden = urlTolak === "";
            }

            if (tombolSetujui) {
                tombolSetujui.hidden = urlSetujui === "";
            }

            if (formSetujui) {
                formSetujui.setAttribute("action", urlSetujui);
            }

            // Isian alasan dikosongkan supaya alasan dari baris sebelumnya
            // tidak terbawa kalau admin membuka baris lain. Dikecualikan
            // sekali saja untuk nilai yang dikembalikan server karena
            // alasan yang tadi ditolak tidak valid.
            if (alasan) {
                alasan.value = alasanSudahDipakai ? "" : alasan.dataset.awal || "";

                alasanSudahDipakai = true;
            }

            dialog.classList.add("is-buka");
            dialog.setAttribute("aria-hidden", "false");

            (bolehPutuskan ? alasan : tombolTutup)?.focus();
        });
    });

    dialog.querySelectorAll("[data-dialog-tutup]").forEach((el) => {
        el.addEventListener("click", tutup);
    });

    // Klik area gelap di luar kartu dialog juga membatalkan.
    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) {
            tutup();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && dialog.classList.contains("is-buka")) {
            tutup();
        }
    });
}

initDrawer();
initDropdownAkun();
initDialog();
