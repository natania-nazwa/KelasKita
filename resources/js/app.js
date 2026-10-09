// Halaman Masuk dan Daftar (tombol mata pada kolom kata sandi).
import "./auth.js";

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

/*
 * Area admin "Konten Pembelajaran": konfirmasi terbitkan dan perpindahan tahap
 * form materi.
 *
 * Diimpor setelah quiz-tambah.js karena tombol "Publish Sekarang" milik
 * wizard quiz memasang listener-nya sendiri lebih dulu, dan listener di
 * konten-publish.js dipasang di fase tangkap supaya tetap menang.
 */
import "./konten-publish.js";

/*
 * Halaman "Konten Pembelajaran": menu aksi per baris (titik-tiga) dan
 * kerangka daftar saat berpindah tab, mencari, menyaring, atau paginasi.
 *
 * Diimpor setelah konten-publish.js karena baris daftar memakai pemicunya
 * untuk aksi Publish, dan listener di konten-publish.js dipasang di fase
 * tangkap supaya tetap menang.
 */
import "./konten-daftar.js";

/*
 * Toast hasil aksi seluruh area admin: menutup dirinya sendiri dan tombol
 * silangnya.
 *
 * Modul ini sama sekali tidak bergantung pada elemen Konten Pembelajaran,
 * dan elemen yang dicari berhenti sendiri kalau halaman tidak punya toast --
 * jadi aman diimpor di mana saja.
 *
 * WAJIB diimpor. Sebelumnya file ini ada tapi tidak pernah diimpor dari
 * mana pun, sehingga tidak ada yang memasang timer maupun listener tombol
 * tutup: toast "Draft berhasil disimpan" dan sejenisnya tidak pernah hilang
 * sendiri, dan tombol X-nya tidak bereaksi. Toast dipakai di delapan
 * halaman admin (Konten, Konten Quiz, Pengguna, dan Pengaturan beserta
 * empat sub-halamannya), jadi satu impor di sini menutup semuanya.
 */
import "./konten-admin.js";

/*
 * Area admin "Pengaturan": dialog pengaturan, saklar, mode terang/gelap,
 * pratinjau foto profil, dan lonceng notifikasi di topbar.
 *
 * Diimpor paling akhir karena modul-modul di atas tidak bergantung padanya,
 * dan urutan ini membuat area admin lainnya selesai dulu.
 */
import "./pengaturan-admin.js";

// Halaman detail quiz (daftar soal yang dilipat, tombol bagikan).
import "./quiz-detail.js";

/*
 * Halaman mengerjakan soal (timer, navigator mini, pemeriksaan isian).
 *
 * WAJIB di-import sebelum quiz-lobby.js. Form jawaban di soal terakhir juga
 * memakai dialog konfirmasi milik quiz-lobby.js, dan kedua modul sama-sama
 * memasang listener submit pada form yang sama. Yang memasang lebih dulu
 * yang menang: kalau dialog konfirmasi lebih dulu, isian yang masih kosong akan
 * tetap membuka dialog "Selesaikan Quiz?" walau jawabannya belum ada.
 */
import "./quiz-kerjakan.js";

// Halaman sesi quiz (polling lobby, salin kode, dialog konfirmasi).
import "./quiz-lobby.js";

// Halaman Profil (dialog, lihat password, pilih foto, mode terang/gelap).
import "./profil.js";

// Lonceng notifikasi di top bar halaman user (tandai terbaca saat diklik).
import "./notifikasi.js";

/*
 * Area admin KelasKita (drawer sidebar, dropdown akun, dialog tinjau).
 *
 * Diimpor terakhir karena modul ini hanya mencari elemen yang ada di
 * halaman admin, dan tidak ada halaman user yang punya elemen-elemen
 * itu.
 */
import "./admin.js";

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

/**
 * Landing page: tombol "Lihat Quiz".
 *
 * Tombol ini tidak pernah membawa langsung ke /user/quiz. Saat ditekan,
 * yang muncul adalah dialog kecil berisi pengingat untuk login — bukan
 * scroll ke bawah dan bukan soal quiz. Isi dialognya satu untuk semua
 * orang: yang menjaga daftar quiz tetap tertutup adalah middleware
 * "auth" di route /user/quiz, bukan tombolnya.
 *
 * Buka/tutup mengikuti pola .modal yang sudah dipakai dialog di halaman
 * Profil dan Admin: display:none sebagai keadaan awal, kelas .is-buka
 * membuatnya flex. Kalau JavaScript mati, dialog tetap tidak pernah
 * menutupi halaman — tombol hero cukup kehilangan efeknya.
 *
 * Berhenti sendiri di halaman lain karena elemen-elemen ini cuma ada di
 * landing page.
 */
function initAksesQuiz() {
    const pemicu = document.querySelector("[data-quiz-buka]");
    const dialog = document.querySelector("[data-quiz-akses]");

    if (!pemicu || !dialog) {
        return;
    }

    const tutup = () => {
        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");

        // Fokus dikembalikan ke tombol pemicu supaya navigasi keyboard
        // tidak melompat ke awal halaman.
        pemicu.focus();
    };

    pemicu.addEventListener("click", (acara) => {
        acara.preventDefault();

        dialog.classList.add("is-buka");
        dialog.setAttribute("aria-hidden", "false");

        /*
         * Fokus ditaruh ke panel dialog, BUKAN langsung ke tombol
         * aksinya.
         *
         * Kalau fokus langsung ke tautan "Buka Daftar Quiz", satu
         * penekanan Space atau Enter berikutnya — yang biasa orang
         * tekan untuk menggulir halaman — langsung mengaktifkan tautan
         * itu dan mengarahkan ke /user/quiz. Fokus di panel membuat
         * tombol aksi tetap satu Tab di depan, seperti dialog biasa.
         */
        dialog.focus({ preventScroll: true });
    });

    dialog.querySelectorAll("[data-quiz-tutup]").forEach((tombol) => {
        tombol.addEventListener("click", tutup);
    });

    // Klik area gelap di luar panel juga membatalkan.
    dialog.addEventListener("click", (acara) => {
        if (acara.target === dialog) {
            tutup();
        }
    });

    document.addEventListener("keydown", (acara) => {
        if (acara.key === "Escape" && dialog.classList.contains("is-buka")) {
            tutup();
        }
    });
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
 * Materi maupun quiz memakai tabel simpanan di database (tb_simpanan_materi
 * dan tb_simpanan_quiz), jadi statusnya sama di perangkat mana pun.
 * Daftar tiap ruang dibaca sekali lewat meta "simpanan-*-daftar", lalu tiap
 * klik mengirim POST ke "simpanan-toggle" (URL-nya masih berisi "__slug__"
 * atau "__id__" yang diganti di sini).
 *
 * Kunci materi adalah slug, kunci quiz adalah id, karena kedua tombolnya
 * memang memakai kunci yang dipakai halaman detailnya masing-masing.
 *
 * Di halaman Simpan (elemen [data-simpanan-halaman]) melepas simpanan
 * ikut membuang kartunya dari daftar, supaya daftar tidak menampilkan
 * barang yang sudah tidak disimpan.
 */
function initBookmark() {
    const tombol = Array.from(document.querySelectorAll("[data-bookmark]"));

    if (!tombol.length) {
        return;
    }

    const meta = (nama) =>
        document.querySelector(`meta[name="${nama}"]`)?.content || "";
    const token = () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    /*
     * Dua ruang yang sama-sama disimpan ke database. Bentuknya map supaya
     * seluruh alur (baca daftar, klik, sinkron) cukup ditulis sekali untuk
     * keduanya.
     */
    const ruangAda = ["materi", "quiz"];

    const urlDaftar = {
        materi: meta("simpanan-daftar"),
        quiz: meta("simpanan-quiz-daftar"),
    };

    const urlToggle = {
        materi: meta("simpanan-toggle"),
        quiz: meta("simpanan-quiz-toggle"),
    };

    const kunci = {
        materi: "__slug__",
        quiz: "__id__",
    };

    const kunciDaftar = {
        materi: "slug",
        quiz: "id",
    };

    const halamanSimpanan = document.querySelector("[data-simpanan-halaman]");

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

    // Satu kunci bisa punya lebih dari satu tombol (kartu daftar + kepala
    // detail), jadi semuanya diperbarui bersama supaya tidak pernah beda.
    const sinkron = (ruang, nilai, tersimpan) => {
        dariRuang(ruang)
            .filter((el) => el.dataset.bookmark === nilai)
            .forEach((el) => terapkan(el, tersimpan));
    };

    /*
     * Status awal dari server sudah benar untuk tombol di halaman detail
     * materi, tapi kartu di daftar masih mengirim aria-pressed="false"
     * bawaan. Satu fetch per ruang membetulkan semuanya sekaligus.
     */
    ruangAda.forEach((ruang) => {
        if (!dariRuang(ruang).length || !urlDaftar[ruang]) {
            return;
        }

        fetch(urlDaftar[ruang], {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                const tersimpan = Array.isArray(data?.[kunciDaftar[ruang]])
                    ? data[kunciDaftar[ruang]].map(String)
                    : [];

                dariRuang(ruang).forEach((el) =>
                    terapkan(el, tersimpan.includes(el.dataset.bookmark)),
                );
            })
            .catch(() => {
                // Gagal memuat daftar: status dari server (halaman detail)
                // tetap dipertahankan, kartu lain dibiarkan sesuai markup.
            });
    });

    /*
     * Di halaman Simpan, kartu yang baru dilepas simpanannya keluar dari
     * daftar: kartu tetap ada sampai server mengonfirmasi, jadi kegagalan
     * jaringan tidak membuat kartu hilang lalu muncul lagi.
     */
    const buangKartu = (tombol) => {
        const kartu = tombol.closest(".kartu-materi, .kartu-quiz");

        if (!kartu) {
            return;
        }

        // Angka tab ikut diperbarui supaya tidak menampilkan jumlah yang
        // sudah basi setelah kartunya hilang.
        const angka = document.querySelector(
            '[role="tab"][aria-selected="true"] .tab-karya__jumlah',
        );

        if (angka) {
            const jumlah = Number(angka.textContent);
            angka.textContent = String(
                Math.max(0, (Number.isFinite(jumlah) ? jumlah : 1) - 1),
            );
        }

        if (reducedMotion) {
            kartu.remove();
            return;
        }

        kartu.classList.add(
            "transition",
            "duration-200",
            "opacity-0",
            "scale-95",
        );

        window.setTimeout(() => kartu.remove(), 200);
    };

    /* ---------- Klik ---------- */

    tombol.forEach((el) => {
        el.addEventListener("click", (event) => {
            // Tombol ada di dalam <a> kartu: cegah agar tidak ikut
            // membuka halaman detail.
            event.preventDefault();
            event.stopPropagation();

            const ruang = el.dataset.bookmarkRuang || "materi";
            const nilai = el.dataset.bookmark;
            const pintu = urlToggle[ruang];

            // Optimistik dulu supaya tombol terasa instan, lalu serahkan
            // ke server sebagai pemilik keadaan sebenarnya.
            const berikutnya = el.getAttribute("aria-pressed") !== "true";

            sinkron(ruang, nilai, berikutnya);

            if (!pintu) {
                return;
            }

            fetch(pintu.replace(kunci[ruang], encodeURIComponent(nilai)), {
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
                        sinkron(ruang, nilai, data.tersimpan);

                        if (halamanSimpanan && !data.tersimpan) {
                            buangKartu(el);
                        }
                    }
                })
                .catch(() => {
                    // Jaringan gagal: kembalikan tombol ke keadaan semula.
                    sinkron(ruang, nilai, !berikutnya);
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

/*
 * Halaman "Simpan": daftar kartu yang panjangnya tidak dibatasi
 * pagination.
 *
 * Tautan "Muat lagi" yang di-render Blade tetap tautan biasa ke
 * ?page=2, jadi tanpa JavaScript daftar tetap bisa dibaca habis. Kalau
 * JavaScript jalan, tautan itu malah diperlakukan sebagai pemicu
 * pemuatan: kartu berikutnya disisipkan ke dalam grid yang sudah ada
 * (data-simpan-grid), lalu tautannya diarahkan ke halaman setelahnya.
 * Kalau tombolnya sudah terlihat di layar, halaman berikutnya dimuat
 * duluan, jadi menggulir ke bawah tidak pernah berhenti di halaman
 * yang sama.
 *
 * Elemen grid punya opacity 0 sampai dapat kelas .is-reveal, dan itu
 * hanya dipasang initReveal() sekali saat halaman pertama dimuat.
 * Karena itu kartu yang baru disisipkan langsung diberi kelas itu,
 * kalau tidak kartunya tidak terlihat sama sekali.
 */
function initMuatLebih() {
    const tautan = document.querySelector("[data-muat-lebih]");
    const grid = document.querySelector("[data-simpan-grid]");

    if (!tautan || !grid) {
        return;
    }

    let sedang = false;

    const sisipkan = (html) => {
        const sebelumnya = new Set(Array.from(grid.children));

        grid.insertAdjacentHTML("beforeend", html);

        Array.from(grid.children).forEach((el) => {
            if (!sebelumnya.has(el)) {
                el.classList.add("is-reveal");
            }
        });
    };

    const muat = async (alamat) => {
        if (sedang || !alamat) {
            return;
        }

        sedang = true;
        tautan.setAttribute("aria-busy", "true");

        try {
            const res = await fetch(alamat, {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });

            const data = res.ok ? await res.json() : null;

            if (!data || typeof data.kartu !== "string") {
                return;
            }

            sisipkan(data.kartu);

            // null = tidak ada halaman berikutnya, jadi tombolnya
            // dibuang dan tidak ada yang perlu dimuat lagi.
            if (data.berikutnya) {
                tautan.setAttribute("href", data.berikutnya);
            } else {
                tautan.remove();
            }
        } catch {
            // Jaringan gagal: tautan dibiarkan apa adanya, jadi klik
            // berikutnya masih membuka halaman penuh sebagai cadangan.
        } finally {
            sedang = false;
            tautan.removeAttribute("aria-busy");
        }
    };

    tautan.addEventListener("click", (event) => {
        // Modifier ditekan atau klik bukan tombol kiri = membuka di tab
        // lain, jadi halaman ini tidak perlu dimuat.
        if (
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.button !== 0
        ) {
            return;
        }

        event.preventDefault();
        muat(tautan.getAttribute("href"));
    });

    if ("IntersectionObserver" in window) {
        const pemantau = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        muat(tautan.getAttribute("href"));
                    }
                });
            },
            // Dilebihkan 400px supaya halaman berikutnya sudah siap
            // sebelum tombolnya benar-benar terlihat.
            { rootMargin: "0px 0px 400px 0px" },
        );

        pemantau.observe(tautan);
    }
}

/**
 * Filter yang langsung mengirim form begitu pilihannya diganti.
 *
 * Halaman "Konten Pembelajaran" punya empat kontrol di satu baris: kotak
 * cari, Kategori, Status, dan Urutan. Tanpa ini, memilih "Draft" baru berlaku
 * setelah admin juga menekan "Terapkan" — padahal pada tiga select sisanya
 * tidak ada alasan untuk menunggu, dan tombol "Terapkan" yang kelihatan
 * seperti tidak ikut bekerja membuat daftar kosong dengan alasan yang tidak
 * ketahuan.
 *
 * Kotak cari sengaja TIDAK ikut di sini: isinya belum selesai diketik,
 * jadi mengirim setiap ketikan akan memuat ulang halaman berkali-kali.
 * Ia tetap punya tombol "Terapkan".
 */
function initSaringLangsung() {
    document.querySelectorAll("[data-konten-saring-pilih]").forEach((pilih) => {
        pilih.addEventListener("change", () => pilih.form?.requestSubmit());
    });
}

initReveal();
initScrollProgress();
initNavSpy();
initAksesQuiz();
initCari();
initSaringLangsung();
initBookmark();
initQuizMuat();
initMuatLebih();
initKonfirmasi();
initUiuxNilai();
