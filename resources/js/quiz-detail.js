// Halaman detail quiz: tombol Bagikan, pembatasan lima baris pada daftar
// soal, dan pemotretan banner kategori kalau fotonya gagal dimuat.
// Semua fungsi berhenti sendiri kalau elemennya tidak ada di halaman ini.

const KELAS_TERBUKA = "is-buka";

/*
 * Daftar soal: lima baris pertama tampil, tombol "Lihat semua" di bawah
 * daftar menambah lima baris setiap klik (5, 10, 15, ...), dan begitu
 * seluruh soal terbuka tombolnya berganti jadi "Sembunyikan" untuk
 * mengembalikan daftar ke lima baris seperti awal.
 *
 * Baris yang belum waktunya tampil sudah diberi atribut hidden oleh
 * server, jadi tidak ada kilatan soal berikutnya sebelum skrip ini
 * berjalan. Tanpa JavaScript seluruh baris dibuka kembali oleh aturan
 * noscript di layout, dan tombolnya ikut disembunyikan karena tidak ada
 * yang bisa dilakukannya.
 */
function initDaftarSoal() {
    const seksi = document.querySelector("[data-daftar-soal]");

    if (!seksi) {
        return;
    }

    const wadah = seksi.querySelector("[data-daftar-soal-tombol]");
    const tombol = wadah ? wadah.querySelector("button") : null;
    const baris = Array.prototype.slice.call(
        seksi.querySelectorAll(".daftar-soal__baris"),
    );

    if (!tombol || baris.length === 0) {
        return;
    }

    const batas = Number(wadah.dataset.batas) || 5;
    let tampil = Math.min(batas, baris.length);

    const pasang = () => {
        baris.forEach((baris, index) => {
            if (index >= tampil) {
                baris.setAttribute("hidden", "");
            } else {
                baris.removeAttribute("hidden");
            }
        });

        // Semua soal sudah terlihat = tugas tombol tinggal menutup kembali.
        tombol.textContent =
            tampil >= baris.length ? "Sembunyikan" : "Lihat semua";
    };

    tombol.addEventListener("click", () => {
        const terbukaPenuh = tampil >= baris.length;

        tampil = terbukaPenuh
            ? Math.min(batas, baris.length)
            : Math.min(tampil + batas, baris.length);

        pasang();

        // Menutup kembali memendekkan daftar drastis, jadi gulir ke atas
        // seksi supaya pengguna tidak ditinggal di ruang kosong bawah.
        if (terbukaPenuh) {
            const kurangiGerak = window.matchMedia(
                "(prefers-reduced-motion: reduce)",
            ).matches;

            seksi.scrollIntoView({
                block: "start",
                behavior: kurangiGerak ? "auto" : "smooth",
            });
        }
    });

    pasang();
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

initBagikan();
initDaftarSoal();
initBanner();
