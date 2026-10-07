/*
 * Interaksi area admin.
 *
 * Semua modul berhenti sendiri kalau elemen yang mereka cari tidak ada
 * di halaman, jadi modul ini bisa diimpor untuk semua halaman admin
 * tanpa perlu tahu halaman mana yang sedang dibuka.
 *
 * Delapan hal yang ditangani:
 *   1. Drawer sidebar di bawah 1024px.
 *   2. Dropdown akun di topbar.
 *   3. Panel notifikasi di topbar.
 *   4. Dialog peninjauan konten.
 *   5. Menu tiga titik di kartu konten.
 *   6. Dialog hapus materi.
 *   7. Halaman Verifikasi: pemilihan baris, panel review, dialog
 *      Setujui / Tolak, filter tambahan, dan toast.
 *   8. Dialog detail pengguna.
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

/* ---------- 3. Panel notifikasi ---------- */
/*
 * Lonceng di topbar admin. Yang dikerjakan dua hal, dan keduanya berhenti
 * sendiri di halaman tanpa lonceng (semua halaman /admin/pengaturan*).
 *
 * Pertama, membuka dan menutup panel. Isi panel sudah dirender server dari
 * tb_notifikasi, jadi tidak ada yang perlu diambil dari jaringan saat
 * dibuka.
 *
 * Kedua, menandai satu baris terbaca lewat fetch begitu diklik, supaya titik
 * merahnya hilang sebelum halaman tujuan selesai dimuat. Kalau permintaan itu
 * ditolak atau jaringan gagal, tandanya dikembalikan: notifikasi yang
 * sebenarnya belum terbaca tidak boleh terlihat sudah dibaca.
 *
 * Tanpa JavaScript panel tidak pernah terbuka dan tidak ada yang ditandai
 * terbaca, tapi tidak ada satu pun notifikasi yang hilang: isinya sudah benar
 * di HTML.
 */
function initNotifikasiTopbar() {
    const wadah = document.querySelector("[data-admin-notif]");

    if (!wadah) {
        return;
    }

    const tombol = wadah.querySelector("[data-admin-notif-tombol]");
    const panel = wadah.querySelector("[data-admin-notif-panel]");
    const titik = wadah.querySelector("[data-admin-notif-titik]");
    const sisa = wadah.querySelector("[data-admin-notif-sisa]");

    if (!tombol || !panel) {
        return;
    }

    const tutup = () => {
        panel.classList.remove("is-buka");
        tombol.setAttribute("aria-expanded", "false");
    };

    tombol.addEventListener("click", (event) => {
        event.stopPropagation();

        const akanBuka = !panel.classList.contains("is-buka");

        panel.classList.toggle("is-buka", akanBuka);
        tombol.setAttribute("aria-expanded", akanBuka ? "true" : "false");
    });

    // Klik di luar menutup panel. Tombolnya dikecualikan supaya klik kedua
    // tidak langsung membukanya lagi setelah dokumen ini menutupnya.
    document.addEventListener("click", (event) => {
        if (!wadah.contains(event.target)) {
            tutup();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            tutup();
        }
    });

    /**
     * Kurangi angka notifikasi yang belum dibaca, lalu sembunyikan titiknya
     * kalau sudah tidak ada.
     */
    const perbaruiSisa = (jumlah) => {
        if (typeof jumlah !== "number") {
            return;
        }

        if (sisa) {
            sisa.textContent = jumlah > 0 ? `${jumlah} belum dibaca` : "";
        }

        if (jumlah > 0) {
            return;
        }

        titik?.remove();
    };

    wadah.addEventListener("click", (event) => {
        const baris = event.target.closest("[data-admin-notif-item]");

        if (!baris) {
            return;
        }

        // Hanya yang belum dibaca yang perlu ditandai; yang sudah dibaca tidak
        // akan mengubah apa pun di server.
        if (!baris.classList.contains("is-belum")) {
            return;
        }

        const alamat = baris.dataset.adminNotifBaca;

        if (!alamat) {
            return;
        }

        baris.classList.remove("is-belum");

        fetch(alamat, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')?.content || "",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
            body: "{}",
        })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => perbaruiSisa(data?.sisa))
            .catch(() => {
                // Jaringan gagal atau server menolak: tandanya dikembalikan
                // supaya tidak hilang notifikasi yang sebenarnya belum
                // ditandai terbaca.
                baris.classList.add("is-belum");
            });
    });
}

/* ---------- 4. Dialog peninjauan ---------- */
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

/* ---------- 5. Menu tiga titik ---------- */
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
    /*
     * Array.from, bukan NodeList mentah: di bawah dipakai .find(), yang
     * hanya ada di Array. NodeList hasil querySelectorAll tidak punya
     * metode itu, sehingga pemanggilannya melempar "find is not a function"
     * di setiap klik — termasuk di halaman yang tidak punya satu pun menu
     * tiga titik, karena error-nya muncul dari listener global di bawah.
     */
    const panel = Array.from(document.querySelectorAll("[data-tutup-luar]"));

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

/* ---------- 6. Dialog hapus materi ---------- */
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
    const pesan = dialog.querySelector("[data-hapus-pesan]");
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

            /*
             * Pesannya boleh ditimpa per baris. Yang paling perlu
             * dibedakan: konten yang sudah tayang lebih berbahaya dihapus,
             * karena ikut hilang dari halaman pengguna — bukan hanya dari
             * daftar admin. Tanpa itu, kalimat yang sama dipakai untuk
             * materi draft dan materi yang sedang dibaca pengguna.
             */
            if (pesan && tombol.dataset.hapusPesan) {
                pesan.textContent = tombol.dataset.hapusPesan;
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

/* ---------- 7. Halaman Verifikasi ---------- */
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

/* ---------- 8. Dialog detail pengguna ---------- */
/*
 * Satu dialog untuk semua baris di halaman Pengguna, diisi dari peta JSON di
 * halaman itu sendiri. Peta-nya dibaca sekali saat halaman dimuat, jadi
 * membuka dialog tidak menembak request apa pun dan daftar yang panjang
 * tetap hanya punya satu kotak detail.
 *
 * Server tetap sumber kebenaran: peta ini cuma berisi angka yang sudah
 * dihitung di server, dan JavaScript tidak menghitung ulang apa pun.
 *
 * Fokus dikurung di dalam dialog selama terbuka. Tanpa itu, menekan Tab
 * berulang kali akan berjalan naik ke sidebar dan topbar di belakang dialog,
 * yang secara visual tertutup tapi masih bisa difokus — jadi pembaca
 * keyboard bisa tersesat keluar dari dialog yang terbuka.
 */
function initDetailPengguna() {
    const dialog = document.querySelector("[data-dialog-pengguna]");
    const sumber = document.querySelector("[data-detail-pengguna]");

    if (!dialog || !sumber) {
        return;
    }

    let peta = {};

    try {
        peta = JSON.parse(sumber.textContent) || {};
    } catch {
        // Peta rusak tidak boleh membuat seluruh halaman error. Tombolnya
        // dinonaktifkan supaya tidak ada dialog kosong yang terbuka.
        document.querySelectorAll("[data-detail-buka]").forEach((tombol) => {
            tombol.disabled = true;
        });

        return;
    }

    const medan = {
        peran: dialog.querySelector("[data-detail-peran]"),
        peranLencana: dialog.querySelector("[data-detail-peran-lencana]"),
        nama: dialog.querySelector("[data-detail-nama]"),
        email: dialog.querySelector("[data-detail-email]"),
        avatar: dialog.querySelector("[data-detail-avatar]"),
        status: dialog.querySelector("[data-detail-status]"),
        aktivitas: dialog.querySelector("[data-detail-aktivitas]"),
        bergabung: dialog.querySelector("[data-detail-bergabung]"),
        materi: dialog.querySelector("[data-detail-materi]"),
        quiz: dialog.querySelector("[data-detail-quiz]"),
        selesai: dialog.querySelector("[data-detail-selesai]"),
        nilai: dialog.querySelector("[data-detail-nilai]"),
        nilaiKet: dialog.querySelector("[data-detail-nilai-ket]"),
    };

    let pemicu = null;

    const tutup = () => {
        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";

        pemicu?.focus();
        pemicu = null;
    };

    /** Elemen yang boleh menerima fokus di dalam dialog. */
    const fokusable = () =>
        Array.from(
            dialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            ),
        ).filter((el) => el.offsetParent !== null);

    const isi = (orang) => {
        const tampil = (medan, nilai) => {
            if (medan) {
                medan.textContent = nilai;
            }
        };

        tampil(medan.peran, orang.peran_label);
        tampil(medan.nama, orang.nama);
        tampil(medan.email, orang.email);
        tampil(medan.status, orang.status_label);
        tampil(medan.aktivitas, orang.aktivitas);
        tampil(medan.bergabung, orang.bergabung_jam ? `${orang.bergabung} · ${orang.bergabung_jam}` : orang.bergabung);
        tampil(medan.materi, orang.materi);
        tampil(medan.quiz, orang.quiz);
        tampil(medan.selesai, orang.selesai);

        // "—" bukan 0 saat belum ada yang dinilai. Teks aslinya sudah
        // disiapkan server supaya JavaScript tidak memutuskan sendiri apa
        // yang harus ditulis kalau datanya kosong.
        tampil(medan.nilai, orang.nilai_rata_label);
        tampil(medan.nilaiKet, orang.nilai_keterangan);

        if (medan.peranLencana) {
            medan.peranLencana.textContent = orang.peran_label;
            // Peran dan status memakai kelas lencana yang sama seperti di
            // tabel, jadi warnanya ikut berubah dari data yang sama.
            medan.peranLencana.className = `ad-lencana ${orang.peran === "admin" ? "ad-lencana--ungu" : "ad-lencana--abu"}`;

            const titik = document.createElement("span");
            titik.className = "ad-lencana__titik";
            titik.setAttribute("aria-hidden", "true");
            medan.status.textContent = "";

            if (orang.aktif) {
                medan.status.className = "ad-lencana ad-lencana--sukses";
                medan.status.append(titik, document.createTextNode(" Aktif"));
            } else {
                medan.status.className = "ad-lencana ad-lencana--abu";
                medan.status.append(titik, document.createTextNode(" Nonaktif"));
            }
        }

        // Avatar memakai komponen yang sama dengan tabel: warna diturunkan
        // dari nama, dan foto profil hanya dipakai kalau ada.
        if (medan.avatar) {
            medan.avatar.style.setProperty("--a", orang.warna);
            medan.avatar.style.setProperty("--a-gelap", orang.warna_gelap);
            medan.avatar.innerHTML = "";

            if (orang.foto) {
                const gambar = document.createElement("img");

                gambar.src = orang.foto;
                gambar.alt = "";
                gambar.width = 52;
                gambar.height = 52;
                gambar.decoding = "async";

                medan.avatar.append(gambar);
            } else {
                medan.avatar.textContent = orang.inisial;
            }
        }
    };

    document.querySelectorAll("[data-detail-buka]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const orang = peta[tombol.dataset.detailBuka];

            // Baris yang tidak ada di peta tidak mungkin terjadi, tapi kalau
            // terjadi dialognya lebih baik tidak terbuka sama sekali
            // daripada terbuka dengan kotak kosong.
            if (!orang) {
                return;
            }

            pemicu = tombol;
            isi(orang);

            dialog.classList.add("is-buka");
            dialog.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";

            dialog.querySelector("[data-detail-tutup]")?.focus();
        });
    });

    dialog.querySelectorAll("[data-detail-tutup]").forEach((el) => {
        el.addEventListener("click", tutup);
    });

    // Klik area gelap di luar kartu dialog juga menutupnya.
    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) {
            tutup();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (!dialog.classList.contains("is-buka")) {
            return;
        }

        if (event.key === "Escape") {
            tutup();

            return;
        }

        if (event.key !== "Tab") {
            return;
        }

        const daftar = fokusable();

        if (!daftar.length) {
            return;
        }

        const awal = daftar[0];
        const akhir = daftar[daftar.length - 1];

        // Tab dari elemen terakhir kembali ke yang pertama, dan sebaliknya,
        // supaya fokus tidak pernah keluar dari dialog.
        if (event.shiftKey && document.activeElement === awal) {
            event.preventDefault();
            akhir.focus();
        } else if (!event.shiftKey && document.activeElement === akhir) {
            event.preventDefault();
            awal.focus();
        }
    });
}

initDrawer();
initDropdownAkun();
initNotifikasiTopbar();
initDialog();
initTutupPanel();
initDialogHapus();
initVerifikasi();
initDetailPengguna();
