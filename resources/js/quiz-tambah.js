/**
 * Wizard "Buat Quiz" (dan "Edit Quiz" yang memakai halaman sama).
 *
 * Menangani:
 *   - perpindahan tiga langkah: Informasi Dasar, Buat Soal, Pengaturan
 *   - pratinjau, ganti, dan hapus thumbnail
 *   - membuat kode quiz secara acak
 *   - saklar "Tampilkan Jawaban Setelah Selesai"
 *   - validasi tiap langkah sebelum boleh lanjut
 *   - baris aksi langkah 2: Kembali, Batal, Draft, Lanjut ke Pengaturan
 *   - tombol "Draft": mengirim form langsung dari langkah "Buat Soal"
 *   - menitipkan isian ke sessionStorage supaya muat ulang tidak hilang
 *
 * Pembuatan soalnya tidak ada di sini: langkah "Buat Soal" memakai builder
 * sendiri di resources/js/quiz-builder.js, yang juga menyediakan fungsi
 * validasinya. File ini hanya memanggil builder itu lewat
 * window.kelasKitaQuizBuilder.
 *
 * Prinsipnya: langkah lain tidak pernah di-unmount, cuma disembunyikan.
 * Karena itu isian langkah 1 dan 3 tetap utuh saat pengguna turun ke
 * langkah 2, dan begitu juga sebaliknya.
 *
 * Selain itu seluruh isian — langkah yang sedang terbuka, isian tiap
 * langkah, sampai daftar soal — dititipkan ke sessionStorage setiap kali
 * berubah, jadi memuat ulang halaman memulihkan keadaan persis seperti
 * sebelum refresh. Drafnya dibuang begitu form dikirim atau tombol
 * "Batal" ditekan.
 *
 * Modul berhenti sendiri kalau halamannya tidak dibuka, jadi aman
 * diimpor dari resources/js/app.js.
 */

const AKAR = document.querySelector("[data-wizard-quiz]");

if (AKAR) {
    initWizardQuiz(AKAR);
}

function initWizardQuiz(akar) {
    const $ = (pilih, induk = akar) => induk.querySelector(pilih);
    const $$ = (pilih, induk = akar) =>
        Array.from(induk.querySelectorAll(pilih));

    const form = $("[data-wizard-form]");
    const stepper = $("[data-wizard-stepper]");
    const panel = $$("[data-wizard-panel]");
    const subjudul = $("[data-wizard-subjudul]");
    const navBawah = $("[data-wizard-nav]");

    const tombolKembali = $("[data-wizard-kembali]");
    const tombolLanjut = $("[data-wizard-lanjut]");
    const tombolSimpan = $("[data-wizard-simpan]");
    const tombolDraft = $("[data-wizard-draft]");
    const teksLanjut = $("[data-wizard-lanjut-teks]");
    const teksSimpan = $("[data-wizard-simpan-teks]");

    /*
     * Tombol kembar untuk aksi yang sama.
     *
     * Baris aksi langkah 2 memuat "Kembali" dan "Lanjut ke Pengaturan";
     * pasangannya ada di baris navigasi bawah yang dipakai langkah 1 dan 3.
     * Yang di kartu itu memakai atribut "-alias" supaya querySelector di
     * atas tetap menunjuk tombol navigasi bawah — satu-satunya yang ada di
     * setiap langkah.
     */
    const tombolLanjutLain = $$("[data-wizard-lanjut-alias]");
    const tombolKembaliLain = $$("[data-wizard-kembali-alias]");

    /* ---------- Langkah 1 ---------- */
    const inputThumbnail = $("[data-wizard-thumbnail-input]");
    const areaThumbnail = $("[data-wizard-thumbnail-area]");
    const isiThumbnail = $("[data-wizard-thumbnail-isi]");
    const gambarThumbnail = $("[data-wizard-thumbnail-img]");
    const tombolPilihGambar = $("[data-wizard-thumbnail-pilih]");
    const tombolHapusGambar = $("[data-wizard-thumbnail-hapus]");
    const namaGambar = $("[data-wizard-thumbnail-nama]");
    const galatGambar = $("[data-wizard-thumbnail-galat]");
    const flagHapusGambar = $("[data-wizard-thumbnail-hapus-flag]");

    /* ---------- Langkah 2 ---------- */
    const galatSoal = $("[data-builder-galat='daftar']");
    const sumberAwal = $("[data-builder-awal]");

    /* ---------- Langkah 3 ---------- */
    const areaKode = $("[data-wizard-kode-area]");
    const inputKode = $("[data-wizard-kode]");
    const wajibKode = $("[data-wizard-kode-wajib]");
    const tombolKode = $("[data-wizard-kode-acak]");
    const saklarJawaban = $("[data-wizard-saklar-jawaban]");
    const inputTampilkanJawaban = form.querySelector(
        "[name='tampilkan_jawaban']",
    );
    const areaPersetujuan = $("[data-wizard-approval-area]");
    const pesanPersetujuan = $("[data-wizard-approval-pesan]");
    const saklarPersetujuan = $("[data-wizard-saklar-publikasikan]");
    const inputPublikasikan = form.querySelector("[name='publikasikan']");
    const wadahCatatan = $("[data-catatan-wadah]");
    const isianCatatan = $("[data-catatan-isian]");
    const ringkasanDaftar = $("[data-wizard-ringkasan-daftar]");

    /*
     * Field "aksi" milik form admin. Form milik pemilik tidak punya input
     * ini, jadi setiap penulisan ke sini otomatis tidak berlaku untuk form
     * itu.
     */
    const inputAksi = form.querySelector("[data-konten-aksi]");

    /*
     * Jumlah langkah wizard, dibaca dari atribut halaman.
     *
     * Form admin punya dua tahap (Informasi Dasar, Buat Soal) sementara form
     * pemilik punya tiga, dan keduanya memakai file ini: logikanya sama,
     * yang berbeda hanya berapa banyak panel yang boleh dibuka.
     */
    const TOTAL_LANGKAH = Number(akar.dataset.langkahTotal) || 3;

    /*
     * Subjudul kepala halaman per langkah.
     *
     * Panjang array boleh lebih pendek dari jumlah langkah: langkah yang
     * tidak punya kalimat sendiri dibiarkan kosong, bukan menampilkan
     * kalimat langkah sebelumnya.
     */
    const SUBJUDUL = [
        "Tentukan informasi dasar untuk kuis yang akan kamu buat.",
        "Susun daftar soal dan tentukan jawaban yang benar.",
        "Atur bagaimana quiz akan dipublikasikan dan dikerjakan siswa.",
    ];

    /** Batas ukuran thumbnail, mengikuti aturan di QuizIsianRequest. */
    const BATAS_GAMBAR = 5 * 1024 * 1024;

    const FORMAT_GAMBAR = ["image/jpeg", "image/png", "image/webp"];

    let langkah = 1;

    /*
     * Builder soal di langkah 2 dibuat modul terpisah, jadi di sini
     * dirujuk lewat objek global.
     *
     * Sengaja dibaca lewat fungsi, bukan disimpan sekali di konstanta:
     * resources/js/app.js mengimpor modul ini lebih dulu, jadi saat baris
     * ini dieksekusi builder belum tentu sudah memasang globalnya. Kalau
     * nilainya dipetakan sekali, langkah 2 akan menolak lanjut untuk
     * selamanya dengan pesan "Form soal belum siap dimuat" walaupun kartu
     * soalnya sudah tampil. Dibaca ulang tiap dipakai, keadaannya selalu
     * yang terbaru — dan kalau builder benar-benar gagal dimuat, pesan
     * yang bisa dibaca tetap muncul, bukan diam-diam menyimpan quiz
     * tanpa soal.
     */
    const builder = () => window.kelasKitaQuizBuilder;

    /* ============================================================
       UTILITAS
    ============================================================ */

    const esc = (teks) =>
        String(teks ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    const jepit = (nilai, min, maks) => Math.min(maks, Math.max(min, nilai));

    /** Teks atau nilai dari satu kolom form. */
    const nilai = (pilih, induk = form) => {
        const el = induk.querySelector(pilih);

        return el && "value" in el ? String(el.value ?? "").trim() : "";
    };

    /**
     * Tampilkan pesan galat di bawah sebuah kolom.
     *
     * Galat yang dihapus disembunyikan dengan atribut hidden, bukan
     * dikosongkan saja, supaya tidak menyisakan baris kosong di bawah
     * kolom yang tadinya bersih.
     */
    const tampilGalat = (sasaran, pesan) => {
        const el = typeof sasaran === "string" ? $(sasaran) : sasaran;

        if (!el) {
            return;
        }

        if (pesan) {
            el.textContent = pesan;
            el.removeAttribute("hidden");
        } else {
            el.textContent = "";
            el.setAttribute("hidden", "");
        }
    };

    const bersihkanGalat = (sasaran) => {
        const el = typeof sasaran === "string" ? $(sasaran) : sasaran;

        if (!el) {
            return;
        }

        el.classList.remove("kolom-form--salah");

        if (typeof sasaran === "string") {
            tampilGalat(`${sasaran}-galat`, "");
        }
    };

    const tandaiKolom = (sasaran, pesan, galatId) => {
        const el = typeof sasaran === "string" ? $(sasaran) : sasaran;

        if (el) {
            el.classList.add("kolom-form--salah");
        }

        tampilGalat(galatId, pesan);
    };

    /* ============================================================
       DRAF — TAHAN MUAT ULANG HALAMAN
    ============================================================ */

    /*
     * Kunci draf memuat URL halamannya juga, supaya membuka form edit quiz
     * lain tidak pernah mengangkat isian quiz sebelumnya.
     *
     * sessionStorage dipilih karena per tab: memuat ulang tab yang sama
     * memulihkan isian, sementara menutup tab membuangnya sendiri tanpa
     * perlu tombol hapus.
     */
    const KUNCI_DRAF = `kelas-kita-draf-wizard:${window.location.pathname}`;

    function bacaDraf() {
        try {
            const mentah = window.sessionStorage.getItem(KUNCI_DRAF);
            const draf = mentah ? JSON.parse(mentah) : null;

            return draf && typeof draf === "object" ? draf : null;
        } catch {
            // Storage diblokir atau isinya rusak: anggap tidak ada draf.
            return null;
        }
    }

    function hapusDraf() {
        try {
            window.sessionStorage.removeItem(KUNCI_DRAF);
        } catch {
            // Mode privat / storage diblokir: draf memang cuma pelengkap.
        }
    }

    /**
     * Isian tiap langkah dalam bentuk sederhana {nama: nilai}.
     *
     * Input tersembunyi milik builder soal (nama berawalan "soal") ikut
     * dilewati: isian soal disimpan terpisah dan ditulis ulang oleh
     * resources/js/quiz-builder.js, jadi dua salinan hanya bisa bertabrakan.
     *
     * @return {Record<string, string|boolean>}
     */
    function bacaIsian() {
        const isian = {};

        Array.from(form.elements).forEach((el) => {
            const nama = el.name;
            const tipe = el.type || "";

            if (
                !nama ||
                nama.startsWith("_") ||
                nama.startsWith("soal") ||
                tipe === "file" ||
                tipe === "submit" ||
                tipe === "button"
            ) {
                return;
            }

            if (tipe === "radio") {
                if (el.checked) {
                    isian[nama] = el.value;
                }
            } else if (tipe === "checkbox") {
                isian[nama] = el.checked;
            } else {
                isian[nama] = el.value;
            }
        });

        return isian;
    }

    /**
     * Tulis balik isian tersimpan ke form.
     *
     * @param {Record<string, string|boolean>} isian
     */
    function tulisIsian(isian) {
        Array.from(form.elements).forEach((el) => {
            const nama = el.name;
            const tipe = el.type || "";

            if (!nama || !(nama in isian)) {
                return;
            }

            if (tipe === "radio") {
                el.checked = isian[nama] === el.value;
            } else if (tipe === "checkbox") {
                el.checked = Boolean(isian[nama]);
            } else if (tipe !== "file") {
                el.value = String(isian[nama]);
            }
        });
    }

    /*
     * Saklar wizard berbentuk tombol, jadi tampilannya (aria-checked) harus
     * disamakan lagi dengan input tersembunyi yang ikut dikirim form.
     * Dilakukan setelah isian dipulihkan, karena nilai saklar bukan value
     * dari tombolnya sendiri.
     */
    function pulihkanSaklar() {
        $$("[data-wizard-saklar-jawaban], [data-wizard-saklar-publikasikan]").forEach((saklar) => {
            const input = saklar.parentElement?.querySelector("input[type='hidden']");

            if (input) {
                saklar.setAttribute("aria-checked", input.value === "1" ? "true" : "false");
            }
        });
    }

    /**
     * Simpan langkah, isian, dan daftar soal yang sedang terbuka.
     *
     * Daftar soal dibaca dari builder kalau builder sudah siap; kalau belum
     * (misalnya isian langkah 1 diketik sebelum builder selesai dimuat),
     * soal lama dipertahankan apa adanya supaya tidak tertimpa data kosong.
     */
    function simpanDraf() {
        const lama = bacaDraf();
        const soal = builder()?.baca();

        try {
            window.sessionStorage.setItem(
                KUNCI_DRAF,
                JSON.stringify({
                    langkah,
                    isian: bacaIsian(),
                    soal: soal ?? lama?.soal ?? null,
                }),
            );
        } catch {
            // Storage penuh atau diblokir: wizard tetap jalan tanpa draf.
        }
    }

    /**
     * Pulihkan draf sebelum langkah mana pun dibuka.
     *
     * Isian soal tidak dipulihkan dari sini: daftar soalnya ditulis balik
     * ke blok sumber (data-builder-awal), supaya builder memakai jalur yang
     * sama dengan isian awal dari server. Isian langkah 1 dan 3 dipulihkan
     * langsung, karena panelnya memang tidak pernah dilepas dari DOM.
     *
     * @return {number|null} langkah tersimpan, atau null kalau tidak ada draf.
     */
    function pulihkanDraf() {
        const draf = bacaDraf();

        if (!draf) {
            return null;
        }

        if (draf.isian && typeof draf.isian === "object") {
            tulisIsian(draf.isian);
            pulihkanSaklar();
        }

        if (Array.isArray(draf.soal) && sumberAwal) {
            sumberAwal.textContent = JSON.stringify(draf.soal);
        }

        return Number(draf.langkah) || null;
    }

    /* ============================================================
       LANGKAH 3 — RINGKASAN
    ============================================================ */

    /**
     * Ringkasan isi quiz di langkah 3, supaya pengguna bisa melihat apa
     * yang akan ia simpan tanpa perlu naik ke langkah sebelumnya.
     */
    function perbaruiRingkasan() {
        if (!ringkasanDaftar) {
            return;
        }

        const kategori = $("#pelajaran_id");
        const tingkat = $("#tingkat_kesulitan");
        const privat =
            form.querySelector("[name='visibilitas']:checked")?.value ===
            "private";
        const tampilJawaban =
            saklarJawaban?.getAttribute("aria-checked") === "true";
        const jumlah = builder()?.jumlah() ?? 0;
        const diajukan = !privat && inputPublikasikan?.value === "1";

        const baris = [
            ["Judul", nilai("#judul") || "Belum diisi"],
            [
                "Kategori",
                kategori?.selectedOptions[0]?.textContent.trim() ||
                    "Belum dipilih",
            ],
            [
                "Tingkat kesulitan",
                tingkat?.selectedOptions[0]?.textContent.trim() ||
                    "Belum dipilih",
            ],
            ["Jumlah soal", `${jumlah} soal`],
            [
                "Cara publikasi",
                privat
                    ? `Gunakan kode${nilai("#kode_akses") ? " (" + nilai("#kode_akses") + ")" : ""}`
                    : "Publikasikan",
            ],
            [
                "Status",
                privat
                    ? "Draft, siap dipakai"
                    : diajukan
                      ? "Menunggu persetujuan admin"
                      : "Draft, belum diajukan",
            ],
            ["Tampilkan jawaban", tampilJawaban ? "Ya" : "Tidak"],
        ];

        ringkasanDaftar.innerHTML = baris
            .map(
                ([nama, isi]) => `
                    <li>
                        <span class="kartu-tips__nama">${esc(nama)}</span>
                        <span class="kartu-tips__isi">${esc(isi)}</span>
                    </li>`,
            )
            .join("");
    }

    /* ============================================================
       LANGKAH 1 — THUMBNAIL
    ============================================================ */

    function tampilkanThumbnail(sumber, namaBerkas = "") {
        if (gambarThumbnail.getAttribute("src") === sumber) {
            return;
        }

        gambarThumbnail.src = sumber;
        gambarThumbnail.removeAttribute("hidden");

        areaThumbnail.hidden = true;
        isiThumbnail.removeAttribute("hidden");

        if (namaBerkas) {
            namaGambar.textContent = namaBerkas;
        }

        // Memilih gambar baru berarti gambar lama tidak lagi perlu dihapus.
        if (flagHapusGambar) {
            flagHapusGambar.value = "0";
        }
    }

    function kosongkanThumbnail() {
        gambarThumbnail.removeAttribute("src");
        gambarThumbnail.setAttribute("hidden", "");

        areaThumbnail.hidden = false;
        isiThumbnail.hidden = true;

        inputThumbnail.value = "";
        namaGambar.textContent = "";
        tampilGalat(galatGambar, "");

        if (flagHapusGambar) {
            flagHapusGambar.value = "1";
        }
    }

    if (inputThumbnail) {
        inputThumbnail.addEventListener("change", () => {
            const berkas = inputThumbnail.files?.[0];

            if (!berkas) {
                return;
            }

            if (berkas.size > BATAS_GAMBAR) {
                tampilGalat(galatGambar, "Ukuran thumbnail maksimal 5MB.");
                inputThumbnail.value = "";
                return;
            }

            if (!FORMAT_GAMBAR.includes(berkas.type)) {
                tampilGalat(
                    galatGambar,
                    "Format thumbnail harus JPG, PNG, atau WEBP.",
                );
                inputThumbnail.value = "";
                return;
            }

            tampilGalat(galatGambar, "");

            const pembaca = new FileReader();

            pembaca.onload = () =>
                tampilkanThumbnail(pembaca.result, berkas.name);
            pembaca.readAsDataURL(berkas);
        });

        tombolPilihGambar?.addEventListener("click", () =>
            inputThumbnail.click(),
        );
        tombolHapusGambar?.addEventListener("click", kosongkanThumbnail);
    }

    /* ============================================================
       LANGKAH 3 — KODE, PUBLIKASI, SAKLAR
    ============================================================ */

    /**
     * Kode acak memakai abjad yang sama dengan App\Support\KodeQuiz,
     * dikirim ke halaman lewat atribut data- supaya aturannya hanya ada
     * di satu tempat.
     */
    function kodeAcak() {
        const abjad = (
            tombolKode?.dataset.abjad ||
            akar.dataset.abjad ||
            ""
        ).split("");
        const panjang = Number(
            tombolKode?.dataset.panjang || akar.dataset.panjang || 6,
        );

        if (!abjad.length) {
            return "";
        }

        let kode = "";

        for (let i = 0; i < panjang; i++) {
            kode += abjad[Math.floor(Math.random() * abjad.length)];
        }

        return kode;
    }

    tombolKode?.addEventListener("click", () => {
        const kode = kodeAcak();

        if (!kode) {
            return;
        }

        inputKode.value = kode;
        inputKode
            .closest(".kode-bungkus")
            ?.classList.remove("kode-bungkus--salah");
        perbaruiRingkasan();
    });

    inputKode?.addEventListener("input", () => {
        // Spasi di dalam kode tidak pernah berguna dan mudah membuat
        // peserta salah ketik, jadi dibuang saat diketik.
        inputKode.value = inputKode.value.replace(/\s+/g, "");
        inputKode
            .closest(".kode-bungkus")
            ?.classList.remove("kode-bungkus--salah");
        perbaruiRingkasan();
    });

    /**
     * Tampilkan kolom kode hanya untuk quiz yang memakai kode, dan blok
     * pengajuan persetujuan hanya untuk quiz yang akan tayang untuk semua.
     *
     * Keduanya disembunyikan, bukan dinonaktifkan, supaya isian di dalamnya
     * tetap ikut terkirim kalau publikasi diubah lagi: kode tidak disimpan
     * untuk quiz publik dan pengajuan tidak berarti apa-apa untuk quiz mode
     * kode, jadi keduanya tidak boleh hilang dari form hanya karena radio
     * yang lain sempat dipilih.
     */
    function terapkanPublikasi() {
        const privat =
            form.querySelector("[name='visibilitas']:checked")?.value ===
            "private";

        $$("[data-publikasi]", akar).forEach((kartu) => {
            kartu.classList.toggle(
                "is-terpilih",
                kartu.dataset.publikasi === (privat ? "private" : "public"),
            );
        });

        if (areaKode) {
            areaKode.hidden = !privat;
        }

        if (wajibKode) {
            wajibKode.hidden = !privat;
        }

        if (inputKode) {
            inputKode.required = privat;
        }

        if (areaPersetujuan) {
            areaPersetujuan.hidden = privat;
        }

        perbaruiRingkasan();
    }

    $$("[name='visibilitas']", form).forEach((radio) =>
        radio.addEventListener("change", terapkanPublikasi),
    );

    /*
     * Saklar "Ajukan Persetujuan". Nilainya ditulis ke input tersembunyi
     * supaya server menerima 0 saat saklar mati, bukan "tidak dikirim".
     */
    saklarPersetujuan?.addEventListener("click", () => {
        if (saklarPersetujuan.disabled) {
            return;
        }

        const aktif = saklarPersetujuan.getAttribute("aria-checked") !== "true";

        saklarPersetujuan.setAttribute("aria-checked", String(aktif));

        if (inputPublikasikan) {
            inputPublikasikan.value = aktif ? "1" : "0";
        }

        if (pesanPersetujuan) {
            pesanPersetujuan.textContent = aktif
                ? "Quiz masuk daftar tunggu admin dan berhenti tayang sampai disetujui."
                : "Quiz disimpan sebagai draft. Tekan sekali lagi untuk mengirimnya ke admin.";
        }

        perbaruiRingkasan();
    });

    saklarJawaban?.addEventListener("click", () => {
        const aktif = saklarJawaban.getAttribute("aria-checked") !== "true";

        saklarJawaban.setAttribute("aria-checked", String(aktif));

        // Input tersembunyi selalu ada supaya form tetap mengirim nilai 0
        // ketika saklar dimatikan, bukan sekadar tidak mengirim apa pun.
        if (inputTampilkanJawaban) {
            inputTampilkanJawaban.value = aktif ? "1" : "0";
        }

        perbaruiRingkasan();
    });

    $("#pelajaran_id")?.addEventListener("change", perbaruiRingkasan);
    $("#tingkat_kesulitan")?.addEventListener("change", perbaruiRingkasan);
    $("#judul")?.addEventListener("input", perbaruiRingkasan);

    /* ============================================================
       NAVIGASI LANGKAH
    ============================================================ */

    function gambarStepper() {
        $$("[data-step]", stepper).forEach((item) => {
            const nomor = Number(item.dataset.step);

            item.classList.toggle("is-aktif", nomor === langkah);
            item.classList.toggle("is-selesai", nomor < langkah);
        });
    }

    function gambarNavigasi() {
        const terakhir = langkah === TOTAL_LANGKAH;

        if (navBawah) {
            /*
             * Seluruh baris navigasi bawah ikut disembunyikan di langkah 2:
             * keempat aksinya sudah berkumpul di kartu aksi langkah itu,
             * jadi tombol bawah hanya akan muncul dua kali — dan menyisakan
             * ruang kosong di bawah form kalau cuma tombolnya yang disembunyikan.
             */
            navBawah.hidden = langkah === 2;
        }

        if (tombolKembali) {
            tombolKembali.hidden = langkah === 1;
        }

        if (tombolLanjut) {
            tombolLanjut.hidden = terakhir || langkah === 2;
        }

        if (tombolSimpan) {
            tombolSimpan.hidden = !terakhir;
        }

        if (teksLanjut) {
            teksLanjut.textContent =
                langkah === 2 ? "Lanjut ke Pengaturan" : "Lanjutkan";
        }
    }

    /**
     * Buka satu langkah.
     *
     * Panel lain tetap di DOM, cuma disembunyikan: isian langkah yang
     * sedang tidak terlihat tidak pernah hilang.
     */
    function keLangkah(baru, arah = 1) {
        langkah = jepit(baru, 1, TOTAL_LANGKAH);

        panel.forEach((bagian) => {
            if (Number(bagian.dataset.wizardPanel) === langkah) {
                bagian.removeAttribute("hidden");
                bagian.dataset.arah = arah > 0 ? "maju" : "mundur";
            } else {
                bagian.setAttribute("hidden", "");
            }
        });

        if (subjudul) {
            subjudul.textContent = SUBJUDUL[langkah - 1] ?? "";
        }

        gambarStepper();
        gambarNavigasi();

        if (langkah === 2) {
            builder()?.segarkan();
        }

        if (langkah === 3) {
            perbaruiRingkasan();
        }

        // Langkah yang sedang terbuka ikut dititipkan ke draf, supaya
        // memuat ulang halaman tidak mengembalikan pengguna ke langkah 1.
        simpanDraf();

        window.scrollTo({ top: 0, behavior: "auto" });
    }

    /* ============================================================
       VALIDASI PER LANGKAH
    ============================================================ */

    const cekWajib = (pilih, pesan) => {
        bersihkanGalat(pilih);

        if (nilai(pilih)) {
            return true;
        }

        tandaiKolom(pilih, pesan, `${pilih}-galat`);
        return false;
    };

    function validasiLangkahSatu() {
        const cek = [
            cekWajib("#judul", "Judul quiz wajib diisi."),
            cekWajib("#deskripsi", "Deskripsi wajib diisi."),
            cekWajib("#pelajaran_id", "Pilih kategori quiz terlebih dahulu."),
            cekWajib(
                "#tingkat_kesulitan",
                "Pilih tingkat kesulitan terlebih dahulu.",
            ),
        ];

        if (cek.every(Boolean)) {
            return true;
        }

        $("[data-wizard-panel='1'] .galat-baris:not([hidden])")?.scrollIntoView(
            {
                block: "center",
                behavior: "smooth",
            },
        );

        return false;
    }

    /**
     * Validasi langkah 2 sepenuhnya diserahkan ke builder, karena hanya dia
     * yang tahu aturan tiap tipe soal. Builder juga menandai galatnya di
     * dekat field yang bermasalah, jadi pesan dari sini cukup satu.
     */
    function validasiLangkahDua() {
        tampilGalat(galatSoal, "");

        if (!builder()) {
            tampilGalat(
                galatSoal,
                "Form soal belum siap dimuat. Muat ulang halaman lalu coba lagi.",
            );
            return false;
        }

        if (builder().validasi()) {
            return true;
        }

        if (builder().jumlah() === 0) {
            tampilGalat(
                galatSoal,
                "Tambahkan minimal 1 soal sebelum melanjutkan.",
            );
        }

        return false;
    }

    function validasiLangkahTiga() {
        /*
         * Kode hanya berarti untuk quiz mode kode. Quiz publik tidak pernah
         * menyimpan kode, jadi kolom kosong di sini bukan kesalahan.
         */
        if (
            form.querySelector("[name='visibilitas']:checked")?.value !==
            "private"
        ) {
            perbaruiRingkasan();

            return true;
        }

        const bungkus = inputKode.closest(".kode-bungkus");

        bersihkanGalat("#kode_akses");

        if (nilai("#kode_akses")) {
            perbaruiRingkasan();

            return true;
        }

        bungkus?.classList.add("kode-bungkus--salah");

        // Fokus hanya dipindahkan kalau panelnya memang sedang terbuka.
        // Saat draft dikirim dari langkah 2, panel Pengaturan masih
        // tersembunyi — pemanggilnya yang membuka lalu memfokuskan.
        if (langkah === TOTAL_LANGKAH) {
            inputKode.focus();
        }

        return false;
    }

    /**
     * Quiz yang ditolak wajib menyertai catatan pendukung. Isiannya
     * disembunyikan sampai saklar pengajuan dinyalakan, jadi klik pertama pada
     * tombol simpan hanya membukanya dan memindahkan kursor ke sana. Pengajuan
     * baru benar-benar dikirim pada klik berikutnya.
     */
    function bukakanCatatanPendukung() {
        if (!wadahCatatan || !wadahCatatan.hasAttribute("hidden")) {
            return true;
        }

        wadahCatatan.removeAttribute("hidden");

        if (isianCatatan) {
            isianCatatan.required = true;
            isianCatatan.focus();
        }

        return false;
    }

    /* ============================================================
       AKSI TOMBOL
    ============================================================ */

    const keLanjut = () => {
        const valid =
            langkah === 1 ? validasiLangkahSatu() : validasiLangkahDua();

        if (valid) {
            keLangkah(langkah + 1);
        }
    };

    [tombolLanjut, ...tombolLanjutLain].forEach((tombol) =>
        tombol?.addEventListener("click", keLanjut),
    );

    [tombolKembali, ...tombolKembaliLain].forEach((tombol) =>
        tombol?.addEventListener("click", () => keLangkah(langkah - 1, -1)),
    );

    /*
     * "Batal" berarti isian tidak jadi dipakai, jadi drafnya ikut
     * dibuang. Tanpa ini, membuka form ini lagi akan memulihkan isian yang
     * sudah ditinggal lewat tombol itu.
     */
    $$("[data-wizard-batal]").forEach((tombol) =>
        tombol.addEventListener("click", hapusDraf),
    );

    /**
     * Pindah ke langkah Pengaturan tanpa membuka isian yang bermasalah
     * berulang-ulang: kalau pengguna memang sudah di sana, cukup berhenti.
     */
    const kePengaturan = () => {
        if (langkah !== TOTAL_LANGKAH) {
            keLangkah(TOTAL_LANGKAH, -1);
        }
    };

    /**
     * Kirim form.
     *
     * Berlaku untuk tiga tempat: "Selesai & Simpan" di langkah terakhir,
     * "Draft" di baris aksi langkah 2, dan — di form admin — "Publish
     * Sekarang". Server memvalidasi seluruh form sekaligus, jadi semuanya
     * harus memeriksa langkah-langkah yang ada dulu; tombolnya bertipe
     * button, bukan submit, supaya pengiriman bisa ditahan dan langkah yang
     * bermasalah dibuka lebih dulu, alih-alih memantulkan halaman ke awal
     * setiap ada yang salah.
     *
     * "aksi" hanya berarti sesuatu di form admin yang punya input
     * [data-konten-aksi]: di situ nilainya menentukan apakah hasil simpan
     * berstatus draft atau langsung terbit. Form milik pemilik tidak punya
     * input itu, jadi parameternya diabaikan di sana.
     */
    function kirimQuiz(aksi = null) {
        if (!validasiLangkahSatu()) {
            keLangkah(1, -1);
            return;
        }

        if (!validasiLangkahDua()) {
            keLangkah(2, -1);
            return;
        }

        /*
         * Langkah Pengaturan hanya diperiksa kalau form ini memang punya
         * langkah itu. Satu-satunya isian yang ada di sana dan bisa menolak
         * kiriman adalah kode akses, jadi form dua tahap tidak pernah
         * tersangkut di sini.
         */
        if (TOTAL_LANGKAH >= 3) {
            if (!validasiLangkahTiga()) {
                kePengaturan();
                inputKode?.focus();

                return;
            }

            if (inputPublikasikan?.value === "1" && !bukakanCatatanPendukung()) {
                kePengaturan();
                return;
            }
        }

        // Isian soal ditulis ulang tepat sebelum form dikirim, supaya isian
        // terakhir pengguna (misalnya pilihan yang baru diketik) ikut
        // terkirim dan tidak tertinggal satu langkah.
        builder()?.tulisInput();

        if (aksi && inputAksi) {
            inputAksi.value = aksi;
        }

        form.requestSubmit();
    }

    /*
     * Tombol di luar form ini — "Publish Sekarang" di baris aksi langkah 2
     * dan dialog konfirmasi terbitan — memanggil fungsi yang sama lewat
     * objek global, persis seperti yang dilakukan builder soal di atas.
     * Dipasang begitu wizard siap, dan dihapus lagi kalau halaman ini
     * dilepas, supaya tidak ada sisa wizard lama yang masih bisa dipanggil.
     */
    window.kelasKitaKontenKirim = kirimQuiz;

    window.addEventListener("pagehide", () => {
        if (window.kelasKitaKontenKirim === kirimQuiz) {
            delete window.kelasKitaKontenKirim;
        }
    });

    tombolSimpan?.addEventListener("click", () => {
        if (langkah === TOTAL_LANGKAH) {
            kirimQuiz();
        }
    });

    // Simpan sebagai draft tanpa menyelesaikan langkah Pengaturan.
    tombolDraft?.addEventListener("click", () => kirimQuiz("draft"));

    /*
     * Mengubah isi form juga harus: (1) menulis ulang input tersembunyi
     * soal, karena ringkasan di langkah 3 dan pengiriman form membacanya
     * dari sana, dan (2) menitipkan draf ke sessionStorage supaya memuat
     * ulang halaman tidak menghapus isian. Debounce supaya setiap ketikan
     * tidak menulis ulang seluruh form.
     */
    let jedaInput = null;

    const tanggapiIsian = (jeda) => {
        window.clearTimeout(jedaInput);

        jedaInput = window.setTimeout(() => {
            builder()?.tulisInput();
            simpanDraf();
        }, jeda);
    };

    form.addEventListener("input", () => tanggapiIsian(400));
    form.addEventListener("change", () => tanggapiIsian(0));

    form.addEventListener("submit", () => {
        /*
         * Draf dibuang sebelum form dikirim: sesudah ini isian yang paling
         * benar justru datang dari respons halaman berikutnya — termasuk
         * old() kalau validasi server menolak.
         */
        hapusDraf();

        // Menandai sedang menyimpan supaya quiz tidak terkirim dua kali
        // saat tombol ditekan berulang.
        if (teksSimpan) {
            teksSimpan.textContent = "Menyimpan...";
        }

        if (tombolSimpan) {
            tombolSimpan.disabled = true;
        }

        if (tombolDraft) {
            tombolDraft.disabled = true;
        }
    });

    /* ============================================================
       MULAI
    ============================================================ */

    /*
     * Builder soal di langkah 2 dimuat sebagai modul terpisah, dan urutan
     * import app.js tidak menjamin modul mana yang selesai lebih dulu.
     * Pemanggilan pertamanya ditunda satu putaran event loop, sekaligus
     * memperbarui ringkasan kalau draf mengembalikan pengguna ke langkah 3
     * (waktu itu jumlah soalnya sudah bisa dibaca).
     */
    window.setTimeout(() => {
        builder()?.tulisInput();

        if (langkah === 3) {
            perbaruiRingkasan();
        }
    }, 0);

    /*
     * Draf dipulihkan sebelum langkah mana pun dibuka: isian soal masuk ke
     * blok sumber yang akan dibaca builder, isian langkah lain menempel
     * langsung di form, lalu langkah tersimpan yang dibuka.
     */
    const langkahDraf = pulihkanDraf();

    terapkanPublikasi();
    keLangkah(langkahDraf ?? (Number(akar.dataset.langkah) || 1));
}
