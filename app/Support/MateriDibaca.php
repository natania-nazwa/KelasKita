<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\MateriDibaca as BarisMateriDibaca;
use App\Models\PengerjaanQuiz;
use App\Models\Quiz;
use App\Models\User;

/**
 * Mencatat materi mana yang sudah dibaca pengguna, dan menghitung progress
 * belajar dari catatan itu.
 *
 * Terpisah dari App\Support\AktivitasHarian yang sengaja menulis
 * "satu baris per hari": yang di sana cukup untuk menghitung streak, tapi
 * tidak bisa menjawab "berapa materi yang sudah selesai dibaca" karena nama
 * materinya tidak ikut disimpan. Dua tabel itu menjawab dua pertanyaan yang
 * berbeda, jadi sengaja tidak digabung.
 *
 * Baris tabelnya dibaca lewat App\Models\MateriDibaca yang diimpor dengan nama
 * lain, karena nama yang sama dipakai kelas ini sendiri: nama file dan nama
 * model boleh sama, nama yang ditulis di dalam file tidak boleh.
 *
 * Satu tempat ini juga yang menulis baris tb_materi_dibaca, supaya tidak ada
 * halaman lain yang bisa menulis tabel yang sama dengan aturannya sendiri.
 */
final class MateriDibaca
{
    /**
     * Tandai satu materi sudah dibaca oleh seorang pengguna.
     *
     * Aman dipanggil setiap kali halaman materi dibuka: barisnya dibuat sekali
     * saja, panggilan berikutnya hanya menyentuh updated_at. Yang terakhir
     * berulang kali dibaca jadi "materi yang sedang dipelajari" — itu
     * informasi yang berguna, jadi updated_at memang sengaja disentuh.
     *
     * Pengguna boleh null supaya pemanggilnya tidak perlu mengecek sendiri:
     * halaman yang bisa dibuka tanpa login sudah punya pengguna null dari
     * outset, dan materi yang dibaca tamu memang tidak boleh masuk ke
     * progress siapa pun.
     */
    public static function catat(?User $pengguna, ?Materi $materi): void
    {
        if ($pengguna === null || $materi === null) {
            return;
        }

        BarisMateriDibaca::query()->updateOrCreate(
            [
                'pengguna_id' => $pengguna->getKey(),
                'materi_id' => $materi->getKey(),
            ],
            ['updated_at' => now()],
        );
    }

    /**
     * Berapa materi berbeda yang sudah dibaca pengguna ini.
     *
     * Count DISTINCT, bukan count baris: unique constraint di database
     * sudah menjamin satu baris per pasangan, jadi keduanya sama, tapi
     * DISTINCT ditulis supaya angkanya tetap benar walau constraint someday
     * dilepas.
     */
    public static function jumlah(?User $pengguna): int
    {
        if ($pengguna === null) {
            return 0;
        }

        return BarisMateriDibaca::query()
            ->milik($pengguna->getKey())
            ->distinct()
            ->count('materi_id');
    }

    /**
     * Berapa quiz berbeda yang sudah pernah ia selesaikan.
     *
     * Yang dihitung quiz, bukan pengerjaan: mengerjakan kuis yang sama tiga
     * kali tetap dihitung sebagai satu quiz yang selesai, karena users-nya
     * progress belajar dan bukan activity count.
     *
     * Pengerjaan yang belum selesai tidak ikut dihitung. Quiz yang baru dibuka
     * lalu ditinggalkan bukan capaian.
     */
    public static function quizSelesai(?User $pengguna): int
    {
        if ($pengguna === null) {
            return 0;
        }

        return PengerjaanQuiz::query()
            ->where('pengguna_id', $pengguna->getKey())
            ->whereNotNull('selesai_pada')
            ->distinct()
            ->count('quiz_id');
    }

    /**
     * Persentase progress belajar seorang pengguna.
     *
     * Rumusnya: (materi dibaca + quiz selesai) / (semua materi terbit +
     * semua quiz terbit). Pembilang dan penyebut memakai satuan yang sama —
     * jumlah konten, bukan jumlah baris — jadi hasilnya benar-benar bisa
     * dibaca sebagai "berapa bagian dari materi dan quiz yang ada yang sudah
     * ia selesaikan".
     *
     * Kalau tidak ada konten yang terbit sama sekali, penyebutnya nol.
     * Pembagian dengan nol tidak menghasilkan progress, jadi hasilnya 0, bukan
     * NaN yang tampil sebagai angka aneh di kartu dashboard.
     *
     * Persentase dijepit di 100: pengguna bisa menyelesaikan lebih dari satu
     * quiz yang sama setelah materi dan quiz yang ada bertambah, dan angka di
     * kartu tidak boleh melewati 100% hanya karena itu.
     *
     * @return array{persen: float, selesai: int, total: int, materi: int, quiz: int, total_materi: int, total_quiz: int}
     */
    public static function progress(?User $pengguna): array
    {
        $materiDibaca = self::jumlah($pengguna);
        $quizSelesai = self::quizSelesai($pengguna);

        $totalMateri = Materi::query()->terbit()->count();
        $totalQuiz = Quiz::query()->terbit()->count();

        $selesai = $materiDibaca + $quizSelesai;
        $total = $totalMateri + $totalQuiz;

        return [
            'persen' => $total === 0 ? 0.0 : min(100.0, round($selesai / $total * 100, 1)),
            'selesai' => $selesai,
            'total' => $total,
            'materi' => $materiDibaca,
            'quiz' => $quizSelesai,
            'total_materi' => $totalMateri,
            'total_quiz' => $totalQuiz,
        ];
    }
}
