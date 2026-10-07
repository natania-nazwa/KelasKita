<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Membuat notifikasi yang masuk ke lonceng milik pengguna.
 *
 * Tiga hal yang bisa sampai ke sana, masing-masing dengan pemanggilnya sendiri:
 *
 *   - konten baru yang ditayangkan admin, lewat kirim() dari pemanggil di
 *     menu Konten Pembelajaran;
 *   - keputusan admin atas karya pengguna sendiri, lewat karyaDisetujui() dan
 *     karyaDitolak() dari halaman Verifikasi;
 *   - hasil kuis yang baru selesai dikerjakan, lewat quizSelesai() dari
 *     halaman soal dan halaman hasil.
 *
 * Tidak ada pemanggil lain, jadi lewat kelas ini tidak mungkin muncul
 * notifikasi yang tidak sengaja atau notifikasi ganda: tiap kabar hanya bisa
 * ditulis oleh satu tempat, pada saat kejadiannya benar-benar terjadi.
 *
 * Berdampingan dengan App\Support\NotifikasiAdmin, yang menulis baris untuk
 * lonceng admin. Dua-duanya memakai satu tabel yang sama, tapi tidak saling
 * memanggil dan tidak saling membaca: notifikasi yang diterima admin tidak
 * pernah ikut hilang karena notifikasi untuk pengguna, dan sebaliknya.
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
     * Status aktif tidak lagi menyaring sasaran: yang lama tidak membuka tetap
     * menerima, dan notifikasinya menunggu sampai ia kembali membuka aplikasi.
     *
     * @return int jumlah notifikasi yang dibuat
     */
    public static function kirim(Model $konten, ?int $idPenerbit = null): int
    {
        [$jenis, $judul, $pesan] = self::pesan($konten);

        $sasaran = User::query()
            ->where('peran', User::PERAN_USER)
            ->when(
                $idPenerbit !== null,
                fn ($query) => $query->whereKeyNot($idPenerbit)
            )
            ->pluck('id');

        if ($sasaran->isEmpty()) {
            return 0;
        }

        return self::tulisMassal($sasaran, $jenis, $judul, $pesan, [
            'konten_id' => $konten->getKey(),
            'konten_tipe' => $konten instanceof Materi
                ? Notifikasi::KONTEN_MATERI
                : Notifikasi::KONTEN_QUIZ,
        ]);
    }

    /**
     * Beri tahu pemilik karyanya bahwa admin sudah menyetujuinya.
     *
     * Dipanggil sekali, tepat setelah Materi::setujui() atau
     * Quiz::setujui(), jadi kabar ini tidak pernah muncul untuk karya yang
     * statusnya belum benar-benar berubah.
     *
     * Konten yang dibuat admin sendiri tidak pernah lewat ke sini: yang
     * diputuskan admin adalah karya orang lain, dan admin tidak butuh diberi
     * tahu tentang keputusannya sendiri.
     *
     * @return int jumlah notifikasi yang dibuat, 0 kalau tidak ada yang perlu diberi tahu
     */
    public static function karyaDisetujui(Model $konten): int
    {
        $pemilik = self::pemilik($konten);

        if ($pemilik === null) {
            return 0;
        }

        [$judul, $pesan] = self::pesanKeputusan($konten, true);

        return self::tulisMassal([$pemilik->getKey()], Notifikasi::JENIS_KARYA_DISETUJUI, $judul, $pesan, [
            'konten_id' => $konten->getKey(),
            'konten_tipe' => $konten instanceof Materi
                ? Notifikasi::KONTEN_MATERI
                : Notifikasi::KONTEN_QUIZ,
        ]);
    }

    /**
     * Beri tahu pemilik karyanya bahwa karyanya ditolak, beserta alasannya.
     *
     * Alasan yang diketik admin ikut dibawa utuh, karena inilah satu-satunya
     * cara pengguna mengetahui kenapa karyanya tidak tayang — tanpa ini dia
     * harus membuka Karya Saya untuk mencarinya sendiri.
     *
     * @return int jumlah notifikasi yang dibuat, 0 kalau tidak ada yang perlu diberi tahu
     */
    public static function karyaDitolak(Model $konten, string $alasan): int
    {
        $pemilik = self::pemilik($konten);

        if ($pemilik === null) {
            return 0;
        }

        [$judul, $pesan] = self::pesanKeputusan($konten, false);

        $pesan .= ' Alasan: '.$alasan;

        return self::tulisMassal([$pemilik->getKey()], Notifikasi::JENIS_KARYA_DITOLAK, $judul, $pesan, [
            'konten_id' => $konten->getKey(),
            'konten_tipe' => $konten instanceof Materi
                ? Notifikasi::KONTEN_MATERI
                : Notifikasi::KONTEN_QUIZ,
        ]);
    }

    /**
     * Beri tahu peserta bahwa hasil quiznya sudah selesai dan tersimpan.
     *
     * Tautannya langsung ke rincian jawaban, bukan ke daftar menu Hasil
     * secara umum, supaya peserta tidak perlu mencari lagi pengerjaan yang
     * tadi ia kerjakan di antara banyak pengerjaan lain.
     *
     * Notifikasi ini hanya dibuat kalau pengerjaannya benar-benar baru
     * ditutup: pemanggilnya menulisnya di dalam blok "belum selesai", jadi
     * membuka ulang halaman hasil tidak akan menumpuk baris yang isinya
     * sama.
     */
    public static function quizSelesai(PengerjaanQuiz $pengerjaan, User $pengguna): int
    {
        $quiz = $pengerjaan->quiz()->first();

        if (! $quiz instanceof Quiz) {
            return 0;
        }

        $judul = $pengerjaan->nilai >= PengerjaanQuiz::NILAI_LULUS
            ? 'Quiz selesai, nilai kamu bagus!'
            : 'Quiz selesai';

        $pesan = 'Hasil quiz "'.$quiz->judul.'" dengan nilai '.$pengerjaan->nilai
            .'% sudah tersimpan di menu Hasil.';

        return self::tulisMassal([$pengguna->getKey()], Notifikasi::JENIS_QUIZ_SELESAI, $judul, $pesan, [
            'konten_id' => $pengerjaan->getKey(),
            'konten_tipe' => Notifikasi::KONTEN_PENGERJAAN,
        ]);
    }

    /**
     * Tulis baris notifikasi untuk beberapa pengguna sekaligus.
     *
     * Satu tempat untuk semua pengiriman supaya kolom created_at/updated_at
     * selalu diisi: insert() massal lewat query builder tidak menyentuh
     * timestamp Eloquent, dan tanpa ini lonceng menampilkan baris tanpa waktu.
     *
     * @param  Collection<int, int>|array<int, int>  $sasaran
     * @param  array<string, mixed>  $tambahan
     * @return int jumlah baris yang ditulis
     */
    private static function tulisMassal(
        Collection|array $sasaran,
        string $jenis,
        string $judul,
        string $pesan,
        array $tambahan = [],
    ): int {
        if ($sasaran instanceof Collection) {
            $sasaran = $sasaran->all();
        }

        if ($sasaran === []) {
            return 0;
        }

        $sekarang = now();

        $baris = array_map(fn ($id) => [
            'pengguna_id' => $id,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            ...$tambahan,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], $sasaran);

        Notifikasi::query()->insert($baris);

        return count($baris);
    }

    /**
     * Pemilik karya yang layak diberi tahu, atau null kalau tidak ada.
     *
     * Hanya karya milik pengguna biasa yang bisa punya pemilik di sini.
     * Karya milik admin sendiri tidak pernah menunggu keputusan admin, jadi
     * keputusan itu tidak pernah sampai ke NotifikasiKonten; dan baris yang
     * ditolak begitu saja karena tidak ada yang perlu diberi tahu.
     */
    private static function pemilik(Model $konten): ?User
    {
        $id = $konten instanceof Materi || $konten instanceof Quiz
            ? $konten->dibuat_oleh
            : null;

        if ($id === null) {
            return null;
        }

        $pemilik = User::query()->find($id);

        return $pemilik?->isAdmin() === true ? null : $pemilik;
    }

    /**
     * Judul dan pesan untuk kabar keputusan admin atas satu karya.
     *
     * @return array{0: string, 1: string}
     */
    private static function pesanKeputusan(Model $konten, bool $disetujui): array
    {
        /** @var Materi|Quiz $konten */
        $benda = $konten instanceof Materi ? 'Materi' : 'Quiz';
        $judulKarya = $konten instanceof Materi ? $konten->nama : $konten->judul;

        if ($disetujui) {
            return [
                'Karyamu disetujui admin',
                $benda.' "'.$judulKarya.'" disetujui dan sekarang tayang untuk semua pengguna.',
            ];
        }

        return [
            'Karyamu ditolak admin',
            $benda.' "'.$judulKarya.'" ditolak dan belum tayang.',
        ];
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

        return self::petikan($baris);
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

        /*
         * Pengerjaan yang boleh ditautkan harus milik penerima notifikasi itu
         * sendiri, bukan pengerjaan orang lain yang kebetulan punya id yang
         * sama di baris lain: notifikasi "hasil kuis" tidak boleh jadi jalan
         * untuk membuka jawaban milik pengguna yang berbeda.
         */
        $penerima = $baris
            ->where('konten_tipe', Notifikasi::KONTEN_PENGERJAAN)
            ->mapWithKeys(fn (Notifikasi $notif) => [$notif->konten_id => $notif->pengguna_id]);

        $pengerjaanMilik = $penerima->isEmpty()
            ? collect()
            : PengerjaanQuiz::query()
                ->whereIn('id', $penerima->keys()->all())
                ->whereIn('pengguna_id', $penerima->values()->unique()->all())
                ->get()
                ->keyBy('id')
                ->all();

        return $baris->map(function (Notifikasi $notif) use ($slugMateri, $quizAda, $penerima, $pengerjaanMilik) {
            $idPengerjaan = $notif->konten_tipe === Notifikasi::KONTEN_PENGERJAAN
                ? $notif->konten_id
                : null;

            $tautan = match ($notif->konten_tipe) {
                Notifikasi::KONTEN_MATERI => isset($slugMateri[$notif->konten_id])
                    ? route('user.materi.detail', $slugMateri[$notif->konten_id])
                    : null,
                Notifikasi::KONTEN_QUIZ => isset($quizAda[$notif->konten_id])
                    ? route('user.quiz.detail', $notif->konten_id)
                    : null,
                Notifikasi::KONTEN_PENGERJAAN => $idPengerjaan !== null
                    && isset($pengerjaanMilik[$idPengerjaan])
                    && $penerima[$idPengerjaan] === $notif->pengguna_id
                        ? route('user.hasil.detail', $idPengerjaan)
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
