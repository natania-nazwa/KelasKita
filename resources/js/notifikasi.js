/*
 * Lonceng notifikasi di kepala halaman user.
 *
 * Dua hal yang dikerjakan, keduanya soal satu hal yang sama: kapan sebuah
 * notifikasi dihitung sudah dibaca.
 *
 * Pertama, begitu panel lonceng dibuka, semua notifikasi yang ada ditandai
 * terbaca. Alasannya sederhana: orang yang membuka lonceng sedang melihat
 * daftarnya, jadi membiarkan titiknya menyala hanya karena satu baris belum
 * diklik akan membuatnya selalu terasa belum dibaca. Efeknya titik hilang
 * sampai notifikasi baru datang — itulah satu-satunya kejadian yang menambah
 * notifikasi, jadi itulah satu-satunya yang menyalakan titik lagi.
 *
 * Kedua, satu baris yang diklik menandai dirinya sendiri. Ini menutup jalur
 * notifikasi yang baru datang setelah panel ditutup, dan tetap bekerja
 * walau panelnya sedang tidak dibuka.
 *
 * Penandaan dikirim sebagai fetch memakai attribute data-* pada markup, jadi
 * tidak ada satu pun markup yang harus diubah server untuk setiap jenis
 * notifikasi.
 *
 * Tanpa JavaScript notifikasi tetap bisa dibaca dan tautannya tetap
 * bekerja; hanya tandanya yang belum hilang. Itu degrade yang wajar: isi
 * notifikasi sudah benar di HTML, tidak ada yang hilang.
 *
 * Kepala halaman dirender DUA KALI — top bar untuk desktop dan header
 * mobile untuk layar kecil — karena server tidak tahu viewport yang akan
 * dipakai. Karena itu:
 *
 *   - semua instance dijalankan, bukan hanya yang pertama; kalau cuma
 *     querySelector dipakai, lonceng yang tampil di ponsel tidak pernah
 *     terpasang listener-nya karena top bar (yang tersembunyi lewat CSS)
 *     selalu datang lebih dulu di DOM;
 *   - titik dan tanda "belum dibaca" disinkronkan lintas instance, supaya
 *     memutar layar tidak menghidupkan lagi tanda yang barusan hilang.
 *
 * Berhenti sendiri kalau halaman ini tidak memakai lonceng sama sekali
 * (lihat $sembunyiTopbar di layouts/app).
 */

document.querySelectorAll("[data-notif]").forEach(initNotifikasi);

function initNotifikasi(akar) {
    /**
     * Ambil seluruh baris yang menunjuk ke notifikasi yang sama, di
     * seluruh dokumen — termasuk kembarannya di kepala halaman lain.
     *
     * Filter dipakai, bukan selector atribut, karena isinya URL yang
     * bisa saja mengandung tanda kutip.
     */
    const kembaran = (alamat) =>
        Array.from(document.querySelectorAll("[data-notif-baca]")).filter(
            (el) => el.dataset.notifBaca === alamat
        );

    /**
     * Kirim penandaan terbaca dan kembalikan sisa yang belum dibaca, atau
     * null kalau server menolak atau jawabannya bukan JSON.
     *
     * keepalive dipakai supaya request tetap terkirim walau halaman
     * ditinggalkan di tengah jalan; pemanggilnya yang memutuskan mau
     * pindah halaman atau tidak.
     */
    const kirim = (alamat) =>
        fetch(alamat, {
            method: "POST",
            keepalive: true,
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
            body: "{}",
        }).then((res) => (res.ok ? res.json() : null));

    /**
     * Sembunyikan titik di SEMUA lonceng ketika tidak ada lagi
     * yang belum dibaca.
     */
    const perbaruiTitik = (sisa) => {
        if (typeof sisa !== "number" || sisa > 0) {
            return;
        }

        document
            .querySelectorAll("[data-notif-titik]")
            .forEach((titik) => titik.remove());
    };

    /**
     * Lepas tanda "belum dibaca" dari sekumpulan baris, dan kembalikan
     * baris-baris itu supaya nilainya bisa dipulihkan kalau ternyata
     * penandaannya tidak sampai ke server.
     */
    const lepasTanda = (baris) => {
        baris.forEach((el) => el.classList.remove("is-belum"));
    };

    const pulihkanTanda = (baris) => {
        baris.forEach((el) => el.classList.add("is-belum"));
    };

    /**
     * Kosongkan tanda "belum dibaca" di seluruh lonceng — dipakai saat panel
     * dibuka, karena yang tersisa tinggal milik notifikasi baru saja.
     */
    const kosongkanSemua = () =>
        Array.from(document.querySelectorAll("[data-notif-item].is-belum"));

    /*
     * Panel dibuka: semua yang ada di dalamnya sudah dilihat, jadi semuanya
     * ditandai terbaca dan titiknya ikut hilang.
     *
     * Yang didengarkan adalah klik pada <summary>, bukan event "toggle" milik
     * <details>. Event itu masih baru: Safari baru mendukungnya sejak 26.5,
     * dan Chrome, Edge, serta Firefox juga baru menyusul di versi terbaru.
     * Di peramban yang lebih tua penandaan ini tidak akan pernah jalan sama
     * sekali. Klik pada <summary> tidak punya masalah itu, dan tetap jalan
     * juga saat panel dibuka lewat keyboard.
     *
     * `open` dibaca lebih dulu karena browser baru membuka panelnya setelah
     * listener ini selesai. Jadi nilainya masih milik keadaan sebelumnya:
     * kalau panelnya sudah terbuka, yang sedang terjadi adalah penutupan dan
     * tidak ada yang perlu ditandai.
     */
    const tandaiSemuaTerbaca = () => {
        const alamat = akar.dataset.notifSemua;

        if (akar.open || !alamat) {
            return;
        }

        const menyala = kosongkanSemua();

        // Tidak ada yang belum dibaca: membuka lonceng tidak perlu
        // membanjiri server dengan permintaan yang tidak mengubah apa pun.
        if (
            menyala.length === 0 &&
            !document.querySelector("[data-notif-titik]")
        ) {
            return;
        }

        lepasTanda(menyala);

        kirim(alamat)
            .then((data) => perbaruiTitik(data?.sisa))
            .catch(() => pulihkanTanda(menyala));
    };

    akar
        .querySelector("summary")
        ?.addEventListener("click", tandaiSemuaTerbaca);

    akar.addEventListener("click", (event) => {
        // Modifier atau tombol selain klik kiri = membuka di tab lain, bukan
        // membuka notifikasi ini, jadi tandanya tidak boleh ikut berubah.
        if (
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey ||
            event.button !== 0
        ) {
            return;
        }

        const baris = event.target.closest("[data-notif-item]");

        if (!baris) {
            return;
        }

        // Hanya yang belum dibaca yang perlu ditandai; yang sudah dibaca
        // tidak akan mengubah apa pun di server.
        if (!baris.classList.contains("is-belum")) {
            return;
        }

        const alamat = baris.dataset.notifBaca;

        if (!alamat) {
            return;
        }

        /*
         * Baris yang masih punya tautan harus langsung membuka halaman
         * tujuannya, sedangkan halaman tujuan dirender ulang dari database.
         * Kalau navigasi boleh berjalan duluan, penandaan bisa kalah cepat
         * dan titik lonceng masih menyala di halaman yang baru dibuka. Jadi
         * navigasi ditahan sampai server mengonfirmasi, lalu dikirim ulang
         * ke alamat aslinya.
         *
         * Notifikasi tanpa tautan (kontennya sudah dihapus) tidak punya
         * tujuan, jadi tidak ada yang perlu ditahan.
         */
        const tujuan = baris.tagName === "A" ? baris.getAttribute("href") : null;

        if (tujuan) {
            event.preventDefault();
        }

        const semua = kembaran(alamat);

        lepasTanda(semua);

        kirim(alamat)
            .then((data) => perbaruiTitik(data?.sisa))
            .catch(() => pulihkanTanda(semua))
            .finally(() => {
                if (tujuan) {
                    window.location.assign(tujuan);
                }
            });
    });
}