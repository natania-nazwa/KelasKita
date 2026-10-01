<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Membuat notifikasi pengguna saat admin menerbitkan materi atau quiz.
 *
 * Satu-satunya pemanggilnya adalah Admin\KontenMateriController::publish dan
 * Admin\KontenQuizController::publish, jadi notifikasi di aplikasi ini hanya
 * punya satu asal: konten yang baru ditayangkan.
 *
 * Yang dikirim ke setiap pengguna adalah materi dan quiz berstatus
 * "published". Konten lain tidak pernah menyentuh tabel notifikasi, jadi
 * memakai ini sama sekali tidak mengubah apa yang terlihat di sisi pengguna.
 */
final class NotifikasiKonten
{
    /**
     * Berapa notifikasi terbaru yang ditampilkan di lonceng topbar.
     *
     * Delapan. Cukup untuk melihat isi terbaru tanpa membuat daftar panjang
     * yang harus digulir, dan jumlahnya tetap kecil supaya tidak ada query
     * berat di setiap halaman.
     */
    public const JUMLAH_DIATAS = 8;

    /**
     * Kirim notifikasi "konten baru tersedia" ke semua pengguna.
     *
     * Pesannya dan jenisnya ditentukan dari modelnya, bukan dari parameter,
     * supaya tidak ada tempat lain yang bisa membuat notifikasi dengan isi
     * yang sama tapi kalimat berbeda.
     *
     * Admin yang menerbitkan tidak ikut diberi notifikasi: ia yang baru saja
     * menekan tombolnya, jadi notifikasi untuknya hanya mengulang kabar yang
     * sudah dia kerjakan sendiri.
     *
     * @return int jumlah notifikasi yang dibuat
     */
    public static function kirim(Model $konten, ?int $idPenerbit = null): int
    {
        [$jenis, $judul, $pesan] = self::pesan($konten);

        $sasaran = User::query()
            ->where('peran', User::PERAN_USER)
            ->where('aktif', true)
            ->when(
                $idPenerbit !== null,
                fn ($query) => $query->whereKeyNot($idPenerbit)
            )
            ->pluck('id');

        if ($sasaran->isEmpty()) {
            return 0;
        }

        $baris = $sasaran->map(fn ($id) => [
            'pengguna_id' => $id,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            'konten_id' => $konten->getKey(),
            'konten_tipe' => $konten instanceof Materi
                ? Notifikasi::KONTEN_MATERI
                : Notifikasi::KONTEN_QUIZ,
        ])->all();

        Notifikasi::query()->insert($baris);

        return count($baris);
    }

    /**
     * Daftar notifikasi milik satu pengguna, siap dipakai komponen tampilan.
     *
     * Isinya sudah dipangkas jadi array polos supaya komponen tidak terikat
     * Eloquent, dan tautannya ikut diselesaikan di sini: halaman detail
     * materi memakai slug sedangkan halaman detail quiz memakai id, jadi
     * keduanya tidak bisa disusun dari satu kolom yang sama.
     *
     * Tautan hanya ditulis kalau kontennya masih ada. Materi atau quiz yang
     * sudah dihapus tidak membuat notifikasi ikut hilang — pesannya masih
     * benar sebagai kabar sejarah — tapi tautannya sudah tidak berlaku lagi.
     * Baris seperti itu ditampilkan tanpa tautan, jadi pembaca tidak
     * mengklik jalan yang berakhir di 404.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftar(?User $pengguna, int $jumlah = self::JUMLAH_DIATAS): array
    {
        if ($pengguna === null) {
            return [];
        }

        $baris = Notifikasi::query()
            ->where('pengguna_id', $pengguna->getKey())
            ->latest('id')
            ->limit($jumlah)
            ->get();

        if ($baris->isEmpty()) {
            return [];
        }

        return self::petakan($baris);
    }

    /**
     * Jumlah notifikasi yang belum dibaca, untuk titik di lonceng.
     */
    public static function belumDibaca(?User $pengguna): int
    {
        if ($pengguna === null) {
            return 0;
        }

        return Notifikasi::query()
            ->where('pengguna_id', $pengguna->getKey())
            ->belumDibaca()
            ->count();
    }

    /**
     * Ubah baris notifikasi menjadi bentuk yang dipakai view.
     *
     * @param  Collection<int, Notifikasi>  $baris
     * @return array<int, array<string, mixed>>
     */
    private static function petikan(Collection $baris): array
    {
        /*
         * Slug materi diambil sekali untuk semua baris, bukan satu query per
         * notifikasi. Halaman detail materi memang memakai slug, sementara
         * halaman detail quiz memakai id — jadi hanya sisi materi yang perlu
         * pencarian tambahan.
         */
        $slugMateri = Materi::query()
            ->whereKey($baris->where('konten_tipe', Notifikasi::KONTEN_MATERI)->pluck('konten_id')->all())
            ->pluck('slug', 'id');

        $quizAda = Quiz::query()
            ->whereKey($baris->where('konten_tipe', Notifikasi::KONTEN_QUIZ)->pluck('konten_id')->all())
            ->pluck('id')
            ->flip();

        return $baris->map(function (Notifikasi $notif) use ($slugMateri, $quizAda) {
            $tautan = match ($notif->konten_tipe) {
                Notifikasi::KONTEN_MATERI => isset($slugMateri[$notif->konten_id])
                    ? route('user.materi.detail', $slugMateri[$notif->konten_id])
                    : null,
                Notifikasi::KONTEN_QUIZ => isset($quizAda[$notif->konten_id])
                    ? route('user.quiz.detail', $notif->konten_id)
                    : null,
                default => null,
            };

            return [
                'id' => $notif->getKey(),
                'jenis' => $notif->jenis,
                'judul' => $notif->judul,
                'pesan' => $notif->pesan,
                'tautan' => $tautan,
                'dibaca' => $notif->sudahDibaca(),
                'waktu' => $notif->created_at,
                'waktu_label' => $notif->created_at?->diffForHumans() ?? '',
            ];
        })
            ->values()
            ->all();
    }

    /**
     * Jenis, judul, dan pesan notifikasi untuk satu konten.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private static function pesan(Model $konten): array
    {
        if ($konten instanceof Materi) {
            return [
                Notifikasi::JENIS_MATERI_BARU,
                'Materi baru tersedia',
                'Materi "'.$konten->nama.'" telah ditambahkan. Yuk mulai belajar!',
            ];
        }

        /** @var Quiz $konten */
        return [
            Notifikasi::JENIS_QUIZ_BARU,
            'Kuis baru tersedia',
            'Kuis "'.$konten->judul.'" telah ditambahkan. Uji pemahamanmu sekarang!',
        ];
    }
}
