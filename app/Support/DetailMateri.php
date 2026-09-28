<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;
use DateTimeInterface;

/**
 * Mengubah model Materi menjadi array polos untuk halaman detail.
 *
 * Sama seperti DaftarMateri, komponen di resources/views/components/materi
 * tidak tahu-menahu soal Eloquent. Perbedaannya: di sini isi materi juga
 * ikut dipecah jadi seksi + blok (lewat IsiMateri) supaya komponen
 * tinggal menampilkan, tidak perlu parses markup.
 */
final class DetailMateri
{
    /**
     * Nama bulan bahasa Indonesia. Locale aplikasi masih "en", jadi
     * translatedFormat() akan menghasilkan "May", bukan "Mei".
     *
     * @var array<int, string>
     */
    private const BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * @return array{
     *     judul: string,
     *     slug: string,
     *     deskripsi: ?string,
     *     thumbnail: ?string,
     *     audio: ?string,
     *     tingkat_kesulitan: ?string,
     *     waktu_baca: int,
     *     tanggal: ?string,
     *     kategori: array{nama: string, ikon: string, warna: string, warna_gelap: string},
     *     pembuat: ?array{nama: string, inisial: string, warna: string, warna_gelap: string},
     *     seksi: array<int, array<string, mixed>>,
     *     tautan_daftar: string,
     *     tautan_latihan: string
     * }
     */
    public static function petikan(Materi $materi): array
    {
        $pelajaran = $materi->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
        $pembuat = $materi->pembuat;

        return [
            'judul' => $materi->nama,
            'slug' => $materi->slug,
            'deskripsi' => $materi->deskripsi,
            // Thumbnail dan audio disimpan di disk "public" sebagai path
            // relatif, jadi dipanggil ulang lewat asset() di sini.
            'thumbnail' => filled($materi->thumbnail) ? asset('storage/'.basename($materi->thumbnail)) : null,
            'audio' => filled($materi->audio) ? asset('storage/'.basename($materi->audio)) : null,
            'tingkat_kesulitan' => $materi->tingkat_kesulitan,
            'waktu_baca' => $materi->waktuBaca(),
            'tanggal' => self::tanggal($materi->created_at),
            'kategori' => [
                'nama' => $kategori['nama'],
                'ikon' => $kategori['ikon'],
                'warna' => $kategori['warna'],
                'warna_gelap' => $kategori['warna_gelap'],
            ],
            'pembuat' => $pembuat ? [
                'nama' => $pembuat->nama,
                'inisial' => $pembuat->inisial(),
                'warna' => $pembuat->warnaAvatar()['warna'],
                'warna_gelap' => $pembuat->warnaAvatar()['warna_gelap'],
            ] : null,
            'seksi' => IsiMateri::seksi($materi->isi, $materi->nama),
            'tautan_daftar' => route('user.materi'),
            'tautan_latihan' => route('user.quiz'),
        ];
    }

    /**
     * "12 Mei 2025" tanpa bergantung pada locale aplikasi.
     */
    private static function tanggal(mixed $waktu): ?string
    {
        if (! $waktu instanceof DateTimeInterface) {
            return null;
        }

        return $waktu->format('j').' '.self::BULAN[(int) $waktu->format('n')].' '.$waktu->format('Y');
    }
}
