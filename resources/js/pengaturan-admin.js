/**
 * Halaman Pengaturan admin (dan sub-halamannya).
 *
 * Berhenti sendiri kalau halamannya tidak ada, seperti modul lain di
 * resources/js.
 *
 * Yang dikerjakan di sini:
 *   1. Dialog pengaturan (buka, tutup, fokus, Escape, klik area gelap).
 *   2. Saklar nyala/mati yang menulis nilainya ke field form tersembunyi.
 *   3. Mode terang/gelap: mengganti data-theme tanpa menunggu server.
 *   4. Tombol mata untuk menampilkan/menyembunyikan password.
 *   5. Pratinjau foto profil sebelum form dikirim.
 *   6. Dialog tambah/ubah mata pelajaran yang berbagi satu form.
 *
 * Tidak ada satu pun request yang dikirim lewat fetch. Semua perubahan
 * dikirim lewat form biasa, jadi setelah aksi selesai seluruh area admin ikut
 * memakai data terbaru tanpa perlu disegarkan manual.
 */

/** Kunci localStorage yang sama dengan area user, supaya satu akun satu tema. */
const KUNCI_TEMA = "kk-tema";

/* ------------------------------------------------------------------
 * DIALOG
 *
 * Semua dialog pengaturan memakai pasangan atribut yang sama:
 *   data-atur-dialog-buka="<id>"   tombol pemicu
 *   data-atur-dialog="<id>"        kotak dialognya
 *   data-atur-dialog-batal         tombol pembatal, di mana pun di dialog
 *
 * Berbeda dari initDialog() di admin.js yang khusus untuk satu dialog
 * tinjauan, di sini satu halaman bisa punya lima dialog sekaligus.
 *
 * Kotak yang sudah terbuka ditutup dulu sebelum yang lain dibuka, jadi tidak
 * mungkin ada dua dialog menumpuk.
 * ------------------------------------------------------------------ */
function initDialogPengaturan() {
    const dialogs = Array.prototype.slice.call(
        document.querySelectorAll("[data-atur-dialog]"),
    );

    if (!dialogs.length) {
        return;
    }

    const tutup = (dialog, kembaliKePemicu = true) => {
        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");

        if (kembaliKePemicu) {
            const pemicu = document.querySelector(
                `[data-atur-dialog-buka="${dialog.dataset.aturDialog}"]`,
            );

            pemicu?.focus();
        }
    };

    const buka = (dialog) => {
        dialogs.forEach((lain) => tutup(lain, false));
        dialog.classList.add("is-buka");
        dialog.setAttribute("aria-hidden", "false");

        /*
         * Fokus ke kolom pertama supaya dialog langsung bisa diketik dan
         * pembaca layar pindah ke dalam dialog. Kalau tidak ada kolom, fokus
         * ke tombol tutup supaya tetap ada yang fokus di dalam dialog.
         */
        const pertama =
            dialog.querySelector(
                "input:not([type='hidden']):not([disabled]), textarea, select, button",
            ) ?? dialog.querySelector("[data-atur-dialog-batal]");

        if (pertama) {
            pertama.focus();
        }
    };

    document.querySelectorAll("[data-atur-dialog-buka]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const dialog = document.querySelector(
                `[data-atur-dialog="${tombol.dataset.aturDialogBuka}"]`,
            );

            if (dialog) {
                buka(dialog);
            }
        });
    });

    document.querySelectorAll("[data-atur-dialog-batal]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const dialog = tombol.closest("[data-atur-dialog]");

            if (dialog) {
                tutup(dialog);
            }
        });
    });

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
}

/* ------------------------------------------------------------------
 * MODE TERANG / GELAP
 *
 * Cara kerjanya satu baris: mengganti data-theme pada <html>. Seluruh
 * perubahan tampilan ada di CSS (blok [data-theme='gelap'] di
 * resources/css/admin.css), termasuk di halaman Dashboard, Konten,
 * Verifikasi, Materi, Quiz, Pengguna, dan Statistik, tanpa perlu disentuh
 * satu pun file halaman.
 *
 * localStorage dipakai karena temanya berlaku lintas halaman dan harus
 * berlaku sebelum halaman pertama selesai dimuat; itu sudah ditangani
 * script kecil di <head> layouts/admin.blade.php.
 *
 * Tombolnya tetap type="submit" dan tetap mengirim form ke server, jadi
 * pilihannya benar-benar tersimpan dan ikut berlaku di perangkat lain.
 * JavaScript ini hanya membuat warnanya langsung berubah, supaya tidak ada
 * kedipan selagi server belum menjawab.
 * ------------------------------------------------------------------ */
function initTema() {
    const wadah = document.querySelector("[data-atur-tema]");

    if (!wadah) {
        return;
    }

    const tombol = Array.prototype.slice.call(
        wadah.querySelectorAll("[data-atur-tema-pilih]"),
    );

    const terapkan = (tema) => {
        document.documentElement.dataset.theme = tema;

        try {
            window.localStorage.setItem(KUNCI_TEMA, tema);
        } catch {
            /*
             * Mode privat atau storage penuh: tema tetap berlaku selama
             * halaman ini terbuka, hanya tidak diingat untuk kunjungan
             * berikutnya. Server tetap menyimpan pilihannya, jadi perangkat
             * lain tetap ikut memakai tema yang sama.
             */
        }

        tombol.forEach((satu) => {
            satu.setAttribute(
                "aria-pressed",
                satu.dataset.aturTemaPilih === tema ? "true" : "false",
            );
        });
    };

    /*
     * Samakan tombol dengan tema yang sudah dipasang script di <head>,
     * bukan dengan nilai default yang tertulis di markup.
     */
    terapkan(
        document.documentElement.dataset.theme === "gelap" ? "gelap" : "terang",
    );

    tombol.forEach((satu) => {
        satu.addEventListener("click", () => terapkan(satu.dataset.aturTemaPilih));
    });
}

/* ------------------------------------------------------------------
 * SAKLAR
 *
 * Saklarnya tombol dengan role="switch", jadi yang perlu diubah saat diklik
 * hanya aria-checked-nya. Nilai aslinya ada di field tersembunyi tepat di
 * sebelahnya: checkbox yang tidak dicentang tidak pernah terkirim, jadi
 * tanpa field itu mematikan saklar akan berarti field-nya hilang dan
 * nilainya tidak akan pernah tersimpan.
 * ------------------------------------------------------------------ */
function initSaklar() {
    document.querySelectorAll("[data-atur-saklar]").forEach((saklar) => {
        saklar.addEventListener("click", () => {
            const menyala = saklar.getAttribute("aria-checked") === "true";

            saklar.setAttribute("aria-checked", menyala ? "false" : "true");

            /*
             * Field pasangannya dicari dari baris tempat saklar ini berada,
             * bukan dari seluruh halaman. Kalau nanti ada baris lain dengan
             * nama yang sama, keduanya tidak akan saling menimpa.
             */
            const baris = saklar.closest(".ad-atur-saklar-baris");

            if (!baris) {
                return;
            }

            const kolom = baris.querySelector("[data-atur-saklar-nilai]");

            if (kolom) {
                kolom.value = menyala ? "0" : "1";
            }
        });
    });
}

/* ------------------------------------------------------------------
 * TOMBOL MATA PASSWORD
 *
 * Hanya mengubah atribut type milik kolom di sebelahnya. Isi password tidak
 * pernah dibaca, disalin, atau disimpan di mana pun; seluruh pemeriksaan
 * tetap dilakukan di server.
 * ------------------------------------------------------------------ */
function initLihatSandi() {
    document.querySelectorAll("[data-atur-lihat-sandi]").forEach((tombol) => {
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

            /*
             * Dua ikon berbagi satu tombol; yang mana yang terlihat
             * ditentukan lewat kelas "hidden".
             */
            const terbuka = tombol.querySelector("[data-atur-mata-terbuka]");
            const tertutup = tombol.querySelector("[data-atur-mata-tertutup]");

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
 * PRATINJAU FOTO PROFIL
 *
 * Pratinjau memakai object URL dari berkas yang dipilih, jadi admin langsung
 * melihat hasilnya sebelum form dikirim. HTML awal avatar disimpan sekali
 * supaya keadaan sebelumnya bisa dikembalikan kalau pemilihan dibatalkan.
 * ------------------------------------------------------------------ */
function initPilihFoto() {
    const kolom = document.querySelector("[data-atur-foto]");

    if (!kolom) {
        return;
    }

    const tombol = document.querySelector("[data-atur-foto-tombol]");
    const namaBerkas = document.querySelector("[data-atur-foto-nama]");

    const wadah = Array.prototype.slice.call(
        document.querySelectorAll("[data-atur-avatar]"),
    );

    const awal = wadah.map((satu) => satu.innerHTML);

    let pratinjau = null;

    const kembalikan = () => {
        wadah.forEach((satu, index) => {
            satu.innerHTML = awal[index];
        });

        if (namaBerkas) {
            namaBerkas.textContent = "";
            namaBerkas.classList.add("hidden");
        }
    };

    kolom.addEventListener("change", () => {
        /*
         * Lepaskan object URL sebelumnya supaya berkas sementara di memori
         * peramban tidak menumpuk setiap kali memilih berkas lagi.
         */
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

        wadah.forEach((satu) => {
            satu.innerHTML = "";

            /*
             * <img> dibuat lewat document.createElement, bukan innerHTML:
             * nama berkas bisa mengandung karakter yang punya arti di HTML,
             * dan nama berkas tidak boleh pernah diperlakukan sebagai markup.
             */
            const gambar = document.createElement("img");

            gambar.src = pratinjau;
            gambar.alt = "Pratinjau foto profil";
            gambar.className = "h-full w-full object-cover";

            satu.appendChild(gambar);
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
 * DIALOG TAMBAH / UBAH MATA PELAJARAN
 *
 * Satu form dipakai dua mode. Yang berubah cuma action, method, judul, dan
 * kolom mana yang ikut tampil.
 *
 * Kolom kode (slug) sengaja disembunyikan di mode ubah: slug dipakai di URL
 * filter, jadi ikut berubahnya slug tanpa disengaja akan mematikan tautan
 * lama. Menonaktifkan kolomnya bukan hanya menyembunyikannya, tapi juga
 * mengosongkan nilainya supaya tidak ikut terkirim.
 * ------------------------------------------------------------------ */
function initDialogPelajaran() {
    const dialog = document.querySelector('[data-atur-dialog="atur-pelajaran"]');

    if (!dialog) {
        return;
    }

    const form = dialog.querySelector("[data-atur-pelajaran-form]");
    const metode = dialog.querySelector("[data-atur-pelajaran-metode]");
    const judul = dialog.querySelector("[data-atur-pelajaran-judul]");
    const kolomSlug = dialog.querySelector("[data-atur-pelajaran-sembunyi]");

    if (!form || !metode || !judul) {
        return;
    }

    const isi = (nama, nilai) => {
        const kolom = dialog.querySelector(`[data-atur-pelajaran-nilai="${nama}"]`);

        if (kolom) {
            kolom.value = nilai ?? "";
        }
    };

    const saklarAktif = dialog.querySelector('[data-atur-saklar-nama="aktif"]');
    const kolomAktif = dialog.querySelector('[data-atur-saklar-nilai][name="aktif"]');

    // Mode tambah: form bawaan server sudah benar, jadi hanya dibersihkan.
    document.querySelectorAll("[data-atur-pelajaran-baru]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            judul.textContent = "Tambah Mata Pelajaran";
            form.setAttribute(
                "action",
                form.dataset.aturPelajaranSimpan ||
                    form.getAttribute("action"),
            );
            metode.value = "POST";

            dialog.querySelectorAll("[data-atur-pelajaran-nilai]").forEach((kolom) => {
                kolom.value = "";
            });

            if (kolomSlug) {
                kolomSlug.classList.remove("hidden");
            }

            if (saklarAktif) {
                saklarAktif.setAttribute("aria-checked", "true");
            }

            if (kolomAktif) {
                kolomAktif.value = "1";
            }
        });
    });

    document.querySelectorAll("[data-atur-pelajaran-ubah]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            judul.textContent = "Ubah Mata Pelajaran";
            form.setAttribute("action", tombol.dataset.aksi || "");
            metode.value = "PUT";

            isi("nama", tombol.dataset.nama);
            isi("deskripsi", tombol.dataset.deskripsi);

            if (kolomSlug) {
                kolomSlug.classList.add("hidden");
                isi("slug", "");
            }

            const aktif = tombol.dataset.aktif === "1";

            if (saklarAktif) {
                saklarAktif.setAttribute("aria-checked", aktif ? "true" : "false");
            }

            if (kolomAktif) {
                kolomAktif.value = aktif ? "1" : "0";
            }
        });
    });
}

initDialogPengaturan();
initTema();
initSaklar();
initLihatSandi();
initPilihFoto();
initDialogPelajaran();
