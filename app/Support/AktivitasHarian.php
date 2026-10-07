<?php

namespace App\Support;

use App\Models\AktivitasHarian as BarisAktivitas;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Mencatat kegiatan belajar harian dan menghitung streak dari catatannya.
 *
 * Ada dua tanggung jawab yang sengaja diletakkan di satu kelas, bukan
 * dipisah-pisah, karena keduanya membaca dan menulis tabel yang sama:
 *
 *   - catat(): dipanggil dari halaman baca materi dan halaman menjawab soal,
 *     setiap kali seorang pengguna benar-benar belajar.
 *   - streak(): dipanggil dari dashboard untuk tahu berapa hari berturut-turut
 *     ia belajar, dan apakah rantainya masih menyala.
 *
 * Baris tabelnya dibaca lewat App\Models\AktivitasHarian yang diimpor dengan
 * nama lain, karena nama yang sama dipakai kelas ini sendiri: nama file dan
 * nama model boleh sama, nama yang ditulis di dalam file tidak boleh.
 *
 * Bentuk hasil streak() sengaja sudah pas untuk dipakai langsung di view:
 *
 *   jumlah   => jumlah hari berturut-turut, 0 kalau rantainya sudah padam
 *   aktif    => true kalau menyala (merah), false kalau padam (abu)
 *   terakhir => kapan kegiatan terakhirnya terjadi
 *
 * Aturan 24 jam
 * -------------
 * Rantai dianggap padam kalau aktivitas terakhirnya sudah lebih dari 24 jam
 * yang lalu, bukan "kalau tidak ada baris untuk tanggal hari ini". Bedanya
 * nyata: pengguna yang belajar pukul 23.00 lalu membuka dashboard lagi pukul
 * 09.00 keesokan hari tetap dihitung aktif, karena baru 10 jam berlalu. Yang
 * mematikan rantainya adalah 24 jam penuh tanpa satu pun kegiatan, dan saat
 * itu angkanya langsung 0 supaya angka yang masih terlihat tidak memberi
 * kesan ada rantai yang sebenarnya sudah terputus.
 *
 * Setelah reset, hari yang masih punya kegiatan dihitung dari 1 lagi, bukan
 * dilanjutkan dari angka sebelumnya.
 */
final class AktivitasHarian
{
    /**
     * Berapa jam sebelum aktivitas terakhir dianggap sudah kedaluwarsa.
     *
     * Dua puluh empat jam, sesuai aturan streak: tidak ada kegiatan dalam
     * sehari penuh berarti rantai padam.
     */
    public const BATAS_JAM = 24;

    /**
     * Tanggal paling tua yang masih mungkin memengaruhi streak.
     *
     * Dipakai untuk memangkas baris yang tidak berguna: setiap hari yang
     * sudah lewat pasti sudah lebih lama dari batas 24 jam, jadi tidak mungkin
     * lagi menjadi bagian rantai yang menyala. Pemangkasan ini terjadi di
     * streak(), bukan di sini, supaya pemanggilnya tidak ikut melakukan
     * penulisan.
     */
    public static function batasTanggal(?Carbon $sekarang = null): Carbon
    {
        // copy() wajib, bukan cuma ($sekarang ?? now()): objek Carbon bisa
        // diubah isinya di tempat, jadi subDay() tanpa copy() ikut menggeser
        // tanggal milik pemanggil. Kalau tidak begini, streak() yang memanggil
        // fungsi ini untuk memangkas baris ikut mundur satu hari tanpa sadar,
        // dan seluruh perhitungannya jadi salah.
        return ($sekarang ?? now())->copy()->subDay()->startOfDay();
    }

    /**
     * Catat satu kegiatan belajar untuk seorang pengguna pada hari ini.
     *
     * Aman dipanggil berkali-kali untuk kegiatan yang sama: barisnya dibuat
     * sekali saja, panggilan berikutnya hanya menyentuh updated_at. Ini
     * penting karena satu sesi menjawab soal memuat banyak soal, dan satu
     * materi bisa dibuka berulang kali.
     *
     * Pengguna boleh null supaya pemanggilnya tidak perlu mengeceknya sendiri;
     * halaman yang memang bisa dibuka tanpa login sudah punya pengguna null
     * dari outset.
     */
    public static function catat(?User $pengguna, string $jenis, ?Carbon $sekarang = null): void
    {
        if ($pengguna === null) {
            return;
        }

        $sekarang ??= now();

        /*
         * Tanggalnya dikirim sebagai Carbon, bukan teks Y-m-d.
         *
         * Alasannya Kolom tanggal punya cast "date" di model, jadi yang
         * masuk ke database adalah "2026-10-06 00:00:00". Kalau pencarian
         * baris yang sama memakai teks "2026-10-06", bentuk keduanya tidak
         * sama persis: di PostgreSQL kolom date ikut menormalkan keduanya
         * sehingga barisnya ketemu, tapi di SQLite perbandingannya
         * membandingkan teks apa adanya sehingga baris yang sebenarnya
         * sudah ada tidak ketemu, updateOrCreate lalu mencoba insert
         * kedua untuk kombinasi yang sama dan ditolak unique constraint.
         *
         * Mengirim Carbon membuat sisi cari dan sisi simpan memakai satu
         * bentuk nilai yang sama persis di kedua database, jadi catatan
         * hari ini selalu memperbarui baris yang sama.
         */
        $tanggal = $sekarang->copy()->startOfDay();

        /*
         * updated_at diisi lewat penulisan langsung, bukan diteruskan ke
         * updateOrCreate. Aturan fillable model sengaja tidak memuat
         * updated_at, jadi kalau kolom itu dikirim sebagai nilai kedua,
         * Eloquent membuangnya lalu diisi ulang dengan waktu sekarang —
         * persis informasi yang paling dibutuhkan fungsi ini, yaitu kapan
         * kegiatan terakhir benar-benar terjadi. created_at tetap menandai
         * kapan hari itu pertama kali dicatat.
         */
        $baris = BarisAktivitas::query()->updateOrCreate(
            [
                'pengguna_id' => $pengguna->getKey(),
                'tanggal' => $tanggal,
                'jenis' => $jenis,
            ],
        );

        $baris->updated_at = $sekarang;
        $baris->save();
    }

    /**
     * Catat kegiatan membaca materi.
     */
    public static function bacaMateri(?User $pengguna, ?Carbon $sekarang = null): void
    {
        self::catat($pengguna, BarisAktivitas::JENIS_BACA_MATERI, $sekarang);
    }

    /**
     * Catat kegiatan mengerjakan quiz.
     */
    public static function kerjakanQuiz(?User $pengguna, ?Carbon $sekarang = null): void
    {
        self::catat($pengguna, BarisAktivitas::JENIS_KERJAKAN_QUIZ, $sekarang);
    }

    /**
     * Streak belajar harian seorang pengguna.
     *
     * Hitungannya berjalan mundur per hari kalender, jadi yang dihitung
     * benar-benar "berapa hari berturut-turut belajar", bukan "berapa baris
     * aktivitas minggu ini". Karena itu tanggalnya dirapikan dulu: satu hari
     * bisa punya dua baris — baca materi dan kerjakan quiz sekaligus — dan
     * dua baris di tanggal yang sama itu tetap harus dihitung sebagai satu
     * hari, bukan dua.
     *
     * Pemangkasan baris yang sudah lewat batas dilakukan di sini, di tengah
     * pembacaan, supaya tabel tidak tumbuh tanpa batas untuk pengguna yang
     * jarang masuk. Baris yang dipangkas dijamin tidak bisa memengaruhi
     * jawaban: hitungan berhenti begitu tanggal yang ditemukan tidak lagi
     * cocok dengan hari yang sedang dihitung.
     *
     * @return array{jumlah: int, aktif: bool, terakhir: ?Carbon}
     */
    public static function streak(?User $pengguna, ?Carbon $sekarang = null): array
    {
        $kosong = ['jumlah' => 0, 'aktif' => false, 'terakhir' => null];

        if ($pengguna === null) {
            return $kosong;
        }

        $sekarang ??= now();

        $baris = BarisAktivitas::query()
            ->milik($pengguna->getKey())
            ->orderByDesc('tanggal')
            ->orderByDesc('updated_at')
            ->get(['tanggal', 'updated_at']);

        if ($baris->isEmpty()) {
            return $kosong;
        }

        $terakhir = $baris->max('updated_at');
        $terakhir = $terakhir === null ? null : Carbon::parse($terakhir);

        // Baris yang sudah lewat batas tidak pernah dihitung, jadi boleh
        // dibuang sekalian.
        BarisAktivitas::query()
            ->milik($pengguna->getKey())
            ->kedaluwarsa(self::batasTanggal($sekarang))
            ->delete();

        // Rantai sudah lewat 24 jam: angka langsung 0 dan mati.
        if ($terakhir === null || $terakhir->copy()->lt($sekarang->copy()->subHours(self::BATAS_JAM))) {
            return ['jumlah' => 0, 'aktif' => false, 'terakhir' => $terakhir];
        }

        /*
         * Satu tanggal = satu hari, newest first. Rapi ini yang membuat dua
         * kegiatan di hari yang sama tidak dihitung sebagai dua hari.
         */
        $tanggal = $baris
            ->pluck('tanggal')
            ->unique()
            ->map(fn ($tanggal) => Carbon::parse($tanggal)->startOfDay())
            ->sortDesc()
            ->values();

        /*
         * Mulai hitung dari hari ini kalau hari ini sudah ada baris, kalau tidak
         * dari kemarin. Yang kedua ini yang menutup celah antara "sudah tengah
         * malam" dan "belum belajar": belajar pukul 23.00 lalu membuka dashboard
         * pukul 01.00 tetap dihitung satu hari berturut, bukan sempat terputus di
         * tengah malam.
         */
        $kunci = $sekarang->copy()->startOfDay();

        if (! $tanggal->first()->isSameDay($kunci)) {
            $kunci->subDay();
        }

        $jumlah = 0;

        foreach ($tanggal as $satuTanggal) {
            if (! $satuTanggal->isSameDay($kunci)) {
                break;
            }

            $jumlah++;
            $kunci->subDay();
        }

        return [
            'jumlah' => $jumlah,
            'aktif' => $jumlah > 0,
            'terakhir' => $terakhir,
        ];
    }

    /**
     * Jumlah hari yang punya aktivitas belajar untuk seorang pengguna.
     *
     * Dipakai kartu ringkasan dashboard supaya angka "Progress Belajar" punya
     * sumber yang sama dengan streak, bukan dihitung ulang dengan rumus lain.
     */
    public static function jumlahHari(?User $pengguna): int
    {
        if ($pengguna === null) {
            return 0;
        }

        return BarisAktivitas::query()
            ->milik($pengguna->getKey())
            ->distinct()
            ->count('tanggal');
    }
}
