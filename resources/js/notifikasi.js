/*
 * Lonceng notifikasi di top bar halaman user.
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
 * Berhenti sendiri kalau top bar halaman ini tidak ada.
 */

const LONTECENG = document.querySelector("[data-notif]");

if (LONTECENG) {
    initNotifikasi(LONTECENG);
}

function initNotifikasi(akar) {
    const titik = akar.querySelector("[data-notif-titik]");

    /**
     * Kurangi angka notifikasi yang belum dibaca, lalu sembunyikan titiknya
     * kalau sudah tidak ada.
     */
    const perbaruiSisa = (sisa) => {
        if (typeof sisa !== "number") {
            return;
        }

        if (sisa > 0) {
            return;
        }

        titik?.remove();
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

        baris.classList.remove("is-belum");

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
            .then((data) => perbaruiSisa(data?.sisa))
            .catch(() => {
                // Jaringan gagal atau server menolak: tandanya dikembalikan
                // supaya tidak hilang notifikasi yang sebenarnya belum
                // ditandai terbaca.
                baris.classList.add("is-belum");
            });
    });
}
