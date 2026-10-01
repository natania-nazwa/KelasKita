/**
 * Konfirmasi sebelum menerbitkan konten, sesuai aturan "Konfirmasi sebelum
 * Publish" di halaman Pengaturan admin.
 *
 * Yang dikerjakan di sini satu hal: tombol publish tidak langsung mengirim
 * form. Dia membuka dialog lebih dulu, lalu form baru dikirim kalau admin
 * menyetujuinya.
 *
 * Berhenti sendiri kalau halamannya tidak punya dialog atau tidak punya satu
 * pun tombol publish, supaya modul ini boleh diimpor tanpa syarat di mana saja.
 *
 * Dua sumber tombol, keduanya satu pasangan atribut:
 *   data-konten-publish="publish"   tombol pemicu
 *   data-konten-nama="..."          nama konten, ditulis di dialog
 *   data-konten-aksi="..."          URL tujuan, dipakai kalau pemicunya bukan
 *                                    bagian dari form (aksi di daftar konten)
 *   data-konten-metode="POST"       method untuk form yang dibuat di sini
 *
 * Dua sumber aturan, keduanya satu atribut:
 *   <body data-konten-konfirmasi="0">   di layout admin, nilainya dibaca dari
 *                                       kolom konfirmasi_publikasi di
 *                                       tb_preferensi.
 *
 * Kalimat dialog boleh ditimpa per pemicu lewat tiga atribut opsional, yang
 * dipakai baris daftar untuk membedakan "Publish" dari "Batalkan
 * Publikasi":
 *   data-konten-terbit-judul    judul dialog
 *   data-konten-terbit-pesan    kalimat penjelasan
 *   data-konten-terbit-tombol   label tombol konfirmasi
 *
 * Tanpa JavaScript tombolnya tetap mengirim form apa adanya, hanya tanpa
 * dialog. Itu urutan yang benar: lebih baik konten terbit tanpa tanya daripada
 * tidak bisa terbit sama sekali.
 */

/**
 * Dialog yang dipakai: satu untuk seluruh halaman, dari komponen
 * <x-admin.dialog-terbitkan>.
 */
function initKonfirmasiTerbit() {
    const dialog = document.querySelector("[data-konten-publish-dialog]");
    const pemicu = Array.prototype.slice.call(
        document.querySelectorAll("[data-konten-publish]"),
    );

    if (!dialog || !pemicu.length) {
        return;
    }

    const judul = dialog.querySelector("[data-konten-terbit-judul]");
    const pesan = dialog.querySelector("[data-konten-terbit-pesan]");
    const labelTombol = dialog.querySelector("[data-konten-terbit-tombol]");
    const nama = dialog.querySelector("[data-konten-nama]");
    const kartu = dialog.querySelector(".ad-dialog__kartu");
    const tombolBatal = dialog.querySelectorAll("[data-konten-publish-batal]");
    const tombolKonfirmasi = dialog.querySelector(
        "[data-konten-publish-konfirmasi]",
    );

    if (!kartu) {
        return;
    }

    /*
     * Aturan dari Pengaturan. Nilai "0" berarti aturan dimatikan, jadi tombol
     * harus langsung mengirim. Atribut yang tidak ada dianggap "perlu
     * konfirmasi": lebih baik bertanya daripada terbit diam-diam.
     */
    const perluKonfirmasi = () =>
        document.body.dataset.kontenKonfirmasi !== "0";

    /*
     * Form untuk tombol yang berdiri sendiri, yaitu aksi publish di daftar
     * konten yang bukan bagian dari form mana pun. Formnya disembunyikan dan
     * ditaruh di dalam kartu dialog supaya ikut hilang bersama dialog, dan
     * action-nya selalu diisi ulang setiap kali dialog dibuka sehingga tidak
     * pernah ada form yang punya action kosong dan tidak bisa terkirim.
     */
    const form = document.createElement("form");
    form.method = "POST";
    form.hidden = true;

    const csrf = document.createElement("input");
    csrf.type = "hidden";
    csrf.name = "_token";
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content ?? "";

    form.appendChild(csrf);

    /*
     * Field "aksi" perlu supaya server membaca intent yang sama seperti
     * tombol "Publish Sekarang" di form. Field "_method" hanya dipakai kalau
     * URL-nya membutuhkan PUT atau DELETE.
     */
    const aksi = document.createElement("input");
    aksi.type = "hidden";
    aksi.name = "aksi";
    aksi.value = "publish";

    const method = document.createElement("input");
    method.type = "hidden";
    method.name = "_method";

    form.appendChild(aksi);
    form.appendChild(method);

    kartu.appendChild(form);

    let pemicuSekarang = null;

    const tutup = () => {
        dialog.classList.remove("is-buka");
        dialog.setAttribute("aria-hidden", "true");

        pemicuSekarang?.focus();
        pemicuSekarang = null;
    };

    pemicu.forEach((tombol) => {
        /*
         * Listener dipasang di fase tangkap supaya preventDefault() masih
         * berlaku walaupun ada listener lain yang menempel di tombol yang sama
         * lebih dulu.
         */
        tombol.addEventListener(
            "click",
            (event) => {
                if (!perluKonfirmasi()) {
                    return;
                }

                event.preventDefault();

                pemicuSekarang = tombol;

                /*
                 * Judul, pesan, dan label tombol diambil dari pemicu kalau
                 * ada, dan jatuh ke kalimat bawaan kalau tidak.
                 *
                 * Ini yang membedakan dua arah aksi ini: memindahkan konten ke
                 * published dan menariknya kembali memakai endpoint yang sama,
                 * jadi tanpa kalimat dari pemicunya, admin yang hanya ingin
                 * membatalkan akan melihat dialog bertuliskan "Publish
                 * konten?" dan menekan "Publish Sekarang".
                 */
                if (judul) {
                    judul.textContent =
                        tombol.dataset.kontenTerbitJudul ||
                        "Publish konten?";
                }

                if (pesan) {
                    pesan.textContent =
                        tombol.dataset.kontenTerbitPesan ||
                        "Konten ini akan langsung tersedia untuk pengguna dan notifikasi akan dikirim.";
                }

                if (labelTombol) {
                    labelTombol.textContent =
                        tombol.dataset.kontenTerbitTombol ||
                        "Publish Sekarang";
                }

                if (nama) {
                    nama.textContent = tombol.dataset.kontenNama || "";
                }

                if (!tombol.form) {
                    form.setAttribute("action", tombol.dataset.kontenAksi || "");
                    method.value = tombol.dataset.kontenMetode || "POST";
                }

                dialog.classList.add("is-buka");
                dialog.setAttribute("aria-hidden", "false");

                (tombolBatal[0] || tombolKonfirmasi)?.focus();
            },
            true,
        );
    });

    /*
     * Konfirmasi: kirim form yang tadi ditahan.
     *
     * Tiga sumber tombol, tiga cara berbeda mengirim:
     *
     *   1. Tombol milik wizard (data-konten-kirim). Tombolnya type="button"
     *      dan berada di luar <form>, jadi yang bisa mengirim bukan form-nya
     *      melainkan fungsi yang dipasang wizard di
     *      window.kelasKitaKontenKirim. Fungsi itu juga memeriksa semua
     *      langkah form lebih dulu, jadi konfirmasi tidak melewati pemeriksaan
     *      yang biasa dilakukan wizard.
     *
     *   2. Tombol di dalam <form> (tombol "Publish Sekarang" pada form
     *      materi). Form itu yang dikirim, karena isian admin ada di sana
     *      dan field "aksi"-nya sudah diisi tombol yang ditekan.
     *
     *   3. Tombol berdiri sendiri di daftar konten. Form yang dibuat di atas
     *      yang dikirim, memakai URL dari data-konten-aksi.
     */
    tombolKonfirmasi?.addEventListener("click", () => {
        const tombolIni = pemicuSekarang;
        const formPemilik = tombolIni?.form;
        const lewatWizard = tombolIni?.hasAttribute("data-konten-kirim");
        const action = form.getAttribute("action");

        tutup();

        if (lewatWizard) {
            window.kelasKitaKontenKirim?.("publish");
        } else if (formPemilik) {
            formPemilik.requestSubmit();
        } else if (action) {
            form.submit();
        }
    });

    tombolBatal.forEach((tombol) => {
        tombol.addEventListener("click", (event) => {
            event.preventDefault();
            tutup();
        });
    });

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

initKonfirmasiTerbit();