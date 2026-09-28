<?php

namespace App\Support;

use App\Models\PesertaQuiz;

/**
 * Mengubah daftar peserta sesi menjadi array polos yang siap dipakai
 * komponen tampilan.
 *
 * Bentuknya sengaja sama dengan App\Support\DaftarQuiz dan
 * App\Support\DaftarMateri: komponen di resources/views/components/sesi
 * tidak tahu-menahu soal Eloquent, hanya menerima array. Kalau nanti
 * sumbernya diganti API, cukup ganti isi petakan() tanpa menyentuh markup.
 *
 * Bentuk array per peserta:
 *   id, nama, inisial, warna, warna_gelap, status, status_label,
 *   bergabung_pada, baru => bool (hanya baris baru di respons polling)
 *
 * Daftar di lobby juga dipakai endpoint JSON yang dipanggil JavaScript,
 * jadi array yang sama bisa langsung dikirim ke browser.
 */
final class DaftarPeserta
{
    /**
     * Petakan sekumpulan peserta ke bentuk array yang dipakai komponen.
     *
     * Urutan hasil mengikuti waktu bergabung, jadi nama yang baru masuk
     * selalu muncul di paling bawah dan animasi masuknya terasa alami.
     *
     * @param  iterable<int, PesertaQuiz>  $peserta
     * @return array<int, array<string, mixed>>
     */
    public static function petakan(iterable $peserta): array
    {
        $hasil = [];

        foreach ($peserta as $item) {
            $pengguna = $item->pengguna;
            $nama = $pengguna?->nama ?? 'Tanpa nama';
            $avatar = $pengguna?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];

            $hasil[] = [
                'id' => $item->getKey(),
                'pengguna_id' => $item->pengguna_id,
                'nama' => $nama,
                'inisial' => $pengguna?->inisial() ?? '?',
                'warna' => $avatar['warna'],
                'warna_gelap' => $avatar['warna_gelap'],
                'status' => (string) $item->status,
                'status_label' => self::labelStatus((string) $item->status),
                'bergabung_pada' => $item->bergabung_pada?->format('H:i'),
            ];
        }

        return $hasil;
    }

    /**
     * Label status peserta untuk ditampilkan di daftar lobby.
     */
    public static function labelStatus(string $status): string
    {
        return match ($status) {
            PesertaQuiz::STATUS_MENGERJAKAN => 'Mengerjakan',
            PesertaQuiz::STATUS_SELESAI => 'Selesai',
            default => 'Siap',
        };
    }
}
