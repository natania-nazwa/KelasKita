// Halaman "Buat Quiz": tambah / hapus baris soal, dan tampilkan kolom
// kode akses hanya saat quiz privatization dipilih.
// Berhenti sendiri kalau halamannya tidak ada.
const formQuiz = document.querySelector("[data-tambah-quiz]");

if (formQuiz) {
    const daftar = formQuiz.querySelector("[data-soal-daftar]");
    const tombolTambah = formQuiz.querySelector("[data-soal-tambah]");
    const penghitung = formQuiz.querySelector("[data-soal-jumlah]");

    const huruf = ["a", "b", "c", "d"];
    const tingkat = ["Mudah", "Sedang", "Sulit"];

    /*
     * Nomor urut pada field name (soal[0], soal[1], dst.) harus rapat
     * tanpa bolong: kalau baris tengah dihapus, server akan menghitung
     * ulang urutannya sendiri (lihat App\Support\SimpanSoalQuiz::ganti),
     * tapi field name yang bolong membuat sebagian browserUTE membingungkan.
     * Karena itu semua name ditulis ulang setiap kali baris berubah.
     */
    const nomorkanUlang = () => {
        const baris = Array.from(daftar.querySelectorAll("[data-soal-baris]"));

        baris.forEach((el, index) => {
            el.querySelectorAll("[name]").forEach((input) => {
                input.name = input.name.replace(
                    /^soal\[\d+\]/,
                    `soal[${index}]`,
                );
            });

            const label = el.querySelector("legend");

            if (label) {
                label.textContent = `Soal ${index + 1}`;
            }
        });

        if (penghitung) {
            penghitung.textContent = String(baris.length);
        }

        // Baris terakhir tidak boleh dihapus: quiz minimal satu soal.
        baris.forEach((el, index) => {
            const hapus = el.querySelector("[data-soal-hapus]");

            if (hapus) {
                hapus.disabled = index === baris.length - 1;
            }
        });
    };

    /*
     * Baris baru dibuat dari baris terakhir yang ada, bukan dari template
     * terpisah: dengan begitu perubahan tampilan pada markup ikut
     * terbawa dan tidak ada duplikasi sumber.
     */
    const tambahBaris = () => {
        const baris = Array.from(daftar.querySelectorAll("[data-soal-baris]"));
        const asal = baris[baris.length - 1];

        if (!asal) {
            return;
        }

        const klon = asal.cloneNode(true);

        klon.querySelectorAll("input, textarea, select").forEach((input) => {
            if (input.tagName === "SELECT") {
                input.selectedIndex = 0;
            } else {
                input.value = "";
            }

            // Atribut required ikut diklon, jadi baris baru tidak dianggap kosong
            // oleh validasi browser sebelum sempat diisi.
            input.removeAttribute("required");
            input.dataset.baru = "true";
        });

        daftar.appendChild(klon);
        nomorkanUlang();

        klon.querySelector("textarea")?.focus();
    };

    const hapusBaris = (target) => {
        const baris = Array.from(daftar.querySelectorAll("[data-soal-baris]"));

        // Sisakan minimal satu baris.
        if (baris.length <= 1) {
            return;
        }

        target.remove();
        nomorkanUlang();
    };

    if (tombolTambah) {
        tombolTambah.addEventListener("click", tambahBaris);
    }

    // Delegasi supaya tombol hapus pada baris yang baru dibuat juga jalan.
    daftar.addEventListener("click", (event) => {
        const tombol = event.target.closest("[data-soal-hapus]");

        if (tombol && !tombol.disabled) {
            hapusBaris(tombol.closest("[data-soal-baris]"));
        }
    });

    nomorkanUlang();

    // Kolom kode akses: hanya relevan untuk quiz privat.
    const wadahKode = formQuiz.querySelector("[data-kode-akses]");
    const inputKode = wadahKode?.querySelector("[name='kode_akses']");

    if (wadahKode && inputKode) {
        const terapkanKode = () => {
            const privat =
                formQuiz.querySelector("[name='visibilitas']:checked")
                    ?.value === "private";

            wadahKode.hidden = !privat;
            //required ikut dimatikan supaya browser tidak protes
            // tentang kolom yang sengaja disembunyikan.
            inputKode.required = privat;
        };

        formQuiz
            .querySelectorAll("[name='visibilitas']")
            .forEach((radio) => radio.addEventListener("change", terapkanKode));

        terapkanKode();
    }
}
