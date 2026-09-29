// Halaman "Tambah Materi" (berhenti sendiri kalau halamannya tidak ada).
import "./materi-tambah.js";

// Halaman "Detail Materi" (salin kode + daftar isi).
import "./materi-detail.js";

// Halaman "Buat Quiz" (tiga langkah wizard, thumbnail, kode, saklar).
import "./quiz-tambah.js";

// Builder soal pada langkah "Buat Soal" (lima tipe soal, pilihan dinamis).
// Diimpor setelah quiz-tambah.js karena builder soal menyediakan validasi
// yang dipakai wizard sebelum lanjut ke langkah berikutnya.
import "./quiz-builder.js";

// Halaman detail quiz (daftar soal yang dilipat, tombol bagikan).
import "./quiz-detail.js";

/*
 * Halaman mengerjakan soal (timer, navigator mini, pemeriksaan isian).
 *
 * WAJIB di-import sebelum quiz-lobby.js. Form jawaban di soal terakhir juga
 * memakai dialog konfirmasi milik quiz-lobby.js, dan kedua modul sama-sama
 * memasang listener submit pada form yang sama. Yang memasang lebih dulu
 *说了算: kalau dialog konfirmasi lebih dulu, isian yang masih kosong akan
 * tetap membuka dialog "Selesaikan Quiz?" walau jawabannya belum ada.
 */
import "./quiz-kerjakan.js";

// Halaman sesi quiz (polling lobby, salin kode, dialog konfirmasi).
import "./quiz-lobby.js";

// Halaman Profil (dialog, lihat password, pilih foto, mode terang/gelap).
import "./profil.js";

const reducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
).matches;

function initReveal() {
    const targets = document.querySelectorAll(
        "[data-reveal], [data-reveal-stagger] > *",
    );

    if (!targets.length) {
        return;
    }

    if (reducedMotion || !("IntersectionObserver" in window)) {
        targets.forEach((el) => el.classList.add("is-reveal"));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("is-reveal");
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.12, rootMargin: "0px 0px -6% 0px" },
    );

    targets.forEach((el) => observer.observe(el));
}

function initScrollProgress() {
    const bar = document.querySelector("[data-scroll-progress]");

    if (!bar || reducedMotion) {
        return;
    }

    let ticking = false;

    const update = () => {
        const scrollable =
            document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollable > 0 ? window.scrollY / scrollable : 0;

        bar.style.transform = `scaleX(${Math.min(Math.max(ratio, 0), 1)})`;
        ticking = false;
    };

    window.addEventListener(
        "scroll",
        () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        },
        { passive: true },
    );

    window.addEventListener("resize", update, { passive: true });

    update();
}

function initNavSpy() {
    const links = Array.prototype.slice.call(
        document.querySelectorAll(".nav-link[href^='#']"),
    );

    if (!links.length) {
        return;
    }

    const sections = links
        .map((link) => document.querySelector(link.getAttribute("href")))
        .filter(Boolean);

    if (!sections.length) {
        return;
    }

    const activate = (id) => {
        links.forEach((link) => {
            link.classList.toggle(
                "active",
                link.getAttribute("href") === `#${id}`,
            );
        });
    };

    const current = () => {
        const offset = window.innerHeight * 0.35;

        return sections.reduce(
            (acc, section) =>
                section.getBoundingClientRect().top <= offset
                    ? section.id
                    : acc,
            "",
        );
    };

    let ticking = false;

    window.addEventListener(
        "scroll",
        () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(() => {
                activate(current());
                ticking = false;
            });
        },
        { passive: true },
    );

    activate(current());
}

/**
 * Pencarian: ketik di kolom search submit setelah jeda, dan ganti filter
 * di dropdown langsung submit.
 *
 * Berlaku untuk form mana pun yang menandai diri dengan data-cari-form,
 * jadi halaman Materi dan Quiz memakai mekanisme yang sama.
 */
function initCari() {
    const form = document.querySelector("[data-cari-form]");

    if (!form) {
        return;
    }

    const input = form.querySelector("[data-cari-input]");
    const filter = form.querySelector("[data-cari-filter]");
    const delay = input ? 450 : 0;
    let timer = null;

    const kirim = () => {
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }

        form.submit();
    };

    if (input) {
        input.addEventListener("input", () => {
            if (timer) {
                window.clearTimeout(timer);
            }

            timer = window.setTimeout(kirim, delay);
        });
    }

    if (filter) {
        filter.addEventListener("change", kirim);
    }

    // Tekan Enter tetap jalan walau jeda debounce belum selesai.
    form.addEventListener("submit", () => {
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }
    });
}

/**
 * Bookmark.
 *
 * Materi memakai tabel tb_simpanan_materi, jadi statusnya sama di
 * perangkat mana pun. Daftarnya dibaca sekali lewat meta
 * "simpanan-daftar", lalu tiap klik mengirim POST ke "simpanan-toggle"
 * (URL-nya masih berisi "__slug__" yang diganti di sini).
 *
 * Quiz masih di localStorage dan dipisah lewat atribut
 * data-bookmark-ruang="quiz" karena belum punya tabel penyimpanan.
 */
function initBookmark() {
    const tombol = Array.from(document.querySelectorAll("[data-bookmark]"));

    if (!tombol.length) {
        return;
    }

    const meta = (nama) => document.querySelector(`meta[name="${nama}"]`)?.content || "";
    const token = () => document.querySelector('meta[name="csrf-token"]')?.content || "";
    const urlDaftar = meta("simpanan-daftar");
    const urlToggle = meta("simpanan-toggle");

    const terapkan = (el, tersimpan) => {
        el.setAttribute("aria-pressed", tersimpan ? "true" : "false");

        // Tombol yang punya label (mis. "Simpan" di halaman detail) ikut
        // mengganti teksnya. Label netral disimpan di data-bookmark-teks.
        const label = el.querySelector("[data-bookmark-teks-nowel]");

        if (label) {
            label.textContent = tersimpan
                ? "Tersimpan"
                : el.dataset.bookmarkTeks || "Simpan";
        }
    };

    const dariRuang = (ruang) =>
        tombol.filter((el) => (el.dataset.bookmarkRuang || "materi") === ruang);

    const tombolMateri = dariRuang("materi");
    const tombolQuiz = dariRuang("quiz");

    // Satu slug bisa punya lebih dari satu tombol (kartu daftar + kepala
    // detail), jadi semuanya diperbarui bersama supaya tidak pernah beda.
    const sinkronMateri = (slug, tersimpan) => {
        tombolMateri
            .filter((el) => el.dataset.bookmark === slug)
            .forEach((el) => terapkan(el, tersimpan));
    };

    /* ---------- Ruang quiz: localStorage ---------- */

    const kunciQuiz = "kk-quiz-disimpan";

    const bacaQuiz = () => {
        try {
            return JSON.parse(window.localStorage.getItem(kunciQuiz) || "[]");
        } catch {
            return [];
        }
    };

    const tulisQuiz = (daftar) => {
        try {
            window.localStorage.setItem(kunciQuiz, JSON.stringify(daftar));
        } catch {
            // Mode privat / storage penuh: bookmark tetap jalan di sesi ini.
        }
    };

    const daftarQuiz = bacaQuiz();

    tombolQuiz.forEach((el) => terapkan(el, daftarQuiz.includes(el.dataset.bookmark)));

    /* ---------- Ruang materi: database ---------- */

    /*
     * Status awal dari server sudah benar untuk tombol di halaman detail,
     * tapi kartu di daftar masih mengirim aria-pressed="false" bawaan.
     * Satu fetch membetulkan semuanya sekaligus.
     */
    if (tombolMateri.length && urlDaftar) {
        fetch(urlDaftar, {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                const tersimpan = Array.isArray(data?.slug) ? data.slug : [];

                tombolMateri.forEach((el) => terapkan(el, tersimpan.includes(el.dataset.bookmark)));
            })
            .catch(() => {
                // Gagal memuat daftar: status dari server (halaman detail)
                // tetap dipertahankan, kartu lain dibiarkan sesuai markup.
            });
    }

    /* ---------- Klik ---------- */

    tombol.forEach((el) => {
        el.addEventListener("click", (event) => {
            // Tombol ada di dalam <a> kartu: cegah agar tidak ikut
            // membuka halaman detail.
            event.preventDefault();
            event.stopPropagation();

            const ruang = el.dataset.bookmarkRuang || "materi";
            const id = el.dataset.bookmark;

            if (ruang === "quiz") {
                const daftar = bacaQuiz();
                const sudah = daftar.includes(id);
                const baru = sudah ? daftar.filter((x) => x !== id) : [...daftar, id];

                tulisQuiz(baru);
                tombolQuiz
                    .filter((x) => x.dataset.bookmark === id)
                    .forEach((x) => terapkan(x, !sudah));

                return;
            }

            // Optimistik dulu supaya tombol terasa instan, lalu serahkan
            // ke server sebagai pemilik keadaan sebenarnya.
            const berikutnya = el.getAttribute("aria-pressed") !== "true";

            sinkronMateri(id, berikutnya);

            if (!urlToggle) {
                return;
            }

            fetch(urlToggle.replace("__slug__", encodeURIComponent(id)), {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                body: "{}",
            })
                .then((res) => (res.ok ? res.json() : null))
                .then((data) => {
                    if (data && typeof data.tersimpan === "boolean") {
                        sinkronMateri(id, data.tersimpan);
                    }
                })
                .catch(() => {
                    // Jaringan gagal: kembalikan tombol ke keadaan semula.
                    sinkronMateri(id, !berikutnya);
                });
        });
    });
}

/**
 * Keadaan memuat pada halaman Quiz.
 *
 * Halaman Quiz dirender di server, jadi kerangka (skeleton) sudah ada di
 * markup tetapi disembunyikan. Fungsi ini menampilkannya tepat sebelum
 * browser meninggalkan halaman: saat tab diganti, tautan pagination
 * diklik, atau hasil pencarian dikirim. Dengan begitu perpindahan tidak
 * terasa kosong. Kalau JavaScript mati tidak ada yang tertinggal
 * tersembunyi, karena atribut hidden memang ada di markup.
 */
function initQuizMuat() {
    const rangka = document.querySelector("[data-quiz-rangka]");

    if (!rangka || reducedMotion) {
        return;
    }

    const daftar = document.querySelector("[data-quiz-daftar]");

    // Fallback keamanan: kalau navigasi dibatalkan (klik kanan, Escape,
    // atau back-forward cache) skeleton tidak boleh menggantung.
    let timer = null;

    const tampilkan = () => {
        if (timer) {
            window.clearTimeout(timer);
        }

        // Jeda pendek supaya skeleton tidak berkedip untuk navigasi yang
        // selesai di bawah 120ms (mis. halaman sudah ter-cache).
        timer = window.setTimeout(() => {
            rangka.hidden = false;

            if (daftar) {
                daftar.hidden = true;
            }
        }, 120);
    };

    const kembalikan = () => {
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }

        rangka.hidden = true;

        if (daftar) {
            daftar.hidden = false;
        }
    };

    /*
     * Kerangka hanya layak untuk "halaman ini, datanya berbeda": ganti
     * tab, ganti kata kunci, pindah halaman pagination. Berpindah ke form
     * tambah atau ke detail quiz adalah halaman baru, jadi tidak perlu
     * kerangka.
     */
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

    document.querySelectorAll("[data-quiz-navigasi]").forEach((el) => {
        el.addEventListener("click", (event) => {
            // Modifier ditekan atau klik bukan tombol kiri = membuka di tab
            // lain atau menu konteks, jadi halaman ini tidak ditinggalkan.
            if (
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.button !== 0
            ) {
                return;
            }

            const target = event.target.closest("a");
            const href = target ? target.getAttribute("href") : null;

            if (href && tetapDiHalaman(href)) {
                tampilkan();
            }
        });
    });

    // Pencarian mengirim form, bukan mengikuti tautan, jadi efeknya
    // dipasang langsung di form-nya.
    document.querySelectorAll("[data-cari-form]").forEach((form) => {
        form.addEventListener("submit", tampilkan);
    });

    // pageshow juga terpanggil saat halaman kembali dari back-forward cache.
    window.addEventListener("pageshow", kembalikan);
    window.addEventListener("pagehide", kembalikan);
}

/**
 * Konfirmasi hapus untuk kartu karya.
 *
 * Tombol "Hapus" pada kartu sengaja type="button": form-nya baru dikirim
 * setelah pengguna menekan "Hapus" di dialog. Kalau JavaScript tidak
 * berjalan, tidak ada yang terkirim sama sekali, bukan terhapus diam-diam.
 *
 * Judul, pesan, dan form yang dijalankan diambil dari atribut data-* pada
 * form di sekitar tombol, jadi satu dialog cukup untuk semua kartu.
 */
function initKonfirmasi() {
    const dialog = document.querySelector("[data-konfirmasi-dialog]");

    if (!dialog) {
        return;
    }

    const judul = dialog.querySelector("[data-konfirmasi-judul]");
    const pesan = dialog.querySelector("[data-konfirmasi-pesan]");
    const tombolBatal = dialog.querySelector("[data-konfirmasi-batal]");
    const tombolYa = dialog.querySelector("[data-konfirmasi-ya]");

    let form = null;

    const tutup = () => {
        dialog.classList.remove("is-buka");
        form = null;
    };

    document.querySelectorAll("[data-konfirmasi]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            form = tombol.closest("form[data-konfirmasi-form]");

            if (!form) {
                return;
            }

            if (judul) {
                judul.textContent = form.dataset.konfirmasiJudul || "Hapus?";
            }

            if (pesan) {
                pesan.textContent =
                    form.dataset.konfirmasiPesan ||
                    "Karya ini akan dihapus dan tidak dapat dikembalikan.";
            }

            dialog.classList.add("is-buka");
            tombolBatal?.focus();
        });
    });

    tombolBatal?.addEventListener("click", tutup);

    // Klik area gelap di luar kotak dialog juga membatalkan.
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

    tombolYa?.addEventListener("click", () => {
        // submit() (bukan requestSubmit) supaya event submit tidak masuk
        // ke penanganan di atas lagi.
        form?.submit();
    });
}

/*
 * Halaman "Hasil Quiz" (kartu besar): angka nilai menghitung naik dari 0.
 *
 * Angka akhirnya sudah tertulis di HTML, jadi animasi ini murni kosmetik dan
 * halamannya tetap benar tanpa JavaScript. Kalau pengguna meminta reduced
 * motion, angkanya dibiarkan langsung tampil.
 */
function initUiuxNilai() {
    const angka = document.querySelector("[data-nilai-akhir]");
    const memuat = document.documentElement.hasAttribute("data-uiux-muat");

    if (!angka) {
        return;
    }

    const tujuan = Number(angka.textContent);
    const jalankan = () => {
        if (!Number.isFinite(tujuan) || reducedMotion) {
            return;
        }

        const mulai = performance.now();
        const lama = 700;

        const hitung = (waktu) => {
            // easeOutCubic: cepat di awal lalu melambat mendekati angka
            // akhir, jadi tidak terlihat seperti penghitung linear.
            const rasio = Math.min(1, (waktu - mulai) / lama);
            const eased = 1 - Math.pow(1 - rasio, 3);

            angka.textContent = String(Math.round(tujuan * eased));

            if (rasio < 1) {
                requestAnimationFrame(hitung);
            }
        };

        requestAnimationFrame(hitung);
    };

    // Angka besar tidak akan terlihat selama kerangka loading masih
    // menutup halaman, jadi hitungannya baru jalan setelah halaman selesai
    // dimuat. Tanpa kerangka (halaman lain) animasi langsung jalan.
    if (memuat) {
        window.addEventListener("load", () => requestAnimationFrame(jalankan), {
            once: true,
        });
    } else {
        jalankan();
    }
}

initReveal();
initScrollProgress();
initNavSpy();
initCari();
initBookmark();
initQuizMuat();
initKonfirmasi();
initUiuxNilai();
