// Halaman detail materi: salin kode, daftar isi yang bisa dilipat, dan
// pemilihan bab (satu bab tampil pada satu waktu, lengkap dengan tombol
// Sebelumnya/Berikutnya).
// Semua fungsi berhenti sendiri kalau elemennya tidak ada di halaman ini.
const KELAS_AKTIF = "is-aktif";

/**
 * Tombol "Copy" pada blok kode.
 *
 * Teks yang disalin diambil dari textContent <code>, bukan dari HTML <pre>,
 * jadi <span> pewarna sintaks tidak ikut tersalin dan yang masuk papan klip
 * persis kode yang ditulis pengajar.
 */
function initSalinKode() {
    const blok = document.querySelectorAll("[data-blok-kode]");

    if (!blok.length) {
        return;
    }

    const teksAwal = "Copy";
    const teksSalin = "Copied!";

    const tulis = (tombol, menyalin) => {
        const label = tombol.querySelector("[data-salin-teks]");

        if (label) {
            label.textContent = menyalin ? teksSalin : teksAwal;
        }

        tombol.dataset.tersalin = menyalin ? "true" : "false";
    };

    const salin = async (tombol) => {
        const sumber = tombol.closest("[data-blok-kode]")?.querySelector("[data-salin-sumber]");
        const kode = sumber?.textContent ?? "";

        if (!kode) {
            return;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(kode);
            } else {
                // Browser lama atau halaman non-HTTPS: pakai textarea
                // sembunyi lalu jalankan perintah salin bawaan.
                const sementara = document.createElement("textarea");

                sementara.value = kode;
                sementara.setAttribute("readonly", "");
                sementara.style.position = "fixed";
                sementara.style.opacity = "0";
                document.body.appendChild(sementara);
                sementara.select();
                document.execCommand("copy");
                sementara.remove();
            }
        } catch {
            // Izin papan klip ditolak: tombol kembali seperti semula
            // tanpa menampilkan pesan apa pun.
            tulis(tombol, false);

            return;
        }

        tulis(tombol, true);
        window.setTimeout(() => tulis(tombol, false), 1800);
    };

    blok.forEach((item) => {
        const tombol = item.querySelector("[data-salin-kode]");

        if (!tombol) {
            return;
        }

        tulis(tombol, false);
        tombol.addEventListener("click", () => salin(tombol));
    });
}

/**
 * Daftar Isi: menjadi panel yang bisa dilipat di mobile.
 *
 * Mengembalikan fungsi tutup() supaya pemilihan bab bisa melipat daftarnya
 * lebih dulu sebelum kontennya berganti. Di desktop daftar selalu tampil.
 */
function initDaftarIsi() {
    const daftar = document.querySelector("[data-daftar-isi]");

    if (!daftar) {
        return null;
    }

    const wadah = daftar.querySelector("[data-daftar-isi-items]");
    const alih = daftar.querySelector("[data-daftar-isi-alih]");
    const layarLebar = window.matchMedia("(min-width: 1024px)");

    if (!wadah) {
        return null;
    }

    const tersembunyi = () => wadah.hidden;

    const tutup = (lipat) => {
        const tertutup = lipat && !layarLebar.matches;

        // Pakai atribut hidden, bukan kelas hidden: di elemen ini juga ada
        // kelas utilitas flex, jadi penampilannya diatur dari app.css.
        wadah.hidden = tertutup;

        if (alih) {
            alih.setAttribute("aria-expanded", String(!tertutup));
            alih.querySelector("[data-daftar-isi-alih-ikon]")?.classList.toggle("rotate-180", tertutup);
        }
    };

    alih?.addEventListener("click", () => tutup(!tersembunyi()));
    layarLebar.addEventListener("change", () => tutup(false));

    tutup(false);

    return tutup;
}

/**
 * Pemilihan bab.
 *
 * Seluruh seksi sudah ikut dirender di HTML (supaya bisa dibaca mesin
 * pencari dan browser tanpa JS), hanya satu yang diberi tampilan pada satu
 * waktu. Sumber kebenarannya:
 *
 *   - [data-bab]            seksi materi, nilainya slug
 *   - [data-daftar-isi-tautan] baris Daftar Isi
 *   - [data-bab-nav]        tombol Sebelumnya/Berikutnya
 *
 * URL diperbarui lewat replaceState hanya saat pengguna berpindah bab,
 * supaya tautan per-bab bisa dibagikan tanpa menambah entri riwayat.
 */
function initPilihBab(tutupDaftar) {
    const wadah = document.querySelector("[data-bab-wadah]");

    if (!wadah) {
        return;
    }

    const seksi = Array.prototype.slice.call(wadah.querySelectorAll("[data-bab]"));
    const tautan = Array.prototype.slice.call(document.querySelectorAll("[data-daftar-isi-tautan]"));

    if (seksi.length < 2) {
        return;
    }

    const nav = wadah.querySelector("[data-bab-nav]");
    const tombolSebelum = nav?.querySelector("[data-bab-sebelum]");
    const tombolSikut = nav?.querySelector("[data-bab-sikut]");
    const judulSebelum = nav?.querySelector("[data-bab-sebelum-judul]");
    const judulSikut = nav?.querySelector("[data-bab-sikut-judul]");

    /*
     * Label tombol diambil dari baris Daftar Isi ("1", "Judul bab"),
     * bukan dari judul di dalam seksi: judul kartu latihan berbentuk
     * kalimat pertanyaan dan akan terlalu panjang untuk tombol.
     */
    const labelBab = (slug) => {
        const baris = tautan.find((item) => item.dataset.daftarIsiTautan === slug);

        if (baris) {
            const bagian = baris.querySelectorAll("span");
            const nomor = bagian[0]?.textContent.trim() ?? "";
            const judul = bagian[1]?.textContent.trim() ?? "";

            return [nomor, judul].filter(Boolean).join(". ");
        }

        return seksi.find((item) => item.dataset.bab === slug)?.querySelector("h2")?.textContent.trim() ?? "";
    };

    /*
     * scrollIntoView dipakai alih-alih scrollTo manual karena CSS sudah
     * memberi .materi-seksi scroll-margin-top yang menyesuaikan tinggi top
     * bar (lihat app.css), jadi jarak bebasnya ikut benar di ponsel dan
     * desktop tanpa perlu dihitung ulang di sini.
     */
    const geserKeBab = (seksiAktif) => {
        seksiAktif.scrollIntoView({ behavior: "smooth", block: "start" });
    };

    let aktif = "";

    const terapkan = (slug, perbaruiUrl) => {
        if (slug === aktif || !seksi.some((item) => item.dataset.bab === slug)) {
            return;
        }

        aktif = slug;

        let indeks = 0;

        seksi.forEach((item, posisi) => {
            const terpilih = item.dataset.bab === slug;

            if (terpilih) {
                indeks = posisi;
            }

            item.hidden = !terpilih;
            item.classList.toggle(KELAS_AKTIF, terpilih);
        });

        tautan.forEach((item) => {
            item.classList.toggle(KELAS_AKTIF, item.dataset.daftarIsiTautan === slug);
        });

        const sebelum = seksi[indeks - 1];
        const sikut = seksi[indeks + 1];

        if (nav) {
            nav.classList.add(KELAS_AKTIF);
        }

        if (tombolSebelum) {
            tombolSebelum.hidden = !sebelum;

            if (sebelum && judulSebelum) {
                judulSebelum.textContent = labelBab(sebelum.dataset.bab);
            }
        }

        if (tombolSikut) {
            tombolSikut.hidden = !sikut;

            if (sikut && judulSikut) {
                judulSikut.textContent = labelBab(sikut.dataset.bab);
            }
        }

        /*
         * URL hanya ditulis saat pengguna sendiri yang berpindah bab, bukan
         * saat halaman baru dibuka: URL bawaan harus tetap bersih supaya
         * tombol Back tidak perlu menekan sekali hanya untuk menghapus hash.
         */
        if (perbaruiUrl) {
            try {
                window.history.replaceState(null, "", "#" + slug);
            } catch {
                // Beberapa browser menolak replaceState di file:// atau
                // sandbox; bab tetap berganti, hanya URL tidak ikut berubah.
            }
        }
    };

    const buka = (slug, denganGeser) => {
        terapkan(slug, true);

        if (denganGeser) {
            const target = seksi.find((item) => item.dataset.bab === slug);

            if (target) {
                geserKeBab(target);
            }
        }
    };

    tautan.forEach((item) => {
        item.addEventListener("click", (peristiwa) => {
            const slug = item.dataset.daftarIsiTautan;

            if (!seksi.some((sekarang) => sekarang.dataset.bab === slug)) {
                return;
            }

            peristiwa.preventDefault();
            tutupDaftar?.(true);
            buka(slug, true);
        });
    });

    tombolSebelum?.addEventListener("click", () => {
        const indeks = seksi.findIndex((item) => item.dataset.bab === aktif);
        const target = seksi[indeks - 1];

        if (target) {
            buka(target.dataset.bab, true);
        }
    });

    tombolSikut?.addEventListener("click", () => {
        const indeks = seksi.findIndex((item) => item.dataset.bab === aktif);
        const target = seksi[indeks + 1];

        if (target) {
            buka(target.dataset.bab, true);
        }
    });

    window.addEventListener("hashchange", () => {
        buka(window.location.hash.slice(1), false);
    });

    const awal = window.location.hash.slice(1);
    const adaAwal = awal && seksi.some((item) => item.dataset.bab === awal);

    terapkan(adaAwal ? awal : seksi[0].dataset.bab, false);

    if (adaAwal) {
        const target = seksi.find((item) => item.dataset.bab === awal);

        if (target) {
            // Jangkar browser tidak bergerak karena bab tujuan masih
            // beratribut hidden waktu halaman diparse, jadi gesernya
            // dilakukan setelah babnya aktif.
            window.requestAnimationFrame(() => geserKeBab(target));
        }
    }
}

initSalinKode();
initPilihBab(initDaftarIsi());
