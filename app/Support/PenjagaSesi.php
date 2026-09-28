<?php

namespace App\Support;

use App\Models\SesiQuiz;
use App\Models\User;

/**
 * Penjaga akses ke sesi quiz.
 *
 * Aturan main dari fitur lobby:
 *   - Host (pembuat sesi) boleh melihat lobby, memulai, dan mengakhiri.
 *   - Peserta boleh melihat lobby, tapi TIDAK boleh memulai atau mengubah
 *     sesi.
 *   - Orang lain yang kebetulan tahu URL sesi tidak boleh masuk sama sekali.
 *
 * Semua controller sesi memanggil penjaga di sini supaya aturannya hanya
 * ditulis satu kali.
 */
final class PenjagaSesi
{
    /**
     * Sudah ikut sebagai peserta sesi ini?
     */
    public static function sudahIkut(SesiQuiz $sesi, User $pengguna): bool
    {
        return $sesi->peserta()
            ->where('pengguna_id', $pengguna->getKey())
            ->exists();
    }

    /**
     * Host atau peserta sesi ini? Cuma dua kelompok ini yang boleh masuk.
     */
    public static function bolehMasuk(SesiQuiz $sesi, User $pengguna): bool
    {
        return $sesi->adalahHost($pengguna) || self::sudahIkut($sesi, $pengguna);
    }

    /**
     * Hentikan request dengan 403 kalau pengguna tidak boleh masuk sesi ini.
     */
    public static function pastikanBolehMasuk(SesiQuiz $sesi, User $pengguna): void
    {
        abort_unless(self::bolehMasuk($sesi, $pengguna), 403);
    }

    /**
     * Hentikan request dengan 403 kalau pengguna bukan host sesi ini.
     *
     * Dipakai untuk aksi yang hanya boleh dilakukan host: memulai quiz,
     * mengakhiri quiz, dan membuka sesi baru.
     */
    public static function pastikanHost(SesiQuiz $sesi, User $pengguna): void
    {
        abort_unless($sesi->adalahHost($pengguna), 403);
    }
}
