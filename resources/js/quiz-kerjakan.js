// Halaman mengerjakan soal (resources/views/user/quiz-kerjakan.blade.php).
//
// Tiga hal yang tidak bisa dikerjakan di server:
//   1. hitung mundur timer, karena harus bergerak tiap detik;
//   2. pemeriksaan isian sebelum form dikirim, supaya peserta tidak
//      sempat menekan tombol lalu baru tahu jawabannya kosong;
//   3. buka/tutup dialog daftar soal, karena isinya perlu fokus dan
//      pemindahan fokus harus baru terjadi ketika dialognya benar-benar
//      terbuka.
//
// Kelima tipe soal memakai isian yang berbeda (radio, checkbox, select,
// input teks, textarea), jadi pemeriksaan di bawah sengaja membaca
// bentuk isiannya, bukan nama field-nya. Satu jalur untuk semua tipe
// membuat halaman ini tidak perlu punya daftar perbandingan per tipe.
//
// Semuanya berhenti sendiri kalau elemennya tidak ada di halaman ini,
// jadi modul ini aman di-import dari app.js untuk semua halaman.

/** Nama nilai data-sisa-level untuk dua keadaan peringatan timer. */
const LEVEL_SEDIKIT = "sedikit";
const LEVEL_MENDEKUTI = "mendekuti";

/** Kalimat yang sama persis dengan pesan validasi di SesiKerjakanController. */
const PESAN_BELUM_DIJAWAB = "Silakan pilih atau isi jawaban terlebih dahulu.";

/**
 * Timer quiz.
 *
 * Sisa waktu dikirim server lewat data-sisa, dalam detik, dan dihitung dari
 * saat pengerjaan dimulai. Karena itu:
 *   - memuat ulang halaman tidak mengulang waktu dari awal;
 *   - jam di perangkat peserta yang salah beberapa detik tidak berpengaruh
 *     besar, karena JavaScript memakai jam prosesnya sendiri untuk menghitung
 *     mundur, bukan mengurangi satu per tick.
 *
 * Waktu habis tidak dihitung di sini. Yang menutup pengerjaan tetap form
 * "selesai" milik server, jadi penilaiannya sama persis dengan selesai
 * manual. Bedanya cuma bentuknya: yang ditunjukkan di sini adalah dialog
 * "Waktu Anda Habis" yang mengunci halaman, dan peserta sendiri yang
 * menekan tombolnya. Sebelumnya halaman langsung pindah begitu hitungan
 * mencapai 00:00, jadi peserta tidak pernah tahu kenapa soalnya berhenti.
 */
function initTimer() {
    const jam = document.querySelector("[data-soal-timer]");

    if (!jam) {
        return;
    }

    const teks = jam.querySelector("[data-soal-timer-teks]");
    const pengirim = document.querySelector("[data-soal-waktu-habis]");
    const halaman = document.querySelector("[data-soal-halaman]");
    const dialog = document.querySelector("[data-soal-dialog-habis]");
    const tombolDialog = document.querySelector("[data-soal-dialog-habis-tombol]");
    const sedikit = Number(jam.dataset.sedikit || 300);
    const mendekati = Number(jam.dataset.mendekuti || 60);

    let sisa = Number(jam.dataset.sisa || 0);
    let sudahTutup = false;

    const tulis = () => {
        const total = Math.max(0, sisa);

        if (teks) {
            teks.textContent = `${String(Math.floor(total / 60)).padStart(2, "0")}:${String(
                total % 60,
            ).padStart(2, "0")}`;
        }

        // Ambang warnanya sama dengan yang dipakai server saat merender
        // warna awal, jadi tidak ada kedipan warna di detik pertama.
        if (sisa <= mendekati) {
            jam.dataset.sisaLevel = LEVEL_MENDEKUTI;
        } else if (sisa <= sedikit) {
            jam.dataset.sisaLevel = LEVEL_SEDIKIT;
        } else {
            delete jam.dataset.sisaLevel;
        }
    };

    /*
     * Waktu habis: kunci halaman dulu, baru tampilkan dialognya.
     *
     * Halaman dikunci dua lapis. Yang pertama atribut inert, yang membuat
     * seluruh isian dan tombol di dalamnya tidak bisa diklik maupun
     * difokuskan. Yang kedua menonaktifkan semua kendali formulir, karena
     * peramban lama tidak mengenal inert dan tombol yang sudah ditekan
     * masih bisa mengirim form sebelum dialognya selesai tampil.
     *
     * "Selanjutnya" ikut disebut meski letaknya di luar form jawaban, jadi
     * selector-nya tidak bisa hanya mengandalkan [data-soal-form].
     *
     * Dialog sengaja tidak punya tombol batal, tidak menutup saat Escape
     * ditekan, dan tidak menutup saat area gelap diklik. Ketiganya berarti
     * "lanjut mengerjakan", dan justru itu yang tidak boleh terjadi di
     * sini. Satu-satunya jalan keluar adalah tombolnya.
     */
    const tutup = () => {
        if (sudahTutup) {
            return;
        }

        sudahTutup = true;

        /*
         * Dialog daftar soal ditutup lebih dulu. Elemennya sengaja berada
         * di luar elemen yang dikunci, jadi tanpa baris ini daftar soal
         * masih terbuka di atas dialog "Waktu Anda Habis" dan menutupi
         * satu-satunya jalan keluar dari halaman ini.
         */
        document
            .querySelector("[data-soal-nav-dialog]")
            ?.classList.remove("is-buka");

        if (halaman) {
            halaman.setAttribute("inert", "");
            halaman.setAttribute("aria-hidden", "true");
        }

        document
            .querySelectorAll(
                "[data-soal-form] input, [data-soal-form] select, [data-soal-form] textarea, [data-soal-form] button, [data-soal-lanjut]",
            )
            .forEach((el) => {
                el.disabled = true;
            });

        // Kalau dialog somehow tidak ada (mis. markup-nya masih versi
        // lama), tetap tutup pengerjaannya supaya tidak menggantung di
        // luar waktunya.
        if (!dialog) {
            pengirim?.submit();

            return;
        }

        dialog.classList.add("is-buka");
        tombolDialog?.focus();
    };

    tombolDialog?.addEventListener("click", () => {
        pengirim?.submit();
    });

    const tick = () => {
        sisa -= 1;

        if (sisa <= 0) {
            sisa = 0;
            tulis();
            tutup();

            return;
        }

        tulis();
    };

    tulis();

    // Halaman yang dirender server dengan sisa waktu 0 — peserta membuka
    // ulang soal setelah waktunya habis — tidak perlu menunggu satu detik
    // pertama supaya dialognya muncul.
    if (sisa <= 0) {
        tutup();

        return;
    }

    window.setInterval(tick, 1000);
}

/**
 * Pemeriksaan isian sebelum form dikirim.
 *
 * Tombol "Selanjutnya" sengaja tidak punya atribut disabled di markup
 * supaya tetap berguna tanpa JavaScript. Di sini tombolnya baru dinonaktifkan
 * setelah halaman siap, dan hanya selama isiannya kosong. Kalau peserta
 * menekan tombolnya tanpa isian, form tidak terkirim dan pesannya muncul di
 * dalam kartu, jadi mereka tidak sempat berpindah soal baru tahu
 * jawabannya kosong.
 *
 * Tombol dicari di seluruh dokumen, bukan di dalam form: di markup ia
 * berada di luar form jawaban (karena "Ragu" di tengahnya adalah form
 * sendiri, dan HTML tidak boleh punya form di dalam form). Tombol itu
 * tetap mengirim form jawaban lewat atribut form="soal-jawab", jadi
 * hubungan keduanya tidak berubah — hanya tempat mencarinya.
 */
function initIsian() {
    const form = document.querySelector("[data-soal-form]");

    if (!form) {
        return;
    }

    const tombol = document.querySelector("[data-soal-lanjut]");
    const galat = form.querySelector("[data-soal-galat]");
    const isian = form.querySelectorAll(
        'input[type="radio"], input[type="checkbox"], input[type="text"], select, textarea',
    );

    if (!tombol || !isian.length) {
        return;
    }

    // Halaman yang sudah dirender server dengan pesan galat tidak boleh
    // ikut disembunyikan di sini: pesannya tetap harus terbaca sampai
    // peserta mengisi isiannya.
    const galatDariServer = Boolean(galat && !galat.hidden);

    const terisi = (el) => {
        if (el.type === "checkbox" || el.type === "radio") {
            return el.checked;
        }

        return String(el.value ?? "").trim() !== "";
    };

    const adaIsian = () => Array.prototype.some.call(isian, terisi);

    const perbarui = () => {
        tombol.disabled = !adaIsian();

        if (adaIsian() && galat && !galatDariServer) {
            galat.hidden = true;
        }
    };

    const tolak = (event) => {
        if (adaIsian()) {
            return;
        }

        /*
         * stopImmediatePropagation(), bukan hanya preventDefault(): di soal
         * terakhir form ini juga memakai dialog konfirmasi dari
         * quiz-lobby.js, dan listener di situ harus ikut berhenti. Kalau
         * tidak, isian yang masih kosong akan tetap membuka dialog
         * "Selesaikan Quiz?".
         *
         * Modul ini di-import sebelum quiz-lobby.js karena itu listener di
         * sini yang harus menang.
         */
        event.preventDefault();
        event.stopImmediatePropagation();

        if (galat) {
            galat.textContent = PESAN_BELUM_DIJAWAB;
            galat.hidden = false;
        }

        // Fokus ke isian pertama supaya peserta yang memakai keyboard
        // langsung bisa menjawab tanpa menebak ke mana harus pindah.
        isian[0].focus();
    };

    isian.forEach((el) => {
        el.addEventListener("change", perbarui);
        el.addEventListener("input", perbarui);
    });

    form.addEventListener("submit", tolak);

    perbarui();
}

/**
 * Dialog daftar soal.
 *
 * Yang membuat dialog ini bisa berdiri sendiri cuma tiga hal: kelas
 * "is-buka" untuk menampilkannya (sama persis dengan dialog hapus bab dan
 * dialog konfirmasi sesi), satu handler Escape, dan satu handler klik di
 * area gelap. Kotak nomor di dalamnya bukan tombol JavaScript, tapi tautan
 * biasa ke halaman soal itu, jadi daftar soal tetap berguna tanpa
 * JavaScript — yang hilang tanpa JavaScript cuma cara membukanya.
 *
 * Fokus dipindah ke tombol tutup saat terbuka dan dikembalikan ke tombol
 * pembuka saat ditutup. Tanpa itu, peserta yang memakai keyboard akan
 * terus fokus di tombol pembuka di belakang dialog dan tidak pernah tahu
 * bahwa isinya sudah terbuka.
 */
function initNavigasi() {
    const buka = document.querySelector("[data-soal-nav-buka]");
    const dialog = document.querySelector("[data-soal-nav-dialog]");

    if (!buka || !dialog) {
        return;
    }

    const tutup = () => {
        dialog.classList.remove("is-buka");
        buka.focus();
    };

    buka.addEventListener("click", () => {
        dialog.classList.add("is-buka");
        dialog.querySelector("[data-soal-nav-tutup]")?.focus();
    });

    // Dua tombol tutup: silang di kanan judul dan "Tutup" di bawah.
    dialog.querySelectorAll("[data-soal-nav-tutup]").forEach((tombol) => {
        tombol.addEventListener("click", tutup);
    });

    // Klik tepat pada area gelap, bukan pada kotak dialog di atasnya.
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

initTimer();
initIsian();
initNavigasi();
