/*
 * Halaman Masuk (login) dan Daftar (register): tombol mata pada kolom
 * kata sandi dan konfirmasi kata sandi.
 *
 * Sama seperti tombol mata di halaman Profil dan Pengaturan, tombol ini
 * hanya mengubah atribut type milik kolom di sekitarnya antara "password"
 * dan "text". Isi kata sandi tidak pernah dibaca, disalin, atau disimpan
 * di mana pun; seluruh pemeriksaan tetap dilakukan di server.
 */

function initLihatSandi() {
    document.querySelectorAll("[data-lihat-sandi]").forEach((tombol) => {
        tombol.addEventListener("click", () => {
            const kolom = tombol.parentElement.querySelector(
                "input[type='password'], input[type='text']",
            );

            if (!kolom) {
                return;
            }

            const tampil = kolom.type === "password";

            kolom.type = tampil ? "text" : "password";
            tombol.setAttribute("aria-pressed", tampil ? "true" : "false");
            tombol.setAttribute(
                "aria-label",
                tampil ? "Sembunyikan kata sandi" : "Tampilkan kata sandi",
            );

            // Dua ikon berbagi satu tombol; yang mana yang terlihat
            // ditentukan lewat kelas "hidden" milik Tailwind.
            const terbuka = tombol.querySelector("[data-mata-terbuka]");
            const tertutup = tombol.querySelector("[data-mata-tertutup]");

            if (terbuka) {
                terbuka.classList.toggle("hidden", !tampil);
            }

            if (tertutup) {
                tertutup.classList.toggle("hidden", tampil);
            }
        });
    });
}

initLihatSandi();
