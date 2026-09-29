<?php

namespace App\Support;

use App\Models\PesertaQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\User;

/**
 * Satu-satunya cara membuat sesi lobby untuk quiz mode kode.
 *
 * Yang membuat ini adalah KODE QUIZ, bukan sesi. Satu quiz mode kode punya
 * satu kode akses, dan semua orang yang mengetik kode itu masuk ke sesi yang
 * sama. Karena itu sesi untuk quiz ini tidak perlu dibuat dua kali: siapa pun
 * yang memulai, hasilnya tetap satu lobby dengan satu daftar peserta.
 *
 * Host sesi selalu pemilik quiz, bukan orang yang pertama mengetik kode.
 * Kalau host-nya peserta yang kebetulan lebih dulu masuk, pemilik quiz
 * kehilangan hak memulai quiznya sendiri; kalau host-nya dibuat bebas, siapa
 * pun bisa mengambil alih quiz orang lain. Jadi sesi dibuat dengan
 * host_id = quiz.dibuat_oleh, dan orang yang mengetik kode hanya menjadi
 * peserta.
 */
final class SesiKode
{
    /**
     * Sesi yang sedang hidup untuk quiz ini, atau sesi baru yang baru dibuat.
     *
     * Sesi lama yang sudah ditutup tidak dipakai lagi: kode yang sama selalu
     * boleh dipakai untuk ronde berikutnya, dan karena belum ada sesi hidup,
     * sesi barulah yang dibuat dengan status "waiting" supaya host yang
     * baru membuka lobby bisa memulai dari nol.
     */
    public function __construct(
        public readonly SesiQuiz $sesi,
        public readonly bool $baruDibuat,
    ) {}

    /**
     * Buka sesi lobby untuk quiz mode kode, atau pakai sesi yang sudah ada.
     *
     * Quiz yang punya sesi hidup tidak mendapat sesi kedua, jadi dua lobby
     * untuk satu quiz tidak mungkin terjadi walau host menekan tombolnya
     * beberapa kali.
     */
    public static function bukaAtauBuat(Quiz $quiz): self
    {
        $yangAda = SesiQuiz::query()
            ->where('quiz_id', $quiz->getKey())
            ->belumSelesai()
            ->latest('id')
            ->first();

        if ($yangAda !== null) {
            return new self($yangAda, false);
        }

        /*
         * Kode sesi disamakan dengan kode akses quiz, bukan dibikin acak.
         * Satu-satunya kode yang peserta baca dari lobby adalah kolom ini,
         * jadi kalau berbeda dari kode yang harus mereka ketik, Lobby hanya
         * menampilkan angka yang tidak bisa dipakai.
         */
        $sesi = SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $quiz->dibuat_oleh,
            'kode' => $quiz->kodeGabung(),
            'status' => SesiQuiz::STATUS_MENUNGGU,
        ]);

        return new self($sesi, true);
    }

    /**
     * Tambahkan pengguna sebagai peserta sesi ini, atau kembalikan sesi yang
     * sudah diikutinya.
     *
     * Peserta yang sudah pernah bergabung tidak dibuat dua baris: kode yang
     * sama bisa diketik berkali-kali, dan satu orang hanya boleh punya satu
     * tempat di daftar peserta.
     *
     * Host tidak dibuat jadi peserta. Ia sudah memegang kendali sesi, dan
     * barinya sendiri tidak akan pernah ikut dihitung sebagai peserta.
     *
     * Hasilnya:
     *   sudah_dibuat = baris peserta benar-benar baru dibuat
     */
    public static function gabung(SesiQuiz $sesi, User $pengguna): self
    {
        if ($sesi->adalahHost($pengguna) || PenjagaSesi::sudahIkut($sesi, $pengguna)) {
            return new self($sesi, false);
        }

        $sesi->peserta()->create([
            'pengguna_id' => $pengguna->getKey(),
            'status' => PesertaQuiz::STATUS_LOBBY,
            'bergabung_pada' => now(),
        ]);

        return new self($sesi, true);
    }
}
