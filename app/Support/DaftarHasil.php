<?php

namespace App\Support;

use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mengubah satu pengerjaan quiz menjadi array polos untuk halaman "Hasil".
 *
 * Bentuk dan alasan kelas ini sama seperti App\Support\DaftarMateri dan
 * DaftarQuiz: komponen di resources/views/components/hasil tidak tahu-menahu
 * soal Eloquent, mereka hanya menerima array. Kalau nanti data hasil diambil
 * dari API, cukup ganti isi petakan() tanpa menyentuh markup.
 *
 * Bentuk array per baris riwayat:
 *   id, nilai, jumlah_soal, jumlah_dijawab, jumlah_benar, jumlah_salah,
 *   belum_dijawab, status, status_label, durasi_menit, durasi_label,
 *   tanggal, tanggal_label, judul, slug, thumbnail, tautan, kategori
 */
final class DaftarHasil
{
    /**
     * Jumlah riwayat per halaman.
     *
     * Enam, bukan delapan: satu baris riwayat lebih tinggi daripada kartu
     * materi, dan enam membagi tiga kolom tablet dan dua kolom ponsel
     * dengan utuh tanpa sisa.
     */
    public static function perHalaman(): int
    {
        return 6;
    }

    /**
     * Nilai filter status yang sah, berurutan sesuai urutan tab.
     *
     * @return array<int, string>
     */
    public static function status(): array
    {
        return [
            PengerjaanQuiz::STATUS_SELESAI,
            PengerjaanQuiz::STATUS_GAGAL,
            PengerjaanQuiz::STATUS_PROSES,
        ];
    }

    /**
     * Pilihan urutan untuk dropdown sorting.
     *
     * Nilai string-nya langsung dipakai sebagai query string, dan dibaca
     * kembali oleh PengerjaanQuiz::scopeUrut().
     *
     * @return array<int, array{nilai: string, label: string}>
     */
    public static function urutan(): array
    {
        return [
            ['nilai' => 'terbaru', 'label' => 'Terbaru'],
            ['nilai' => 'terlama', 'label' => 'Terlama'],
            ['nilai' => 'nilai-tinggi', 'label' => 'Nilai Tertinggi'],
            ['nilai' => 'nilai-rendah', 'label' => 'Nilai Terendah'],
        ];
    }

    /**
     * Petakan kumpulan pengerjaan ke bentuk array yang dipakai komponen.
     *
     * @param  iterable<int, PengerjaanQuiz>  $pengerjaan
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $pengerjaan): array
    {
        $hasil = [];

        foreach ($pengerjaan as $item) {
            $quiz = $item->quiz;
            $pelajaran = $quiz?->pelajaran;
            $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
            $durasi = self::durasiLabel($item->durasiDetik());
            $status = $item->status();

            $hasil[] = [
                'id' => $item->getKey(),
                'nilai' => (int) $item->nilai,
                'persen' => (int) $item->nilai,
                // Warna progress indicator mengikuti status, bukan warna
                // sendiri per nilai, supaya tidak ada palet baru: hijau
                // untuk lulus, merah di bawah ambang, kuning-oranye
                // selama masih dikerjakan.
                'warna_nilai' => match ($status) {
                    PengerjaanQuiz::STATUS_SELESAI => '#0f9d76',
                    PengerjaanQuiz::STATUS_GAGAL => '#d0505e',
                    default => '#d99a06',
                },
                'jumlah_soal' => (int) $item->jumlah_soal,
                'jumlah_dijawab' => (int) $item->jumlah_dijawab,
                'jumlah_benar' => (int) $item->jumlah_benar,
                'jumlah_salah' => (int) $item->jumlah_salah,
                'belum_dijawab' => $item->belumDijawab(),
                'status' => $status,
                'status_label' => $item->labelStatus(),
                'sudah_selesai' => $item->sudahSelesai(),
                'durasi_menit' => $durasi['menit'],
                'durasi_label' => $durasi['label'],
                'tanggal' => $item->selesai_pada ?? $item->dimulai_pada ?? $item->created_at,
                'tanggal_label' => ($item->selesai_pada ?? $item->dimulai_pada ?? $item->created_at)
                    ?->translatedFormat('d M Y · H:i'),
                'judul' => $quiz?->judul ?? 'Quiz sudah dihapus',
                'slug' => $quiz?->slug,
                /*
                 * Baca thumbnail lewat BerkasQuiz::url() supaya hasilnya sama
                 * persis dengan kartu di halaman Quiz: path relatif di disk
                 * publik (termasuk nama foldernya) jadi URL storage, dan URL
                 * penuh dari seeder dipakai apa adanya.
                 *
                 * basename() dulu dipakai di sini, dan itu salah: kolomnya
                 * berisi "thumbnails-quiz/contoh.jpg", jadi memotongnya
                 * menghasilkan "storage/contoh.jpg" yang menunjuk berkas yang
                 * tidak ada. Thumbnail dengan path relatif ikut rusak.
                 */
                'thumbnail' => BerkasQuiz::url($quiz?->thumbnail),
                'kategori' => [
                    'nama' => $kategori['nama'],
                    'slug' => $kategori['slug'],
                    'ikon' => $kategori['ikon'],
                    'warna' => $kategori['warna'],
                    'warna_gelap' => $kategori['warna_gelap'],
                ],
                'tautan' => route('user.hasil.detail', $item->getKey()),
            ];
        }

        return $hasil;
    }

    /**
     * Query riwayat milik satu pengguna, sudah difilter, dicari, diurutkan,
     * dan dipaginasi.
     *
     * Filter kepemilikan ditegakkan di sini (scopeMilik), bukan di view,
     * jadi hasil milik pengguna lain tidak pernah masuk ke markup.
     *
     * @param  int|null  $idPengguna  pengguna yang sedang login
     * @param  string|null  $status  'selesai' | 'gagal' | 'proses' | null
     * @param  int|null  $idQuiz  batasi ke satu quiz (riwayat per quiz)
     * @return array{baris: array<int, array<string, mixed>>, hal: LengthAwarePaginator}
     */
    public static function riwayat(
        ?int $idPengguna,
        ?string $status = null,
        string $kataKunci = '',
        string $urut = 'terbaru',
        ?int $idQuiz = null,
    ): array {
        $hal = self::query($idPengguna, $status, $kataKunci, $urut, $idQuiz)->paginate(self::perHalaman());

        return [
            'baris' => self::petakan($hal->items()),
            'hal' => $hal->withQueryString(),
        ];
    }

    /**
     * Bangun query riwayat tanpa memaginasi, dipakai untuk menghitung
     * jumlah tiap tab supaya angka di tab tidak dikarang.
     *
     * @return Builder<PengerjaanQuiz>
     */
    public static function query(?int $idPengguna, ?string $status = null, string $kataKunci = '', string $urut = 'terbaru', ?int $idQuiz = null): Builder
    {
        return PengerjaanQuiz::query()
            ->milik($idPengguna)
            ->quiz($idQuiz)
            ->status($status)
            ->cari($kataKunci)
            ->urut($urut)
            ->with(['quiz.pelajaran']);
    }

    /**
     * Jumlah pengerjaan per status, untuk angka di tiap tab.
     *
     * Mengikuti kata kunci pencarian yang sedang dipakai, jadi mengetik
     * kata kunci ikut memperbarui jumlah di tab.
     *
     * @return array<string, int>
     */
    public static function jumlahStatus(?int $idPengguna, string $kataKunci = '', ?int $idQuiz = null): array
    {
        $daftar = [];

        foreach ([null, ...self::status()] as $status) {
            $daftar[$status ?? 'semua'] = PengerjaanQuiz::query()
                ->milik($idPengguna)
                ->quiz($idQuiz)
                ->status($status)
                ->cari($kataKunci)
                ->count();
        }

        return $daftar;
    }

    /**
     * Ubah detik menjadi label yang enak dibaca.
     *
     * Pemakaian App\Support\Angka::durasi() supaya "1,3 jam" atau "45
     * menit" ditulis dengan aturan yang sama di baris riwayat, di kartu
     * waktu belajar, dan di pembanding mingguannya.
     *
     * @return array{menit: int, label: string}
     */
    public static function durasiLabel(int $detik): array
    {
        $menit = (int) ceil($detik / 60);

        return [
            'menit' => $menit,
            'label' => Angka::durasi((float) $menit),
        ];
    }

    /**
     * Satu pengerjaan menjadi array polos untuk kartu hasil yang besar.
     *
     * Bentuk dan alasan kelas ini sama seperti petakan() di atas: view
     * tidak tahu-menahu soal Eloquent, hanya menerima array. Semua angka
     * di sini diambil apa adanya dari tb_pengerjaan_quiz lewat atributnya,
     * tidak ada satu pun yang dihitung ulang di sini, jadi halaman ini
     * tidak mungkin menampilkan nilai yang berbeda dari yang disimpan
     * PengerjaanQuiz::hitungUlang().
     *
     * "batas_menit" diambil dari kolom durasi milik quiz. Nol berarti quiz
     * itu tidak dibatasi waktunya, jadi view tidak boleh menulis
     * "maksimal ..." kalau angka ini nol.
     *
     * @return array<string, mixed>
     */
    public static function ringkasanPengerjaan(PengerjaanQuiz $pengerjaan): array
    {
        $pengerjaan->loadMissing(['quiz.pelajaran']);

        $quiz = $pengerjaan->quiz;
        $pelajaran = $quiz?->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
        $durasi = self::durasiLabel($pengerjaan->durasiDetik());
        $status = $pengerjaan->status();
        $benar = (int) $pengerjaan->jumlah_benar;
        $jumlahSoal = (int) $pengerjaan->jumlah_soal;

        return [
            'nilai' => (int) $pengerjaan->nilai,
            'jumlah_soal' => $jumlahSoal,
            'jumlah_benar' => $benar,
            'jumlah_salah' => (int) $pengerjaan->jumlah_salah,
            'jumlah_dijawab' => (int) $pengerjaan->jumlah_dijawab,
            'belum_dijawab' => $pengerjaan->belumDijawab(),

            // Status, bukan predikat baru. PengerjaanQuiz sudah punya
            // ambang lulus sendiri, jadi halaman ini tidak perlu
            // menentukan "Baik" atau "Sangat Bagus" dari nilai.
            'status' => $status,
            'status_label' => $pengerjaan->labelStatus(),
            'sudah_selesai' => $pengerjaan->sudahSelesai(),

            'durasi_menit' => $durasi['menit'],
            'durasi_label' => $durasi['label'],
            'batas_menit' => (int) ($quiz?->durasi ?? 0),

            'judul' => $quiz?->judul ?? 'Quiz sudah dihapus',
            'tingkat_kesulitan' => $quiz?->tingkat_kesulitan,
            'kategori' => [
                'nama' => $kategori['nama'],
                'slug' => $kategori['slug'],
                'ikon' => $kategori['ikon'],
                'warna' => $kategori['warna'],
                'warna_gelap' => $kategori['warna_gelap'],
            ],
            'tanggal' => $pengerjaan->selesai_pada ?? $pengerjaan->dimulai_pada ?? $pengerjaan->created_at,
            'tanggal_label' => ($pengerjaan->selesai_pada ?? $pengerjaan->dimulai_pada ?? $pengerjaan->created_at)
                ?->translatedFormat('d M Y · H:i'),

            // Contoh rumus, ditulis dari angka yang benar-benar tampil
            // di halaman: 16 benar dari 20 soal = nilai 80.
            'contoh_rumus' => $jumlahSoal > 0
                ? sprintf('%d jawaban benar dari %d soal = nilai %d.', $benar, $jumlahSoal, (int) $pengerjaan->nilai)
                : null,

            'tautan_detail' => route('user.hasil.detail', $pengerjaan->getKey()),
        ];
    }
}
