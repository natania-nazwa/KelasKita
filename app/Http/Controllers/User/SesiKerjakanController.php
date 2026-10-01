<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JawabanQuiz;
use App\Models\PengerjaanQuiz;
use App\Models\PesertaQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\SoalRagu;
use App\Models\User;
use App\Support\NotifikasiAdmin;
use App\Support\Penilaian;
use App\Support\PenjagaSesi;
use App\Support\SesiAktif;
use App\Support\TujuanHasil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Mengerjakan soal di dalam sesi quiz yang sudah dimulai.
 *
 * Semua aturan "soal tidak boleh dibuka sebelum host memulai" ditegakkan di
 * sini, di server:
 *   - hanya host dan peserta sesi ini yang boleh menjawab, selain itu 403;
 *   - status sesi harus "started"; kalau belum, dikembalikan ke lobby;
 *   - kalau sesi sudah "finished", dikembalikan ke halaman hasil;
 *   - host quiz mode kode mengembalikan nilai sendiri, bukan mengerjakan:
 *     ia memandu sesi, dan pesertanya yang masuk lewat kode.
 *     Sesi solo (tombol "Mulai Quiz" di halaman detail) tidak kena aturan
 *     ini karena tidak ada peserta lain di sana.
 *
 * Karena dicek di server, membuka URL soal secara langsung tidak bisa
 * melewati host.
 *
 * URL halaman ini menyebut quiz-nya sendiri lewat slug
 * (/user/quiz/{quiz}/soal/{nomor}) tapi TIDAK memuat id sesi, jadi sesi yang
 * sedang dikerjakan dibaca dari session pengguna lewat
 * App\Support\SesiAktif. Setiap tautan dan form di halaman tetap membawa id
 * sesi sebagai field "sesi", jadi dua tab yang membuka quiz berbeda tidak
 * saling menimpa. Field itu bukan pengganti pemeriksaan akses: PenjagaSesi
 * tetap dijalankan pada sesi yang ditemukan, dan sesi itu juga harus milik
 * quiz yang disebut URL (lihat sesiUntuk()).
 *
 * Jawaban disimpan di tb_jawaban_quiz dan rekapnya dihitung ulang di
 * PengerjaanQuiz, jadi mengubah jawaban tidak membuat nilai dobel.
 */
class SesiKerjakanController extends Controller
{
    /**
     * Sisa waktu (detik) di bawah mana timer berubah jadi tanda waktu
     * kuning, dan di bawah mana jadi merah.
     *
     * Nilainya dikirim ke view supaya warna awal yang dirender server sama
     * dengan ambang yang dipakai JavaScript saat menghitung mundur, jadi
     * tidak ada kedipan warna di detik pertama.
     */
    public const WAKTU_SEDIKIT = 300;

    public const WAKTU_MENDEKUTI = 60;

    /**
     * Halaman satu soal.
     *
     * $quiz boleh null: halaman ini juga dilayani lewat URL lama
     * /user/judulsoal/soal/{nomor} yang tidak menyebut quiz sama sekali. Di
     * URL itu quiz diambil dari sesi yang sedang dikerjakan.
     */
    public function show(Request $request, ?Quiz $quiz, int $nomor): View|RedirectResponse
    {
        $pengguna = $request->user();

        $sesi = $this->sesiUntuk($request, $quiz);

        if ($sesi === null) {
            return $this->tidakBekerja();
        }

        if ($larangan = $this->larangan($sesi, $pengguna)) {
            return $larangan;
        }

        $sesi->loadMissing('quiz.pelajaran');

        // pilihanSoal ikut dimuat di sini supaya halaman yang memuat lima
        // sampai sepuluh soal tidak jadi lima sampai sepuluh query tambahan
        // hanya untuk membaca daftar pilihan.
        $daftar = $sesi->quiz->soal()->aktif()->terurut()->with('pilihanSoal')->get();

        // Quiz tanpa soal aktif tidak bisa dikerjakan. Bukan 404, karena
        // sesi dan quiz-nya benar-benar ada; pesertanya yang belum siap.
        if ($daftar->isEmpty()) {
            return $this->tanpaSoal($sesi, $pengguna);
        }

        $posisi = $this->posisi($daftar, $nomor);

        abort_if($posisi === null, 404);

        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        $this->tandaiMengerjakan($sesi, $pengguna);

        $soal = $daftar[$posisi];

        return view('user.quiz-kerjakan', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'soal' => $soal,
            'tanpaSoal' => false,
            'nomor' => $posisi + 1,
            'jumlahSoal' => $daftar->count(),
            'jawaban' => $this->jawabanSoal($pengerjaan, $soal),
            /*
             * (int) karena instance yang baru dibuat lewat create() belum
             * pernah dibaca ulang dari database, jadi jumlah_dijawab masih
             * null di memori walau kolomnya sudah berisi 0. Tanpa
             * pemindahan ini, halaman soal yang pertama kali dibuka akan
             * menampilkan badge kosong dan kalimat "dari 2 soal sudah
             * dijawab." tanpa angkanya di depannya.
             */
            'jumlahDijawab' => (int) $pengerjaan->jumlah_dijawab,
            'nomorTerjawab' => $this->nomorTerjawab($pengerjaan, $daftar),
            'nomorRagu' => $this->nomorRagu($pengerjaan, $daftar),
            'sisaDetik' => $this->sisaDetik($sesi->quiz, $pengerjaan),
            'waktuSedikit' => self::WAKTU_SEDIKIT,
            'waktuMendekuti' => self::WAKTU_MENDEKUTI,
            'adalahHost' => $sesi->adalahHost($pengguna),
        ]);
    }

    /**
     * Simpan jawaban satu soal, lalu pindah ke soal berikutnya.
     *
     * Menjawab soal terakhir otomatis berarti selesai, jadi tidak ada tombol
     * "Selesai" yang terpisah di ujung.
     */
    public function simpan(Request $request, ?Quiz $quiz, int $nomor): RedirectResponse
    {
        $pengguna = $request->user();

        $sesi = $this->sesiUntuk($request, $quiz);

        if ($sesi === null) {
            return $this->tidakBekerja();
        }

        if ($larangan = $this->larangan($sesi, $pengguna)) {
            return $larangan;
        }

        $sesi->loadMissing('quiz');

        $daftar = $sesi->quiz->soal()->aktif()->terurut()->with('pilihanSoal')->get();

        $posisi = $this->posisi($daftar, $nomor);

        abort_if($posisi === null, 404);

        $soal = $daftar[$posisi];

        /*
         * Aturan isian mengikuti tipe soalnya sendiri. Empat tipe berdaftar
         * mengirim isian "jawaban" (huruf, atau larik huruf untuk pilihan
         * banyak), sedangkan dua tipe teks mengirim "jawaban_teks". Daftar
         * huruf allowable diambil dari pilihan yang benar-benar dipakai soal
         * ini, bukan dari daftar huruf tetap, karena builder bisa menyimpan
         * pilihan sampai J dan membiarkan huruf yang tidak terpakai kosong.
         */
        $pilihan = array_keys($soal->pilihan());
        $kunci = 'jawaban';

        if ($soal->tipeTeks()) {
            $kunci = 'jawaban_teks';
        }

        $aturan = $soal->tipeTeks()
            ? [$kunci => ['required', 'string', 'max:2000']]
            : [$kunci => $soal->tipeBanyakBenar()
                ? ['required', 'array', 'min:1']
                : ['required', 'string']];

        // Aturan "huruf itu harus salah satu pilihan di soal ini" hanya
        // bermakna untuk tipe berdaftar, dan hanya dijaga per elemen untuk
        // tipe yang memang menerima beberapa huruf sekaligus.
        if (! $soal->tipeTeks()) {
            if ($soal->tipeBanyakBenar()) {
                $aturan[$kunci.'.*'] = ['string', 'in:'.implode(',', $pilihan)];
            } else {
                $aturan[$kunci][] = 'in:'.implode(',', $pilihan);
            }
        }

        $pesan = [
            $kunci.'.required' => 'Silakan pilih atau isi jawaban terlebih dahulu.',
            $kunci.'.array' => 'Pilih minimal satu jawaban.',
            $kunci.'.min' => 'Pilih minimal satu jawaban.',
            $kunci.'.in' => 'Pilihan itu tidak ada di soal ini. Pilih jawaban yang tersedia.',
            $kunci.'.*.in' => 'Pilihan itu tidak ada di soal ini. Pilih jawaban yang tersedia.',
        ];

        // Kalimat "belum dijawab" sengaja sama dengan yang ditulis
        // resources/js/quiz-kerjakan.js, jadi pesan dari server dan dari
        // peramban tidak terlihat melompat.
        $data = $request->validate($aturan, $pesan);

        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        // Satu-satunya tempat yang memutuskan benar atau salahnya jawaban.
        $nilai = Penilaian::nilai($soal, $data);

        JawabanQuiz::query()->updateOrCreate(
            [
                'pengerjaan_quiz_id' => $pengerjaan->getKey(),
                'soal_id' => $soal->getKey(),
            ],
            [
                ...$nilai,
                'dijawab_pada' => now(),
            ],
        );

        $pengerjaan->hitungUlang();

        // Menyimpan jawaban berarti peserta sedang mengerjakan, apa pun jalan
        // masuknya. Penandaan ini tidak harus bergantung pada halaman soal
        // yang sempat dibuka lebih dulu.
        $this->tandaiMengerjakan($sesi, $pengguna);

        /*
         * Menjawab soal terakhir berarti selesai. Form dikirim lewat POST,
         * sedangkan halaman hasil dibaca lewat GET, jadi di sini pengerjaan
         * ditutup langsung dan pengguna dikirim ke halaman hasil. Kalau
         * objek Laravel ikut ikut diarahkan ke user.sesi.selesai yang
         * hanya menerima POST, hasilnya 405.
         */
        if ($posisi + 1 < $daftar->count()) {
            return redirect()->route('user.judulsoal.soal', [$sesi->quiz->slug, $posisi + 2]);
        }

        $this->tutupPengerjaan($sesi, $pengguna);

        return redirect()->to($this->tujuanHasil($sesi, $pengguna));
    }

    /**
     * Tutup pengerjaan dan tampilkan hasil.
     *
     * Tombol "Selesai" ada di semua soal supaya peserta bisa berhenti di
     * tengah, bukan hanya setelah menjawab yang terakhir. Timer yang sudah
     * habis memakai aksi yang sama, jadi waktu yang benar-benar habis tidak
     * menuntut peserta memilih jawaban apa pun.
     */
    public function selesai(Request $request, SesiQuiz $sesi): RedirectResponse
    {
        $pengguna = $request->user();

        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        $this->tutupPengerjaan($sesi, $pengguna);

        return redirect()->to($this->tujuanHasil($sesi, $pengguna));
    }

    /**
     * Tandai satu soal sebagai "ragu", atau lepas tandanya kalau sudah ada.
     *
     * Satu aksi untuk dua arah supaya formnya cukup satu tombol, dan klik
     * ganda tidak pernah menghasilkan dua baris: delete() mengembalikan
     * jumlah baris yang hilang, jadi 0 berarti memang belum ditandai.
     *
     * Tanda ini sama sekali tidak menyentuh tb_jawaban_quiz, jadi menandai
     * ragu tidak pernah mengubah nilai, benar/salah, atau jumlah jawaban
     * yang sudah tersimpan.
     *
     * Setelah berubah, peserta dikembalikan ke soal yang sama persis,
     * jadi posisinya tidak bergeser hanya karena menandai sesuatu.
     */
    public function ragu(Request $request, ?Quiz $quiz, int $nomor): RedirectResponse
    {
        $pengguna = $request->user();

        $sesi = $this->sesiUntuk($request, $quiz);

        if ($sesi === null) {
            return $this->tidakBekerja();
        }

        if ($larangan = $this->larangan($sesi, $pengguna)) {
            return $larangan;
        }

        $sesi->loadMissing('quiz');

        $daftar = $sesi->quiz->soal()->aktif()->terurut()->get();

        $posisi = $this->posisi($daftar, $nomor);

        abort_if($posisi === null, 404);

        $soal = $daftar[$posisi];

        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        $pasangan = [
            'pengerjaan_quiz_id' => $pengerjaan->getKey(),
            'soal_id' => $soal->getKey(),
        ];

        $ditandai = SoalRagu::query()->where($pasangan)->delete() === 0;

        if ($ditandai) {
            SoalRagu::query()->create($pasangan);
        }

        // Id sesi tidak ikut di URL, jadi kembali ke soal yang sama cukup
        // menulis ulang sesi aktif yang sudah dilakukan oleh
        // larangan() di atas — persis seperti redirect setelah menjawab.
        return redirect()->route('user.judulsoal.soal', [$sesi->quiz->slug, $posisi + 1]);
    }

    /**
     * Halaman hasil mana yang jadi tujuan setelah pengguna selesai.
     *
     * Tiga pemanggil (soal terakhir, tombol "Selesai", dan sesi yang sudah
     * ditutup host) memakai satu fungsi yang sama supaya ketiganya tidak
     * pernah membuka tujuan yang berbeda untuk keadaan yang sama.
     */
    private function tujuanHasil(SesiQuiz $sesi, User $pengguna): string
    {
        return TujuanHasil::untuk($sesi, $pengguna);
    }

    /**
     * Tandai pengerjaan pengguna sebagai selesai dan perbarui status
     * pesertanya di daftar lobby.
     *
     * Dipakai oleh tiga pemanggil: tombol "Selesai" di tiap soal, jawaban
     * soal terakhir yang otomatis berarti selesai, dan timer yang mencapai
     * 00:00. Kalau sesi sudah ditutup host, atau pengguna belum pernah
     * membuka soal, maka pengerjaannya tidak dibuat di sini.
     */
    private function tutupPengerjaan(SesiQuiz $sesi, User $pengguna): void
    {
        if (! $sesi->sudahDimulai() || $sesi->sudahSelesai()) {
            return;
        }

        $pengerjaan = $this->pengerjaan($sesi, $pengguna);

        if (! $pengerjaan->sudahSelesai()) {
            $pengerjaan->hitungUlang();
            $pengerjaan->selesai_pada = now();
            $pengerjaan->save();

            // Hasil kuis yang baru selesai diberi tahu ke admin, selama admin
            // masih ingin menerima kabar itu. Saklarnya ada di Pengaturan
            // admin, jadi mematikan "Ada hasil kuis baru" benar-benar membuat
            // baris notifikasi ini tidak pernah dibuat.
            NotifikasiAdmin::hasilKuisBaru($pengerjaan);
        }

        $sesi->peserta()
            ->where('pengguna_id', $pengguna->getKey())
            ->update(['status' => PesertaQuiz::STATUS_SELESAI]);

        /*
         * Pengerjaan sudah tutup, jadi tidak ada lagi yang perlu dilanjutkan
         * di peramban ini. Sesi yang tersisa di session justru akan membuat
         * /user/quiz/{quiz}/soal/{nomor} yang dibuka tanpa tautan masuk lagi ke
         * sesi yang tidak berlaku, jadi sekalian dibuang. Tombol "Kembali
         * Mengerjakan" di halaman hasil tetap jalan karena tautannya
         * menyertakan id sesi.
         */
        SesiAktif::lupa();
    }

    /**
     * Tujuan redirect kalau tidak ada sesi yang bisa dikerjakan.
     *
     * Ini terjadi ketika /user/quiz/{quiz}/soal/{nomor} dibuka tanpa pernah
     * memulai quiz dan tanpa tautan yang menyebut sesi. Bukan error fatal,
     * jadi pengguna dikirim ke daftar quiz dengan penjelasan singkat.
     */
    private function tidakBekerja(): RedirectResponse
    {
        return redirect()
            ->route('user.quiz')
            ->withErrors(['sesi' => 'Belum ada quiz yang sedang kamu kerjakan.']);
    }

    /**
     * Halaman yang sama, tapi untuk quiz yang belum punya soal aktif.
     */
    private function tanpaSoal(SesiQuiz $sesi, User $pengguna): View
    {
        return view('user.quiz-kerjakan', [
            'sesi' => $sesi,
            'quiz' => $sesi->quiz,
            'soal' => null,
            'tanpaSoal' => true,
            'nomor' => 0,
            'jumlahSoal' => 0,
            'jawaban' => null,
            'jumlahDijawab' => 0,
            'nomorTerjawab' => [],
            'nomorRagu' => [],
            'sisaDetik' => null,
            'waktuSedikit' => self::WAKTU_SEDIKIT,
            'waktuMendekuti' => self::WAKTU_MENDEKUTI,
            'adalahHost' => $sesi->adalahHost($pengguna),
        ]);
    }

    /**
     * Tujuan redirect kalau pengguna belum boleh mengerjakan soal, atau null
     * kalau boleh.
     */
    private function larangan(SesiQuiz $sesi, User $pengguna): ?RedirectResponse
    {
        // Bukan host dan bukan peserta sesi ini: 403.
        PenjagaSesi::pastikanBolehMasuk($sesi, $pengguna);

        // Quiz sudah ditutup: semua orang pindah ke halaman hasil.
        if ($sesi->sudahSelesai()) {
            return redirect()->to($this->tujuanHasil($sesi, $pengguna));
        }

        /*
         * Host quiz mode KODE bukan peserta: tugasnya memandu, bukan ikut
         * menjawab. Sesi seperti ini selalu dibuat untuk peserta yang masuk
         * lewat kode, jadi host yang ikut mengerjakan akan muncul di rekap
         * nilai seolah-olah dia salah satu peserta.
         *
         * Bedakan dari sesi solo (tombol "Mulai Quiz" di halaman detail):
         * di sana host sekaligus satu-satunya orang yang menjawab, dan itu
         * memang yang dimaksud. Keduanya sama-sama punya host_id, jadi
         * pembedaannya hanya cara sesinya dibuat: sesi lobby hanya pernah
         * dibuat untuk quiz mode kode.
         */
        if ($sesi->adalahHost($pengguna) && $sesi->quiz->pakaiKode()) {
            return redirect()->route('user.sesi.lobby', $sesi);
        }

        // Aturan utama: soal hanya boleh dibuka setelah host memulai.
        if (! $sesi->sudahDimulai()) {
            return redirect()->route('user.sesi.lobby', $sesi);
        }

        // Sesi ini sudah pasti hidup, jadi sekarang saja yang jadikan acuan
        // halaman ini kalau dibuka lagi tanpa id sesi di URL.
        SesiAktif::pakai($sesi);

        return null;
    }

    /**
     * Posisi soal pada daftar (basis nol).
     *
     * Nomor di luar rentang dijepit ke soal terdekat, jadi /soal/0 dan
     * /soal/999 masih menampilkan soal yang paling mendekati, bukan error.
     *
     * @param  Collection<int, Soal>  $daftar
     */
    private function posisi(Collection $daftar, int $nomor): ?int
    {
        if ($daftar->isEmpty()) {
            return null;
        }

        return min(max($nomor, 1), $daftar->count()) - 1;
    }

    /**
     * Pengerjaan milik pengguna di sesi ini. Dibuat otomatis saat pertama
     * kali soal dibuka, lalu dipakai ulang setiap kali menjawab.
     */
    private function pengerjaan(SesiQuiz $sesi, User $pengguna): PengerjaanQuiz
    {
        $pengerjaan = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->where('pengguna_id', $pengguna->getKey())
            ->first();

        if ($pengerjaan !== null) {
            return $pengerjaan;
        }

        return PengerjaanQuiz::create([
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $pengguna->getKey(),
            'quiz_id' => $sesi->quiz_id,
            'jumlah_soal' => $sesi->quiz->soal()->aktif()->count(),
            'dimulai_pada' => now(),
        ]);
    }

    /**
     * Jawaban yang tersimpan untuk satu soal, atau null kalau belum dijawab.
     */
    private function jawabanSoal(PengerjaanQuiz $pengerjaan, Soal $soal): ?JawabanQuiz
    {
        return JawabanQuiz::query()
            ->where('pengerjaan_quiz_id', $pengerjaan->getKey())
            ->where('soal_id', $soal->getKey())
            ->first();
    }

    /**
     * Nomor soal yang jawabannya sudah tersimpan, urut menaik.
     *
     * @param  Collection<int, Soal>  $daftar
     * @return array<int, int>
     */
    private function nomorTerjawab(PengerjaanQuiz $pengerjaan, Collection $daftar): array
    {
        return $this->nomorSesuaiId(
            $daftar,
            $pengerjaan->jawaban()->pluck('soal_id')
        );
    }

    /**
     * Nomor soal yang ditandai "ragu", urut menaik.
     *
     * @param  Collection<int, Soal>  $daftar
     * @return array<int, int>
     */
    private function nomorRagu(PengerjaanQuiz $pengerjaan, Collection $daftar): array
    {
        return $this->nomorSesuaiId(
            $daftar,
            SoalRagu::query()
                ->where('pengerjaan_quiz_id', $pengerjaan->getKey())
                ->pluck('soal_id')
        );
    }

    /**
     * Ubah daftar id soal jadi daftar nomor urut soal, urut menaik.
     *
     * Dua hal yang dikerjakan di sini, dan keduanya penting supaya view
     * tidak perlu tahu soal mana yang mana:
     *
     *   - Yang dikirim adalah nomor urut (1..N), bukan id soal. View hanya
     *     mencetak angka di dalam kotak-kotaknya, jadi yang diterimanya
     *     cukup data yang bisa dibaca manusia.
     *   - Id dari database bisa datang sebagai string pada sebagian driver
     *     (mis. pgsql), jadi semuanya dikembalikan ke int lebih dulu supaya
     *     perbandingan dengan in_array yang ketat selalu benar.
     *
     * Urut naik bukan kebetulan: view memakai hasil ini langsung di dalam
     * satu larik, jadi kotak nomor soal-soal yang sudah dijawab dimulai dari
     * kotak pertama, bukan disebar di antara kotak yang belum dijawab.
     *
     * @param  Collection<int, Soal>  $daftar
     * @param  Collection<int, mixed>  $id
     * @return array<int, int>
     */
    private function nomorSesuaiId(Collection $daftar, $id): array
    {
        $dicari = $id
            ->map(fn ($nilai) => (int) $nilai)
            ->all();

        return $daftar
            ->values()
            ->map(fn (Soal $soal, int $index) => in_array((int) $soal->getKey(), $dicari, true)
                ? $index + 1
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Sesi yang sedang dikerjakan, kalau memang sesi milik quiz itu.
     *
     * Sesi dibaca dari session pengguna (App\Support\SesiAktif) karena id
     * sesi sengaja tidak ikut di URL.
     *
     * $quiz boleh null, yaitu saat halaman dibuka lewat URL lama yang tidak
     * menyebut quiz (/user/judulsoal/soal/{nomor}). Kalau null, quiz diambil
     * dari sesi itu sendiri.
     *
     * Kalau $quiz diberikan tapi berbeda dari quiz milik sesi, jawabannya
     * null: sesi quiz lain tidak boleh dipakai untuk membuka soal quiz ini.
     * Tanpa pengecekan ini, kombinasi "/seputar-teknologi/soal/1" dengan id
     * sesi milik quiz lain akan menampilkan soal yang salah di session yang
     * salah.
     */
    private function sesiUntuk(Request $request, ?Quiz $quiz): ?SesiQuiz
    {
        $sesi = SesiAktif::cari(SesiAktif::idDari($request));

        if ($sesi === null) {
            return null;
        }

        if ($quiz === null) {
            return $sesi;
        }

        return (int) $sesi->quiz_id === (int) $quiz->getKey() ? $sesi : null;
    }

    /**
     * Sisa waktu dalam detik, atau null kalau quiz ini tidak punya batas
     * waktu.
     *
     * Hitungannya hidup di PengerjaanQuiz::sisaDetik() supaya timer di
     * halaman ini dan pemeriksaan "percobaan ini sudah kehabisan waktu" di
     * QuizMulaiController tidak pernah berbeda jawaban untuk pengerjaan
     * yang sama.
     */
    private function sisaDetik(Quiz $quiz, PengerjaanQuiz $pengerjaan): ?int
    {
        return $pengerjaan->sisaDetik($quiz);
    }

    /**
     * Tandai peserta sedang mengerjakan, supaya status di lobby tidak lagi
     * menampilkan "Siap".
     */
    private function tandaiMengerjakan(SesiQuiz $sesi, User $pengguna): void
    {
        $sesi->peserta()
            ->where('pengguna_id', $pengguna->getKey())
            ->where('status', PesertaQuiz::STATUS_LOBBY)
            ->update(['status' => PesertaQuiz::STATUS_MENGERJAKAN]);
    }
}
