/**
 * Daftar Konten Pembelajaran: menu aksi per baris dan kerangka saat memuat.
 *
 * Dua hal, keduanya berhenti sendiri kalau elemennya tidak ada di halaman:
 *
 *   1. Menu aksi (tombol titik-tiga) di tiap baris. Menu disembunyikan lewat
 *      display, bukan opacity, jadi tidak akan pernah bisa difokus keyboard
 *      sebelum benar-benar dibuka. Membuka menu lain menutup yang sebelumnya,
 *      dan Escape mengembalikan fokus ke tombol pemicunya.
 *
 *   2. Kerangka (skeleton) daftar selama perpindahan di dalam halaman ini:
 *      ganti tab, kirim pencarian, ganti filter, pindah halaman pagination.
 *      Elemen kerangka sudah ada di markup tetapi disembunyikan, jadi tanpa
 *      JavaScript tidak ada yang tertinggal tersembunyi dan daftar tetap
 *      terbaca utuh.
 *
 * Menu ini sengaja terpisah dari resources/js/admin.js: yang di sana memakai
 * <details> milik kartu materi katalog, sedangkan di sini menu harus
 * support ditutup dari luar (klik di luar, Escape) lewat satu listener
 * global. Dua bentuk menu, satu listener.
 */

const daftarKonten = document.querySelector("[data-konten-daftar]");

/* ---------- 1. Menu aksi ---------- */

/**
 * Semua menu di halaman. Disimpan sekali supaya listener global cukup satu.
 */
const semuaMenu = Array.prototype.slice.call(
    document.querySelectorAll("[data-konten-menu]"),
);

/**
 * Menutup satu menu dan mengembalikan fokus ke tombolnya.
 *
 * Fokus dikembalikan hanya kalau fokus sedang berada di dalam menu itu —
 * kalau admin sudah mengklik ke elemen lain, memaksa fokus kembali akan
 * menariknya ke tempat yang tidak ia tuju.
 */
const tutupMenu = (menu, kembali) => {
    const isi = menu.querySelector("[data-konten-menu-isi]");
    const tombol = menu.querySelector("[data-konten-menu-tombol]");

    isi?.classList.remove("is-buka");
    tombol?.setAttribute("aria-expanded", "false");

    if (kembali && menu.contains(document.activeElement)) {
        tombol?.focus();
    }
};

/**
 * Menutup semua menu, kecuali yang sedang dikecualikan.
 */
const tutupSemua = (kecuali = null) => {
    semuaMenu.forEach((menu) => {
        if (menu !== kecuali) {
            tutupMenu(menu, false);
        }
    });
};

semuaMenu.forEach((menu) => {
    const tombol = menu.querySelector("[data-konten-menu-tombol]");
    const isi = menu.querySelector("[data-konten-menu-isi]");

    if (!tombol || !isi) {
        return;
    }

    /*
     * Event delegation di dalam menu, bukan listener per tombol.
     *
     * Alasannya tombol "Hapus" membuka dialog konfirmasi, dan dialog itu
     * menutup menunya sendiri lewat listener global di bawah. Kalau setiap
     * tombol punya listener sendiri, urutan dua listener itu jadi bergantung
     * pada urutan pemasangan — dan menu bisa kelihatan terbuka di belakang
     * dialog yang baru saja muncul.
     *
     * Yang ditangani di sini hanya "menu ini harus tertutup" karena
     * pengiriman form dan buka dialog-nya sudah diurus modul lain.
     */
    menu.addEventListener("click", (event) => {
        if (event.target.closest("[data-konten-menu-isi] a, [data-konten-menu-isi] button")) {
            tutupMenu(menu, false);
        }
    });

    tombol.addEventListener("click", (event) => {
        event.stopPropagation();

        const akanBuka = !isi.classList.contains("is-buka");

        tutupSemua(menu);

        isi.classList.toggle("is-buka", akanBuka);
        tombol.setAttribute("aria-expanded", akanBuka ? "true" : "false");

        if (akanBuka) {
            isi.querySelector("a, button")?.focus();
        }
    });
});

/*
 * Klik di luar menutup menu yang sedang terbuka. stopPropagation() di
 * pemicunya yang membuat klik pada tombol pemicu tidak sekaligus ikut
 * menutup menu yang baru saja dibuka di baris itu.
 */
document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-konten-menu]")) {
        tutupSemua();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
        return;
    }

    /*
     * Hanya satu menu yang terbuka pada satu waktu, jadi mencari menu yang
     * sedang terbuka cukup dengan menelusuri daftarnya. Kalau tidak ada yang
     * terbuka, Escape diteruskan ke penangan lain (mis. menutup dialog)
     * seperti sebelumnya.
     */
    semuaMenu.forEach((menu) => {
        if (menu.querySelector("[data-konten-menu-isi]")?.classList.contains("is-buka")) {
            tutupMenu(menu, true);
        }
    });
});

/* ---------- 2. Kerangka saat memuat ---------- */

/**
 * Kerangka ditampilkan tepat sebelum browser meninggalkan halaman.
 *
 * Halaman ini dirender di server, jadi datanya sudah ada saat dokumen selesai
 * dimuat — kerangkanya hanya untuk perpindahan yang berarti "halaman ini,
 * datanya berbeda": ganti tab, kirim pencarian, ganti filter, pindah halaman
 * pagination.
 *
 * Perpindah ke form tambah atau ke halaman detail adalah halaman baru, jadi
 * tidak perlu kerangka: isinya memang halaman lain.
 */
function initKerangka() {
    const rangka = document.querySelector("[data-konten-rangka]");

    if (!rangka || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    let timer = null;

    const tampilkan = () => {
        if (timer) {
            window.clearTimeout(timer);
        }

        /*
         * Jeda 120ms supaya kerangka tidak berkedip untuk navigasi yang selesai
         * di bawah jeda itu — halaman yang sudah ter-cache, atau filter yang
         * datanya memang sudah ada di HTML.
         */
        timer = window.setTimeout(() => {
            rangka.hidden = false;

            if (daftarKonten) {
                daftarKonten.hidden = true;
            }
        }, 120);
    };

    /*
     * Fallback: kalau navigasi dibatalkan (klik kanan, Escape, atau
     * back-forward cache), kerangka tidak boleh menggantung tanpa daftar di
     * bawahnya.
     */
    const kembalikan = () => {
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }

        rangka.hidden = true;

        if (daftarKonten) {
            daftarKonten.hidden = false;
        }
    };

    const tetapDiHalaman = (href) => {
        try {
            return (
                new URL(href, window.location.href).pathname ===
                window.location.pathname
            );
        } catch {
            return false;
        }
    };

    /* Tab dan pagination: keduanya tautan ke halaman yang sama. */
    document
        .querySelectorAll("[data-konten-navigasi] a[href]")
        .forEach((tautan) => {
            tautan.addEventListener("click", (event) => {
                if (
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.button !== 0
                ) {
                    return;
                }

                if (tetapDiHalaman(tautan.getAttribute("href"))) {
                    tampilkan();
                }
            });
        });

    /* Pencarian mengirim form, bukan mengikuti tautan. Filter select juga
       mengirim form (dari initSaringLangsung di app.js), jadi keduanya
       memasang listener yang sama di sini. */
    document
        .querySelectorAll("[data-konten-saring]")
        .forEach((form) => form.addEventListener("submit", tampilkan));

    window.addEventListener("pageshow", kembalikan);
    window.addEventListener("pagehide", kembalikan);
}

initKerangka();
