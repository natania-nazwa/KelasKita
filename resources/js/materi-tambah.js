/**
 * Halaman "Tambah Materi".
 *
 * Menangani:
 *   - daftar bab (tambah, pilih, rename, duplikat, urutkan, hapus)
 *   - editor rich text sederhana berbasis contenteditable
 *   - preview yang selalu mengikuti bab aktif
 *   - unggah thumbnail + character counter
 *   - menyusun field "isi" (teks polos) sebelum form dikirim
 *
 * Modul ini berhenti langsung kalau halamannya tidak dibuka, jadi
 * aman diimpor dari resources/js/app.js.
 */
const AKAR = document.querySelector("[data-tambah-materi]");

if (AKAR) {
    initTambahMateri(AKAR);
}

function initTambahMateri(akar) {
    const $ = (pilihan, induk = akar) => induk.querySelector(pilihan);
    const $$ = (pilihan, induk = akar) => Array.from(induk.querySelectorAll(pilihan));

    const form = $("#form-tambah-materi");
    const editor = $("[data-editor]");
    const judulBab = $("[data-editor-judul]");
    const badgeBab = $("[data-editor-bab]");
    const huruf = $("[data-editor-huruf]");
    const daftarBab = $("[data-bab-list]");
    const jumlahBab = $("[data-bab-jumlah]");
    const labelTambah = $("[data-bab-tambah-label]");
    const tombolTambah = $("[data-bab-tambah-utama]");
    const inputIsi = $("[data-input-isi]");
    const inputBab = $("[data-input-bab]");

    const judulMateri = $("[data-judul-materi]");
    const judulCount = $("[data-judul-count]");
    const deskripsi = $("[data-deskripsi]");
    const deskripsiCount = $("[data-deskripsi-count]");
    const kategori = $("[data-kategori]");
    const tips = $("[data-tips]");
    const tipsCount = $("[data-tips-count]");

    const thumbnailInput = $("[data-thumbnail-input]");
    const thumbnailDrop = $("[data-thumbnail-drop]");
    const thumbnailPreview = $("[data-thumbnail-preview]");
    const thumbnailBingkai = $("[data-thumbnail-bingkai]");
    const thumbnailImg = $("[data-thumbnail-img]");
    const thumbnailHapus = $("[data-thumbnail-hapus]");
    const thumbnailError = $("[data-thumbnail-error]");
    const thumbnailFlagHapus = $("[data-thumbnail-hapus-flag]");
    const thumbnailKini = $("[data-thumbnail-kini]");
    const thumbnailZoom = $("[data-thumbnail-zoom]");
    const thumbnailZoomNilai = $("[data-thumbnail-zoom-nilai]");
    const thumbnailPerbesar = $("[data-thumbnail-perbesar]");
    const thumbnailPerkecil = $("[data-thumbnail-perkecil]");
    const thumbnailReset = $("[data-thumbnail-reset]");

    const gambarInput = $("[data-gambar-input]");

    // Tab pada kartu Daftar Bab: "Daftar Bab" dan "Preview".
    const tabBab = $("[data-tab-bab]");
    const tabPreview = $("[data-tab-preview]");
    const panelBab = $("[data-panel-bab]");
    const panelPreview = $("[data-panel-preview]");
    const panelIsian = $("[data-isian-panel]");

    /*
     * Kartu "Isi Materi" mengikuti isi daftar bab.
     *
     * Satu aturan saja: kartu tampil kalau daftar babnya tidak kosong. Jadi
     * form yang baru dibuka â€” yang daftar babnya masih kosong â€” belum
     * menampilkan editor, dan baru menampilkannya setelah admin menekan
     * "+ Tambah Bab". Sebaliknya, materi yang sudah punya bab (mode edit, atau
     * kiriman yang gagal validasi lalu diulang) langsung menampilkan editornya
     * karena isian yang lalu ada yang perlu disunting.
     *
     * Editor disembunyikan lewat JavaScript, bukan atribut hidden di HTML:
     * tanpa JavaScript kartu tetap tampil, jadi isian materi masih bisa diisi
     * dan form masih bisa dikirim lewat tombolnya.
     *
     * Yang menyembunyikannya bukan hanya tab Preview, jadi syarat tab ikut
     * dibaca di sini.
     */
    let tabAktif = "bab";

    /**
     * Kartu "Isi Materi" hanya tampil kalau daftar babnya sudah terisi dan tab
     * yang terbuka bukan Preview.
     */
    function perbaruiKartuIsian() {
        panelIsian?.classList.toggle(
            "hidden",
            tabAktif === "preview" || bab.length === 0,
        );
    }

    /*
     * Elemen pratinjau.
     *
     * Kepala pratinjau dirender sekali oleh Blade (x-materi.detail-kepala)
     * dan hanya perlu ditimpa isinya: judul, deskripsi, kategori, tingkat
     * kesulitan, dan thumbnail. Semuanya milik form dan masih hidup di
     * browser, jadi tidak pernah dikirim ke server.
     *
     * Badan pratinjau â€” Daftar Isi, kartu seksi, blok kode, navigasi antar
     * bab â€” tidak dirender di sini. App\Support\IsiMateri dan
     * App\Support\SorotKode bekerja di PHP, jadi badannya diambil dari
     * endpoint pratinjau dan ditukar ke wadah di bawah setiap kali isian
     * berubah. See Admin\KontenMateriController::pratinjau.
     */
    const p = {
        indeks: $("[data-preview-indeks]"),
        bar: $("[data-preview-bar]"),

        judul: $("[data-pratinjau-judul]"),
        deskripsi: $("[data-pratinjau-deskripsi]"),
        kategori: $("[data-pratinjau-kategori]"),
        kategoriIkon: $("[data-pratinjau-kategori-ikon]"),
        kesulitan: $("[data-pratinjau-kesulitan]"),
        thumbnail: $("[data-pratinjau-thumbnail]"),
        thumbIkon: $("[data-pratinjau-thumb-ikon]"),
        waktu: $("[data-pratinjau-waktu]"),
    };

    /*
     * Wadah badan pratinjau, dan endpoint yang mengisinya.
     *
     * URL-nya dibaca dari komponen x-materi.preview (dipasang sebagai
     * data-pratinjau-url) dan bukan dari elemen akar, karena modul ini dipakai
     * oleh form admin dan form pemilik yang pratinjunya dilayani route
     * berbeda. Halaman yang tidak punya endpoint (mis. pratinjau dimatikan)
     * hanya akan menampilkan kepala pratinjau saja — bukan error.
     */
    const wadahPratinjau = $("[data-preview-isi-wadah]");
    const urlPratinjau = $("[data-pratinjau-url]")?.dataset.pratinjauUrl ?? "";

    const tokenCsrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

    /* Warna lencana tingkat kesulitan, sama dengan yang dipakai
       components/materi/detail-kepala.blade.php. */
    const WARNA_KESULITAN = {
        mudah: "bg-[#dcfce7] text-[#15803d]",
        sedang: "bg-[#fef3c7] text-[#b45309]",
        sulit: "bg-[#fee2e2] text-[#b91c1c]",
    };

    const kesulitan = $("[data-kesulitan]");
    const estimasiWaktu = $("#estimasi_waktu");

    const saklar = $("[data-publikasikan]");
    const wadahCatatan = $("[data-catatan-wadah]");
    const isianCatatan = $("[data-catatan-isian]");
    const dialog = $("[data-dialog-hapus]");
    const dialogPesan = $("[data-dialog-pesan]");
    const dialogBatal = $("[data-dialog-batal]");
    const dialogHapus = $("[data-dialog-hapus-tombol]");

    /** @type {{id: string, title: string, content: string}[]} */
    let bab = [];
    let aktifId = null;
    let urlThumbnail = "";
    let menungguHapus = null;
    let idSeret = null;
    let tundaPratinjau = null;
    let rentangKursor = null;
    let batalPratinjau = null;

    /**
     * Potongan thumbnail: zoom 100%â€“300% plus posisi geser yang
     * disimpan relatif (nx, ny antara -1 dan 1) supaya tetap cocok
     * walau bingkai preview berbeda ukuran dengan bingkai form.
     */
    let potong = { zoom: 1, nx: 0, ny: 0 };

    /** Batas ukuran berkas thumbnail: 100MB. */
    const BATAS_BERKAS = 100 * 1024 * 1024;

    const uid = () => "bab-" + Math.random().toString(36).slice(2, 9);

    const indeksAktif = () => bab.findIndex((item) => item.id === aktifId);
    const aktif = () => bab[indeksAktif()] ?? bab[0];

    const jepit = (nilai, min, max) => Math.min(max, Math.max(min, nilai));

    /* ============================================================
       TAB KARTU: DAFTAR BAB / PREVIEW
       ============================================================ */

    function pilihTab(nama) {
        const diPreview = nama === "preview";

        tabAktif = nama;

        tabBab.classList.toggle("is-aktif", !diPreview);
        tabPreview.classList.toggle("is-aktif", diPreview);
        tabBab.setAttribute("aria-selected", String(!diPreview));
        tabPreview.setAttribute("aria-selected", String(diPreview));

        panelBab.classList.toggle("hidden", diPreview);
        panelPreview.classList.toggle("hidden", !diPreview);

        // Tombol tambah bab hanya relevan pada tab daftar bab.
        tombolTambah.classList.toggle("hidden", diPreview);

        /*
         * Kartu "Isi Materi" disembunyikan selama pratinjau yang terbuka.
         * Editor itu yang paling panjang di halaman, dan isinya persis
         * apa yang sudah sedang dibaca di pratinjau, jadi menampilkan dua
         *-duanya cuma membuat halaman jauh lebih panjang tanpa menambah
         * informasi. Yang disembunyikan hanya tampilannya: isian editor tetap
         * ada dan tetap ikut terkirim saat form disimpan.
         *
         * Aturan yang sama juga menjaga kartu ini tetap tersembunyi di form
         * yang isiannya belum siap (lihat perbaruiKartuIsian di atas).
         */
        perbaruiKartuIsian();

        // Bingkai preview tadinya display:none, jadi posisi potongan
        // thumbnail dihitung ulang begitu tabnya terbuka.
        terapkanPotongan();

        /*
         * Badannya baru diambil dari server saat tab ini dibuka, dan diambil
         * langsung — bukan lewat jadwalPratinjau() — karena admin sedang
         * menunggu halaman pratinjau muncul, bukan sedang mengetik sesuatu.
         *
         * isian editor juga belum tentu sudah masuk ke daftar bab: admin
         * bisa saja mengetik lalu langsung menekan tab Preview tanpa
         * memindahkan kursor, jadi isian editor yang aktif disimpan dulu.
         */
        if (diPreview) {
            simpanAktif();
            muatPratinjau();
        }
    }

    tabBab.addEventListener("click", () => pilihTab("bab"));
    tabPreview.addEventListener("click", () => pilihTab("preview"));

    /* ============================================================
       BAB
       ============================================================ */

    function muatAwal() {
        let pulih = null;

        if (inputBab.value.trim()) {
            try {
                const parsed = JSON.parse(inputBab.value);
                if (Array.isArray(parsed) && parsed.length) {
                    pulih = parsed
                        .filter((item) => item && typeof item === "object")
                        .map((item) => ({
                            id: typeof item.id === "string" && item.id ? item.id : uid(),
                            title: typeof item.title === "string" && item.title.trim() ? item.title : "Bab Baru",
                            content: typeof item.content === "string" ? item.content : "",
                        }));
                }
            } catch {
                pulih = null;
            }
        }

        /*
         * Bab tidak pernah dibuat diam-diam. Form yang belum punya bab apa pun
         * dibiarkan kosong, supaya jelas apa yang harus dilakukan admin lebih
         * dulu: menekan "+ Tambah Bab". Bab pertama yang dibuat itu bernama
         * "Pendahuluan" supaya sama dengan bab bawaan yang dulu muncul otomatis.
         */
        bab = pulih && pulih.length ? pulih : [];
        aktifId = bab[0]?.id ?? null;

        perbaruiKartuIsian();
    }

    function simpanAktif() {
        const sekarang = aktif();
        if (!sekarang) return;

        sekarang.content = editor.innerHTML;

        const judul = judulBab.value.trim();
        if (judul) {
            sekarang.title = judul;
        }
    }

    function muatEditor() {
        const sekarang = aktif();

        /*
         * Daftar bab bisa kosong â€” itulah keadaan awal form baru, dan juga
         * hasil yang mungkin dari menghapus bab terakhir. Editor dikosongkan
         * supaya isian bab yang sudah dihapus tidak ikut tersimpan lagi, dan
         * kartu "Isi Materi" yang sedang disembunyikan tidak menyimpan isian
         * basi untuk form berikutnya.
         */
        if (!sekarang) {
            editor.innerHTML = "";
            judulBab.value = "";
            badgeBab.textContent = "Bab 0";

            hitungKarakter();
            perbaruiStatusAlat();

            return;
        }

        editor.innerHTML = sekarang.content || "";
        judulBab.value = sekarang.title;
        badgeBab.textContent = `Bab ${indeksAktif() + 1}`;

        hitungKarakter();
        perbaruiStatusAlat();
    }

    function pilih(id) {
        if (!bab.some((item) => item.id === id) || id === aktifId) return;

        simpanAktif();
        aktifId = id;
        muatEditor();
        renderBab();
        muatPratinjau();
    }

    function tambahBab() {
        simpanAktif();

        const baru = {
            id: uid(),
            title: bab.length === 0 ? "Pendahuluan" : "Bab Baru",
            content: "",
        };

        bab.splice(indeksAktif() + 1, 0, baru);
        aktifId = baru.id;

        muatEditor();
        renderBab();
        muatPratinjau();
        perbaruiKartuIsian();

        judulBab.focus();
        judulBab.select();
    }

    function duplikatBab(id) {
        const asal = bab.findIndex((item) => item.id === id);
        if (asal < 0) return;

        simpanAktif();

        const salinan = {
            id: uid(),
            title: `${bab[asal].title} (Salinan)`,
            content: bab[asal].content,
        };

        bab.splice(asal + 1, 0, salinan);
        aktifId = salinan.id;

        muatEditor();
        renderBab();
        muatPratinjau();
        perbaruiKartuIsian();
    }

    function geserBab(id, selisih) {
        const dari = bab.findIndex((item) => item.id === id);
        const ke = dari + selisih;
        if (dari < 0 || ke < 0 || ke >= bab.length) return;

        const [pindah] = bab.splice(dari, 1);
        bab.splice(ke, 0, pindah);

        renderBab();
        muatPratinjau();
    }

    function bukaDialogHapus(id) {
        const index = bab.findIndex((item) => item.id === id);
        if (index < 0 || bab.length <= 1) return;

        // Halaman tanpa dialog hapus (lihat blok listener di bawah) langsung
        // menghapus, bukan mematikan tombolnya.
        if (!dialog) {
            hapusBabTerpilih(id);
            return;
        }

        menungguHapus = id;
        dialogPesan.textContent = `Bab ${index + 1} "${bab[index].title}" akan dihapus dari materi ini.`;
        dialog.classList.add("is-buka");
        dialogHapus.focus();
    }

    function tutupDialogHapus() {
        menungguHapus = null;
        dialog?.classList.remove("is-buka");
    }

    function hapusBabTerpilih(id = menungguHapus) {
        const index = bab.findIndex((item) => item.id === id);
        if (index < 0) {
            tutupDialogHapus();
            return;
        }

        const [dihapus] = bab.splice(index, 1);
        tutupDialogHapus();

        if (aktifId === dihapus.id) {
            // Daftar bab boleh jadi kosong setelah penghapusan terakhir, jadi
            // bab berikutnya dicari tahu lebih dulu sebelum dipakainya.
            const baru = bab[Math.min(index, bab.length - 1)];
            aktifId = baru?.id ?? null;
            muatEditor();
        }

        renderBab();
        muatPratinjau();
        perbaruiKartuIsian();
    }

    function renderBab() {
        /*
         * Daftar bab yang kosong mengisi kartu dengan satu kalimat yang
         * mengarah ke tombol "+ Tambah Bab", karena di keadaan itu editor pun
         * belum muncul. Tanpa kalimat itu, kartu terlihat seperti gagal dimuat:
         * ada judul "Daftar Bab" tapi isinya nol.
         */
        const kosong = bab.length === 0
            ? `
                <p class="w-full rounded-xl border border-dashed border-lavender px-4 py-6 text-center text-sm leading-relaxed text-muted">
                    Belum ada bab. Tekan <span class="font-bold text-ungu">+ Tambah Bab</span> untuk memulai menulis isi materi.
                </p>`
            : "";

        daftarBab.innerHTML =
            kosong +
            bab
                .map((item, index) => {
                    const terpilih = item.id === aktifId;
                    const bisaNaik = index > 0;
                    const bisaTurun = index < bab.length - 1;

                    return `
                        <div class="bab-chip${terpilih ? " is-aktif" : ""}" draggable="true" data-id="${esc(item.id)}">
                            <button type="button" class="bab-chip__pilih" data-pilih role="tab"
                                aria-selected="${terpilih ? "true" : "false"}" title="${esc(item.title)}">
                                <span class="bab-chip__nomor">${index + 1}</span>
                                <span class="bab-chip__judul">${esc(item.title)}</span>
                            </button>

                            <button type="button" class="bab-chip__menu-tombol" data-menu aria-expanded="false"
                                aria-label="Opsi bab ${index + 1}">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 7.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z" />
                                </svg>
                            </button>

                            <div class="bab-menu" data-menu-panel>
                                <button type="button" class="bab-menu__aksi" data-aksi="nama">Ganti nama</button>
                                <button type="button" class="bab-menu__aksi" data-aksi="duplikat">Duplikat</button>
                                <button type="button" class="bab-menu__aksi" data-aksi="naik"${bisaNaik ? "" : " disabled"}>Naik</button>
                                <button type="button" class="bab-menu__aksi" data-aksi="turun"${bisaTurun ? "" : " disabled"}>Turun</button>
                                <button type="button" class="bab-menu__aksi bab-menu__aksi--hapus" data-aksi="hapus"${bab.length <= 1 ? " disabled" : ""}>Hapus</button>
                            </div>
                        </div>`;
                })
                .join("");

        jumlahBab.textContent = bab.length === 0 ? "Belum ada bab" : `${bab.length} Bab`;

        // Nomor bab aktif ikut berubah setelah bab dipindah/dihapus.
        badgeBab.textContent = `Bab ${indeksAktif() + 1}`;

        if (labelTambah) {
            // Tanpa "+" di depan: ikon plus di dalam tombol sudah menandainya,
            // jadi "+ Tambah Bab" di teksnya hanya membuat "+ +Tambah Bab".
            labelTambah.textContent = bab.length > 1 ? "Tambah Bab Berikutnya" : "Tambah Bab";
        }
    }

    function tutupMenu() {
        $$(".bab-menu.is-buka", daftarBab).forEach((menu) => menu.classList.remove("is-buka"));
        $$("[data-menu][aria-expanded='true']", daftarBab).forEach((tombol) =>
            tombol.setAttribute("aria-expanded", "false"),
        );
    }

    function jalankanAksi(aksi, id) {
        tutupMenu();

        if (aksi === "nama") {
            pilih(id);
            judulBab.focus();
            judulBab.select();
            return;
        }

        if (aksi === "duplikat") {
            duplikatBab(id);
            return;
        }

        if (aksi === "naik") {
            geserBab(id, -1);
            return;
        }

        if (aksi === "turun") {
            geserBab(id, 1);
            return;
        }

        if (aksi === "hapus") {
            bukaDialogHapus(id);
        }
    }

    daftarBab.addEventListener("click", (event) => {
        const aksi = event.target.closest("[data-aksi]");
        if (aksi && !aksi.disabled) {
            jalankanAksi(aksi.dataset.aksi, aksi.closest(".bab-chip")?.dataset.id);
            return;
        }

        const menu = event.target.closest("[data-menu]");
        if (menu) {
            const panel = menu.closest(".bab-chip")?.querySelector("[data-menu-panel]");
            const terbuka = panel?.classList.contains("is-buka");
            tutupMenu();
            if (panel && !terbuka) {
                panel.classList.add("is-buka");
                menu.setAttribute("aria-expanded", "true");
            }
            return;
        }

        const chip = event.target.closest(".bab-chip");
        if (chip) {
            pilih(chip.dataset.id);
        }
    });

    daftarBab.addEventListener("dragstart", (event) => {
        const chip = event.target.closest(".bab-chip");
        if (!chip) return;

        idSeret = chip.dataset.id;
        chip.classList.add("is-seret");
        event.dataTransfer.effectAllowed = "move";

        try {
            event.dataTransfer.setData("text/plain", idSeret);
        } catch {
            // Beberapa browser menolak setData tertentu; drag tetap jalan.
        }
    });

    daftarBab.addEventListener("dragend", () => {
        $$(".is-seret", daftarBab).forEach((chip) => chip.classList.remove("is-seret"));
        idSeret = null;
    });

    daftarBab.addEventListener("dragover", (event) => {
        if (!idSeret) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
    });

    daftarBab.addEventListener("drop", (event) => {
        const chip = event.target.closest(".bab-chip");
        if (!chip || !idSeret || chip.dataset.id === idSeret) return;

        event.preventDefault();

        const dari = bab.findIndex((item) => item.id === idSeret);
        if (dari < 0) return;

        const [pindah] = bab.splice(dari, 1);
        const tujuan = bab.findIndex((item) => item.id === chip.dataset.id);
        const kotak = chip.getBoundingClientRect();
        const setelah = event.clientX > kotak.left + kotak.width / 2;

        bab.splice(tujuan + (setelah ? 1 : 0), 0, pindah);

        idSeret = null;
        renderBab();
        muatPratinjau();
    });

    tombolTambah.addEventListener("click", tambahBab);

    /* ============================================================
       EDITOR
       ============================================================ */

    function esc(teks) {
        return String(teks)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function pulihkanKursor() {
        editor.focus();

        if (rentangKursor) {
            const seleksi = document.getSelection();
            seleksi.removeAllRanges();
            seleksi.addRange(rentangKursor);
        }
    }

    function perintah(cmd, nilai) {
        pulihkanKursor();
        document.execCommand(cmd, false, nilai);
        simpanAktif();
        hitungKarakter();
        perbaruiStatusAlat();
        jadwalPratinjau();
    }

    function perbaruiStatusAlat() {
        $$(".alat-tombol[data-cmd]", akar).forEach((tombol) => {
            const cmd = tombol.dataset.cmd;
            if (!["bold", "italic", "underline"].includes(cmd)) return;

            let aktifSekarang = false;
            try {
                aktifSekarang = document.queryCommandState(cmd);
            } catch {
                aktifSekarang = false;
            }
            tombol.classList.toggle("is-aktif", aktifSekarang);
        });

        const pemilihFormat = $("[data-cmd-format]");
        if (pemilihFormat) {
            let blok = "";
            try {
                blok = (document.queryCommandValue("formatBlock") || "").toLowerCase();
            } catch {
                blok = "";
            }
            pemilihFormat.value = blok === "h2" || blok === "h3" ? blok : "p";
        }
    }

    function sisipkanTautan() {
        const url = window.prompt("Masukkan alamat tautan (https://...)");
        if (url === null) return;

        perintah(url.trim() ? "createLink" : "unlink", url.trim() || undefined);
    }

    $$(".alat-tombol[data-cmd]", akar).forEach((tombol) => {
        // mousedown dicegah supaya selection di editor tidak hilang.
        tombol.addEventListener("mousedown", (event) => event.preventDefault());
        tombol.addEventListener("click", () => {
            const cmd = tombol.dataset.cmd;
            if (cmd === "quote") {
                perintah("formatBlock", "<blockquote>");
                return;
            }
            if (cmd === "link") {
                sisipkanTautan();
                return;
            }
            perintah(cmd);
        });
    });

    const pemilihFormat = $("[data-cmd-format]");
    if (pemilihFormat) {
        pemilihFormat.addEventListener("change", () => {
            perintah("formatBlock", `<${pemilihFormat.value}>`);
        });
    }

    document.addEventListener("selectionchange", () => {
        const seleksi = document.getSelection();
        if (!seleksi || !seleksi.rangeCount) return;

        if (editor.contains(seleksi.anchorNode)) {
            rentangKursor = seleksi.getRangeAt(0).cloneRange();
        }
    });

    editor.addEventListener("keyup", perbaruiStatusAlat);
    editor.addEventListener("mouseup", perbaruiStatusAlat);
    editor.addEventListener("focus", perbaruiStatusAlat);

    editor.addEventListener("input", () => {
        simpanAktif();
        hitungKarakter();
        jadwalPratinjau();
    });

    judulBab.addEventListener("input", () => {
        const sekarang = aktif();
        if (!sekarang) return;

        sekarang.title = judulBab.value.trim() || "Bab Baru";
        renderBab();
        jadwalPratinjau();
    });

    judulBab.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            editor.focus();
        }
    });

    function hitungKarakter() {
        const jumlah = (editor.textContent || "").replace(/\s+/g, " ").trim().length;
        huruf.textContent = `${jumlah} karakter`;
    }

    /* --- Gambar --- */

    $$("[data-tambah-gambar]", akar).forEach((tombol) => {
        tombol.addEventListener("click", () => {
            // Simpan posisi kursor sebelum dialog file membuka dirinya.
            const seleksi = document.getSelection();
            if (seleksi && seleksi.rangeCount && editor.contains(seleksi.anchorNode)) {
                rentangKursor = seleksi.getRangeAt(0).cloneRange();
            }
            gambarInput.click();
        });
    });

    gambarInput.addEventListener("change", () => {
        const berkas = Array.from(gambarInput.files || []).slice(0, 5);

        berkas.forEach((berkasTunggal) => {
            if (!berkasTunggal.type.startsWith("image/")) return;

            const pembaca = new FileReader();
            pembaca.onload = () => {
                pulihkanKursor();
                document.execCommand("insertImage", false, pembaca.result);
                simpanAktif();
                hitungKarakter();
                jadwalPratinjau();
            };
            pembaca.readAsDataURL(berkasTunggal);
        });

        gambarInput.value = "";
    });

/* ============================================================
       PRATINJAU
       ============================================================
       Kepala pratinjau dirender sekali oleh Blade dan hanya perlu ditimpa
       isinya. Badannya — Daftar Isi, kartu seksi, blok kode, navigasi antar
       seksi — datang dari server, karena App\Support\IsiMateri dan
       App\Support\SorotKode bekerja di PHP dan aturannya tidak bisa
       ditulis ulang di sini tanpa membuat versi kedua yang pasti
       menyimpang. Lihat Admin\KontenMateriController::pratinjau. */

    function jadwalPratinjau() {
        window.clearTimeout(tundaPratinjau);

        /*
         * 450ms, bukan jeda pendek seperti penandaan di editor. Yang di
         * sini berakhir sebagai permintaan ke server, jadi jeda pendek
         * hanya berarti satu kalimat yang diketik akan dibaca beberapa kali.
         */
        tundaPratinjau = window.setTimeout(muatPratinjau, 450);
    }

    /*
     * Deskripsi ikut ditulis ke kepala pratinjau, persis seperti di halaman
     * detail: di bawah judul, dan disembunyikan kalau isiannya kosong.
     * Elemennya sendiri selalu ada di markup, jadi yang ditulis JavaScript
     * hanya teksnya dan kelihatan/tidaknya.
     */
    function terangkanDeskripsi() {
        const teks = deskripsi.value.trim();

        p.deskripsi.textContent = teks;
        p.deskripsi.classList.toggle("hidden", !teks);
    }

    /*
     * Warna lencana dan ikon kategori diambil dari <option> kategori di
     * form, bukan dari daftar hardcode di JavaScript. Jadi katalog di
     * App\Models\Pelajaran tetap satu-satunya sumber warna kategori.
     */
    function terangkanKategori() {
        const terpilih = kategori.selectedOptions[0];
        const nama = (terpilih?.textContent || "Pilih kategori").trim();
        const ikon = terpilih?.dataset.ikon || "";

        p.kategori.textContent = nama;
        p.kategoriIkon.textContent = ikon;

        if (ikon) {
            p.kategoriIkon.classList.remove("hidden");
        } else {
            p.kategoriIkon.classList.add("hidden");
        }

        // Tanpa thumbnail, kartu kepala memakai ikon kategori sebagai gantinya.
        p.thumbIkon.textContent = ikon;
    }

    function terangkanKesulitan() {
        const nilai = kesulitan.value;

        p.kesulitan.textContent = nilai;
        p.kesulitan.className =
            "lencana capitalize " +
            (WARNA_KESULITAN[String(nilai).toLowerCase()] || "bg-lavender text-dark/60");
    }

    /*
     * Halaman detail menulis waktu baca sebagai "10 menit baca", sementara
     * isian form bebasnya teks ("10 menit", "1 jam"). Yang diambil
     * angkanya supaya kalimat pratinjau sama dengan halaman detail.
     */
    function terangkanWaktuBaca() {
        const angka = estimasiWaktu.value.match(/\d+/);

        p.waktu.textContent = angka ? `${Number(angka[0])} menit baca` : "Belum diisi";
    }

    /*
     * Kepala pratinjau selalu dikerjakan lebih dulu dan selalu lokal.
     * Kategori, tingkat kesulitan, dan thumbnail belum pernah menyentuh
     * server dan tidak akan pernah dikirim ke sana, jadi bagian ini tidak
     * boleh ikut menunggu permintaan yang bisa saja gagal.
     */
    function renderKepalaPratinjau() {
        p.judul.textContent = judulMateri.value.trim() || "Judul materi belum diisi";
        terangkanDeskripsi();
        terangkanKategori();
        terangkanKesulitan();
        terangkanWaktuBaca();
    }

    async function muatPratinjau() {
        renderKepalaPratinjau();

        // Tanpa bab tidak ada yang untuk dipratinjau, dan tanpa tab Preview
        // tidak ada yang melihatnya — jadi tidak ada yang perlu diunduh.
        if (!wadahPratinjau || !urlPratinjau || bab.length === 0 || tabAktif !== "preview") {
            return;
        }

        // Permintaan yang sedang jalan sudah tidak benar lagi.
        batalPratinjau?.abort();

        const pembatal = new AbortController();
        batalPratinjau = pembatal;

        const kirim = new URLSearchParams();

        kirim.set("nama", judulMateri.value.trim());
        kirim.set("isi", susunIsi());
        kirim.set("tingkat_kesulitan", kesulitan.value);
        kirim.set("pelajaran_id", kategori.value);

        try {
            const respons = await fetch(urlPratinjau, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                    "X-CSRF-TOKEN": tokenCsrf(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: kirim.toString(),
                signal: pembatal.signal,
            });

            if (!respons.ok) return;

            const html = await respons.text();

            // Balasan yang telat sudah dibatalkan di atas; pemeriksaannya
            // diulang supaya isi yang lebih lama tidak pernah menimpa isi
            // yang lebih baru.
            if (batalPratinjau !== pembatal) return;

            wadahPratinjau.innerHTML = html;
            pasangPemilihSeksi();
        } catch {
            /*
             * Dibatalkan karena isian berubah lagi, atau jaringan gagal.
             * Isi pratinjau yang terakhir dibiarkan apa adanya: lebih baik
             * pratinjau yang sedikit tertinggal daripada yang kosong tanpa
             * penjelasan.
             */
        }
    }

    /*
     * Pemilih seksi untuk pratinjau.
     *
     * Bentuknya sama persis dengan halaman detail: [data-bab] untuk tiap
     * seksi, [data-daftar-isi-tautan] untuk baris Daftar Isi, dan
     * [data-bab-nav] untuk tombol Sebelumnya/Berikutnya. Atributnya sama
     * karena markup-nya memang dari view yang sama; yang berbeda hanya
     * tempat mencari elemen dan tidak adanya penulisan ke URL — hash URL
     * halaman form bukan tempat menyimpan apa pun.
     */
    function pasangPemilihSeksi() {
        const seksi = $$("[data-bab]", wadahPratinjau);
        const tautan = $$("[data-daftar-isi-tautan]", wadahPratinjau);

        if (seksi.length === 0) {
            p.indeks.textContent = "0 / 0";
            p.bar.style.width = "0%";

            return;
        }

        const nav = $("[data-bab-nav]", wadahPratinjau);
        const tombolSebelum = $("[data-bab-sebelum]", wadahPratinjau);
        const tombolSikut = $("[data-bab-sikut]", wadahPratinjau);
        const judulSebelum = $("[data-bab-sebelum-judul]", wadahPratinjau);
        const judulSikut = $("[data-bab-sikut-judul]", wadahPratinjau);

        /*
         * Label tombol diambil dari baris Daftar Isi, bukan dari judul di
         * dalam kartu seksi: judul kartu latihan berbentuk kalimat
         * pertanyaan dan akan terlalu panjang untuk sebuah tombol.
         */
        const labelSeksi = (slug) => {
            const baris = tautan.find((item) => item.dataset.daftarIsiTautan === slug);

            if (!baris) return "";

            const bagian = baris.querySelectorAll("span");

            return [bagian[0]?.textContent.trim(), bagian[1]?.textContent.trim()]
                .filter(Boolean)
                .join(". ");
        };

        const terapkan = (slug) => {
            const posisi = seksi.findIndex((item) => item.dataset.bab === slug);

            if (posisi < 0) return;

            seksi.forEach((item, index) => {
                item.hidden = index !== posisi;
            });

            tautan.forEach((item) => {
                item.classList.toggle("is-aktif", item.dataset.daftarIsiTautan === slug);
            });

            nav?.classList.add("is-aktif");

            const sebelum = seksi[posisi - 1];
            const sikut = seksi[posisi + 1];

            if (tombolSebelum) {
                tombolSebelum.hidden = !sebelum;

                if (sebelum && judulSebelum) {
                    judulSebelum.textContent = labelSeksi(sebelum.dataset.bab);
                }
            }

            if (tombolSikut) {
                tombolSikut.hidden = !sikut;

                if (sikut && judulSikut) {
                    judulSikut.textContent = labelSeksi(sikut.dataset.bab);
                }
            }

            const total = seksi.length;

            p.indeks.textContent = `${posisi + 1} / ${total}`;
            p.bar.style.width = `${Math.round(((posisi + 1) / total) * 100)}%`;
        };

        tautan.forEach((item) => {
            item.addEventListener("click", (peristiwa) => {
                peristiwa.preventDefault();
                terapkan(item.dataset.daftarIsiTautan);
            });
        });

        tombolSebelum?.addEventListener("click", () => {
            const posisi = seksi.findIndex((item) => !item.hidden);

            if (posisi > 0) terapkan(seksi[posisi - 1].dataset.bab);
        });

        tombolSikut?.addEventListener("click", () => {
            const posisi = seksi.findIndex((item) => !item.hidden);

            if (posisi >= 0 && posisi < seksi.length - 1) terapkan(seksi[posisi + 1].dataset.bab);
        });

        /*
         * Seksi yang ditampilkan mengikuti bab yang sedang dipilih di daftar
         * bab di atas. Tanpa ini, memilih bab lain hanya mengubah editor
         * dan pratinjau tetap menampilkan seksi yang sama.
         *
         * Pasangan bab-seksi tidak selalu satu-satu: penanda "Bab 2: ..."
         * dibaca BabMateri, sedangkan penanda seksi "# Judul" dibaca
         * IsiMateri, dan keduanya bisa tidak ada. Karena itu yang dipakai
         * adalah posisi, dijepit supaya tidak keluar dari daftar — kalau
         * materinya belum punya penanda seksi sama sekali, seluruh isinya
         * memang jadi satu seksi dan itu juga yang tampil setelah disimpan.
         */
        const posisi = Math.min(Math.max(indeksAktif(), 0), seksi.length - 1);

        terapkan(seksi[posisi].dataset.bab);
    }

    /* ============================================================
       FORM: counter, unggah, submit
    ============================================================ */

    function hitungJudul() {
        judulCount.textContent = `${judulMateri.value.length}/100`;
    }

    function hitungDeskripsi() {
        deskripsiCount.textContent = `${deskripsi.value.length}/220`;
    }

    function hitungTips() {
        tipsCount.textContent = `${tips.value.length}/500`;
    }

    /*
     * Empat isian ini ikut dibaca pratinjau, jadi perubahannya memicu
     * render ulang: judul, deskripsi, kategori (nama, ikon, dan warna
     * lencana), dan tingkat kesulitan.
     */
    judulMateri.addEventListener("input", () => {
        hitungJudul();
        jadwalPratinjau();
    });

    deskripsi.addEventListener("input", () => {
        hitungDeskripsi();
        jadwalPratinjau();
    });

    kategori.addEventListener("change", jadwalPratinjau);
    kesulitan.addEventListener("change", jadwalPratinjau);
    estimasiWaktu.addEventListener("input", jadwalPratinjau);
    tips.addEventListener("input", hitungTips);

    /* --- Thumbnail: unggah, zoom, geser --- */

    /**
     * Batas geser dalam piksel pada suatu bingkai: seberapa jauh gambar
     * boleh digeser sampai tepinya menyentuh tepi bingkai.
     */
    function batasGeser(bingkai, gambar) {
        const kotak = bingkai.getBoundingClientRect();
        const lebarAsli = gambar.naturalWidth;
        const tinggiAsli = gambar.naturalHeight;

        if (!kotak.width || !kotak.height || !lebarAsli || !tinggiAsli) {
            return { x: 0, y: 0 };
        }

        // object-fit: cover â†’ skala dasar yang mengisi seluruh bingkai.
        const dasar = Math.max(kotak.width / lebarAsli, kotak.height / tinggiAsli);
        const lebar = lebarAsli * dasar * potong.zoom;
        const tinggi = tinggiAsli * dasar * potong.zoom;

        return {
            x: Math.max(0, (lebar - kotak.width) / 2),
            y: Math.max(0, (tinggi - kotak.height) / 2),
        };
    }

    /**
     * Terapkan zoom + posisi ke gambar di bingkai crop form.
     *
     * Kepala pratinjau memakai kelas .thumb-materi yang sudah menangani
     * sendiri skalanya, jadi tidak ikut dihitung di sini: gambar di sana
     * cuma object-fit, tanpa zoom dan geser.
     */
    function terapkanPotongan() {
        if (!urlThumbnail) return;

        const batas = batasGeser(thumbnailBingkai, thumbnailImg);
        thumbnailImg.style.transform =
            `translate(${batas.x * potong.nx}px, ${batas.y * potong.ny}px) scale(${potong.zoom})`;
    }

    function aturZoom(nilai) {
        potong.zoom = jepit(nilai, 1, 3);

        const persen = Math.round(potong.zoom * 100);
        thumbnailZoom.value = String(persen);
        thumbnailZoomNilai.textContent = `${persen}%`;

        terapkanPotongan();
    }

    function resetPotongan() {
        potong = { zoom: 1, nx: 0, ny: 0 };
        thumbnailZoom.value = "100";
        thumbnailZoomNilai.textContent = "100%";
    }

    function bersihkanThumbnail() {
        resetPotongan();
        urlThumbnail = "";
        thumbnailInput.value = "";
        thumbnailImg.removeAttribute("src");
        thumbnailImg.style.transform = "";
        thumbnailPreview.classList.add("hidden");
        thumbnailDrop.classList.remove("hidden");
        thumbnailError.classList.add("hidden");

        /*
         * Gambar yang sekarang tersimpan ikut diminta dibuang lewat server.
         * Tanpa bendera ini tombol Hapus cuma membersihkan pratinjau browser:
         * setelah halaman dimuat ulang gambarnya muncul lagi.
         */
        if (thumbnailFlagHapus) {
            thumbnailFlagHapus.value = "1";
        }

        if (thumbnailKini) {
            thumbnailKini.classList.add("hidden");
        }

        // Kepala pratinjau memakai elemen yang sama dengan halaman detail.
        if (p.thumbnail.getAttribute("src")) {
            p.thumbnail.classList.add("hidden");
            p.thumbIkon.classList.remove("hidden");
            p.thumbnail.removeAttribute("src");
            p.thumbnail.style.transform = "";
        }
    }

    thumbnailInput.addEventListener("change", () => {
        const berkas = thumbnailInput.files && thumbnailInput.files[0];
        if (!berkas) return;

        const gabarkan = (pesan) => {
            thumbnailError.textContent = pesan;
            thumbnailError.classList.remove("hidden");
            thumbnailInput.value = "";
        };

        if (berkas.size > BATAS_BERKAS) {
            gabarkan("Ukuran thumbnail maksimal 100MB.");
            return;
        }

        if (!["image/jpeg", "image/png", "image/webp"].includes(berkas.type)) {
            gabarkan("Format thumbnail harus JPG, PNG, atau WEBP.");
            return;
        }

        thumbnailError.classList.add("hidden");

        /*
         * Berkas baru menggantikan yang lama, jadi permintaan hapus dari
         * langkah sebelumnya dibatalkan dan thumbnail tersimpan kembali
         * disebutkan sebagai berkas yang akan diganti.
         */
        if (thumbnailFlagHapus) {
            thumbnailFlagHapus.value = "0";
        }

        if (thumbnailKini) {
            thumbnailKini.classList.remove("hidden");
        }

        const pembaca = new FileReader();
        pembaca.onload = () => {
            urlThumbnail = pembaca.result;
            thumbnailImg.src = pembaca.result;
            thumbnailPreview.classList.remove("hidden");
            thumbnailDrop.classList.add("hidden");

            p.thumbnail.src = pembaca.result;
            p.thumbnail.classList.remove("hidden");
            p.thumbIkon.classList.add("hidden");

            resetPotongan();

            // Ukuran asli baru diketahui setelah gambar selesai dibaca.
            thumbnailImg.addEventListener("load", terapkanPotongan, { once: true });
            terapkanPotongan();
        };
        pembaca.readAsDataURL(berkas);
    });

    thumbnailHapus.addEventListener("click", bersihkanThumbnail);

    /* --- Geser gambar dengan pointer --- */

    let seretThumbnail = null;

    thumbnailBingkai.addEventListener("pointerdown", (event) => {
        if (!urlThumbnail || event.target.closest("[data-thumbnail-hapus]")) return;

        const batas = batasGeser(thumbnailBingkai, thumbnailImg);

        seretThumbnail = {
            x: event.clientX,
            y: event.clientY,
            nx: potong.nx,
            ny: potong.ny,
            mx: batas.x || 1,
            my: batas.y || 1,
        };

        thumbnailBingkai.classList.add("is-seret");

        try {
            thumbnailBingkai.setPointerCapture(event.pointerId);
        } catch {
            // Beberapa browser tidak mendukung pointer capture.
        }
    });

    thumbnailBingkai.addEventListener("pointermove", (event) => {
        if (!seretThumbnail) return;

        potong.nx = jepit(
            seretThumbnail.nx + (event.clientX - seretThumbnail.x) / seretThumbnail.mx,
            -1,
            1,
        );
        potong.ny = jepit(
            seretThumbnail.ny + (event.clientY - seretThumbnail.y) / seretThumbnail.my,
            -1,
            1,
        );

        terapkanPotongan();
    });

    const selesaiSeret = (event) => {
        if (!seretThumbnail) return;

        seretThumbnail = null;
        thumbnailBingkai.classList.remove("is-seret");

        try {
            thumbnailBingkai.releasePointerCapture(event.pointerId);
        } catch {
            // Aman diabaikan kalau capture-nya sudah lepas sendiri.
        }
    };

    thumbnailBingkai.addEventListener("pointerup", selesaiSeret);
    thumbnailBingkai.addEventListener("pointercancel", selesaiSeret);

    /* --- Kontrol zoom --- */

    thumbnailZoom.addEventListener("input", () => aturZoom(Number(thumbnailZoom.value) / 100));
    thumbnailPerbesar.addEventListener("click", () => aturZoom(potong.zoom + 0.05));
    thumbnailPerkecil.addEventListener("click", () => aturZoom(potong.zoom - 0.05));
    thumbnailReset.addEventListener("click", () => {
        resetPotongan();
        terapkanPotongan();
    });

    window.addEventListener("resize", terapkanPotongan);

    /* --- Saklar publikasi ---
     * Saklar hanya ada di halaman tambah. Di form edit(status tidak berubah
     * dari sekadar menyimpan) tidak ada saklar, dan di situ tombol "Ajukan
     * Persetujuan" berdiri sendiri, jadi bagian ini dilewati saja. */

    if (saklar) {
        saklar.addEventListener("click", () => {
            const nilai = saklar.getAttribute("aria-checked") !== "true";
            saklar.setAttribute("aria-checked", String(nilai));
        });
    }

    $$('[name="publikasikan"]', form).forEach((tombol) => {
        tombol.addEventListener("click", (event) => {
            saklar?.setAttribute("aria-checked", tombol.value === "1" ? "true" : "false");

            /*
             * Materi yang ditolak wajib menyertai catatan pendukung. Isiannya
             * disembunyikan sampai tombol ajukan ditekan, jadi klik pertama
             * hanya membukanya dan memindahkan kursor ke sana. Pengajuan baru
             * benar-benar dikirim pada klik berikutnya.
             */
            if (tombol.value !== "1" || !wadahCatatan?.hasAttribute("hidden")) return;

            event.preventDefault();
            wadahCatatan.removeAttribute("hidden");

            if (isianCatatan) {
                isianCatatan.required = true;
                isianCatatan.focus();
            }
        });
    });

    /* --- Dialog hapus --- */

    /*
     * Dialognya ikut ditangani di sini, tapi seluruh blok ini dilewati kalau
     * halamannya tidak memilikinya.
     *
     * Alasannya, semua elemen di modul ini dicari di dalam akar form
     * (data-tambah-materi), sementara dialog hapus diletakkan di luar akar itu
     * di sebagian halaman. Tanpa penjagaan, satu baris ini sudah cukup untuk
     * menghentikan seluruh modul â€” termasuk hal-hal yang tidak ada hubungannya
     * dengan dialog, seperti daftar bab, editor, dan baris tombol simpan.
     */
    if (dialog && dialogBatal && dialogHapus) {
        dialogBatal.addEventListener("click", tutupDialogHapus);
        dialogHapus.addEventListener("click", hapusBabTerpilih);
        dialog.addEventListener("click", (event) => {
            if (event.target === dialog) tutupDialogHapus();
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") return;
        tutupMenu();
        tutupDialogHapus();
    });

    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) return;
        if (!event.target.closest(".bab-chip")) tutupMenu();
    });

    /* --- Submit: field "isi" disusun dari semua bab --- */

    function htmlKeTeks(html) {
        const kotak = document.createElement("div");
        kotak.innerHTML = html || "";

        kotak.querySelectorAll("br").forEach((elemen) => elemen.replaceWith("\n"));
        kotak
            .querySelectorAll("p,div,h1,h2,h3,h4,h5,blockquote,li,tr,figcaption")
            .forEach((elemen) => elemen.append("\n"));

        return (kotak.textContent || "")
            .replace(/\u00a0/g, " ")
            .replace(/[ \t]+\n/g, "\n")
            .replace(/\n{3,}/g, "\n\n")
            .trim();
    }

    function susunIsi() {
        const perBab = bab.map((item, index) => {
            const teks = htmlKeTeks(item.content);
            // Materi satu bab tidak perlu diberi kepala "Bab N", tapi
            // begitu ada banyak bab, judulnya ikut ditulis supaya isi
            // tetap terbaca rapi di halaman detail (yang menampilkan
            // isi sebagai teks biasa, bukan HTML).
            return bab.length > 1 ? `Bab ${index + 1}: ${item.title}\n\n${teks}` : teks;
        });

        return perBab.filter(Boolean).join("\n\n").trim();
    }

    form.addEventListener("submit", () => {
        simpanAktif();
        inputIsi.value = susunIsi();
        inputBab.value = JSON.stringify(
            bab.map((item, index) => ({
                id: item.id,
                title: item.title,
                content: item.content,
                order: index,
            })),
        );
    });

    /* ============================================================
       MULAI
       ============================================================ */

    muatAwal();
    muatEditor();
    renderBab();
    muatPratinjau();
    hitungJudul();
    hitungDeskripsi();
    hitungTips();
}


