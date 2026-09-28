// Halaman sesi quiz: polling lobby, salin kode, dialog konfirmasi, dan
// tombol lanjut di halaman soal.
// Semua bagian berhenti sendiri kalau elemennya tidak ada di halaman ini,
// jadi modul ini aman di-import dari app.js untuk semua halaman.
const INTERVAL_POLL = 3000;
const KELAS_BARU = "lobi-daftar__item--baru";

/**
 * Polling lobby.
 *
 * Aplikasi ini tidak memakai WebSocket, jadi lobby diperbarui dengan
 * mengambil JSON kecil dari user.sesi.data setiap beberapa detik. Yang
 * dipantau cuma dua hal:
 *
 *   - status sesi berubah -> peserta langsung pindah ke soal pertama,
 *     atau ke halaman hasil kalau quiz sudah ditutup;
 *   - daftar peserta berubah -> nama yang baru masuk ditambahkan dengan
 *     animasi, jumlah peserta dihitung ulang.
 *
 * Polling dihentikan sementara saat tab disembunyikan supaya tidak
 * menguras baterai, dan langsung ditembakkan sekali lagi saat tab
 * dikembalikan supaya peserta tidak menunggu polling berikutnya.
 */
function initPolling() {
    const akar = document.querySelector("[data-lobi]");

    if (!akar) {
        return;
    }

    const url = akar.dataset.tautanStatus;
    const tautanSoal = akar.dataset.tautanSoal;
    const tautanHasil = akar.dataset.tautanHasil;

    if (!url) {
        return;
    }

    let statusAwal = "";
    let sedangJalan = false;
    let sidikDaftar = "";

    const badgeStatus = document.querySelector("[data-lobi-status]");
    const teksStatus = document.querySelector("[data-lobi-status-teks]");
    const chipHitung = document.querySelector("[data-lobi-hitung]");
    const teksHitung = document.querySelector("[data-lobi-hitung-teks]");
    const wadah = document.querySelector("[data-lobi-daftar]");

    /** id peserta yang sudah ada di layar, supaya bisa dibedakan "baru". */
    const idTampil = new Set(
        Array.prototype.map.call(
            document.querySelectorAll("[data-lobi-peserta]"),
            (el) => el.dataset.lobiPeserta,
        ),
    );

    const barisPeserta = (orang, baru) => {
        const baris = document.createElement("div");
        // Hanya baris yang benar-benar baru yang dapat animasi masuk.
        // Kalau semua baris selalu diberi kelas ini, animasinya akan
        // diputar ulang tiap tiga detik dan terlihat berkedip.
        baris.className = baru
            ? `lobi-daftar__item ${KELAS_BARU}`
            : "lobi-daftar__item";
        baris.dataset.lobiPeserta = orang.id;
        baris.setAttribute("role", "listitem");

        // Nama ditulis lewat textContent, bukan innerHTML, supaya nama yang
        // mengandung tanda kutip atau tag tidak diurai sebagai HTML.
        const avatar = document.createElement("span");
        avatar.className = "lobi-daftar__avatar";
        avatar.style.setProperty("--a", orang.warna);
        avatar.style.setProperty("--a-gelap", orang.warna_gelap);
        avatar.setAttribute("aria-hidden", "true");
        avatar.textContent = orang.inisial;

        const nama = document.createElement("span");
        nama.className = "lobi-daftar__nama";
        nama.title = orang.nama;
        nama.textContent = orang.nama;

        const status = document.createElement("span");
        status.className = "lobi-daftar__status";
        status.dataset.lobiPesertaStatus = "";
        status.textContent = orang.status_label;

        baris.append(avatar, nama, status);

        if (orang.bergabung_pada) {
            const waktu = document.createElement("span");
            waktu.className = "lobi-daftar__waktu";
            waktu.textContent = orang.bergabung_pada;

            baris.append(waktu);
        }

        return baris;
    };

    /**
     * Sidik jari daftar peserta.
     *
     * Dipakai untuk melewati penggambaran ulang kalau isinya sama persis
     * dengan respons sebelumnya. Tanpa ini, polling yang berjalan tiap tiga
     * detik akan terus membangun ulang DOM: pilihan teks ikut hilang, posisi
     * scroll daftar kembali ke atas, dan fokus keyboard kehilangan tempatnya.
     */
    const sidik = (daftar) =>
        daftar
            .map(
                (orang) =>
                    `${orang.id}|${orang.nama}|${orang.status_label}|${orang.bergabung_pada}`,
            )
            .join(";");

    const gambarDaftar = (daftar) => {
        if (!wadah) {
            return;
        }

        const sidikBaru = sidik(daftar);

        if (sidikBaru === sidikDaftar) {
            return;
        }

        sidikDaftar = sidikBaru;

        // Daftar peserta satu sesi kecil, jadi menggambar ulang lebih
        // sederhana daripada mencocokkan tiap baris satu per satu.
        wadah.textContent = "";

        if (!daftar.length) {
            const kosong = document.createElement("p");
            kosong.className = "lobi-daftar__kosong";
            kosong.dataset.lobiDaftarKosong = "";
            kosong.textContent = "Belum ada peserta yang bergabung.";

            wadah.append(kosong);
            idTampil.clear();

            return;
        }

        const fragmen = document.createDocumentFragment();

        daftar.forEach((orang) => {
            const kunci = String(orang.id);

            fragmen.append(barisPeserta(orang, !idTampil.has(kunci)));
            idTampil.add(kunci);
        });

        wadah.append(fragmen);
    };

    const gambarJumlah = (jumlah) => {
        if (chipHitung) {
            chipHitung.dataset.jumlah = String(jumlah);
            // Abu-abu saat masih kosong, ungu begitu ada yang masuk.
            chipHitung.classList.toggle("lobi-hitung--sepi", jumlah === 0);
        }

        if (teksHitung) {
            teksHitung.textContent = `${jumlah} peserta bergabung`;
        }
    };

    const terapkanStatus = (data) => {
        if (teksStatus) {
            teksStatus.textContent = data.status_label;
        }

        if (badgeStatus) {
            badgeStatus.classList.toggle(
                "lobi-status--menunggu",
                data.status === "waiting",
            );
            badgeStatus.classList.toggle(
                "lobi-status--berjalan",
                data.status !== "waiting",
            );
        }
    };

    const alih = (data) => {
        if (data.status === "finished") {
            window.location.href = data.tautan_hasil || tautanHasil;

            return true;
        }

        if (data.status === "started" && !data.adalah_host) {
            window.location.href = data.tautan_soal || tautanSoal;

            return true;
        }

        return false;
    };

    const tarik = async () => {
        if (sedangJalan || document.hidden) {
            return;
        }

        sedangJalan = true;

        try {
            const respons = await fetch(url, {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });

            if (!respons.ok) {
                return;
            }

            const data = await respons.json();

            gambarJumlah(data.jumlah_peserta);
            gambarDaftar(data.peserta || []);
            terapkanStatus(data);

            // Perubahan status baru dialihkan ke halaman lain. Status
            // pertama hanya dicatat, karena halaman memang sedang dibuka
            // dengan status itu.
            if (statusAwal === "") {
                statusAwal = data.status;

                return;
            }

            if (data.status !== statusAwal) {
                alih(data);
            }
        } catch {
            // Jaringan putus sebentar: coba lagi di siklus berikutnya.
        } finally {
            sedangJalan = false;
        }
    };

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            tarik();
        }
    });

    window.setInterval(tarik, INTERVAL_POLL);

    // Host yang menekan "Mulai Quiz" sudah diarahkan balik ke lobby oleh
    // server, jadi status di markup sudah benar. Polling pertama tetap
    // ditembakkan supaya daftar peserta langsung lengkap.
    tarik();
}

/**
 * Tombol "Salin Kode" di lobby host.
 *
 * Mengikuti perilaku tombol salin di blok kode materi: label berubah
 * sebentar setelah berhasil, lalu kembali sendiri.
 */
function initSalinKode() {
    const tombol = document.querySelector("[data-lobi-salin]");

    if (!tombol) {
        return;
    }

    const kode = document
        .querySelector("[data-lobi-kode]")
        ?.textContent?.trim();

    if (!kode) {
        return;
    }

    const label = tombol.querySelector("[data-lobi-salin-teks]");
    const tulis = (tersalin) => {
        if (label) {
            label.textContent = tersalin ? "Tersalin!" : "Salin Kode";
        }

        tombol.dataset.tersalin = String(tersalin);
    };

    tombol.addEventListener("click", async () => {
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
            tulis(false);

            return;
        }

        tulis(true);
        window.setTimeout(() => tulis(false), 1800);
    });
}

/**
 * Dialog konfirmasi aksi sesi (Mulai / Akhiri / Selesai).
 *
 * Form yang perlu konfirmasi menandai diri dengan
 * data-lobi-konfirmasi-judul. Tanpa JavaScript form tetap dikirim
 * langsung, karena mengonfirmasi adalah kenyamanan, bukan syarat.
 */
function initDialog() {
    const dialog = document.querySelector("[data-lobi-dialog]");

    if (!dialog) {
        return;
    }

    const judul = dialog.querySelector("[data-lobi-dialog-judul]");
    const pesan = dialog.querySelector("[data-lobi-dialog-pesan]");
    const labelTombol = dialog.querySelector("[data-lobi-dialog-tombol]");
    const tombolBatal = dialog.querySelector("[data-lobi-dialog-batal]");
    const tombolYa = dialog.querySelector("[data-lobi-dialog-ya]");

    let form = null;

    const tutup = () => {
        dialog.classList.remove("is-buka");
        form = null;
    };

    document
        .querySelectorAll("[data-lobi-konfirmasi-judul]")
        .forEach((penanda) => {
            penanda.addEventListener("submit", (event) => {
                event.preventDefault();
                form = penanda;

                if (judul) {
                    judul.textContent = penanda.dataset.lobiKonfirmasiJudul || "";
                }

                if (pesan) {
                    pesan.textContent = penanda.dataset.lobiKonfirmasiPesan || "";
                }

                if (labelTombol) {
                    labelTombol.textContent =
                        penanda.dataset.lobiKonfirmasiTombol || "Ya, Lanjutkan";
                }

                dialog.classList.add("is-buka");
                tombolBatal?.focus();
            });
        });

    tombolBatal?.addEventListener("click", tutup);

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

/**
 * Tombol "Simpan & Lanjut" di halaman soal.
 *
 * Tombolnya sengaja tidak punya atribut disabled di markup, supaya tetap
 * berguna tanpa JavaScript. Setelah halaman siap, tombol dinonaktifkan
 * sampai ada pilihan yang dicentang; kalau tidak ada jawaban, server
 * yang mengembalikan pesan kesalahannya.
 */
function initPilihJawaban() {
    const halaman = document.querySelector("[data-soal]");

    if (!halaman) {
        return;
    }

    const tombol = halaman.querySelector("[data-soal-lanjut]");
    const pilihan = halaman.querySelectorAll('input[type="radio"][name="jawaban"]');

    if (!tombol || !pilihan.length) {
        return;
    }

    const perbarui = () => {
        tombol.disabled = !halaman.querySelector(
            'input[type="radio"][name="jawaban"]:checked',
        );
    };

    pilihan.forEach((radio) => radio.addEventListener("change", perbarui));
    perbarui();
}

initPolling();
initSalinKode();
initDialog();
initPilihJawaban();
