// Halaman detail materi: salin kode, daftar isi yang bisa dilipat, dan
// penanda seksi aktif yang mengikuti scroll.
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
 * Daftar Isi: menjadi panel yang bisa dilipat di mobile, dan menandai
 * seksi yang sedang dibaca.
 *
 * Penanda aktif memakai posisi scroll, bukan IntersectionObserver, karena
 * yang dicari adalah "seksi mana yang sudah lewat garis batas atas viewport".
 * Cara ini tidak bergantung tinggi tiap seksi, jadi tetap benar walau ada
 * seksi yang lebih pendek daripada layar.
 */
function initDaftarIsi() {
    const daftar = document.querySelector("[data-daftar-isi]");

    if (!daftar) {
        return;
    }

    const wadah = daftar.querySelector("[data-daftar-isi-items]");
    const alih = daftar.querySelector("[data-daftar-isi-alih]");
    const tautan = Array.prototype.slice.call(daftar.querySelectorAll("[data-daftar-isi-tautan]"));
    const layarLebar = window.matchMedia("(min-width: 1024px)");

    const seksi = tautan
        .map((item) => document.getElementById(item.dataset.daftarIsiTautan))
        .filter(Boolean);

    if (!wadah || !seksi.length) {
        return;
    }

    const tersembunyi = () => wadah.hidden;

    // Di desktop daftar selalu tampil, jadi status lipatan diabaikan.
    const tutup = (lipat) => {
        const tertutup = lipat && !layarLebar.matches;

        // Pakai atribut hidden, bukan kelas hidden: di elemen ini juga ada
        // kelas utilitas flex, jadi penampilannya diatur dari app.css.
        wadah.hidden = tertutup;

        if (alih) {
            alih.setAttribute("aria-expanded", String(!terutup));
            alih.querySelector("[data-daftar-isi-alih-ikon]")?.classList.toggle("rotate-180", tertutup);
        }
    };

    let aktif = "";

    const geserKeTengah = (target) => {
        /*
         * Di mobile daftar isi bisa lebih lebar dari layar, jadi item
         * aktif digeser agar tetap terlihat. scrollLeft diubah langsung
         * (bukan scrollIntoView) supaya halaman tidak ikut ter-scroll ke
         * atas dan ke bawah.
         */
        if (layarLebar.matches) {
            return;
        }

        const geser = target.offsetLeft - (wadah.clientWidth - target.clientWidth) / 2;
        wadah.scrollLeft = Math.max(0, geser);
    };

    const sorot = (id) => {
        if (id === aktif) {
            return;
        }

        aktif = id;

        tautan.forEach((item) => {
            item.classList.toggle(KELAS_AKTIF, item.dataset.daftarIsiTautan === id);
        });

        const target = tautan.find((item) => item.dataset.daftarIsiTautan === id);

        if (target) {
            geserKeTengah(target);
        }
    };

    const sedangDibaca = () => {
        // Garis batas di 30% tinggi viewport: seksi yang sudah lewat garis
        // ini dianggap selesai dibaca, jadi item aktifnya bergeser ke atas.
        const batas = window.innerHeight * 0.3;
        let id = seksi[0].id;

        seksi.forEach((bagian) => {
            if (bagian.getBoundingClientRect().top <= batas) {
                id = bagian.id;
            }
        });

        return id;
    };

    let menunggu = false;

    const perbarui = () => {
        if (menunggu) {
            return;
        }

        menunggu = true;
        window.requestAnimationFrame(() => {
            sorot(sedangDibaca());
            menunggu = false;
        });
    };

    tautan.forEach((item) => {
        item.addEventListener("click", () => {
            // Lipat daftarnya supaya konten yang dituju langsung terlihat.
            tutup(true);
        });
    });

    alih?.addEventListener("click", () => tutup(!tersembunyi()));
    layarLebar.addEventListener("change", () => tutup(false));

    tutup(false);
    perbarui();

    window.addEventListener("scroll", perbarui, { passive: true });
    window.addEventListener("resize", perbarui, { passive: true });
}

initSalinKode();
initDaftarIsi();
