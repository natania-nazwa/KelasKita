<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\User;
use App\Support\DaftarKategoriLanding;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIK
|--------------------------------------------------------------------------
*/

/*
 * Landing page untuk tamu.
 *
 * Satu-satunya halaman yang menampilkan kartu kategori materi, dan kartu itu
 * diambil dari daftar resmi mata pelajaran lewat
 * App\Support\DaftarKategoriLanding. landing/index.blade.php tidak lagi
 * menulis kartu kategori sendiri, jadi nama pelajaran yang tampil di sini
 * sama dengan yang muncul di filter Materi, filter Quiz, dan form tambah
 * konten.
 */
Route::get('/', function () {
    return view('landing.index', [
        'kartuKategori' => DaftarKategoriLanding::kartu(),
    ]);
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
         * Pratinjau materi yang sedang disusun, untuk tab Preview pada form
         * Tambah/Edit Materi milik pengguna. Mengembalikan fragment HTML dari
         * view yang sama dengan halaman detail admin (materi-detail-isi),
         * karena isinya memang sama: Daftar Isi dan kartu seksi.
         *
         * Dipakai juga form edit, jadi yang didaftarkan adalah action
         * MateriTambahController, bukan MateriKelolaController — form edit
         * membuka view yang sama dan endpoint-nya pun tidak butuh tahu
         * materi mana yang sedang disunting.
         */
        Route::post('/materi/pratinjau', [User\MateriTambahController::class, 'pratinjau'])
            ->name('materi.pratinjau');

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
         * =============================================================
         * SIMPANAN
         * =============================================================
         * Tujuan tombol bookmark di pojok kanan atas kartu materi dan
         * kartu quiz: halaman "Simpanan" menampilkan keduanya sebagai
         * kartu, lengkap dengan tab Materi / Quiz dan pencarian.
         *
         * Menyimpan dan melepas dilakukan lewat fetch, bukan lewat form
         * halaman ini, supaya tombol bookmark yang sama bisa dipakai di
         * halaman Materi, Quiz, detail materi, dan halaman Simpanan itu
         * sendiri tanpa membuka halaman lain.
         *
         * "simpanan" segmen literal dan didaftarkan di sini juga sebagai
         * penanda: seluruh rute simpanan berkumpul di satu blok, dan
         * tidak ada route berparameter yang bisa menelannya karena pola
         * "/{quiz:slug}/soal/{nomor}" di bawah menuntut tiga segmen.
         */
        Route::get('/simpanan', User\SimpananController::class)->name('simpanan');

        // Halaman berikutnya untuk tombol "Muat lagi" di halaman Simpan.
        // Dikirim sebagai JSON berisi kartu + tautan halaman setelahnya,
        // bukan halaman penuh, supaya daftar yang sudah dibaca tetap utuh.
        Route::get('/simpanan/muat', [User\SimpananController::class, 'muat'])
            ->name('simpanan.muat');

        Route::get('/simpanan/quiz', [User\SimpananQuizController::class, 'data'])
            ->name('simpanan.quiz');

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
         * =============================================================
         * QUIZ
         * =============================================================
         * Satu quiz punya DUA cara dipakai, dan keduanya punya URL sendiri:
         *
         *   1. Mode kode. Quiz privat yang dibuka lewat kode yang diketik
         *      peserta:
         *        /user/quiz/gabung        form masuk dengan kode
         *        /user/sesi/{sesi}         lobby bersama-sama
         *        /user/quiz/detail-quiz/{quiz}   halaman detail pemilik,
         *                              tempat ia memshare kode dan memulai
         *      Gagal kode cocok quiz yang owner-nya lewat "Kode Akses".
         *
         *   2. Mode publik. Quiz yang sudah disetujui admin dan tayang di
         *      /user/quiz:
         *        /user/quiz/detail-quiz/{quiz}   halaman detail semua orang
         *        /user/quiz/detail-quiz/{quiz}/mulai  langsung mulai mengerjakan
         *
         * Halaman baca memakai segmen literal "detail-quiz", bukan "/quiz/{quiz}"
         * seperti semula. Alasannya sama seperti "/materi-detail/{materi}":
         * "/quiz/{quiz}" hanya punya satu segmen setelah "/quiz", dan
         * "/quiz/tambah" maupun "/quiz/gabung" harus terdaftar lebih dulu
         * supaya kata "tambah" dan "gabung" tidak ditelan sebagai id quiz.
         * Dengan segmen "detail-quiz" di tengah, "/quiz/{quiz}" tidak lagi
         * bisa menabrak URL literal mana pun, tapi tetap didaftarkan
         * setelahnya supaya urutannya tidak bergantung pada mana yang lebih
         * spesifik.
         *
         * Nama route TIDAK ikut berubah: user.quiz.detail, user.quiz.mulai,
         * dan seterusnya tetap sama, jadi tidak ada panggilan route() di
         * view atau controller yang harus diperbarui.
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

        /*
         * Halaman detail: judul, informasi, dan daftar soal. Quiz yang belum
         * terbit hanya boleh dibuka pembuatnya, selain itu 404.
         */
        Route::get('/quiz/detail-quiz/{quiz}', User\QuizDetailController::class)->name('quiz.detail');

        /*
         * Tombol "Mulai Quiz" di halaman detail quiz.
         *
         * Bukan membuat halaman mengerjakan quiz yang baru: ia memakai sesi
         * yang sudah ada. Sesi dibuat untuk pengguna yang sedang login lalu
         * langsung berstatus "started", jadi yang stumbled ke soal pertama
         * tanpa lewat lobby. KalauQuiz punya sesi lobby yang masih menunggu,
         * pengguna dikembalikan ke lobby itu, bukan membuat sesi kedua.
         *
         * Aturan siapa boleh membuka quiz sama persis dengan halaman detail.
         */
        Route::get('/quiz/detail-quiz/{quiz}/mulai', User\QuizMulaiController::class)->name('quiz.mulai');

        /*
         * Tombol bookmark di pojok kanan atas kartu quiz. Kembaran dari
         * "/materi-detail/{materi}/simpan" di atas, hanya identifikasinya
         * memakai id karena halaman detail quiz memang memakai id.
         */
        Route::post('/quiz/detail-quiz/{quiz}/simpan', [User\SimpananQuizController::class, 'toggle'])
            ->name('quiz.simpan');

        /*
         * Host membuka sesi lobby dari halaman detail quiz mode kodenya.
         * Sesi memakai kode yang sama dengan kode akses quiz, jadi angka di
         * lobby persis sama dengan yang diketik peserta.
         */
        Route::post('/quiz/{quiz}/sesi', [User\SesiHostController::class, 'buka'])->name('sesi.buka');

        Route::get('/quiz/{quiz}/edit', [User\QuizKelolaController::class, 'edit'])->name('quiz.edit');
        Route::put('/quiz/{quiz}', [User\QuizKelolaController::class, 'update'])->name('quiz.update');
        Route::delete('/quiz/{quiz}', [User\QuizKelolaController::class, 'destroy'])->name('quiz.destroy');

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
         *   /user/sesi/{sesi}/selesai    menutup pengerjaan sendiri
         *   /user/sesi/{sesi}/hasil      halaman nilai
         *
         * Prefix "sesi" dipakai supaya URL sesi tidak bertabrakan dengan
         * URL quiz di atas (/user/quiz/detail-quiz/{quiz}).
         *
         * Aturan "soal tidak boleh dibuka sebelum host memulai" ditegakkan
         * di SesiKerjakanController, bukan hanya lewat query string atau
         * JavaScript, jadi membuka URL soal langsung tetap ditolak.
         */
        Route::get('/sesi/{sesi}', [User\SesiLobbyController::class, '__invoke'])->name('sesi.lobby');
        Route::get('/sesi/{sesi}/status', [User\SesiLobbyController::class, 'data'])->name('sesi.data');
        Route::post('/sesi/{sesi}/mulai', [User\SesiHostController::class, 'mulai'])->name('sesi.mulai');
        Route::post('/sesi/{sesi}/akhiri', [User\SesiHostController::class, 'akhiri'])->name('sesi.akhiri');
        Route::post('/sesi/{sesi}/selesai', [User\SesiKerjakanController::class, 'selesai'])->name('sesi.selesai');
        Route::get('/sesi/{sesi}/hasil', [User\SesiHasilController::class, '__invoke'])->name('sesi.hasil');

        /*
         * Halaman peringkat sesi: nama, peringkat, dan skor nilai semua
         * orang yang masuk lewat kode, urut dari yang tertinggi.
         *
         * Berbeda dari /sesi/{sesi}/hasil di atas yang dipakai host, yang ini
         * yang dibuka peserta lewat tombol "Lihat Peringkat" di kartu hasil
         * quiz mode kode. Keduanya membaca App\Support\DaftarPeringkat, jadi
         * urutan yang dilihat keduanya tidak mungkin berbeda.
         *
         * Daftar ini tidak memakai halaman terpisah untuk tiap peran: host dan
         * peserta memakai URL yang sama, dan penjaga sesi di controller yang
         * membedakan siapa yang boleh membukanya.
         */
        Route::get('/sesi/{sesi}/peringkat', [User\SesiPeringkatController::class, '__invoke'])->name('sesi.peringkat');
        /*
         * =============================================================
         * MENGERJAKAN QUIZ
         * =============================================================
         * Segmen di URL adalah slug judul quiz yang sedang dikerjakan, lalu
         * nomor soalnya:
         *
         *   /user/{slug}/soal/{nomor}
         *
         * Begitu misalnya /user/seputar-teknologi/soal/1 untuk quiz
         * "Seputar Teknologi". Platzholder "judulsoal" yang sebelumnya ada
         * di sini justru tidak memberi informasi apa pun tentang quiz yang
         * sedang dikerjakan, padahal soal nomor 1 bisa milik quiz mana saja.
         *
         * Hanya halaman menjawab soal yang memakai judul. Halaman detail,
         * edit, dan hasil tetap memakai angka id seperti sebelumnya, jadi
         * tidak ada tautan lama yang ikut berubah.
         *
         * Id sesi tetap TIDAK ikut di URL. Sesi mana yang sedang dikerjakan
         * dibaca dari session milik pengguna (App\Support\SesiAktif), dan
         * setiap tautan serta form di halaman itu tetap membawa id sesi
         * sebagai field "sesi" supaya dua tab yang membuka quiz berbeda tidak
         * saling menimpa.
         *
         * Quiz di URL tidak menggantikan pemeriksaan akses.
         * SesiKerjakanController tetap memanggil PenjagaSesi, dan juga menolak
         * sesi yang quiz-nya berbeda dari quiz di URL, jadi menebak kombinasi
         * keduanya tidak mendapat jalan masuk.
         *
         * Tanpa sesi yang bisa ditemukan, pengguna diarahkan ke daftar quiz.
         *
         * Catatan urutan: pola "/{slug}/soal/{nomor}" mau menerima segmen
         * pertama apa saja, jadi route dengan segmen literal yang juga tiga
         * bagian (mis. "/hasil/quiz/{quiz}") harus terdaftar lebih dulu — dan
         * memang begitu di atas. Pola ini tidak akan tertangkap dengan route
         * itu karena menuntut segmen kedua persis "soal" dan segmen ketiga
         * berupa nomor. Satu-satunya yang perlu disisakan adalah "judulsoal"
         * di bawah, supaya route URL lama tidak ikut tertangkap.
         */
        Route::get('/{quiz:slug}/soal/{nomor}', [User\SesiKerjakanController::class, 'show'])
            ->where('quiz', '^(?!judulsoal)[a-z0-9-]+$')
            ->name('judulsoal.soal');
        Route::post('/{quiz:slug}/soal/{nomor}', [User\SesiKerjakanController::class, 'simpan'])
            ->where('quiz', '^(?!judulsoal)[a-z0-9-]+$')
            ->name('judulsoal.jawab');

        /*
         * Tanda "ragu": aksi terpisah, bukan field di form jawaban.
         *
         * Form jawaban tidak boleh ikut terpakai karena satu klik di
         * tombolnya berarti "pentingkan soal ini", bukan "kirim jawaban".
         * Kalau keduanya jadi satu form, menandai ragu di tengah isian akan
         * ikut mengirim jawaban yang belum selesai — atau sebaliknya,
         * peserta tidak bisa menandai soal yang isiannya masih kosong.
         *
         * Satu aksi untuk dua arah: memutar status, jadi tombolnya cukup
         * satu dan klik ganda tidak pernah menghasilkan dua baris.
         */
        Route::post('/{quiz:slug}/soal/{nomor}/ragu', [User\SesiKerjakanController::class, 'ragu'])
            ->where('quiz', '^(?!judulsoal)[a-z0-9-]+$')
            ->name('judulsoal.ragu');

        /*
         * URL lama halaman menjawab soal: /user/judulsoal/soal/{nomor}.
         *
         * Tetap dipertahankan karena tautan seperti ini sudah pernah
         * dibagikan dan disimpan, dan polanya berbeda dari yang di atas
         * sehingga tidak bentrok. Bedanya di sini slug tidak ada di URL,
         * jadi quiz-nya diambil dari sesi yang sedang dikerjakan.
         */
        Route::get('/judulsoal/soal/{nomor}', [User\SesiKerjakanController::class, 'show'])->name('judulsoal.soal.lama');
        Route::post('/judulsoal/soal/{nomor}', [User\SesiKerjakanController::class, 'simpan'])->name('judulsoal.jawab.lama');

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
         * HASIL QUIZ (KARTU BESAR)
         * =============================================================
         * Satu halaman hasil yang menaruh nilai, rincian jawaban, waktu
         * pengerjaan, dan detail quiz dalam satu kartu besar. Berdiri
         * sendiri: /user/hasil, /user/hasil/{pengerjaan}, dan
         * /user/sesi/{sesi}/hasil tetap seperti sebelumnya.
         *
         * Prefix "uiux-design" sengaja dipakai supaya halaman ini punya
         * URL sendiri dan tidak pernah menabrak route hasil yang sudah ada.
         * Pengerjaan yang ditampilkan ditentukan lewat "?pengerjaan=<id>";
         * tanpa parameter itu yang dibuka adalah pengerjaan terbaru milik
         * pengguna yang sedang login, jadi halaman ini tidak pernah
         * menampilkan nilai orang lain.
         */
        Route::get('/uiux-design/hasil', User\UiuxHasilController::class)->name('uiux.hasil');

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
         * Hapus akun juga DELETE sendiri (bukan /profil/akun) supaya URL-nya
         * tetap "/user/profil" dan tidak perlu ada path baru. Password akun
         * dicek HapusAkunRequest sebelum satu baris pun ikut terhapus.
         */
        Route::get('/profil', User\ProfilController::class)->name('profil');
        Route::put('/profil', [User\ProfilController::class, 'update'])->name('profil.update');
        Route::delete('/profil', [User\ProfilController::class, 'hapus'])->name('profil.hapus');
        Route::delete('/profil/foto', [User\ProfilController::class, 'hapusFoto'])->name('profil.foto.destroy');
        Route::put('/profil/kata-sandi', [User\ProfilController::class, 'ubahKataSandi'])->name('profil.kata-sandi');

        /*
         * =============================================================
         * NOTIFIKASI
         * =============================================================
         * Dua rute notifikasi, dan keduanya hanya menandai: satu baris
         * (baca) dan seluruh miliknya sekaligus (baca-semua). Daftar
         * notifikasi sendiri dirender di lonceng topbar, jadi tidak ada
         * halaman notifikasi terpisah yang harus dibuka dan ditutup lagi.
         *
         * Notifikasi dibuat admin dari menu "Konten Pembelajaran" setiap kali
         * ia menerbitkan materi atau quiz (lihat
         * App\Support\NotifikasiKonten). Tidak ada sumber notifikasi lain,
         * jadi tidak ada rute "buat notifikasi" pun.
         *
         * Rute "baca-semua" tidak punya parameter notifikasi dan tidak
         * pernah membaca kolom notifikasi milik orang lain: ia hanya
         * menyentuh baris milik pengguna yang sedang login, karena itu
         * cukup dengan memeriksa usersan saja. Rute "baca" membawa satu
         * baris, jadi tetap menolak 403 kalau baris itu bukan miliknya.
         *
         * Keduanya dijaga middleware "auth" bersama seluruh halaman user di
         * atas.
         */
        Route::post('/notifikasi/baca-semua', [User\NotifikasiController::class, 'bacaSemua'])
            ->name('notifikasi.baca-semua');

        Route::post('/notifikasi/{notifikasi}/baca', [User\NotifikasiController::class, 'baca'])
            ->name('notifikasi.baca');
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
         * =============================================================
         * KONTEN PEMBELAJARAN
         * =============================================================
         * Ruang kerja admin untuk membuat dan mengelola kontennya sendiri:
         * materi dan quiz, lengkap dengan simpan draft dan terbitkan
         * langsung.
         *
         * Menu ini berdiri sendiri dan tidak menyenggol tiga halaman admin
         * yang sudah ada, karena ketiganya memang beda tujuan:
         *
         *   - "Materi" dan "Quiz" adalah kumpulan konten yang sudah tayang,
         *     siapa pun yang membuatnya. Hanya dibaca, dan diubah kalau
         *     karyanya milik admin sendiri.
         *   - "Verifikasi" adalah antrean keputusan untuk karya pengguna.
         *   - "Konten Pembelajaran" (bagian ini) tempat admin menambah,
         *     menyunting, menggandakan, menerbitkan, dan menarik kembali
         *     karyanya sendiri.
         *
         * Tidak ada tahap persetujuan di sini. Admin di aplikasi ini satu
         * role dan dia sendiri yang membuat kontennya, jadi status yang
         * dipakai hanya "draft" dan "published" — dua status yang sudah ada
         * di tb_materi dan tb_quiz, tidak ada kolom atau tabel baru.
         *
         * URL memakai awalan /konten supaya tidak pernah tertukar dengan
         * /admin/materi dan /admin/quiz yang tetap mengelola katalog konten
         * published. Segmen literal ("materi", "quiz", "tambah")
         * didaftarkan sebelum route berparameter, mengikuti pola "/materi"
         * milik pengguna.
         *
         * Parameter materi tetap slug dan parameter quiz tetap id, sama
         * seperti route admin yang sudah ada, supaya tidak ada tautan lama
         * yang ikut berubah bentuknya.
         */
        Route::get('/konten', Admin\KontenController::class)->name('konten');

        Route::get('/konten/materi/tambah', [Admin\KontenMateriController::class, 'create'])
            ->name('konten.materi.tambah');
        Route::post('/konten/materi/tambah', [Admin\KontenMateriController::class, 'store'])
            ->name('konten.materi.tambah.store');

        /*
         * Pratinjau materi yang sedang disusun, untuk tab Preview pada form
         * Tambah/Edit Materi. Mengembalikan fragment HTML dari view yang sama
         * dengan halaman detail (admin.materi-detail-isi), bukan halaman utuh,
         * karena pemanggilnya cuma menukar isi satu wadah.
         *
         * Didaftarkan di sini, sebelum route berparameter /konten/materi/{materi},
         * karena segmen "pratinjau" dan slug materi bisa sama-sama berupa
         * satu barek teks.
         */
        Route::post('/konten/materi/pratinjau', [Admin\KontenMateriController::class, 'pratinjau'])
            ->name('konten.materi.pratinjau');

        /*
         * Halaman baca materi dari ruang kerja admin.
         *
         * Controller, view, dan datanya sama persis dengan
         * admin.materi.show di bawah — satu halaman detail, bukan dua
         * salinan. Yang dibedakan cuma nama route-nya, dan itu sudah cukup:
         * penanda aktif di sidebar memakai admin.konten*, jadi menekan
         * "Lihat" pada kartu di /admin/konten tidak memindahkan menu yang
         * menyala dari Konten Pembelajaran ke Materi.
         *
         * Segmen literal ("tambah") didaftarkan lebih dulu di atas,
         * mengikuti pola yang sama dengan /materi dan /quiz.
         */
        Route::get('/konten/materi/{materi}', Admin\MateriDetailController::class)
            ->name('konten.materi.show');

        Route::get('/konten/materi/{materi}/edit', [Admin\KontenMateriController::class, 'edit'])
            ->name('konten.materi.edit');
        Route::put('/konten/materi/{materi}', [Admin\KontenMateriController::class, 'update'])
            ->name('konten.materi.update');
        Route::delete('/konten/materi/{materi}', [Admin\KontenMateriController::class, 'destroy'])
            ->name('konten.materi.destroy');
        Route::post('/konten/materi/{materi}/terbitkan', [Admin\KontenMateriController::class, 'publish'])
            ->name('konten.materi.publish');
        Route::post('/konten/materi/{materi}/duplikat', [Admin\KontenMateriController::class, 'duplikat'])
            ->name('konten.materi.duplikat');

        Route::get('/konten/quiz/tambah', [Admin\KontenQuizController::class, 'create'])
            ->name('konten.quiz.tambah');
        Route::post('/konten/quiz/tambah', [Admin\KontenQuizController::class, 'store'])
            ->name('konten.quiz.tambah.store');

        /* Sama seperti admin.konten.materi.show di atas: satu halaman detail
         * yang sama dengan admin.quiz.show, dibedakan hanya nama route-nya
         * supaya tombol "Lihat" di /admin/konten tidak memindahkan menu yang
         * menyala dari Konten Pembelajaran ke Quiz. */
        Route::get('/konten/quiz/{quiz}', Admin\QuizDetailController::class)
            ->name('konten.quiz.show');

        Route::get('/konten/quiz/{quiz}/edit', [Admin\KontenQuizController::class, 'edit'])
            ->name('konten.quiz.edit');
        Route::put('/konten/quiz/{quiz}', [Admin\KontenQuizController::class, 'update'])
            ->name('konten.quiz.update');
        Route::delete('/konten/quiz/{quiz}', [Admin\KontenQuizController::class, 'destroy'])
            ->name('konten.quiz.destroy');
        Route::post('/konten/quiz/{quiz}/terbitkan', [Admin\KontenQuizController::class, 'publish'])
            ->name('konten.quiz.publish');
        Route::post('/konten/quiz/{quiz}/duplikat', [Admin\KontenQuizController::class, 'duplikat'])
            ->name('konten.quiz.duplikat');

        /*
         * Verifikasi: satu halaman untuk semua konten yang menunggu
         * persetujuan admin. Bukan halaman baru yang berisi logika tersendiri
         * — ini daftar gabungan "Tinjau Materi" dan "Tinjau Quiz". Tombol
         * keputusan di halaman ini tetap memakai route setujui/tolak milik
         * halaman-halaman tersebut; formnya hanya mengirim field "kembali"
         * supaya admin kembali ke sini setelah memutuskan.
         */
        Route::get('/verifikasi', Admin\VerifikasiController::class)->name('verifikasi');

        /*
         * Panel review di kolom kanan halaman Verifikasi. Endpoint ini hanya
         * mengembalikan HTML satu konten (bukan halaman utuh), dipakai saat
         * admin memilih baris lain supaya hero, filter, dan daftar tidak ikut
         * dimuat ulang. Tanpa JavaScript, tautan tiap baris tetap membuka
         * halaman utuh dengan query "pilih".
         */
        Route::get('/verifikasi/panel', [Admin\VerifikasiController::class, 'panel'])->name('verifikasi.panel');

        /*
         * =============================================================
         * MATERI
         * =============================================================
         * Halaman ini hanya mengelola materi yang sudah terbit: yang masih
         * menunggu keputusan ditinjau di Verifikasi, dan yang ditolak atau
         * masih draft dikelola pemiliknya di "Karya Saya". Jadi isinya bukan
         * papan semua status seperti sebelumnya, melainkan daftar published
         * saja dengan pencarian, filter, dan panel detail.
         *
         * Halaman detail memakai slug, tanpa penjaga status, supaya admin
         * tetap bisa membuka materi dari status mana pun lewat URL — Verifikasi
         * dan Karya Saya tidak berubah karena daftar di sini published-only.
         *
         * Isi detail dirender dengan komponen yang sama dengan halaman detail
         * milik pengguna (resources/views/components/materi), jadi admin
         * membaca materi persis seperti membacanya pengguna.
         */
        Route::get('/materi', Admin\MateriController::class)->name('materi');
        Route::get('/materi/{materi}', Admin\MateriDetailController::class)->name('materi.show');

        /*
         * Edit dan hapus dari menu tiga titik kartu.
         *
         * Belum ada route ini sebelumnya: user.materi.edit dan
         * user.materi.destroy milik pemilik dan menolak admin dengan 403.
         *
         * Edit hanya berlaku untuk materi yang dibuat admin sendiri — itu
         * satu-satunya alasan tombolnya muncul di menu, dan controller
         * menegakkannya lagi supaya URL yang diketik manual tidak melewati
         * aturan yang sama. Hapus tidak dibatasi: materi yang sudah tayang
         * memang harus bisa ditarik admin, siapa pun yang membuatnya.
         *
         * Form edit-nya memakai komponen form yang sama dengan halaman Tambah
         * Materi milik pengguna (x-materi.informasi / .bab / .editor), hanya
         * judul, penjelas, dan tujuan simpan yang mengikuti bahasa admin.
         *
         * Berbeda dari pemilik, admin tidak perlu mengulang review: materi
         * yang sudah terbit tetap terbit setelah diperbarui, karena admin
         * adalah pihak yang menyetujui konten.
         */
        Route::get('/materi/{materi}/edit', [Admin\MateriKelolaController::class, 'edit'])->name('materi.edit');
        Route::put('/materi/{materi}', [Admin\MateriKelolaController::class, 'update'])->name('materi.update');
        Route::delete('/materi/{materi}', [Admin\MateriKelolaController::class, 'destroy'])->name('materi.destroy');

        /*
         * Dua aksi di bawah memakai slug materi dan hanya berlaku untuk materi
         * yang statusnya masih "menunggu": controller mengembalikan 404 untuk
         * status lain. Route tetap dipakai halaman Verifikasi, yang mengirim
         * field "kembali" supaya admin kembali ke sana setelah memutuskan.
         */
        Route::post('/materi/{materi}/setujui', [Admin\MateriTinjauController::class, 'setujui'])->name('materi.setujui');
        Route::post('/materi/{materi}/tolak', [Admin\MateriTinjauController::class, 'tolak'])->name('materi.tolak');

        /*
         * =============================================================
         * QUIZ
         * =============================================================
         * Batas dan bentuk halaman ini sengaja sama persis dengan halaman
         * Materi di atas: hanya konten yang sudah terbit, tanpa tab status,
         * tanpa setujui/tolak, dengan hero, kartu filter, grid kartu, dan
         * paginasi yang sama. Quiz yang masih menunggu keputusan ditinjau di
         * menu Verifikasi — persis seperti materi — dan yang ditolak atau
         * masih draft dikelola pemiliknya di "Karya Saya".
         *
         * Karena itu halaman ini tidak lagi menjadi tempat memutuskan
         * persetujuan; dua route setujui/tolak di bawah tetap ada karena
         * dipakai halaman Verifikasi.
         *
         * Detail, edit, dan hapus adalah pasangan dari route milik pengguna
         * (user.quiz.detail / .edit / .destroy) yang menolak admin dengan
         * 403.(parameternya id quiz, bukan slug, sama seperti route detail
         * quiz milik pengguna)
         */
        Route::get('/quiz', Admin\QuizController::class)->name('quiz');
        Route::get('/quiz/{quiz}', Admin\QuizDetailController::class)->name('quiz.show');
        Route::get('/quiz/{quiz}/edit', [Admin\QuizKelolaController::class, 'edit'])->name('quiz.edit');
        Route::put('/quiz/{quiz}', [Admin\QuizKelolaController::class, 'update'])->name('quiz.update');
        Route::delete('/quiz/{quiz}', [Admin\QuizKelolaController::class, 'destroy'])->name('quiz.destroy');

        /*
         * Dua aksi di bawah memakai id quiz dan hanya berlaku untuk quiz yang
         * statusnya masih "menunggu": controller mengembalikan 404 untuk
         * status lain. Route tetap dipakai halaman Verifikasi, yang mengirim
         * field "kembali" supaya admin kembali ke sana setelah memutuskan.
         */
        Route::post('/quiz/{quiz}/setujui', [Admin\QuizTinjauController::class, 'setujui'])->name('quiz.setujui');
        Route::post('/quiz/{quiz}/tolak', [Admin\QuizTinjauController::class, 'tolak'])->name('quiz.tolak');

        /*
         * Daftar materi dan quiz yang menunggu keputusan, digabung dalam
         * satu daftar dengan tab jenis dan tab status.
         */
        Route::get('/verifikasi', Admin\VerifikasiController::class)->name('verifikasi');

        /*
         * Daftar seluruh akun. Halaman ini membaca daftar, dan menyediakan
         * aksi hapus akun.
         *
         * Aktif/nonaktif tidak diubah dari sini: statusnya dihitung otomatis
         * dari kapan terakhir pengguna membuka aplikasi, dibaca dari kolom
         * "terakhir_aktivitas". Ubah peran juga sengaja tidak ada: hanya ada
         * dua peran dan tidak ada kebutuhan memindahkan akun di antara
         * keduanya.
         *
         * Hapus dikirim lewat form DELETE biasa supaya tetap jalan tanpa
         * JavaScript. Penjagaannya ada di controller, bukan hanya dengan
         * menyembunyikan tombol: akun sendiri dan admin terakhir tidak bisa
         * dihapus.
         */
        Route::get('/pengguna', Admin\PenggunaController::class)->name('pengguna');
        Route::delete('/pengguna/{pengguna}', [Admin\PenggunaController::class, 'destroy'])
            ->name('pengguna.destroy');

        /*
         * =============================================================
         * PENGATURAN
         * =============================================================
         * Pusat pengaturan admin. Semuanya satu grup route di bawah
         * /admin/pengaturan supaya menu sidebar "Pengaturan" punya satu
         * pola URL dan active state-nya bisa dicocokkan dengan
         * routeIs('admin.pengaturan*').
         *
         * Yang dipisah halaman dan yang dipisah dialog mengikuti
         * beratnya pekerjaan, bukan sekadar selera:
         *
         *   - Halaman sendiri  : Profil, Keamanan, Kelola Mata Pelajaran,
         *                       Sesi Login, Informasi Sistem, Tentang.
         *                       Semuanya punya isi panjang atau langkah
         *                       lebih dari satu, dan memakai GET supaya
         *                       bisa ditandai, dibagikan, dan di-back.
         *   - Dialog di /pengaturan : Notifikasi dan Publikasi. Keduanya satu
         *                       form pendek yang selesai dalam satu langkah,
         *                       dan tidak perlu halaman tersendiri.
         *   - Konfirmasi       : Logout dari semua perangkat dan Keluar
         *                       dari akun. Keduanya mengirim POST ke route
         *                       di bawah, jadi tetap jalan tanpa JavaScript.
         *
         * Tidak ada endpoint JSON di sini. Seluruh perubahan dikirim lewat
         * form biasa supaya topbar dan sidebar ikut memakai data terbaru
         * tanpa perlu disegarkan manual.
         */
        Route::get('/pengaturan', Admin\PengaturanController::class)->name('pengaturan');

        // Profil admin: nama, email, dan foto profil.
        Route::get('/pengaturan/profil', [Admin\ProfilAdminController::class, 'edit'])->name('pengaturan.profil');
        Route::put('/pengaturan/profil', [Admin\ProfilAdminController::class, 'update'])->name('pengaturan.profil.update');
        Route::delete('/pengaturan/profil/foto', [Admin\ProfilAdminController::class, 'destroyFoto'])->name('pengaturan.profil.foto.destroy');

        // Keamanan: ubah kata sandi.
        Route::get('/pengaturan/keamanan', [Admin\KeamananAdminController::class, 'edit'])->name('pengaturan.keamanan');
        Route::put('/pengaturan/keamanan/kata-sandi', [Admin\KeamananAdminController::class, 'update'])->name('pengaturan.keamanan.kata-sandi');

        // Preferensi: tema, notifikasi, dan aturan publikasi.
        Route::put('/pengaturan/tema', [Admin\PreferensiController::class, 'tema'])->name('pengaturan.tema');
        Route::put('/pengaturan/notifikasi', [Admin\PreferensiController::class, 'notifikasi'])->name('pengaturan.notifikasi');
        Route::put('/pengaturan/publikasi', [Admin\PreferensiController::class, 'publikasi'])->name('pengaturan.publikasi');

        /*
         * Kelola Mata Pelajaran. Mengelola baris tb_pelajaran yang sudah ada,
         * termasuk kolom "aktif"-nya, jadi admin bisa menyembunyikan sebuah
         * mata pelajaran dari pilihan tanpa merusak materi yang memakainya.
         */
        Route::get('/pengaturan/pelajaran', [Admin\PelajaranKelolaController::class, 'index'])->name('pengaturan.pelajaran');
        Route::post('/pengaturan/pelajaran', [Admin\PelajaranKelolaController::class, 'store'])->name('pengaturan.pelajaran.store');
        Route::put('/pengaturan/pelajaran/{pelajaran}', [Admin\PelajaranKelolaController::class, 'update'])->name('pengaturan.pelajaran.update');
        Route::delete('/pengaturan/pelajaran/{pelajaran}', [Admin\PelajaranKelolaController::class, 'destroy'])->name('pengaturan.pelajaran.destroy');

        // Keamanan lanjutan: daftar perangkat dan logout dari semuanya.
        Route::get('/pengaturan/sesi', [Admin\SesiController::class, 'index'])->name('pengaturan.sesi');
        Route::delete('/pengaturan/sesi', [Admin\SesiController::class, 'hapusSemua'])->name('pengaturan.sesi.destroy');

        // Informasi sistem dan tentang aplikasi.
        Route::get('/pengaturan/sistem', [Admin\SistemController::class, 'index'])->name('pengaturan.sistem');
        Route::get('/pengaturan/tentang', [Admin\SistemController::class, 'tentang'])->name('pengaturan.tentang');

        /*
         * =============================================================
         * NOTIFIKASI ADMIN
         * =============================================================
         * Satu-satunya rute notifikasi admin: menandai satu notifikasi sudah
         * dibaca. Daftar notifikasi sendiri dirender di lonceng topbar
         * (components/admin/topbar-kanan), jadi tidak ada halaman notifikasi
         * terpisah yang harus dibuka dan ditutup lagi.
         *
         * Rute ini hidup di luar /admin/pengaturan, bukan di dalam blok
         * preference di atas, karena topbar yang memakainya sengaja tidak
         * dirender di halaman Pengaturan: lonceng tidak akan pernah pakai
         * tautan dari /admin/pengaturan*.
         *
         * Tidak ada rute "buat notifikasi". Barisnya dibuat oleh
         * App\Support\NotifikasiAdmin dari kejadian nyata di aplikasi, dan
         * masing-masing jenisnya dikendalikan satu saklar di
         * /admin/pengaturan.
         */
        Route::post('/notifikasi/{notifikasi}/baca', [Admin\NotifikasiAdminController::class, 'baca'])
            ->name('pengaturan.notifikasi.baca');
    });
