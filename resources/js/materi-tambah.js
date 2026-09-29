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
     * Elemen pratinjau. Semuanya memakai komponen halaman detail yang
     * sama (x-materi.detail-kepala dan kartu seksi), jadi yang ada di sini
     * hanya titik tempat isinya ditimpa: judul, badge, thumbnail, dan isi
     * bab.
     */
    const p = {
        indeks: $("[data-preview-indeks]"),
        bar: $("[data-preview-bar]"),
        posisi: $("[data-preview-posisi]"),
        prev: $("[data-preview-prev]"),
        next: $("[data-preview-next]"),

        judul: $("[data-pratinjau-judul]"),
        kategori: $("[data-pratinjau-kategori]"),
        kategoriIkon: $("[data-pratinjau-kategori-ikon]"),
        kesulitan: $("[data-pratinjau-kesulitan]"),
        thumbnail: $("[data-pratinjau-thumbnail]"),
        thumbIkon: $("[data-pratinjau-thumb-ikon]"),
        waktu: $("[data-pratinjau-waktu]"),

        bab: $("[data-preview-bab]"),
        isi: $("[data-preview-isi]"),
    };

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
    let tundaPreview = null;
    let rentangKursor = null;

    /**
     * Potongan thumbnail: zoom 100%–300% plus posisi geser yang
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
         */
        panelIsian?.classList.toggle("hidden", diPreview);

        // Bingkai preview tadinya display:none, jadi posisi potongan
        // thumbnail dihitung ulang begitu tabnya terbuka.
        terapkanPotongan();
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

        bab = pulih && pulih.length ? pulih : [{ id: uid(), title: "Pendahuluan", content: editor.innerHTML }];
        aktifId = bab[0].id;
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
        if (!sekarang) return;

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
        renderPratinjau();
    }

    function tambahBab() {
        simpanAktif();

        const baru = { id: uid(), title: "Bab Baru", content: "" };
        bab.splice(indeksAktif() + 1, 0, baru);
        aktifId = baru.id;

        muatEditor();
        renderBab();
        renderPratinjau();

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
        renderPratinjau();
    }

    function geserBab(id, selisih) {
        const dari = bab.findIndex((item) => item.id === id);
        const ke = dari + selisih;
        if (dari < 0 || ke < 0 || ke >= bab.length) return;

        const [pindah] = bab.splice(dari, 1);
        bab.splice(ke, 0, pindah);

        renderBab();
        renderPratinjau();
    }

    function bukaDialogHapus(id) {
        const index = bab.findIndex((item) => item.id === id);
        if (index < 0 || bab.length <= 1) return;

        menungguHapus = id;
        dialogPesan.textContent = `Bab ${index + 1} "${bab[index].title}" akan dihapus dari materi ini.`;
        dialog.classList.add("is-buka");
        dialogHapus.focus();
    }

    function tutupDialogHapus() {
        menungguHapus = null;
        dialog.classList.remove("is-buka");
    }

    function hapusBabTerpilih() {
        const index = bab.findIndex((item) => item.id === menungguHapus);
        if (index < 0) {
            tutupDialogHapus();
            return;
        }

        const [dihapus] = bab.splice(index, 1);
        tutupDialogHapus();

        if (aktifId === dihapus.id) {
            const baru = bab[Math.min(index, bab.length - 1)];
            aktifId = baru.id;
            muatEditor();
        }

        renderBab();
        renderPratinjau();
    }

    function renderBab() {
        daftarBab.innerHTML =
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
                .join("") +
            `
                <button type="button" class="bab-tambah-chip" data-bab-tambah-chip>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>

                    Tambah Bab
                </button>`;

        jumlahBab.textContent = `${bab.length} Bab`;

        // Nomor bab aktif ikut berubah setelah bab dipindah/dihapus.
        badgeBab.textContent = `Bab ${indeksAktif() + 1}`;

        if (labelTambah) {
            labelTambah.textContent = bab.length > 1 ? "+ Tambah Bab Berikutnya" : "+ Tambah Bab";
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

        if (event.target.closest("[data-bab-tambah-chip]")) {
            tambahBab();
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
        renderPratinjau();
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
        jadwalPreview();
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
        jadwalPreview();
    });

    judulBab.addEventListener("input", () => {
        const sekarang = aktif();
        if (!sekarang) return;

        sekarang.title = judulBab.value.trim() || "Bab Baru";
        renderBab();
        jadwalPreview();
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
                jadwalPreview();
            };
            pembaca.readAsDataURL(berkasTunggal);
        });

        gambarInput.value = "";
    });

    /* ============================================================
       PREVIEW
       ============================================================ */

    function jadwalPreview() {
        window.clearTimeout(tundaPreview);
        tundaPreview = window.setTimeout(renderPratinjau, 140);
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
        p.kesulitan.className = "lencana capitalize " + (WARNA_KESULITAN[String(nilai).toLowerCase()] || "bg-lavender text-dark/60");
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

    function renderPratinjau() {
        const index = indeksAktif();
        const total = bab.length;
        const sekarang = aktif();
        if (!sekarang || index < 0) return;

        p.judul.textContent = judulMateri.value.trim() || "Judul materi belum diisi";
        terangkanKategori();
        terangkanKesulitan();
        terangkanWaktuBaca();

        p.bab.textContent = `${index + 1}. ${sekarang.title}`;

        const isi = sekarang.content || "";
        if (p.isi.innerHTML !== isi) {
            p.isi.innerHTML = isi;
        }

        p.indeks.textContent = `${index + 1} / ${total}`;
        p.bar.style.width = `${Math.round(((index + 1) / total) * 100)}%`;
        p.posisi.textContent = `Bab ${index + 1} dari ${total}`;
        p.prev.disabled = index <= 0;
        p.next.disabled = index >= total - 1;
    }

    p.prev.addEventListener("click", () => {
        const index = indeksAktif();
        if (index > 0) pilih(bab[index - 1].id);
    });

    p.next.addEventListener("click", () => {
        const index = indeksAktif();
        if (index < bab.length - 1) pilih(bab[index + 1].id);
    });

    /* ============================================================
       FORM: counter, unggah, submit
    ============================================================ */

    function hitungJudul() {
        judulCount.textContent = `${judulMateri.value.length}/100`;
    }

    function hitungTips() {
        tipsCount.textContent = `${tips.value.length}/500`;
    }

    /*
     * Tiga isian ini ikut dibaca pratinjau, jadi perubahannya memicu
     * render ulang: judul, kategori (nama, ikon, dan warna lencana), dan
     * tingkat kesulitan.
     */
    judulMateri.addEventListener("input", () => {
        hitungJudul();
        jadwalPreview();
    });

    kategori.addEventListener("change", jadwalPreview);
    kesulitan.addEventListener("change", jadwalPreview);
    estimasiWaktu.addEventListener("input", jadwalPreview);
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

        // object-fit: cover → skala dasar yang mengisi seluruh bingkai.
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

    dialogBatal.addEventListener("click", tutupDialogHapus);
    dialogHapus.addEventListener("click", hapusBabTerpilih);
    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) tutupDialogHapus();
    });

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
       ACTION BAR: SELAMAT DI SCROLL KE BAWAH
       ============================================================
       Baris tombol disembunyikan selama form masih diisi, lalu muncul
       begitu halaman sudah di-scroll ke bawah. Yang diamati adalah pikuan
       setinggi 1px tepat di atas baris tombol, bukan baris tombolnya
       sendiri: baris itu menempel di dasar layar, jadi posisinya tidak
       pernah berubah dan tidak bisa dijadikan penanda.

       "Turun 15% dari bawah" dipakai sebagai batas: tanpa itu baris
       tombol muncul sebelum pengguna benar-benar sampai ke ujung form. */

    const picuActionBar = $("[data-action-bar-picu]");
    const actionBar = $("[data-action-bar]");

    function tampilkanActionBar(tampil) {
        actionBar.classList.toggle("is-terlihat", tampil);
    }

    if (picuActionBar && actionBar) {
        if ("IntersectionObserver" in window) {
            const pengamatActionBar = new IntersectionObserver(
                ([entri]) => tampilkanActionBar(entri.isIntersecting),
                { rootMargin: "0px 0px -15% 0px" },
            );

            pengamatActionBar.observe(picuActionBar);
        } else {
            // Peramban tanpa IntersectionObserver: lebih baik langsung
            // tampil daripada membiarkan form tanpa tombol simpan.
            tampilkanActionBar(true);
        }
    }

    /* ============================================================
       MULAI
       ============================================================ */

    muatAwal();
    muatEditor();
    renderBab();
    renderPratinjau();
    hitungJudul();
    hitungTips();
}
