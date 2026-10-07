<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\PengerjaanQuiz;
use App\Models\Preferensi;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Notifikasi milik admin (tabel tb_notifikasi, baris untuk pengguna dengan
 * peran admin).
 *
 * Berdampingan dengan App\Support\NotifikasiKonten, yang menulis notifikasi
 * untuk pengguna. Dua-duanya memakai satu tabel yang sama, tapi kelas ini dan
 * NotifikasiKonten tidak saling memanggil dan tidak saling membaca: notifikasi
 * yang diterima admin tidak pernah ikut hilang karena notifikasi untuk
 * pengguna, dan sebaliknya.
 *
 * Setiap metode di sini membaca saklar preferensi admin sebelum menulis apa
 * pun. Saklarnya di halaman Pengaturan bukan hiasan tampilan: mematikan
 * notifikasi_konten_terbit benar-benar membuat tidak ada baris yang pernah
 * dibuat di tabel.
 *
 * Sisi baca hanya dipakai lonceng topbar, dan lonceng itu sengaja tidak
 * dirender di halaman Pengaturan: saklarnya ada di sana, jadi notifikasi yang
 * baru saja dibuat akan memenuhi panel dengan kabar yang saklarnya baru saja
 * dimatikan. Daftar di sini karena itu tidak pernah dipanggil untuk
 * /admin/pengaturan*.
 *
 * Tidak ada sumber yang dikarang. Setiap pemberitahuan di bawah adalah
 * kejadian nyata di aplikasi ini:
 *
 *   - admin menerbitkaryanya sendiri di menu Konten Pembelajaran;
 *   - admin menyimpannya sebagai draft di halaman yang sama;
 *   - seorang pengguna menyelesaikan sebuah quiz;
 *   - seorang pengguna mengirim karyanya ke antrean Verifikasi.
 */
final class NotifikasiAdmin
{
    /**
     * Berapa notifikasi terbaru yang ditampilkan di lonceng topbar admin.
     */
    public const JUMLAH_DIATAS = 8;

    /**
     * Admin menerbitkari karyanya sendiri. Saklarnya:
     * notifikasi_konten_terbit.
     */
    public static function kontenDiterbitkan(Model $konten, User $admin): int
    {
        return self::kirim(
            ke: [$admin],
            jenis: Notifikasi::JENIS_KONTEN_TERBIT,
            saklar: 'notifikasi_konten_terbit',
            judul: 'Konten berhasil dipublikasikan',
            pesan: self::namaKonten($konten).' sudah tayang dan tersedia untuk pengguna.',
            konten: $konten,
        );
    }

    /**
     * Admin menyimpan karyanya sebagai draft. Saklarnya:
     * notifikasi_konten_draft.
     */
    public static function kontenDisimpanDraft(Model $konten, User $admin): int
    {
        return self::kirim(
            ke: [$admin],
            jenis: Notifikasi::JENIS_KONTEN_DRAFT,
            saklar: 'notifikasi_konten_draft',
            judul: 'Konten disimpan sebagai draft',
            pesan: self::namaKonten($konten).' tersimpan sebagai draft dan belum tayang.',
            konten: $konten,
        );
    }

    /**
     * Hasil kuis baru dari seorang pengguna. Saklarnya:
     * notifikasi_aktivitas_kuis.
     *
     * Dikirim ke semua admin yang mengaktifkannya, bukan hanya satu: di
     * aplikasi ini semua admin adalah pengelola yang setara.
     */
    public static function hasilKuisBaru(PengerjaanQuiz $pengerjaan): int
    {
        $peserta = $pengerjaan->pengguna;
        $quiz = $pengerjaan->quiz;

        if ($peserta === null || $quiz === null) {
            return 0;
        }

        return self::kirim(
            ke: self::semuaAdmin(),
            jenis: Notifikasi::JENIS_HASIL_KUIS,
            saklar: 'notifikasi_aktivitas_kuis',
            judul: 'Ada hasil kuis baru',
            pesan: $peserta->nama.' menyelesaikan "'.$quiz->judul.'" dengan nilai '
                .(int) $pengerjaan->nilai.'.',
            // Pengerjaan tidak punya kolom konten_tipe sendiri, jadi yang
            // disimpan adalah quiz yang jadi sumber hasilnya.
            konten: $quiz,
        );
    }

    /**
     * Karya pengguna yang masuk antrean Verifikasi. Saklarnya:
     * notifikasi_aktivitas_konten.
     *
     * Yang dikirim adalah kabar, bukan permintaan keputusan: keputusan tetap
     * diambil di halaman Verifikasi seperti biasa. Notifikasi ini tidak
     * membuat alur persetujuan baru.
     */
    public static function kontenMenunggu(Model $konten): int
    {
        $jenis = match (true) {
            $konten instanceof Materi => 'Materi',
            $konten instanceof Quiz => 'Quiz',
            default => 'Konten',
        };

        return self::kirim(
            ke: self::semuaAdmin(),
            jenis: Notifikasi::JENIS_KONTEN_MENUNGGU,
            saklar: 'notifikasi_aktivitas_konten',
            judul: 'Konten menunggu ditinjau',
            pesan: $jenis.' "'.self::namaKonten($konten).'" dikirim untuk ditinjau.',
            konten: $konten,
        );
    }

    /**
     * Tulis satu baris notifikasi untuk setiap penerima yang mengaktifkan
     * jenisnya.
     *
     * Satu query untuk membaca preferensi semua penerima, lalu satu insert
     * untuk yang boleh diberi tahu. Kalau tidak ada yang mengaktifkan
     * saklarnya, tidak ada query insert sama sekali.
     *
     * created_at dan updated_at ditulis sendiri karena insert() massif lewat
     * query builder tidak menyentuh timestamp Eloquent. Tanpa itu kolomnya
     * tetap null dan lonceng menampilkan baris tanpa waktu ("1 yang lalu"
     * berubah jadi kosong).
     *
     * @param  array<int, User>  $ke
     * @return int jumlah baris yang dibuat
     */
    private static function kirim(
        array $ke,
        string $jenis,
        string $saklar,
        string $judul,
        string $pesan,
        ?Model $konten,
    ): int {
        $idBoleh = self::idYangMengaktifkan($ke, $saklar);

        if ($idBoleh === []) {
            return 0;
        }

        $sekarang = now();

        $baris = array_map(fn (int $id) => [
            'pengguna_id' => $id,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            'konten_id' => $konten?->getKey(),
            'konten_tipe' => match (true) {
                $konten instanceof Materi => Notifikasi::KONTEN_MATERI,
                $konten instanceof Quiz => Notifikasi::KONTEN_QUIZ,
                default => null,
            },
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], $idBoleh);

        Notifikasi::query()->insert($baris);

        return count($baris);
    }

    /**
     * Id penerima yang saklarnya menyala.
     *
     * Admin yang belum punya baris preferensi dianggap saklarnya menyala,
     * sama seperti nilai bawaan di migrasi. Kalau tidak, admin yang belum
     * pernah membuka Pengaturan akan kehilangan semua notifikasi.
     *
     * @param  array<int, User>  $ke
     * @return array<int, int>
     */
    private static function idYangMengaktifkan(array $ke, string $saklar): array
    {
        if ($ke === []) {
            return [];
        }

        $id = array_map(fn (User $user) => (int) $user->getKey(), $ke);

        $dimatikan = Preferensi::query()
            ->whereIn('pengguna_id', $id)
            ->where($saklar, false)
            ->pluck('pengguna_id')
            ->map(fn ($nilai) => (int) $nilai)
            ->all();

        return array_values(array_diff($id, $dimatikan));
    }

    /**
     * Semua admin, untuk pemberitahuan yang berlaku buat semua pengelola.
     *
     * Status aktif tidak lagi menyaring: dulu admin bisa menonaktifkan
     * akunnya sendiri untuk berhenti menerima, sekarang status itu sekadar
     * penanda kapan terakhir membuka aplikasi. Notifikasi tetap tercatat dan
     * bisa dimatikan lewat saklar preferensi masing-masing.
     *
     * @return array<int, User>
     */
    private static function semuaAdmin(): array
    {
        return User::query()
            ->where('peran', User::PERAN_ADMIN)
            ->get()
            ->all();
    }

    /**
     * Nama konten untuk kalimat notifikasi, sudah diapit tanda kutip.
     */
    private static function namaKonten(Model $konten): string
    {
        return match (true) {
            $konten instanceof Materi => '"'.$konten->nama.'"',
            $konten instanceof Quiz => '"'.$konten->judul.'"',
            default => 'Konten',
        };
    }

    /**
     * Daftar notifikasi terbaru milik satu admin, siap dipakai lonceng topbar.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function daftar(?User $admin, int $jumlah = self::JUMLAH_DIATAS): Collection
    {
        if ($admin === null) {
            return collect();
        }

        return Notifikasi::query()
            ->where('pengguna_id', $admin->getKey())
            ->latest('id')
            ->limit($jumlah)
            ->get()
            ->map(fn (Notifikasi $notif) => self::petakan($notif))
            ->values();
    }

    /**
     * Jumlah notifikasi admin yang belum dibaca, untuk titik di lonceng.
     */
    public static function belumDibaca(?User $admin): int
    {
        if ($admin === null) {
            return 0;
        }

        return Notifikasi::query()
            ->where('pengguna_id', $admin->getKey())
            ->belumDibaca()
            ->count();
    }

    /**
     * Ubah satu baris Notifikasi menjadi bentuk yang dipakai view lonceng.
     *
     * @return array<string, mixed>
     */
    private static function petakan(Notifikasi $notif): array
    {
        return [
            'id' => $notif->getKey(),
            'jenis' => $notif->jenis,
            'ikon' => Notifikasi::ikon($notif->jenis),
            'judul' => $notif->judul,
            'pesan' => $notif->pesan,
            'tautan' => $notif->tautanAdmin(),
            'dibaca' => $notif->sudahDibaca(),
            'waktu' => $notif->created_at,
            'waktu_label' => $notif->created_at?->diffForHumans() ?? '',
        ];
    }
}
