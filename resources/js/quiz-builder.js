/**
 * Builder soal pada wizard "Buat Quiz".
 *
 * Menangani:
 *   - menambah, menduplikasi, menghapus, dan mengurutkan soal
 *   - mengganti tipe soal beserta isi area jawabannya
 *   - menambah dan menghapus pilihan jawaban
 *   - menandai jawaban benar, sesuai aturan tiap tipe
 *   - saklar "jawaban harus sama persis" untuk soal jawaban singkat
 *   - menulis seluruh isian sebagai input tersembunyi milik form utama
 *
 * Prinsipnya: DOM adalah state-nya. Tidak ada larik data terpisah yang harus
 * disinkronkan dengan isi kartu — kartu yang sedang tampil adalah satu-satunya
 * sumber kebenaran, dan isian baru disalin ke input tersembunyi saat wizard
 * disimpan. Karena itu pilihan pengguna tidak pernah hilang karena lupa
 * ditulis balik ke state.
 *
 * Modul berhenti sendiri kalau halamannya tidak dibuka, jadi aman diimpor
 * dari resources/js/app.js.
 */

const AKAR = document.querySelector("[data-builder-daftar]");

if (AKAR) {
    initBuilderSoal(AKAR);
}

function initBuilderSoal(akar) {
    const $ = (pilih, induk = akar) => induk.querySelector(pilih);
    const $$ = (pilih, induk = akar) => Array.from(induk.querySelectorAll(pilih));

    const panel = document.querySelector("[data-wizard-panel='2']");

    const daftar = AKAR;
    const cetakKartu = $("[data-builder-cetak]", panel);
    const cetakPilihan = $("[data-builder-cetak-pilihan]", panel);
    const sumberAwal = $("[data-builder-awal]", panel);
    const jumlahSoal = $("[data-builder-jumlah]", panel);
    const galatDaftar = $("[data-builder-galat='daftar']", panel);
    const kosongSoal = $("[data-builder-kosong]", panel);
    const tombolTambah = $("[data-builder-tambah]", panel);

    /*
     * Nilai internal tipe. Disalin dari App\Models\Soal lewat atribut data
     * supaya daftar ini tidak bisa melenceng dari konstanta di PHP.
     */
    const TIPE = {
        GANDA: "multiple_choice",
        BANYAK: "multiple_select",
        BENAR_SALAH: "true_false",
        DROPDOWN: "dropdown",
        SINGKAT: "short_answer",
        PARAGRAF: "paragraph",
    };

    const LABEL_TIPE = {
        [TIPE.GANDA]: "Pilihan Ganda",
        [TIPE.BANYAK]: "Pilihan Ganda Kompleks",
        [TIPE.BENAR_SALAH]: "Benar / Salah",
        [TIPE.DROPDOWN]: "Dropdown",
        [TIPE.SINGKAT]: "Isian Singkat",
        [TIPE.PARAGRAF]: "Essay",
    };

    /** Judul bagian jawaban di card, ikut berubah mengikuti tipe. */
    const JUDUL_ISIAN = {
        [TIPE.GANDA]: "Pilihan Jawaban",
        [TIPE.BANYAK]: "Pilihan Jawaban",
        [TIPE.BENAR_SALAH]: "Pilihan Jawaban",
        [TIPE.DROPDOWN]: "Pilihan Jawaban",
        [TIPE.SINGKAT]: "Jawaban Benar",
        [TIPE.PARAGRAF]: "Jawaban / Panduan Penilaian",
    };

    /*
     * Ikon kecil di tombol tipe, disalin dari App\Support\Ikon supaya
     * tampilannya satu keluarga dengan ikon di halaman lain.
     */
    const IKON_TIPE = {
        [TIPE.GANDA]:
            "M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z",
        [TIPE.BANYAK]:
            "M2.25 6.75h13.5m-13.5 0v1.5a2.25 2.25 0 0 0 2.25 2.25h1.5m-3.75-3.75h13.5m-13.5 0v1.5a2.25 2.25 0 0 0 2.25 2.25h1.5m-3.75-3.75h13.5m-13.5 0v1.5a2.25 2.25 0 0 0 2.25 2.25h1.5m11.25-3.75-2.06 2.06a1.125 1.125 0 0 1-1.591 0l-.75-.75",
        [TIPE.BENAR_SALAH]: "M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z",
        [TIPE.DROPDOWN]: "M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5",
        [TIPE.SINGKAT]:
            "M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z",
        [TIPE.PARAGRAF]:
            "M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10",
    };

    /*
     * Pilihan yang teksnya dikunci per tipe: Benar / Salah selalu berisi
     * dua baris itu saja, tidak bisa ditambah maupun dihapus, dan hurufnya
     * tidak ditampilkan karena teksnya sudah jelas.
     */
    const PILIHAN_TETAP = {
        [TIPE.BENAR_SALAH]: ["Benar", "Salah"],
    };

    /**
     * Apakah tipe ini memakai daftar pilihan jawaban.
     *
     * Dipakai di beberapa tempat supaya daftar tipenya hanya ditulis satu
     * kali dan tidak gampang melenceng antar fungsi.
     */
    const tipeDaftar = (tipe) =>
        tipe === TIPE.GANDA ||
        tipe === TIPE.BANYAK ||
        tipe === TIPE.BENAR_SALAH ||
        tipe === TIPE.DROPDOWN;

    /** Batas pilihan, mengikuti App\Models\Soal. */
    const MAKSIMAL_PILIHAN = parseInt(akar.dataset.maksimal || "10", 10);
    const MINIMAL_PILIHAN = 2;

    /** Batas karakter pertanyaan, mengikuti kolom pada langkah ini. */
    const MAKSIMAL_PERTANYAAN = 255;

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

    const Huruf = (n) => String.fromCharCode(65 + n);

    const tampilGalat = (el, pesan) => {
        if (!el) {
            return;
        }

        el.textContent = pesan || "";
        el.hidden = !pesan;
    };

    const bersihkanGalatKartu = (kartu) => {
        $$("[data-builder-galat]", kartu).forEach((el) => tampilGalat(el, ""));
    };

    const tandai = (el, pesan, namaGalat, kartu) => {
        if (el) {
            el.classList.add("kolom-form--salah");
        }

        tampilGalat($(`[data-builder-galat="${namaGalat}"]`, kartu), pesan);
    };

    /**
     * Tulis ulang penghitung karakter di pojok textarea pertanyaan.
     *
     * Hitungan diambil dari panjang nilai sebenarnya, bukan dari maxlength,
     * supaya tetap sinkron walau isiannya diisi ulang oleh JavaScript
     * (misalnya saat soal diduplikasi atau dimuat dari request yang gagal
     * validasi).
     */
    function perbaruiHitung(kartu) {
        const el = $("[data-builder-hitung]", kartu);

        if (!el) {
            return;
        }

        const pertanyaan = $("[data-builder-pertanyaan]", kartu);

        el.textContent = String(pertanyaan?.value ?? "").length + "/" + MAKSIMAL_PERTANYAAN;
    }

    /* ============================================================
       AREA JAWABAN YANG AKTIF
    ============================================================ */

    /**
     * Blok area jawaban yang sedang dipakai sebuah kartu.
     *
     * Tiap tipe punya blok sendiri, dan hanya satu yang terlihat. Semua
     * pembacaan isian nanti selalu ke blok ini, jadi tipe yang sedang aktif
     * tidak perlu ditanya berulang-ulang.
     */
    const blokAktif = (kartu) => $("[data-builder-blok]:not([hidden])", kartu);

    const barisPilihan = (kartu) => $$("[data-builder-pilihan-baris]", blokAktif(kartu) || kartu);

    /** Nama kolom tb_soal_pilihan, mis. "pilihan_a". */
    const namaKolom = (huruf) => `pilihan_${huruf.toLowerCase()}`;

    /* ============================================================
       BARIS PILIHAN
    ============================================================ */

    /** Nama grup kontrol jawaban benar, unik per kartu soal. */
    const namaGrup = (kartu) => "benar_" + (kartu.dataset.builderId || "satu");

    /**
     * Tandai satu baris pilihan sebagai jawaban benar (atau lepaskan).
     *
     * Warna baris dan lencana "Jawaban Benar" selalu ikut berubah
     * bersamaan, jadi tidak mungkin baris yang terlihat seperti kunci tetapi
     * lantainya tidak pernah muncul.
     */
    function tandaiBenar(baris, benar) {
        baris.classList.toggle("is-benar", benar);

        const lencana = $("[data-builder-benar-lencana]", baris);

        if (lencana) {
            lencana.hidden = !benar;
        }
    }

    /**
     * Pasang kontrol jawaban benar yang tepat untuk satu baris pilihan.
     *
     * Tipe yang boleh lebih dari satu jawaban memakai checkbox, tipe lainnya
     * memakai radio. Radio baru bisa saling mematikan kalau semua baris
     * dalam satu kartu berbagi satu name — itulah yang membuat hanya satu
     * lingkaran bisa tercentang. Name-nya dibuat per kartu, jadi lingkaran
     * di Soal 1 tidak ikut mematikan lingkaran di Soal 2.
     *
     * Untuk tipe dropdown kontrolnya disembunyikan sepenuhnya: kuncinya
     * ditandai lewat select di bagian "Jawaban Benar", bukan dari baris
     * pilihan.
     */
    function pasangKontrol(kartu, baris, benar) {
        const tipe = kartu.dataset.tipe;
        const input = document.createElement("input");

        input.type = tipe === TIPE.BANYAK ? "checkbox" : "radio";
        input.className = "sr-only";
        input.setAttribute("data-builder-pilihan-benar", "");
        input.name = namaGrup(kartu);
        input.tabIndex = -1;
        input.checked = benar;

        $("[data-builder-pilihan-benar]", baris)?.replaceWith(input);
        $("[data-builder-indikator]", baris)?.toggleAttribute("hidden", tipe === TIPE.DROPDOWN);
        tandaiBenar(baris, benar);
    }

    /**
     * Buat satu baris pilihan jawaban.
     *
     * Barisnya disalin dari <template>, sama seperti kartu soal, supaya
     * bentuk baris hanya ditulis sekali di markup.
     *
     * Pilihan tetap (Benar / Salah) datang sebagai pilihan biasa, hanya
     * tampilannya yang dibedakan: huruf disembunyikan karena teksnya sudah
     * jelas, dan tombol hapusnya dicabut karena barisnya memang tidak boleh
     * dihapus.
     */
    function barisPilihanBaru(kartu, huruf, teks = "", benar = false, tetap = false) {
        const klon = cetakPilihan.content.firstElementChild.cloneNode(true);

        klon.dataset.huruf = huruf;
        $("[data-builder-huruf]", klon).textContent = huruf;
        $("[data-builder-pilihan-teks]", klon).value = teks;

        if (tetap) {
            klon.classList.add("is-tetap");
            $("[data-builder-huruf]", klon).hidden = true;
            $("[data-builder-pilihan-teks]", klon).readOnly = true;
            $("[data-builder-pilihan-hapus]", klon)?.remove();
        }

        pasangKontrol(kartu, klon, benar);

        return klon;
    }

    /** Urutan ulang label huruf A, B, C, ... mengikuti urutan baris. */
    function nomorUlangPilihan(kartu) {
        barisPilihan(kartu).forEach((baris, i) => {
            const huruf = Huruf(i);

            baris.dataset.huruf = huruf;
            $("[data-builder-huruf]", baris).textContent = huruf;
        });
    }

    /** Ban tombol tambah dan hapus sesuai jumlah pilihan saat ini. */
    function perbaruiBatasPilihan(kartu) {
        const baris = barisPilihan(kartu);
        const blok = blokAktif(kartu);

        if (!blok) {
            return;
        }

        const tombolTambah = $("[data-builder-pilihan-tambah]", blok);
        const tetap = Boolean(PILIHAN_TETAP[kartu.dataset.tipe]);

        if (tombolTambah) {
            // Pilihan tetap tidak punya baris tambahan sama sekali, jadi
            // tombolnya disembunyikan, bukan hanya dinonaktifkan.
            tombolTambah.hidden = tetap;

            // Batas atas disables tombolnya, batas bawah tidak pernah
            // menghilangkan baris supaya pengguna tidak terjebak soalnya
            // tinggal satu baris lagi.
            tombolTambah.disabled = baris.length >= MAKSIMAL_PILIHAN;
            tombolTambah.title =
                baris.length >= MAKSIMAL_PILIHAN
                    ? "Maksimal " + MAKSIMAL_PILIHAN + " pilihan jawaban"
                    : "";
        }
    }

    /**
     * Tulis ulang baris pilihan sebuah kartu dari data.
     *
     * Dipakai saat kartu dibuat, diduplikasi, dan saat tipenya diganti.
     * Yang tidak terpakai dibuang supaya tidak ikut terkirim.
     *
     * Pilihan tetap (Benar / Salah) diabaikan isinya dan selalu dibuat
     * dari daftarnya sendiri, supaya teksnya tidak bisa berubah walau
     * datang dari kartu hasil duplikasi atau dari request yang gagal.
     */
    function isiPilihan(kartu, pilihan = [], benar = []) {
        const blok = blokAktif(kartu);

        if (!blok) {
            return;
        }

        const wadah = $("[data-builder-pilihan]", blok);

        if (!wadah) {
            return;
        }

        const tetap = PILIHAN_TETAP[kartu.dataset.tipe];

        /*
         * Hanya pilihan ganda kompleks yang boleh memegang lebih dari satu
         * kunci. Kalau datang dengan beberapa huruf — misalnya bekas tipe
         * kompleks yang barusan diganti — cukup huruf pertama yang
         * dipertahankan, supaya radio dan lencana "Jawaban Benar" tidak
         * pernah menunjuk dua baris berbeda.
         */
        const kunci =
            kartu.dataset.tipe === TIPE.BANYAK ? benar : benar.slice(0, 1);

        wadah.innerHTML = "";

        if (tetap) {
            tetap.forEach((teks, i) => {
                const huruf = Huruf(i);

                wadah.appendChild(
                    barisPilihanBaru(kartu, huruf, teks, kunci.includes(huruf), true),
                );
            });

            nomorUlangPilihan(kartu);
            perbaruiBatasPilihan(kartu);

            return;
        }

        const teks = pilihan.filter((p) => String(p.teks ?? "").trim() !== "");

        // Dua pilihan kosong supaya kartu baru langsung bisa diisi.
        const final = teks.length >= MINIMAL_PILIHAN ? teks : [{}, {}];

        final.forEach((p, i) => {
            const baris = barisPilihanBaru(kartu, Huruf(i), String(p.teks ?? ""), kunci.includes(Huruf(i)));

            wadah.appendChild(baris);
        });

        nomorUlangPilihan(kartu);
        perbaruiBatasPilihan(kartu);
    }

    /* ============================================================
       JAWABAN BENAR
    ============================================================ */

    /**
     * Perbarui pemilih kunci jawaban pada tipe dropdown.
     *
     * Kunci dropdown tidak ditandai lewat lingkaran di baris pilihan,
     * melainkan lewat select tersendiri, jadi select itulah yang dibangun
     * ulang tiap kali teks pilihan berubah. Tipe lain tidak memakai blok
     * ini sama sekali karena kuncinya sudah jelas dari radio per baris.
     */
    function perbaruiRingkasan(kartu) {
        const tipe = kartu.dataset.tipe;
        const bungkus = $("[data-builder-ringkas-bungkus]", kartu);

        if (!bungkus) {
            return;
        }

        const pakaiSelect = tipe === TIPE.DROPDOWN;

        bungkus.hidden = !pakaiSelect;

        if (!pakaiSelect) {
            return;
        }

        const select = $("[data-builder-pilih-benar]", bungkus);

        if (!select) {
            return;
        }

        const baris = barisPilihan(kartu);

        /*
         * Select-nya dibangun ulang tiap kali teks pilihan berubah, tapi
         * pilihan yang sedang aktif harus dipertahankan — kalau tidak,
         * setiap ketikan akan memantulkan select ke nilai lama.
         */
        const sebelumnya = select.value;

        select.innerHTML = '<option value="">Pilih jawaban benar</option>';

        baris.forEach((b) => {
            const huruf = b.dataset.huruf;
            const opsi = document.createElement("option");

            opsi.value = huruf;
            opsi.textContent = String($("[data-builder-pilihan-teks]", b).value).trim() || "Pilihan " + huruf;
            opsi.selected = huruf === sebelumnya;

            select.appendChild(opsi);
        });

        if (!Array.from(select.options).some((o) => o.selected && o.value)) {
            // Huruf pertama yang ditandai benar jadi pilihan bawaan, supaya
            // kunci tidak pernah kosong selama masih ada opsi.
            const benar = baris.find((b) => $("[data-builder-pilihan-benar]", b)?.checked);
            const bawaan = benar?.dataset.huruf || baris[0]?.dataset.huruf;

            if (bawaan) {
                select.value = bawaan;
            }
        }
    }

    /* ============================================================
       GANTI TIPE
    ============================================================ */

    /**
     * Pastikan dropdown tipe punya opsi untuk nilai yang sedang dipakai.
     *
     * Blade hanya merender tipe yang boleh dipilih baru. Soal lama bertipe
     * dropdown tidak lagi ada di daftar itu, tapi tetap harus bisa diedit
     * apa adanya, jadi opsinya disisipkan lagi tepat di tempat. Tanpa ini
     * nilai dropdown akan jatuh ke opsi pertama dan tipe soal berubah
     * diam-diam saat kartu dibuka.
     */
    function pastikanOpsiTipe(pilihTipe, tipe) {
        if (!pilihTipe || !LABEL_TIPE[tipe]) {
            return;
        }

        if (pilihTipe.querySelector(`option[value="${tipe}"]`)) {
            return;
        }

        const opsi = document.createElement("option");

        opsi.value = tipe;
        opsi.textContent = LABEL_TIPE[tipe];
        pilihTipe.appendChild(opsi);

        // Select-nya ikut dipasang, bukan cuma opsinya. Tanpa ini nilainya
        // masih menunjuk opsi pertama, lalu perbedaan antara select dan
        // data-tipe kartu akan terbaca sebagai "pengguna mengganti tipe"
        // pada pergantian berikutnya.
        pilihTipe.value = tipe;
    }

    /**
     * Perbarui tombol tipe di kepala kartu: teksnya dan ikonnya.
     *
     * Isi menu tidak ditulis ulang di sini, hanya ketika menunya dibuka,
     * supaya menu tidak kehilangan opsinya di tengah jalan.
     */
    function gambarTombolTipe(kartu, tipe) {
        const teks = $("[data-builder-tipe-teks]", kartu);
        const ikon = $("[data-builder-tipe-ikon] svg path", kartu);

        if (teks) {
            teks.textContent = LABEL_TIPE[tipe] || tipe;
        }

        if (ikon) {
            ikon.setAttribute("d", IKON_TIPE[tipe] || IKON_TIPE[TIPE.GANDA]);
        }

        const tandaWajib = $("[data-builder-tanda-wajib]", kartu);

        if (tandaWajib) {
            // Isian essay tidak diwajibkan: panduannya sendiri sudah disebut
            // opsional, jadi tanda bintangnya ikut dicabut.
            tandaWajib.hidden = tipe === TIPE.PARAGRAF;
        }
    }

    /**
     * Terapkan tipe baru ke sebuah kartu.
     *
     * Pilihan yang sudah diketik tetap dibawa ke tipe baru selama tipe baru
     * masih memakai daftar. Kalau tipe barunya berbasis teks, daftarnya
     * disimpan sementara di kartu supaya kembali ke tipe berdaftar tidak
     * membuat pengguna mengetik ulang dari awal.
     */
    function terapkanTipe(kartu, tipe, pilihanLama = null, benarLama = []) {
        const pakaiDaftar = tipeDaftar(tipe);

        kartu.dataset.tipe = tipe;

        pastikanOpsiTipe($("[data-builder-tipe]", kartu), tipe);

        $$("[data-builder-blok]", kartu).forEach((blok) => {
            blok.hidden = blok.dataset.builderBlok !== tipe;
        });

        // Tombol tipe di kepala kartu dan judul bagian jawaban ikut
        // berubah, jadi kartu selalu menyebut tipe yang sedang dikerjakan.
        gambarTombolTipe(kartu, tipe);

        const judulLangkah = $("[data-builder-langkah-judul='3']", kartu);

        if (judulLangkah) {
            judulLangkah.textContent = JUDUL_ISIAN[tipe] || "Jawaban";
        }

        if (pakaiDaftar) {
            // Baris pilihan dibangun ulang dengan kontrol yang sesuai tipe
            // baru, jadi radio di tipe pilihan ganda dan checkbox di tipe
            // pilihan banyak tidak saling tertinggal.
            isiPilihan(kartu, pilihanLama || [], benarLama);
        }

        perbaruiRingkasan(kartu);
    }

    /* ============================================================
       BACA / TULIS KARTU
    ============================================================ */

    /**
     * Ambil seluruh isian satu kartu.
     *
     * Hanya blok tipe yang aktif yang dibaca, jadi kolom tipe lain tidak
     * pernah ikut terkirim.
     */
    function bacaKartu(kartu) {
        const tipe = kartu.dataset.tipe;
        const pakaiDaftar = tipeDaftar(tipe);

        const pilihan = [];
        const benar = [];

        if (pakaiDaftar) {
            barisPilihan(kartu).forEach((baris) => {
                const teks = String($("[data-builder-pilihan-teks]", baris).value).trim();

                if (!teks) {
                    return;
                }

                pilihan.push({ huruf: baris.dataset.huruf, teks });

                if ($("[data-builder-pilihan-benar]", baris)?.checked) {
                    benar.push(baris.dataset.huruf);
                }
            });

            if (tipe === TIPE.DROPDOWN) {
                benar.length = 0;

                const terpilih = $("[data-builder-pilih-benar]", kartu)?.value;

                if (terpilih) {
                    benar.push(terpilih);
                }
            }
        }

        return {
            pertanyaan: String($("[data-builder-pertanyaan]", kartu)?.value ?? "").trim(),
            tipe,
            pilihan,
            benar,
            kunciTeks: String($("[data-builder-kunci-teks]", kartu)?.value ?? "").trim(),
            ceklis: $("[data-builder-saklar]", kartu)?.getAttribute("aria-checked") === "true",
            pembahasan: String($("[data-builder-pembahasan]", kartu)?.value ?? "").trim(),
            tingkat: String($("[data-builder-tingkat]", kartu)?.value ?? "Mudah"),
        };
    }

    /** Isi satu kartu dari data. Kebalikan dari bacaKartu(). */
    function tulisKartu(kartu, data) {
        const tipe = data.tipe || TIPE.GANDA;

        $("[data-builder-pertanyaan]", kartu).value = data.pertanyaan || "";

        const pilihTipe = $("[data-builder-tipe]", kartu);

        if (pilihTipe) {
            pilihTipe.value = tipe;
        }

        terapkanTipe(kartu, tipe, data.pilihan || [], data.benar || []);

        // Kolom kunci teks ada di dua blok (singkat dan paragraf), jadi
        // keduanya ditulis dari sumber yang sama.
        $$("[data-builder-kunci-teks]", kartu).forEach((el) => {
            el.value = data.kunciTeks || "";
        });

        const saklar = $("[data-builder-saklar]", kartu);
        const ceklis = data.ceklis !== false;

        if (saklar) {
            saklar.setAttribute("aria-checked", String(ceklis));
        }

        const inputCeklis = $("[data-builder-tokok-persis]", kartu);

        if (inputCeklis) {
            inputCeklis.value = ceklis ? "1" : "0";
        }

        const pembahasan = $("[data-builder-pembahasan]", kartu);

        if (pembahasan) {
            pembahasan.value = data.pembahasan || "";
        }

        const tingkat = $("[data-builder-tingkat]", kartu);

        if (tingkat) {
            tingkat.value = data.tingkat || "Mudah";
        }

        perbaruiHitung(kartu);
        perbaruiRingkasan(kartu);
    }

    const kartuSoal = () => $$("[data-builder-soal]", daftar);

    /* ============================================================
        DAFTAR SOAL
    ============================================================ */

    /**
     * Tulis ulang nomor dan hitungan setelah daftar berubah.
     *
     * Nomor dihitung dari posisi kartu di daftar, tidak pernah disimpan,
     * jadi menghapus atau mengurutkan soal tidak pernah meninggalkan nomor
     * yang salah.
     */
    function gambarDaftar() {
        const kartu = kartuSoal();

        kartu.forEach((el, i) => {
            const nomor = $("[data-builder-nomor]", el);

            if (nomor) {
                nomor.textContent = "Soal " + (i + 1);
            }
        });

        if (jumlahSoal) {
            jumlahSoal.textContent = kartu.length + " soal";
        }

        if (kosongSoal) {
            kosongSoal.hidden = kartu.length > 0;
        }

        // Menyisakan satu soal saja tidak boleh dihapus, jadi tombolnya
        // dinonaktifkan, bukan disembunyikan, supaya alasannya terlihat.
        kartu.forEach((el) => {
            const tombol = $("[data-builder-hapus]", el);

            if (tombol) {
                tombol.disabled = kartu.length <= 1;
                tombol.title = kartu.length <= 1 ? "Quiz minimal harus punya satu soal" : "";
            }
        });

        if (tombolTambah) {
            tombolTambah.disabled = kartu.length >= 30;
        }
    }

    function tambahSoal(data = {}) {
        const kartu = cetakKartu.content.firstElementChild.cloneNode(true);

        // Id dipakai sebagai nama grup kontrol jawaban benar, jadi dua kartu
        // tidak pernah saling mematikan lingkarannya.
        kartu.dataset.builderId = Math.random().toString(36).slice(2, 9);

        daftar.appendChild(kartu);
        tulisKartu(kartu, {
            pertanyaan: "",
            tipe: TIPE.GANDA,
            pilihan: [],
            benar: [],
            kunciTeks: "",
            ceklis: true,
            ...data,
        });

        gambarDaftar();

        return kartu;
    }

    /* ============================================================
       VALIDASI
    ============================================================ */

    /**
     * Periksa satu kartu dan tandai galatnya di dekat field yang bermasalah.
     *
     * Aturannya sama persis dengan App\Http\Requests\QuizIsianRequest, jadi
     * galat yang lolos dari peramban tetap akan ditolak server.
     */
    function validasiKartu(kartu) {
        bersihkanGalatKartu(kartu);

        const data = bacaKartu(kartu);
        const tipe = data.tipe;

        const pertanyaan = $("[data-builder-pertanyaan]", kartu);
        let valid = true;

        if (!data.pertanyaan) {
            tandai(pertanyaan, "Pertanyaan soal wajib diisi.", "pertanyaan", kartu);
            valid = false;
        }

        if (tipe === TIPE.SINGKAT) {
            if (!data.kunciTeks) {
                tandai(
                    $("[data-builder-kunci-teks]", kartu),
                    "Jawaban singkat wajib diisi, karena tidak ada pilihan untuk dibandingkan.",
                    "kunciTeks",
                    kartu,
                );
                valid = false;
            }

            return valid;
        }

        // Paragraf tidak punya pilihan maupun kunci yang wajib.
        if (tipe === TIPE.PARAGRAF) {
            return valid;
        }

        if (data.pilihan.length < MINIMAL_PILIHAN) {
            // Galatnya ditempel ke wadah pilihan milik blok yang sedang
            // aktif; kalau dicari ke seluruh kartu, yang ketemu justru
            // wadah blok tersembunyi sehingga pesannya tidak terlihat.
            const wadah = $("[data-builder-pilihan]", blokAktif(kartu)) || $("[data-builder-pilihan]", kartu);

            tandai(
                wadah,
                "Soal bertipe " + LABEL_TIPE[tipe] + " minimal punya " + MINIMAL_PILIHAN + " pilihan jawaban.",
                "pilihan",
                kartu,
            );
            return false;
        }

        if (data.benar.length === 0) {
            tandai(null, "Tandai minimal satu jawaban benar.", "benar", kartu);
            return false;
        }

        if (tipe !== TIPE.BANYAK && data.benar.length > 1) {
            tandai(
                null,
                "Tipe " + LABEL_TIPE[tipe] + " hanya boleh punya tepat satu jawaban benar.",
                "benar",
                kartu,
            );
            return false;
        }

        return valid;
    }

    /* ============================================================
       INPUT TERSEMBUNYI
    ============================================================ */

    /**
     * Salin seluruh kartu menjadi input tersembunyi milik form utama.
     *
     * Nama field mengikuti aturan App\Http\Requests\QuizIsianRequest:
     * soal[0][pertanyaan], soal[0][pilihan][A], dst. Urutannya mengikuti
     * urutan kartu di layar, jadi server menghitung ulang nomor soal sendiri.
     */
    function tulisInput() {
        const wadah = document.querySelector("[data-builder-input]");

        if (!wadah) {
            return;
        }

        const bagian = [];

        kartuSoal().forEach((kartu, index) => {
            const data = bacaKartu(kartu);
            const dasar = "soal[" + index + "]";

            bagian.push(
                '<input type="hidden" name="' + dasar + '[pertanyaan]" value="' + esc(data.pertanyaan) + '">',
                '<input type="hidden" name="' + dasar + '[tipe]" value="' + esc(data.tipe) + '">',
                '<input type="hidden" name="' + dasar + '[pembahasan]" value="' + esc(data.pembahasan) + '">',
                '<input type="hidden" name="' + dasar + '[tingkat_kesulitan]" value="' + esc(data.tingkat) + '">',
            );

            data.pilihan.forEach((p) => {
                bagian.push(
                    '<input type="hidden" name="' +
                        dasar +
                        "[pilihan][" +
                        esc(p.huruf) +
                        ']" value="' +
                        esc(p.teks) +
                        '">',
                );
            });

            data.benar.forEach((huruf) => {
                bagian.push('<input type="hidden" name="' + dasar + '[benar][]" value="' + esc(huruf) + '">');
            });

            if (data.tipe === TIPE.SINGKAT || data.tipe === TIPE.PARAGRAF) {
                bagian.push(
                    '<input type="hidden" name="' +
                        dasar +
                        '[jawaban_teks]" value="' +
                        esc(data.kunciTeks) +
                        '">',
                );

                bagian.push(
                    '<input type="hidden" name="' +
                        dasar +
                        '[tococok_persis]" value="' +
                        (data.ceklis ? "1" : "0") +
                        '">',
                );
            }
        });

        wadah.innerHTML = bagian.join("");
    }

    /* ============================================================
       AKSI
    ============================================================ */

    // Tambah soal.
    tombolTambah?.addEventListener("click", () => {
        const kartu = tambahSoal();

        gambarDaftar();
        $("[data-builder-pertanyaan]", kartu)?.focus();
        kartu.scrollIntoView({ block: "center", behavior: "smooth" });
    });

    // Semua aksi di dalam kartu.
    daftar.addEventListener("click", (event) => {
        const kartu = event.target.closest("[data-builder-soal]");

        if (!kartu) {
            return;
        }

        // Tambah pilihan.
        const tambahPilihan = event.target.closest("[data-builder-pilihan-tambah]");

        if (tambahPilihan) {
            const blok = blokAktif(kartu);
            const wadah = $("[data-builder-pilihan]", blok);
            const baris = barisPilihan(kartu);

            if (!wadah || baris.length >= MAKSIMAL_PILIHAN) {
                return;
            }

            const baru = barisPilihanBaru(kartu, Huruf(baris.length));

            wadah.appendChild(baru);
            nomorUlangPilihan(kartu);
            perbaruiBatasPilihan(kartu);
            $("[data-builder-pilihan-teks]", baru)?.focus();
            perbaruiRingkasan(kartu);

            return;
        }

        // Hapus pilihan.
        const hapusPilihan = event.target.closest("[data-builder-pilihan-hapus]");

        if (hapusPilihan) {
            const baris = hapusPilihan.closest("[data-builder-pilihan-baris]");

            if (barisPilihan(kartu).length <= MINIMAL_PILIHAN) {
                return;
            }

            baris.remove();
            nomorUlangPilihan(kartu);
            perbaruiBatasPilihan(kartu);
            perbaruiRingkasan(kartu);

            return;
        }

        // Duplikat soal.
        if (event.target.closest("[data-builder-duplikat]")) {
            /*
             * Disalin dari isi kartu, bukan dari node-nya, supaya atribut
             * data dan pendengar perambannya ikut terpasang untuk kartu baru.
             */
            const salinan = tambahSoal({ ...bacaKartu(kartu) });

            gambarDaftar();
            salinan.scrollIntoView({ block: "center", behavior: "smooth" });
            $("[data-builder-pertanyaan]", salinan)?.focus();

            return;
        }

        // Hapus soal.
        if (event.target.closest("[data-builder-hapus]")) {
            if (kartuSoal().length <= 1) {
                return;
            }

            kartu.remove();
            gambarDaftar();
        }
    });

    // Ganti tipe soal.
    daftar.addEventListener("change", (event) => {
        const kartu = event.target.closest("[data-builder-soal]");

        if (!kartu) {
            return;
        }

        if (event.target.matches("[data-builder-tipe]")) {
            // Pilihan lama disimpan supaya kembali ke tipe berdaftar tidak
            // memaksa pengguna mengetik ulang.
            const lama = bacaKartu(kartu);

            terapkanTipe(
                kartu,
                event.target.value,
                lama.pilihan.length ? lama.pilihan : null,
                lama.benar,
            );

            return;
        }

        if (event.target.matches("[data-builder-pilihan-teks]")) {
            perbaruiRingkasan(kartu);
        }
    });

    /* ============================================================
       PILIH TIPE (menu di kepala kartu)
    ============================================================
       Select tipenya tetap jadi pemegang nilai, hanya tampilannya yang
       diganti: sebuah tombol di kanan kepala kartu yang membuka daftar
       bertanda centang. Memilih baris menulis nilai ke select lalu
       mengirim event change yang sama, jadi seluruh logika mengganti
       tipe tidak perlu ditulis ulang. */

    function tutupSemuaMenuTipe() {
        $$("[data-builder-tipe-menu]", daftar).forEach((menu) => {
            if (menu.hidden) {
                return;
            }

            menu.hidden = true;

            const bungkus = menu.closest("[data-builder-tipe-pilih]");

            $("[data-builder-tipe-tombol]", bungkus)?.setAttribute("aria-expanded", "false");
        });
    }

    /** Isi menu dari opsi yang ada di select, supaya isinya tidak pernah beda. */
    function isiMenuTipe(kartu, menu) {
        const pilihTipe = $("[data-builder-tipe]", kartu);
        const aktif = kartu.dataset.tipe;

        if (!pilihTipe) {
            return;
        }

        menu.innerHTML = "";

        Array.from(pilihTipe.options).forEach((opsi) => {
            const nilai = opsi.value;
            const dipilih = nilai === aktif;
            const baris = document.createElement("li");

            baris.className = "tipe-pilih__opsi";
            // Pembungkus hanya wadah tata letak; peran "option" ada di
            // tombolnya supaya daftar dan isinya tidak tumpang tindih.
            baris.setAttribute("role", "presentation");

            const tombol = document.createElement("button");

            tombol.type = "button";
            tombol.className = "tipe-pilih__opsi-tombol" + (dipilih ? " is-aktif" : "");
            tombol.dataset.builderTipeNilai = nilai;
            tombol.setAttribute("role", "option");
            tombol.setAttribute("aria-selected", String(dipilih));

            const ikon = document.createElementNS("http://www.w3.org/2000/svg", "svg");

            ikon.setAttribute("class", "tipe-pilih__opsi-ikon");
            ikon.setAttribute("fill", "none");
            ikon.setAttribute("stroke", "currentColor");
            ikon.setAttribute("stroke-width", "1.8");
            ikon.setAttribute("viewBox", "0 0 24 24");
            ikon.setAttribute("aria-hidden", "true");

            const jalur = document.createElementNS("http://www.w3.org/2000/svg", "path");

            jalur.setAttribute("stroke-linecap", "round");
            jalur.setAttribute("stroke-linejoin", "round");
            jalur.setAttribute("d", IKON_TIPE[nilai] || IKON_TIPE[TIPE.GANDA]);
            ikon.appendChild(jalur);

            const teks = document.createElement("span");

            teks.textContent = opsi.textContent;

            tombol.append(ikon, teks);

            if (dipilih) {
                const cek = document.createElementNS("http://www.w3.org/2000/svg", "svg");

                cek.setAttribute("class", "tipe-pilih__cek");
                cek.setAttribute("fill", "none");
                cek.setAttribute("stroke", "currentColor");
                cek.setAttribute("stroke-width", "2.4");
                cek.setAttribute("viewBox", "0 0 24 24");
                cek.setAttribute("aria-hidden", "true");

                const jalurCek = document.createElementNS("http://www.w3.org/2000/svg", "path");

                jalurCek.setAttribute("stroke-linecap", "round");
                jalurCek.setAttribute("stroke-linejoin", "round");
                jalurCek.setAttribute("d", "m4.5 12.75 6 6 9-13.5");
                cek.appendChild(jalurCek);

                tombol.appendChild(cek);
            }

            baris.appendChild(tombol);
            menu.appendChild(baris);
        });
    }

    function pilihTipeDari(kartu, nilai) {
        const pilihTipe = $("[data-builder-tipe]", kartu);

        if (!pilihTipe || !nilai) {
            return;
        }

        tutupSemuaMenuTipe();

        if (pilihTipe.value === nilai) {
            $("[data-builder-tipe-tombol]", kartu)?.focus();
            return;
        }

        pilihTipe.value = nilai;
        pilihTipe.dispatchEvent(new Event("change", { bubbles: true }));
        $("[data-builder-tipe-tombol]", kartu)?.focus();
    }

    daftar.addEventListener("click", (event) => {
        const tombol = event.target.closest("[data-builder-tipe-tombol]");

        if (tombol) {
            const kartu = tombol.closest("[data-builder-soal]");
            const menu = $("[data-builder-tipe-menu]", kartu);

            if (!menu) {
                return;
            }

            const sudahTerbuka = !menu.hidden;

            tutupSemuaMenuTipe();

            if (sudahTerbuka) {
                return;
            }

            isiMenuTipe(kartu, menu);
            menu.hidden = false;
            tombol.setAttribute("aria-expanded", "true");

            return;
        }

        const opsi = event.target.closest("[data-builder-tipe-nilai]");

        if (opsi) {
            pilihTipeDari(opsi.closest("[data-builder-soal]"), opsi.dataset.builderTipeNilai);
        }
    });

    // Tutup menu begitu mengklik di luar kartu mana pun.
    document.addEventListener("click", (event) => {
        if (!event.target.closest("[data-builder-tipe-pilih]")) {
            tutupSemuaMenuTipe();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            tutupSemuaMenuTipe();
        }
    });

    // Tandai jawaban benar lewat lingkaran / kotak centang.
    daftar.addEventListener("change", (event) => {
        const kartu = event.target.closest("[data-builder-soal]");

        if (!kartu) {
            return;
        }

        const input = event.target.closest("[data-builder-pilihan-benar]");

        if (input) {
            const baris = input.closest("[data-builder-pilihan-baris]");

            if (input.type === "radio") {
                /*
                 * Radio mematikan saudaranya sendiri tanpa mengirim event,
                 * jadi lencana baris lama dibersihkan dari sini. Tanpa ini
                 * Pilihan Ganda bisa menampilkan dua baris bertanda
                 * "Jawaban Benar" padahal hanya satu lingkaran yang
                 * tercentang.
                 */
                const terpilih = baris;

                barisPilihan(kartu).forEach((barisLain) =>
                    tandaiBenar(barisLain, barisLain === terpilih),
                );
            } else {
                tandaiBenar(baris, input.checked);
            }

            perbaruiRingkasan(kartu);

            return;
        }

        // Dropdown: kuncinya dibaca dari select, bukan dari lingkaran.
        if (event.target.matches("[data-builder-pilih-benar]")) {
            perbaruiRingkasan(kartu);
        }
    });

    // Bersihkan penanda galat begitu pengguna mulai memperbaiki.
    daftar.addEventListener("input", (event) => {
        const kartu = event.target.closest("[data-builder-soal]");

        if (!kartu) {
            return;
        }

        if (event.target.matches("[data-builder-pertanyaan]")) {
            event.target.classList.remove("kolom-form--salah");
            tampilGalat($("[data-builder-galat='pertanyaan']", kartu), "");
            perbaruiHitung(kartu);
        }

        if (event.target.matches("[data-builder-pilihan-teks]")) {
            event.target.classList.remove("kolom-form--salah");
            perbaruiRingkasan(kartu);
        }

        if (event.target.matches("[data-builder-kunci-teks]")) {
            event.target.classList.remove("kolom-form--salah");
            tampilGalat($("[data-builder-galat='kunciTeks']", kartu), "");
        }
    });

    // Saklar "jawaban harus sama persis".
    daftar.addEventListener("click", (event) => {
        const saklar = event.target.closest("[data-builder-saklar]");

        if (!saklar) {
            return;
        }

        const aktif = saklar.getAttribute("aria-checked") !== "true";
        const kartu = saklar.closest("[data-builder-soal]");

        saklar.setAttribute("aria-checked", String(aktif));

        const input = $("[data-builder-tokok-persis]", kartu);

        if (input) {
            input.value = aktif ? "1" : "0";
        }
    });

    /* ============================================================
       URUTKAN SOAL (drag & drop)
    ============================================================ */

    let idSeret = null;

    daftar.addEventListener("dragstart", (event) => {
        const gaget = event.target.closest("[data-builder-seret]");
        const kartu = gaget?.closest("[data-builder-soal]");

        if (!kartu) {
            return;
        }

        // Kartu hanya boleh diseret dari gaget-nya, supaya teks di dalam
        // kartu tetap bisa diseleksi seperti biasa.
        event.preventDefault();

        idSeret = kartu;
        kartu.classList.add("is-seret");
        event.dataTransfer.effectAllowed = "move";

        try {
            event.dataTransfer.setData("text/plain", "soal");
        } catch {
            // Beberapa browser menolak setData tertentu; drag tetap jalan.
        }
    });

    daftar.addEventListener("dragover", (event) => {
        if (!idSeret) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
    });

    daftar.addEventListener("drop", (event) => {
        const kartu = event.target.closest("[data-builder-soal]");

        if (!kartu || !idSeret || kartu === idSeret) {
            return;
        }

        event.preventDefault();

        const kotak = kartu.getBoundingClientRect();
        const setelah = event.clientY > kotak.top + kotak.height / 2;

        // Kartunya dipindah langsung di DOM; urutan baris itulah yang
        // menentukan nomor dan urutan simpan ke database.
        if (setelah) {
            kartu.after(idSeret);
        } else {
            kartu.before(idSeret);
        }

        gambarDaftar();
    });

    daftar.addEventListener("dragend", () => {
        if (idSeret) {
            idSeret.classList.remove("is-seret");
        }

        idSeret = null;
    });

    /* ============================================================
       API UNTUK MODUL LAIN
    ============================================================ */

    /*
     * Beri tahu modul wizard (quiz-tambah.js) bahwa builder sudah siap, lalu
     * dua hal yang dibutuhkan wizard: memvalidasi seluruh soal sebelum
     * lanjut, dan menulis ulang input tersembunyi setiap kali daftar berubah.
     */
    window.kelasKitaQuizBuilder = {
        /** Berapa soal yang sekarang ada. */
        jumlah: () => kartuSoal().length,

        /** Periksa semua soal. Return true kalau semuanya lolos. */
        validasi() {
            let valid = true;

            kartuSoal().forEach((kartu) => {
                if (!validasiKartu(kartu)) {
                    valid = false;
                }
            });

            if (!valid) {
                const galat = $("[data-builder-galat]", kartuSoal().find((k) => k.querySelector(".kolom-form--salah")) || kartuSoal()[0]);

                galat?.scrollIntoView({ block: "center", behavior: "smooth" });
            }

            return valid;
        },

        /** Tulis ulang input tersembunyi milik form utama. */
        tulisInput,

        /**
         * Seluruh isi kartu, dalam bentuk yang sama dengan isian awal
         * (data-builder-awal). Dipakai wizard untuk menitipkan draf ke
         * sessionStorage, supaya memuat ulang halaman tidak menghapus soal
         * yang sedang disusun.
         */
        baca: () => kartuSoal().map(bacaKartu),

        /** Dipanggil wizard setiap kali panel builder dibuka. */
        segarkan: gambarDaftar,
    };

    /* ============================================================
       MULAI
    ============================================================ */

    /*
     * Soal awal dikirim Blade sebagai JSON di dalam blok script, bukan
     * dibaca ulang dari teks kartu, supaya pembahasan dan tingkat kesulitan
     * per soal yang sengaja tidak tampil di kartu tidak ikut hilang.
     */
    function muatAwal() {
        let awal = [];

        if (sumberAwal?.textContent) {
            try {
                const parsed = JSON.parse(sumberAwal.textContent);

                awal = Array.isArray(parsed) ? parsed : [];
            } catch {
                awal = [];
            }
        }

        if (awal.length === 0) {
            // Quiz baru: satu soal kosong supaya bisa langsung diisi.
            tambahSoal();
        } else {
            awal.forEach((item) => {
                /*
                 * Pertanyaan yang masih kosong tetap dibuat kartunya.
                 * Isian setengah jadi dari draf harus kembali ke layar saat
                 * halaman dimuat ulang, bukan hilang diam-diam — dan untuk
                 * kiriman server yang gagal validasi, galatnya justru menunjuk
                 * baris yang baris itu.
                 */
                if (item && typeof item === "object") {
                    tambahSoal(item);
                }
            });

            if (kartuSoal().length === 0) {
                tambahSoal();
            }
        }

        gambarDaftar();
    }

    muatAwal();

    // Gallat dari server (soal yang gagal disimpan) membuka langkah builder.
    if (galatDaftar && galatDaftar.textContent.trim()) {
        galatDaftar.hidden = false;
    }
}
