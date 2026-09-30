/*
 * Interaksi area admin.
 *
 * Semua modul berhenti sendiri kalau elemen yang mereka cari tidak ada
 * di halaman, jadi modul ini bisa diimpor untuk semua halaman admin
 * tanpa perlu tahu halaman mana yang sedang dibuka.
 *
 * Empat hal yang ditangani:
 *   1. Drawer sidebar di bawah 1024px.
 *   2. Dropdown akun di topbar.
 *   3. Dialog peninjauan konten.
 *   4. Halaman Verifikasi: pemilihan baris, panel review, dialog
 *      Setujui / Tolak, filter tambahan, dan toast.
 *
 * Tanpa JavaScript: sidebar tetap tampil di desktop, tombol Keluar di
 * sidebar tetap ada, setiap keputusan Setujui / Tolak tetap punya
 * form-nya sendiri di markup, dan tautan tiap baris Verifikasi tetap
 * membuka halaman penuh dengan query "pilih". Tidak ada satu pun aksi
 * yang hilang.
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

/* ---------- 4. Menu tiga titik ---------- */
/*
 * Menu tiga titik di kartu materi memakai <details>, bukan tombol + <div>
 * yang disembunyikan kelas utilitas. Alasannya: buka/tutup-nya sudah
 * ditangani browser, jadi menunya tetap berfungsi tanpa JavaScript, dan satu
 * blok kecil di bawah ini cukup untuk membuat perilakunya tidak
 * membingungkan.
 *
 * Yang ditambahkan di sini hanya dua hal yang tidak bisa diberikan oleh
 * <details> sendiri: menutup menu yang kebetulan masih terbuka ketika admin
 * mengklik di luar atau menekan Escape.
 *
 * Panel filter tidak lagi ikut di sini: sejak ketiga filternya tampil
 * langsung, tidak ada yang perlu dibuka atau ditutup.
 */
function initTutupPanel() {
    const panel = document.querySelectorAll("[data-tutup-luar]");

    if (!panel.length) {
        return;
    }

    const tutup = (kecuali) => {
        panel.forEach((item) => {
            if (item !== kecuali && item.open) {
                item.open = false;
            }
        });
    };

    panel.forEach((item) => {
        // Hanya satu menu terbuka: membuka satu menutup yang lain.
        item.addEventListener("toggle", () => {
            if (item.open) {
                tutup(item);
            }
        });
    });

    document.addEventListener("click", (event) => {
        const dibuka = panel.find((item) => item.open && item.contains(event.target));

        if (!dibuka) {
            tutup();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            tutup();
        }
    });
}

/* ---------- 5. Dialog hapus materi ---------- */
/*
 * Sama seperti dialog tinjauan: satu dialog dipakai ulang untuk semua kartu,
 * dan isinya diambil dari data-* tombol yang ditekan, bukan dari server. Jadi
 * daftar materi yang panjang tetap hanya punya satu kotak konfirmasi.
 *
 * Bedanya dengan dialog tinjauan, dialog ini tidak punya keputusan menyetujui
 * atau menolak: isinya cuma satu peringatan dan satu form DELETE. Form-nya
 * disembunyikan di dalam dialog dan dipasang lewat atribut form= pada
 * tombolnya, jadi tetap satu form HTML biasa yang membawa CSRF token-nya
 * sendiri.
 *
 * Efek samping yang sengaja dibiarkan: menutup dialog mengembalikan fokus ke
 * tombol pemicu, supaya navigasi keyboard tidak melompat ke awal halaman.
 */
function initDialogHapus() {
    const dialog = document.querySelector("[data-dialog-hapus]");

    if (!dialog) {
        return;
    }

    const judul = dialog.querySelector("[data-hapus-judul]");
    const meta = dialog.querySelector("[data-hapus-meta]");
    const form = dialog.querySelector("[data-hapus-form]");

    let pemicu = null;

    const tutup = () => {
        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");

        pemicu?.focus();
        pemicu = null;
    };

    document.querySelectorAll("[data-hapus-buka]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            pemicu = tombol;

            if (judul) {
                judul.textContent = tombol.dataset.hapusJudul || "Hapus Materi?";
            }

            if (meta) {
                meta.textContent = tombol.dataset.hapusMeta || "";
            }

            // Tanpa URL dari tombol, form tidak boleh dikirim ke mana pun.
            if (form && !tombol.dataset.hapusAksi) {
                return;
            }

            form?.setAttribute("action", tombol.dataset.hapusAksi || "");

            dialog.classList.add("is-buka");
            dialog.setAttribute("aria-hidden", "false");

            dialog.querySelector("[data-hapus-tutup]")?.focus();
        });
    });

    dialog.querySelectorAll("[data-hapus-tutup]").forEach((el) => {
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

/* ---------- 6. Halaman Verifikasi ---------- */
/*
 * Halaman ini punya dua bagian yang berubah tanpa memuat ulang halaman:
 * isi panel review di kolom kanan, dan dialog Setujui / Tolak yang
 * dipakai bersama oleh semua baris.
 *
 * Prinsipnya sama dengan dialog di atas: server tetap sumber kebenaran.
 * JavaScript hanya mengambil partial verifikasi-panel untuk baris yang
 * dipilih, lalu mengisi action form keputusan dari data-* kartu panel.
 * Kalau fetch gagal atau JavaScript mati, tautan baris membuka halaman
 * penuh dengan query "pilih" dan server merender panel yang sama.
 */
function initVerifikasi() {
    const halaman = document.querySelector("[data-vf-halaman]");

    if (!halaman) {
        return;
    }

    const sisi = halaman.querySelector("[data-vf-panel]");
    const daftar = halaman.querySelector("[data-vf-daftar]");
    const saring = halaman.querySelector("[data-vf-saring]");
    const saringBuka = halaman.querySelector("[data-vf-saring-buka]");
    const toast = halaman.querySelector("[data-vf-toast]");
    const dialogSetujui = halaman.querySelector('[data-vf-dialog="setujui"]');
    const dialogTolak = halaman.querySelector('[data-vf-dialog="tolak"]');

    const urlPanel = halaman.dataset.vfPanelUrl;

    if (!sisi || !urlPanel) {
        return;
    }

    let pemicu = null;
    let urutanMuat = 0;
    let jamToast = null;

    const hpKecil = () => window.matchMedia("(max-width: 1199px)").matches;

    const perbaruiBaris = (aktif) => {
        halaman.querySelectorAll("[data-vf-item]").forEach((baris) => {
            const sama = baris === aktif;

            baris.classList.toggle("ad-vf-item--aktif", sama);

            if (sama) {
                baris.setAttribute("aria-current", "true");
            } else {
                baris.removeAttribute("aria-current");
            }
        });
    };

    /*
     * Query "pilih" hanya cerminan pilihan yang sedang terbuka. Ia dipakai
     * sebagai tautan bila JavaScript mati, jadi ditulis dengan replaceState
     * supaya memilih baris tidak menambah entri riwayat.
     */
    const perbaruiQuery = (jenis, id) => {
        const url = new URL(window.location.href);

        if (jenis && id) {
            url.searchParams.set("pilih", `${jenis}:${id}`);
        } else {
            url.searchParams.delete("pilih");
        }

        window.history.replaceState({}, "", `${url.pathname}${url.search}`);
    };

    const muatPanel = async (jenis, id) => {
        const tanda = ++urutanMuat;
        const url = new URL(urlPanel, window.location.origin);

        if (jenis && id) {
            url.searchParams.set("jenis", jenis);
            url.searchParams.set("id", String(id));
        }

        let html = null;

        try {
            const res = await fetch(url.toString(), {
                headers: {
                    Accept: "text/html",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });

            if (res.ok) {
                html = await res.text();
            }
        } catch (e) {
            html = null;
        }

        // Respons yang datang terlambat tidak boleh menimpa pilihan terbaru.
        if (html === null || tanda !== urutanMuat) {
            return;
        }

        sisi.innerHTML = html;
        perbaruiQuery(jenis, id);
    };

    const pilihBaris = (baris) => {
        perbaruiBaris(baris);

        // Daftar menyempit dan panel review muncul sebelum isinya dimuat,
        // supaya perpindahan dua kolom terasa langsung.
        halaman.classList.remove("ad-vf--keluar");
        halaman.classList.add("ad-vf--dengan-panel");

        // Kartu kosong tidak ikut dibawa saat memuat: kotak polos lebih
        // tenang daripada teks "pilih konten" yang kedip sebentar sebelum
        // isi panel yang sebenarnya datang.
        if (sisi.querySelector('[data-vf-id=""]')) {
            sisi.innerHTML = "";
        }

        muatPanel(baris.dataset.jenis, baris.dataset.id).then(() => {
            if (hpKecil()) {
                sisi.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    };

    const kosongkanPanel = () => {
        // Permintaan yang masih di jalan dibatalkan supaya tidak ada isi
        // panel lama yang datang terlambat dan mengisi kolom yang sudah tutup.
        urutanMuat++;

        const tanda = urutanMuat;

        perbaruiBaris(null);
        perbaruiQuery(null, null);

        /*
         * Panel diberi kelas "keluar" lebih dulu supaya kartunya sempat
         * memudar selagi track-nya menyusut, baru kelas panel dilepas oleh
         * timeout. Kalau admin memilih baris lain sebelum timeout berakhir,
         * pengecekan tanda di bawah membatalkan pelepasan itu.
         */
        halaman.classList.add("ad-vf--keluar");
        halaman.classList.remove("ad-vf--dengan-panel");

        window.setTimeout(() => {
            if (tanda !== urutanMuat) {
                return;
            }

            halaman.classList.remove("ad-vf--keluar");
        }, 400);

        if (hpKecil()) {
            daftar?.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    };

    const pindahTab = (tombol) => {
        const isi = tombol.dataset.vfTab;

        sisi.querySelectorAll("[data-vf-tab]").forEach((tab) => {
            const aktif = tab === tombol;

            tab.classList.toggle("ad-vf-tab__tombol--aktif", aktif);
            tab.setAttribute("aria-selected", aktif ? "true" : "false");
        });

        sisi.querySelectorAll("[data-vf-isi]").forEach((pane) => {
            pane.hidden = pane.dataset.vfIsi !== isi;
        });
    };

    const tutupDialog = (dialog) => {
        if (!dialog) {
            return;
        }

        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");

        pemicu?.focus();
        pemicu = null;
    };

    /*
     * Satu dialog untuk semua baris: URL keputusan diambil dari kartu
     * panel yang sedang terbuka, bukan dari tombol yang membuka dialog.
     * Kalau kartu tidak membawa URL (statusnya sudah final), dialog tidak
     * dibuka sama sekali supaya tidak ada form kosong yang bisa dikirim.
     */
    const bukaDialog = (dialog, pemicuBaru) => {
        const kartu = sisi.querySelector("[data-vf-kartu]");

        if (!dialog || !kartu) {
            return;
        }

        const aksi = dialog === dialogTolak ? kartu.dataset.vfTolak : kartu.dataset.vfSetujui;

        if (!aksi) {
            return;
        }

        const form = dialog.querySelector("[data-vf-form]");
        const meta = dialog.querySelector("[data-vf-dialog-meta]");

        form?.setAttribute("action", aksi);

        if (meta) {
            meta.textContent = kartu.dataset.vfJudul || "";
        }

        if (dialog === dialogTolak) {
            const alasan = dialog.querySelector("[data-vf-alasan]");

            // Alasan yang gagal validasi dikembalikan sekali saat dialog dibuka.
            if (alasan) {
                alasan.value = alasan.dataset.awal || "";
            }
        }

        pemicu = pemicuBaru;
        dialog.classList.add("is-buka");
        dialog.setAttribute("aria-hidden", "false");

        const kotakAlasan = dialog.querySelector("[data-vf-alasan]");
        const fokus = kotakAlasan || dialog.querySelector("[data-vf-dialog-kirim]");
        fokus?.focus();
    };

    halaman.addEventListener("click", (event) => {
        const tab = event.target.closest("[data-vf-tab]");

        if (tab) {
            pindahTab(tab);
            return;
        }

        const baris = event.target.closest("[data-vf-item]");

        if (baris) {
            event.preventDefault();
            pilihBaris(baris);
            return;
        }

        const bukaSetujui = event.target.closest("[data-vf-buka-setujui]");

        if (bukaSetujui) {
            bukaDialog(dialogSetujui, bukaSetujui);
            return;
        }

        const bukaTolak = event.target.closest("[data-vf-buka-tolak]");

        if (bukaTolak) {
            bukaDialog(dialogTolak, bukaTolak);
            return;
        }

        const tombolTutup = event.target.closest("[data-vf-dialog-tutup]");

        if (tombolTutup) {
            tutupDialog(tombolTutup.closest("[data-vf-dialog]"));
            return;
        }

        if (event.target.closest("[data-vf-kembali]") || event.target.closest("[data-vf-tutup]")) {
            kosongkanPanel();
            return;
        }

        const bukaSaring = event.target.closest("[data-vf-saring-buka]");

        if (bukaSaring && saring) {
            const terbuka = saring.hasAttribute("hidden");

            saring.toggleAttribute("hidden", !terbuka);
            bukaSaring.setAttribute("aria-expanded", terbuka ? "true" : "false");
            const label = terbuka ? "Tutup filter tambahan" : "Buka filter tambahan";
            bukaSaring.setAttribute("aria-label", label);

            if (terbuka) {
                saring.querySelector("input, select")?.focus();
            }

            return;
        }

        if (event.target.closest("[data-vf-toast-tutup]")) {
            if (jamToast) {
                window.clearTimeout(jamToast);
            }

            toast?.remove();
            return;
        }

        // Klik area gelap di luar kartu dialog juga membatalkan.
        const dialog = event.target.closest("[data-vf-dialog]");

        if (dialog && event.target === dialog) {
            tutupDialog(dialog);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        tutupDialog(dialogSetujui?.classList.contains("is-buka") ? dialogSetujui : null);
        tutupDialog(dialogTolak?.classList.contains("is-buka") ? dialogTolak : null);
    });

    if (toast) {
        jamToast = window.setTimeout(() => toast.remove(), 6000);
    }

    // Saat filter tambahan sudah terisi, bentuknya ikut terbuka dari server.
    if (saringBuka && saring && !saring.hasAttribute("hidden")) {
        saringBuka.setAttribute("aria-expanded", "true");
        saringBuka.setAttribute("aria-label", "Tutup filter tambahan");
    }
}

initDrawer();
initDropdownAkun();
initDialog();
initTutupPanel();
initDialogHapus();
initVerifikasi();
