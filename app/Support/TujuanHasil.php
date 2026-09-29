<?php

namespace App\Support;

use App\Models\PengerjaanQuiz;
use App\Models\SesiQuiz;
use App\Models\User;

/**
 * Menentukan halaman hasil mana yang harus dibuka untuk satu sesi.
 *
 * Ada dua halaman hasil yang berbeda dan keduanya masih dipakai:
 *
 *   - /user/uiux-design/hasil  kartu hasil. Satu layar berisi nilai,
 *     rincian jawaban, waktu pengerjaan, dan detail quiz. Ini yang dibuka
 *     begitu seseorang selesai menjawab.
 *   - /user/sesi/{sesi}/hasil  halaman hasil sesi. Selain nilai sendiri,
 *     dia satu-satunya tempat yang punya tabel rekap nilai seluruh peserta,
 *     dan satu-satunya yang bisa bilang "kamu belum mengerjakan soal".
 *
 * Keduanya membaca angka dari tb_pengerjaan_quiz yang sama, jadi nilai yang
 * tampil tidak akan pernah berbeda antarhalaman.
 *
 * Aturan pemilihannya:
 *   - Host sesi mode KODE selalu ke halaman hasil sesi. Ia memandu, bukan
 *     ikut menjawab, jadi tidak punya nilai sendiri, dan rekap peserta hanya
 *     ada di sana.
 *   - SELAIN ITU, kalau ada pengerjaan milik sendiri di sesi ini, jawabannya
 *     kartu hasil, dengan id pengerjaan sebagai query string supaya kartu itu
 *     menampilkan nilai sesi yang tepat, bukan pengerjaan lain milik orang
 *     yang sama.
 *   - Belum menjawab apa-apa tetap ke halaman hasil sesi, karena menampilkan
 *     angka nol di sana akan terbaca seperti nilai akhir.
 *
 * Satu tempat ini dipakai tiga pemanggil: SesiKerjakanController (redirect
 * setelah menjawab atau menekan "Selesai"), SesiHasilController (ketika
 * halaman hasil sesi dibuka lewat URL lama atau tautan yang masih
 * tersimpan), dan UiuxHasilController (untuk tautan "halaman hasil sesi"),
 * supaya semuanya tidak pernah memutuskan tujuan yang berbeda untuk
 * keadaan yang sama.
 */
final class TujuanHasil
{
    public static function untuk(SesiQuiz $sesi, ?User $pengguna): string
    {
        $sesi->loadMissing('quiz');

        if ($pengguna === null) {
            return route('user.sesi.hasil', $sesi);
        }

        if ($sesi->adalahHost($pengguna) && $sesi->quiz->pakaiKode()) {
            return route('user.sesi.hasil', $sesi);
        }

        $pengerjaan = PengerjaanQuiz::query()
            ->where('sesi_id', $sesi->getKey())
            ->where('pengguna_id', $pengguna->getKey())
            ->latest('id')
            ->first();

        return $pengerjaan !== null
            ? route('user.uiux.hasil', ['pengerjaan' => $pengerjaan->getKey()])
            : route('user.sesi.hasil', $sesi);
    }
}
