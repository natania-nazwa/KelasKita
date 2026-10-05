/*
 * Lonceng notifikasi di kepala halaman user.
 *
 * Yang dikerjakan satu hal saja: begitu satu notifikasi diklik, tandanya
 * langsung hilang tanpa menunggu halaman tujuan selesai dimuat.
 *
 * Penandaan dikirim sebagai fetch ke "user.notifikasi.baca" memakai
 * attribute data-notif-baca pada barisnya, jadi tidak ada satu pun markup
 * yang harus diubah server untuk setiap jenis notifikasi.
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
     * Sembunyikan titik merah di SEMUA lonceng ketika tidak ada lagi
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

    akar.addEventListener("click", (event) => {
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

        const semua = kembaran(alamat);
        semua.forEach((el) => el.classList.remove("is-belum"));

        fetch(alamat, {
            method: "POST",
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
        })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => perbaruiTitik(data?.sisa))
            .catch(() => {
                // Jaringan gagal atau server menolak: tandanya dikembalikan
                // supaya tidak hilang notifikasi yang sebenarnya belum
                // ditandai terbaca.
                semua.forEach((el) => el.classList.add("is-belum"));
            });
    });
}
