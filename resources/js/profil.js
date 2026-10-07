/**
 * Halaman Profil (berhenti sendiri kalau halamannya tidak ada).
 *
 * Empat hal yang dikerjakan di sini:
 *   1. Buka/tutup dialog (edit profil, ubah password, hapus foto, hapus akun).
 *   2. Kolom password: tombol mata untuk menampilkan/menyembunyikan isi.
 *   3. Pilih foto: tombol "Ganti Foto" membuka kolom file, dan pratinjau
 *      avatar langsung ikut berubah.
 *   4. Mode terang/gelap: menulis atribut data-theme ke <html> dan
 *      mengingat pilihan di localStorage.
 *
 * Tidak ada satu pun request yang dikirim lewat fetch. Semua perubahan
 * dikirim lewat form biasa, jadi setelah aksi selesai seluruh aplikasi
 * (termasuk top bar) ikut memakai data terbaru tanpa perlu disegarkan
 * manual. Yang perlu JavaScript hanyalah bagian interaktifnya.
 */

const KUNCI_TEMA = "kk-tema";

/* ------------------------------------------------------------------
 * DIALOG
 *
 * Satu pasang atribut untuk semua dialog di halaman:
 *   data-dialog-buka="<id>"   tombol pemicu
 *   data-dialog="<id>"        kotak dialognya
 *
 * Kotak yang sudah terbuka ditutup dulu sebelum yang lain dibuka, jadi
 * tidak mungkin ada dua dialog menumpuk.
 * ------------------------------------------------------------------ */

function initDialog() {
    const dialogs = Array.prototype.slice.call(
        document.querySelectorAll("[data-dialog]"),
    );

    if (!dialogs.length) {
        return;
    }

    const tutup = (dialog, kembaliKePemicu = true) => {
        dialog.classList.remove("is-buka");

        if (!kembaliKePemicu) {
            return;
        }

        // Kembalikan fokus ke pemicu supaya navigasi keyboard tidak
        // tiba-tiba jatuh ke elemen paling atas halaman.
        const pemicu = document.querySelector(
            `[data-dialog-buka="${dialog.dataset.dialog}"]`,
        );

        if (pemicu) {
            pemicu.focus();
        }
    };

    const buka = (dialog) => {
        dialogs.forEach((lain) => tutup(lain, false));
        dialog.classList.add("is-buka");

        // Fokus ke kolom pertama supaya dialog langsung bisa diketik dan
        // supaya pembaca layar pindah ke dalam dialog.
        const pertama = dialog.querySelector(
            "input:not([type='hidden']):not([disabled]), textarea, button",
        );

        if (pertama) {
            pertama.focus();
        }
    };

    document.querySelectorAll("[data-dialog-buka]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const dialog = document.querySelector(
                `[data-dialog="${tombol.dataset.dialogBuka}"]`,
            );

            if (dialog) {
                buka(dialog);
            }
        });
    });

    // Tombol Batal: satu handler untuk semua dialog karena nama
    // atributnya sama di mana-mana.
    document.querySelectorAll("[data-dialog-batal]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const dialog = tombol.closest("[data-dialog]");

            if (dialog) {
                tutup(dialog);
            }
        });
    });

    // Klik area gelap di luar panel juga membatalkan.
    dialogs.forEach((dialog) => {
        dialog.addEventListener("click", (event) => {
            if (event.target === dialog) {
                tutup(dialog);
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        const terbuka = dialogs.find((dialog) =>
            dialog.classList.contains("is-buka"),
        );

        if (terbuka) {
            tutup(terbuka);
        }
    });

    /*
     * Submit yang gagal membuat Laravel memuat ulang halaman, dan kotak
     * dialog ikut menutup lagi karena display:none-nya kembali berlaku.
     * Kalau tidak dibuka ulang, pesan kesalahan (mis. password lama salah
     * atau konfirmasi hapus akun salah password) terkunci di dalam kotak
     * yang tertutup dan pengguna tidak pernah melihatnya.
     *
     * Penandanya satu saja: .profil-galat, kelas yang dipakai setiap
     * @error di halaman ini. Dialog mana pun yang memuatnya langsung
     * dibuka lagi, dan buka() sudah memindah fokus ke kolom pertama.
     */
    const dialogBermasalah = dialogs.find((dialog) =>
        dialog.querySelector(".profil-galat"),
    );

    if (dialogBermasalah) {
        buka(dialogBermasalah);
    }
}

/* ------------------------------------------------------------------
 * KOLOM PASSWORD
 *
 * Tombol mata hanya mengubah atribut type milik kolom di sebelahnya.
 * Isi password tidak pernah dibaca, disalin, atau disimpan di mana pun;
 * seluruh pemeriksaan tetap dilakukan di server.
 * ------------------------------------------------------------------ */

function initLihatSandi() {
    document.querySelectorAll("[data-profil-lihat-sandi]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const kolom = tombol.parentElement.querySelector(
                "input[type='password']",
            );

            if (!kolom) {
                return;
            }

            const tampil = kolom.type === "password";

            kolom.type = tampil ? "text" : "password";
            tombol.setAttribute("aria-pressed", tampil ? "true" : "false");
            tombol.setAttribute(
                "aria-label",
                tampil ? "Sembunyikan password" : "Tampilkan password",
            );

            // Dua ikon berbagi satu tombol; yang mana yang terlihat
            // ditentukan lewat kelas "hidden" milik Tailwind.
            const terbuka = tombol.querySelector("[data-profil-mata-terbuka]");
            const tertutup = tombol.querySelector(
                "[data-profil-mata-tertutup]",
            );

            if (terbuka) {
                terbuka.classList.toggle("hidden", !tampil);
            }

            if (tertutup) {
                tertutup.classList.toggle("hidden", tampil);
            }
        });
    });
}

/* ------------------------------------------------------------------
 * PILIH FOTO
 *
 * Kolom file disembunyikan (sr-only) lalu diganti tombol biasa, karena
 * gaya bawaan kolom file tidak cocok dengan tombol lain di halaman ini.
 * Yang benar-benar diklik tetap <input type="file">, jadi papan ketik
 * dan pembaca layar tetap bisa memakainya.
 *
 * Pratinjau memakai object URL dari file yang dipilih, jadi pengguna
 * langsung melihat hasilnya sebelum form dikirim. HTML awal setiap
 * avatar disimpan sekali supaya keadaan sebelumnya bisa dikembalikan
 * kalau pemilihan dibatalkan.
 * ------------------------------------------------------------------ */

function initPilihFoto() {
    const kolom = document.querySelector("[data-profil-pilih-foto]");

    if (!kolom) {
        return;
    }

    const tombol = document.querySelector("[data-profil-pilih-foto-tombol]");
    const namaBerkas = document.querySelector("[data-profil-nama-berkas]");

    // Semua avatar di halaman (kartu kepala + dialog edit) memakai kelas
    // yang sama, jadi cukup satu pencarian.
    const avatars = Array.prototype.slice.call(
        document.querySelectorAll(".profil-avatar"),
    );

    // Keadaan awal, untuk dikembalikan kalau pengguna membatalkan
    // pemilihan file.
    const awal = avatars.map((avatar) => avatar.innerHTML);

    let pratinjau = null;

    const kembalikan = () => {
        avatars.forEach((avatar, index) => {
            avatar.innerHTML = awal[index];
        });

        if (namaBerkas) {
            namaBerkas.textContent = "";
            namaBerkas.classList.add("hidden");
        }
    };

    kolom.addEventListener("change", () => {
        // Lepaskan object URL sebelumnya supaya file sementara di memori
        // peramban tidak menumpuk setiap kali memilih file lagi.
        if (pratinjau) {
            URL.revokeObjectURL(pratinjau);
            pratinjau = null;
        }

        const berkas = kolom.files && kolom.files[0];

        if (!berkas) {
            kembalikan();
            return;
        }

        pratinjau = URL.createObjectURL(berkas);

        avatars.forEach((avatar) => {
            avatar.innerHTML = "";

            const gambar = document.createElement("img");

            gambar.src = pratinjau;
            gambar.alt = "Pratinjau foto profil";
            gambar.className = "profil-avatar__foto";
            gambar.setAttribute("data-profil-foto", "");

            avatar.appendChild(gambar);
        });

        if (namaBerkas) {
            namaBerkas.textContent = berkas.name;
            namaBerkas.classList.remove("hidden");
        }
    });

    if (tombol) {
        tombol.addEventListener("click", () => kolom.click());
    }
}

/* ------------------------------------------------------------------
 * MODE TERANG / GELAP
 *
 * Cara kerjanya cuma satu baris: mengganti data-theme pada <html>.
 * Seluruh perubahan tampilan ada di CSS (lihat blok "TEMA GELAP" di
 * resources/css/app.css), termasuk di halaman Dashboard, Materi, Quiz,
 * dan Hasil, tanpa perlu disentuh satu pun file halaman.
 *
 * localStorage dipakai karena temanya berlaku lintas halaman dan harus
 * berlaku sebelum halaman pertama selesai dimuat; itu sudah ditangani
 * oleh script kecil di <head> layouts/app.blade.php.
 * ------------------------------------------------------------------ */

function initTema() {
    const wadah = document.querySelector("[data-tema-pilih]");

    if (!wadah) {
        return;
    }

    const tombol = Array.prototype.slice.call(
        wadah.querySelectorAll("[data-tema]"),
    );

    const terapkan = (tema) => {
        document.documentElement.dataset.theme = tema;

        try {
            window.localStorage.setItem(KUNCI_TEMA, tema);
        } catch {
            // Mode privat atau storage penuh: tema tetap berlaku selama
            // halaman ini terbuka, hanya tidak diingat untuk kunjungan
            // berikutnya.
        }

        tombol.forEach((tombol) => {
            tombol.setAttribute(
                "aria-pressed",
                tombol.dataset.tema === tema ? "true" : "false",
            );
        });
    };

    // Samakan tombol dengan tema yang sudah dipasang script di <head>,
    // bukan dengan nilai default yang tertulis di markup.
    terapkan(
        document.documentElement.dataset.theme === "gelap" ? "gelap" : "terang",
    );

    tombol.forEach((tombol) => {
        tombol.addEventListener("click", () => terapkan(tombol.dataset.tema));
    });
}

initDialog();
initLihatSandi();
initPilihFoto();
initTema();
