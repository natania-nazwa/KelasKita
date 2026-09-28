<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIK
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing.index');
})->name('landing');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
|
| Sengaja TANPA middleware "guest".
|
| dulu route ini memakai "guest", jadi orang yang sudah login ikut
| dilempar ke "/" (landing page) ketika menekan tombol "Login/Daftar".
| Akibatnya tombol itu terlihat tidak bekerja, dan tidak pernah sampai
| ke halaman login.
|
| Sekarang /login dan /register selalu menampilkan formnya:
|   landing page -> /login -> (link "Daftar") -> /register
| lalu setelah berhasil baru redirect ke dashboard sesuai peran.
|
| Mengirim form login While sudah login diperbolehkan: sesi akan
| berpindah ke akun yang baru dipakai, lalu masuk ke dashboard-nya.
|
*/

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
|
| Hanya butuh login. Buka "/admin/..." di sini akan kena 403 karena
| route tersebut memakai middleware "admin".
|
*/

/*
| Prefix "user" mirrored the admin group: "/admin/dashboard".
| URL becomes /user/dashboard, /user/materi, /user/quiz, /user/profil.
| The route NAME stays "user.dashboard" etc. so every route() call in the
| views and controllers keeps working without modification.
*/

Route::middleware('auth')
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('/dashboard', User\DashboardController::class)->name('dashboard');

        /*
         * =============================================================
         * JADWAL
         * =============================================================
         * Tujuan tombol "Lihat Semua" di panel "Jadwal Hari Ini" milik
         * dashboard. Daftar pelajaran per hari, lengkap dengan pemilih
         * hari, filter pelajaran, pencarian, kalender mini, dan
         * pengelolaan jadwal sendiri.
         *
         * Jadwal disimpan per pengguna (kolom dibuat_oleh), jadi halaman
         * dan form hanya pernah membaca jadwal miliknya sendiri.
         * Kepemilikan ditegakkan di query lewat Jadwal::scopeMilik, dan
         * form edit/hapus menolak 403 kalau jadwalnya milik orang lain.
         *
         * "/jadwal/tambah" HARUS didaftarkan sebelum "/jadwal/{jadwal}",
         * sama seperti "/materi/tambah" dan "/quiz/tambah" di atas.
         * Kalau dibalik, route edit akan menelan kata "tambah" sebagai
         * id jadwal dan form tambah tidak akan pernah terbuka.
         */
        Route::get('/jadwal', User\JadwalController::class)->name('jadwal');

        Route::get('/jadwal/tambah', [User\JadwalController::class, 'create'])->name('jadwal.tambah');
        Route::post('/jadwal/tambah', [User\JadwalController::class, 'store'])->name('jadwal.tambah.store');

        Route::get('/jadwal/{jadwal}/edit', [User\JadwalKelolaController::class, 'edit'])->name('jadwal.edit');
        Route::put('/jadwal/{jadwal}', [User\JadwalKelolaController::class, 'update'])->name('jadwal.update');
        Route::delete('/jadwal/{jadwal}', [User\JadwalKelolaController::class, 'destroy'])->name('jadwal.destroy');

        Route::get('/materi', User\MateriController::class)->name('materi');

        /*
         * Segmen literal didaftarkan lebih dulu mengikuti pola yang sudah
         * dipakai "/materi/tambah" dan "/quiz/tambah": URL literal tidak
         * pernah berisiko ditelan route berparameter.
         */
        Route::get('/materi/tambah', [User\MateriTambahController::class, 'create'])->name('materi.tambah');
        Route::post('/materi/tambah', [User\MateriTambahController::class, 'store'])->name('materi.tambah.store');

        /*
         * Halaman baca materi memakai segmen "materi-detail", bukan
         * "materi". Kalau memakai "/materi/{slug}", URL baca harus berbagi
         * prefix dengan segmen literal "/materi/tambah" dan dengan
         * "/materi/{slug}/edit" di bawahnya, sehingga urutan pendaftaran
         * route menjadi rapuh. Nama route tetap "user.materi.detail",
         * jadi seluruh panggilan route() di view dan controller tidak
         * berubah.
         */
        Route::get('/materi-detail/{materi}', User\MateriDetailController::class)->name('materi.detail');

        /*
         * Tombol "Simpan" di kepala Materi Detail dan status simpan di
         * kartu materi. Toggle dipanggil fetch dari app.js, daftarnya
         * dibaca sekali per halaman.
         */
        Route::post('/materi-detail/{materi}/simpan', [User\SimpananMateriController::class, 'toggle'])
            ->name('materi.simpan');

        Route::get('/simpanan/materi', [User\SimpananMateriController::class, 'data'])
            ->name('simpanan.materi');

        /*
         * Kelola materi milik sendiri (edit, simpan, hapus). Dipakai dari
         * menu "Karya Saya". Parameter materi tetap slug, sama seperti
         * halaman detail, dan controller menolak dengan 403 kalau materi itu
         * bukan milik pengguna yang sedang login.
         */
        Route::get('/materi/{materi}/edit', [User\MateriKelolaController::class, 'edit'])->name('materi.edit');
        Route::put('/materi/{materi}', [User\MateriKelolaController::class, 'update'])->name('materi.update');
        Route::delete('/materi/{materi}', [User\MateriKelolaController::class, 'destroy'])->name('materi.destroy');

        /*
         * Sama seperti materi: "/quiz/tambah" harus didaftarkan sebelum
         * "/quiz/{quiz}" di bawahnya, kalau dibalik route detail akan
         * menelan kata "tambah" sebagai id dan form tambah quiz tidak
         * akan pernah terbuka.
         */
        Route::get('/quiz/tambah', [User\QuizTambahController::class, 'create'])->name('quiz.tambah');
        Route::post('/quiz/tambah', [User\QuizTambahController::class, 'store'])->name('quiz.tambah.store');

        /*
         * "Masukkan Kode" juga harus didaftarkan sebelum "/quiz/{quiz}".
         * Kalau tidak, kata "gabung" akan dianggap id quiz dan form gabung
         * tidak akan pernah terbuka.
         */
        Route::get('/quiz/gabung', [User\SesiGabungController::class, 'create'])->name('sesi.gabung');
        Route::post('/quiz/gabung', [User\SesiGabungController::class, 'store'])->name('sesi.gabung.store');

        Route::get('/quiz', User\QuizController::class)->name('quiz');
        Route::get('/quiz/{quiz}', User\QuizDetailController::class)->name('quiz.detail');

        Route::get('/quiz/{quiz}/edit', [User\QuizKelolaController::class, 'edit'])->name('quiz.edit');
        Route::put('/quiz/{quiz}', [User\QuizKelolaController::class, 'update'])->name('quiz.update');
        Route::delete('/quiz/{quiz}', [User\QuizKelolaController::class, 'destroy'])->name('quiz.destroy');

        /*
         * Host membuka sesi baru dari halaman detail quiz miliknya. Sesi
         * yang dibuat langsung berstatus "waiting" dan punya kode join.
         */
        Route::post('/quiz/{quiz}/sesi', [User\SesiHostController::class, 'buka'])->name('sesi.buka');

        /*
         * =============================================================
         * LOBBY QUIZ
         * =============================================================
         * Prefix "sesi" dipakai supaya URL sesi tidak bertabrakan dengan
         * URL quiz di atas (/user/quiz/{quiz}).
         *
         *   /user/sesi/{sesi}            lobby, dipakai host dan peserta
         *   /user/sesi/{sesi}/status     data untuk polling JavaScript
         *   /user/sesi/{sesi}/mulai      host saja: waiting -> started
         *   /user/sesi/{sesi}/akhiri     host saja: menutup quiz
         *   /user/sesi/{sesi}/soal/{n}   mengerjakan soal
         *   /user/sesi/{sesi}/selesai    menutup pengerjaan sendiri
         *   /user/sesi/{sesi}/hasil      halaman nilai
         *
         * Aturan "soal tidak boleh dibuka sebelum host memulai" ditegakkan
         * di SesiKerjakanController, bukan hanya lewat query string atau
         * JavaScript, jadi membuka URL soal langsung tetap ditolak.
         */
        Route::get('/sesi/{sesi}', [User\SesiLobbyController::class, '__invoke'])->name('sesi.lobby');
        Route::get('/sesi/{sesi}/status', [User\SesiLobbyController::class, 'data'])->name('sesi.data');
        Route::post('/sesi/{sesi}/mulai', [User\SesiHostController::class, 'mulai'])->name('sesi.mulai');
        Route::post('/sesi/{sesi}/akhiri', [User\SesiHostController::class, 'akhiri'])->name('sesi.akhiri');
        Route::get('/sesi/{sesi}/soal/{nomor}', [User\SesiKerjakanController::class, 'show'])->name('sesi.soal');
        Route::post('/sesi/{sesi}/soal/{nomor}', [User\SesiKerjakanController::class, 'simpan'])->name('sesi.jawab');
        Route::post('/sesi/{sesi}/selesai', [User\SesiKerjakanController::class, 'selesai'])->name('sesi.selesai');
        Route::get('/sesi/{sesi}/hasil', [User\SesiHasilController::class, '__invoke'])->name('sesi.hasil');

        /*
         * Pusat pengelolaan konten pribadi: materi dan quiz buatan
         * pengguna yang sedang login, lengkap dengan aksi edit dan hapus.
         */
        Route::get('/karya-saya', User\KaryaSayaController::class)->name('karya-saya');

        /*
         * =============================================================
         * HASIL
         * =============================================================
         * Rekap seluruh pengerjaan quiz milik pengguna yang sedang
         * login: statistik, riwayat, filter, pencarian, dan pengurutan.
         *
         * Prefix "user" sama seperti halaman lain yang butuh login, jadi
         * URL-nya /user/hasil. Route detail memakai id pengerjaan
         * (tb_pengerjaan_quiz) dan hanya bisa dibuka pemiliknya:
         * controller membandingkan pengguna_id dengan user yang sedang
         * login dan mengembalikan 403 kalau beda.
         *
         * Halaman ini terpisah dari /user/sesi/{sesi}/hasil, yang
         * hanya menampilkan nilai dari satu sesi live.
         */
        Route::get('/hasil', User\HasilController::class)->name('hasil');

        // Segmen literal didaftarkan lebih dulu, mengikuti pola "/quiz/tambah"
        // di atas, supaya tidak pernah tertelan route berparameter.
        Route::get('/hasil/quiz/{quiz}', User\HasilController::class)->name('hasil.daftar');

        Route::get('/hasil/{pengerjaan}', User\HasilDetailController::class)->name('hasil.detail');

        /*
         * =============================================================
         * PROFIL
         * =============================================================
         * Satu-satunya tempat mengelola data akun sendiri: nama, email,
         * foto profil, dan password.
         *
         * Rute ini tidak punya parameter id sama sekali. Semua action
         * bekerja pada $request->user(), jadi tidak ada URL yang bisa
         * dipakai untuk mengedit akun orang lain.
         *
         * Foto profil dihapus lewat DELETE terpisah, bukan penanda di
         * dalam form edit, supaya "hapus foto" tetap butuh konfirmasi
         * sendiri dan tidak bisa ikut terkirim bersama simpan biasa.
         *
         * Hapus akun sengaja belum ada rutenya. UI konfirmasinya sudah
         * ada di halaman, tapi belum ada endpoint, jadi tidak ada aksi
         * yang bisa menghapus akun hanya karena satu klik.
         */
        Route::get('/profil', User\ProfilController::class)->name('profil');
        Route::put('/profil', [User\ProfilController::class, 'update'])->name('profil.update');
        Route::delete('/profil/foto', [User\ProfilController::class, 'hapusFoto'])->name('profil.foto.destroy');
        Route::put('/profil/kata-sandi', [User\ProfilController::class, 'ubahKataSandi'])->name('profil.kata-sandi');
    });

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
|
| Dijaga middleware "admin" (App\Http\Middleware\EnsureAdmin).
| Inilah proteksi sesungguhnya: meskipun menu disembunyikan di Blade,
| URL ini tetap akan ditolak 403 untuk selain admin.
|
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

        /*
         * Tinjau Materi: materi yang dibuat pengguna tidak tayang sampai
         * admin menyetujuinya, jadi halaman ini tempat admin memutuskan.
         *
         * Dua aksi di bawah memakai slug materi, sama seperti halaman detail
         * materi, dan hanya berlaku untuk materi yang statusnya masih
         * "menunggu": controller mengembalikan 404 untuk status lain.
         *
         * Field "status" ikut dikirim supaya setelah memutuskan, admin
         * kembali ke tab yang tadi dibuka dan bukan selalu ke daftar tunggu.
         */
        Route::get('/materi', Admin\MateriController::class)->name('materi');
        Route::post('/materi/{materi}/setujui', [Admin\MateriTinjauController::class, 'setujui'])->name('materi.setujui');
        Route::post('/materi/{materi}/tolak', [Admin\MateriTinjauController::class, 'tolak'])->name('materi.tolak');
    });
