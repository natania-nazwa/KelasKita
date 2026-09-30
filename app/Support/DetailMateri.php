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
 *
 * Halaman detail materi di area admin memakai kelas ini juga, bukan bentuk
 * array sendiri. Admin membuka materi dengan tampilan yang sama persis
 * dengan yang dibaca pengguna, jadi sumber datanya harus sama; yang berbeda
 * hanya tujuan tombolnya, dan itu dikembalikan lewat $tautanDaftar /
 * $tautanLatihan.
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
     * @param  bool  $tersimpan  status "materi ini sudah disimpan" untuk
     *                           pengguna yang sedang login, dipakai tombol
     *                           Simpan di kepala materi.
     * @param  string|null  $tautanDaftar  tujuan tombol "Kembali" dan
     *                                     "Lihat semua". Null = daftar materi
     *                                     milik pengguna. Area admin
     *                                     mengoper route-nya sendiri supaya
     *                                     satu komponen ini bisa dipakai
     *                                     kedua sisi tanpa membuat salinan.
     * @param  string|null  $tautanLatihan  tujuan kartu latihan di dalam
     *                                      seksi. Null = daftar quiz milik
     *                                      pengguna.
     * @return array{
     *     judul: string,
     *     slug: string,
     *     deskripsi: ?string,
     *     thumbnail: ?string,
     *     tingkat_kesulitan: ?string,
     *     waktu_baca: int,
     *     tanggal: ?string,
     *     jumlah_dilihat: int,
     *     dilihat: string,
     *     tersimpan: bool,
     *     kategori: array{nama: string, ikon: string, warna: string, warna_gelap: string},
     *     pembuat: ?array{nama: string, inisial: string, warna: string, warna_gelap: string},
     *     seksi: array<int, array<string, mixed>>,
     *     tautan_daftar: string,
     *     tautan_latihan: string
     * }
     */
    public static function petikan(
        Materi $materi,
        bool $tersimpan = false,
        ?string $tautanDaftar = null,
        ?string $tautanLatihan = null,
    ): array {
        $pelajaran = $materi->pelajaran;
        $kategori = Pelajaran::warna($pelajaran?->slug ?? '', $pelajaran?->nama ?? 'Umum');
        $pembuat = $materi->pembuat;
        $jumlahDilihat = (int) ($materi->jumlah_dilihat ?? 0);

        return [
            'judul' => $materi->nama,
            'slug' => $materi->slug,
            'deskripsi' => $materi->deskripsi,
            // Thumbnail dipanggil ulang lewat BerkasMateri::url, yang tahu
            // bedanya antara path unggahan dan URL penuh.
            'thumbnail' => BerkasMateri::url($materi->thumbnail),
            'tingkat_kesulitan' => $materi->tingkat_kesulitan,
            'waktu_baca' => $materi->waktuBaca(),
            'tanggal' => self::tanggal($materi->created_at),
            'jumlah_dilihat' => $jumlahDilihat,
            'dilihat' => Angka::ringkas($jumlahDilihat),
            'tersimpan' => $tersimpan,
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
            'tautan_daftar' => $tautanDaftar ?? route('user.materi'),
            'tautan_latihan' => $tautanLatihan ?? route('user.quiz'),
        ];
    }

    /**
     * "12 Mei 2025" tanpa bergantung pada locale aplikasi.
     *
     * Dipakai juga oleh pratinjau pada form Tambah Materi, supaya tanggal
     * di kepala pratinjau ditulis dengan aturan yang sama dengan halaman
     * detail.
     */
    public static function tanggal(mixed $waktu): ?string
    {
        if (! $waktu instanceof DateTimeInterface) {
            return null;
        }

        return $waktu->format('j').' '.self::BULAN[(int) $waktu->format('n')].' '.$waktu->format('Y');
    }
}
