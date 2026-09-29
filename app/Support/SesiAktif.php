<?php

namespace App\Support;

use App\Models\SesiQuiz;
use Illuminate\Http\Request;

/**
 * Sesi quiz yang sedang dikerjakan pengguna di peramban miliknya.
 *
 * Kenapa perlu kelas ini: URL halaman mengerjakan soal sengaja dibuat
 * pendek, yaitu /user/quiz/{quiz}/soal/{nomor}, tanpa id sesi di dalamnya.
 * Jadi sumber kebenaran "sesi mana yang sedang dikerjakan" disimpan di
 * session milik pengguna sendiri, lalu dibaca lagi di setiap permintaan.
 *
 * Id sesi tidak pernah dipercayai begitu saja:
 *   - yang diterima selalu dicek lagi oleh PenjagaSesi (403 kalau pengguna
 *     bukan host maupun peserta sesi itu), jadi menebak angka di URL tidak
 *     memberi akses apa pun;
 *   - session punya cookie sendiri yang sudah ditandatangani Laravel.
 *
 * Sesi yang tersimpan di peramban hanya petunjuk untuk menemukan halaman
 * lagi. Karena itu begitu pengguna membuka soal atau mengirim jawaban, sesi
 * itu ditulis ulang sebagai "yang aktif", sehingga satu peramban bisa
 * berpindah-pindah quiz tanpa meninggalkan jejak yang tertinggal.
 */
final class SesiAktif
{
    /** Kunci di session milik pengguna. */
    private const KUNCI = 'sesi-kerja';

    /**
     * Tandai sesi ini sebagai sesi yang sedang dikerjakan.
     */
    public static function pakai(SesiQuiz $sesi): void
    {
        session()->put(self::KUNCI, $sesi->getKey());
    }

    /**
     * Lupakan sesi yang sedang dikerjakan, dipakai setelah quiz ditutup.
     */
    public static function lupa(): void
    {
        session()->forget(self::KUNCI);
    }

    /**
     * Id sesi yang sedang dikerjakan, atau null kalau belum ada.
     */
    public static function id(): ?int
    {
        $id = session()->get(self::KUNCI);

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * Id sesi yang disebut oleh permintaan ini, atau null kalau tidak ada.
     *
     * Urutan pembacaan:
     *   1. Input "sesi" dari form atau query string. Dibaca lebih dulu supaya
     *      dua tab yang membuka quiz berbeda tidak saling menimpa, dan
     *      supaya tautan "Soal sebelumnya" tetap menuju sesi yang benar.
     *   2. Sesi yang tersimpan di session, dipakai ketika peserta sampai dari
     *      lobby atau dari tombol "Mulai Quiz" lewat tautan yang tidak
     *      menyebut sesi.
     *
     * Nilai yang bukan angka dianggap tidak ada, jadi input rusak tidak
     * membuat query mencari id yang aneh.
     */
    public static function idDari(Request $request): ?int
    {
        $dariInput = $request->input('sesi');

        if (is_numeric($dariInput)) {
            return (int) $dariInput;
        }

        return self::id();
    }

    /**
     * Model sesi untuk sebuah id, atau null kalau id-nya kosong atau sesinya
     * sudah tidak ada.
     */
    public static function cari(?int $id): ?SesiQuiz
    {
        if ($id === null) {
            return null;
        }

        return SesiQuiz::query()->find($id);
    }
}
