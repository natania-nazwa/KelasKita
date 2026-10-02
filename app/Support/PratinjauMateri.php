<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;

/**
 * Membangun array halaman detail materi dari isian form yang belum disimpan.
 *
 * Pratinjau pada form Tambah/Edit Materi harus sama persis dengan halaman
 * detail. Yang dijamin itu bukan dengan menyalin markup-nya, tapi dengan
 * menyalin sumbernya: pratinjau merakit model Materi yang belum disimpan,
 * lalu memanggil App\Support\DetailMateri::petikan() dengan pemecah yang sama.
 * Karena itu blok kode, Daftar Isi, dan navigasi antar seksi di pratinjau
 * benar-benar keluaran komponen yang sama, bukan tiruan yang bisa menyimpang
 * begitu salah satu sisi berubah.
 *
 * Kelas ini dipakai dua kali, oleh form admin dan form pemilik, dan keduanya
 * memakai view yang sama untuk badannya (admin.materi-detail-isi). Yang
 * membedakan hanya tujuan tautan di kepala materi, jadi keduanya diteruskan
 * sebagai argumen.
 *
 * Yang tidak ikut adalah penanda yang belum ada sebelum materi disimpan:
 * jumlah dilihat dan status simpan. Keduanya selalu kosong di sini, sama
 * seperti yang terjadi kalau materi baru pertama kali dibuka.
 */
final class PratinjauMateri
{
    /**
     * Batas ukuran isian yang masih wajar untuk dipratinjau.
     *
     * Pratinjau dikirim ulang setiap kali admin berhenti mengetik, jadi
     * isiannya masuk ke memori server berulang kali. 512KB jauh di atas isi
     * materi paling panjang yang masuk akal, tapi jauh di bawah ukuran yang
     * satu kiriman bisa Boros untuk setiap request.
     */
    private const BATAS_ISI = 512 * 1024;

    /**
     * Batas panjang judul, mengikuti kolomnya di database.
     */
    private const BATAS_NAMA = 200;

    /**
     * Apakah isian terlalu besar untuk dirender.
     *
     * Pratinjau tidak menyimpan apa pun dan isiannya sudah diperiksa penuh
     * waktu form dikirim, jadi tidak ada yang perlu gagal di sini selain
     * menjaga ukuran. Ditolak dengan 422, bukan diam-diam dipotong: isi
     * yang dipotong akan menghasilkan pratinjau yang berbeda dari hasil
     * sebenarnya.
     */
    public static function melebihiBatas(string $nama, string $isi): bool
    {
        return strlen($isi) > self::BATAS_ISI || strlen($nama) > self::BATAS_NAMA;
    }

    /**
     * Array halaman detail untuk materi yang sedang disusun.
     *
     * $isi harus sudah disusun persis seperti yang akan dikirim form — yaitu
     * hasil gabung seluruh bab (lihat susunIsi() di
     * resources/js/materi-tambah.js). Kalau isinya dirangkai dengan aturan
     * lain, pratinjau akan menampilkan jumlah seksi yang berbeda dengan yang
     * nanti muncul setelah disimpan.
     *
     * @param  string  $isi  Gabungan seluruh bab, siap disimpan ke kolom isi.
     * @param  Pelajaran|null  $pelajaran  Kategori terpilih di form.
     * @param  User|null  $pembuat  Admin atau pengguna yang sedang menulis.
     * @return array<string, mixed> Bentuk yang sama dengan DetailMateri::petikan().
     */
    public static function detail(
        string $nama,
        string $isi,
        string $tautanDaftar,
        string $tautanLatihan,
        ?string $tingkatKesulitan = null,
        ?Pelajaran $pelajaran = null,
        ?User $pembuat = null,
    ): array {
        /*
         * Model ini tidak pernah menyentuh database: tidak ada save(), tidak
         * ada refresh(), dan relasinya diisi langsung dengan setRelation().
         * Yang dibutuhkan DetailMateri::petikan() hanya isi, nama, tingkat
         * kesulitan, dan dua relasi itu.
         */
        $materi = new Materi([
            'nama' => $nama !== '' ? $nama : 'Judul materi belum diisi',
            'isi' => $isi,
            'tingkat_kesulitan' => $tingkatKesulitan,
            'status' => Materi::STATUS_DRAFT,
        ]);

        $materi->setRelation('pelajaran', $pelajaran);
        $materi->setRelation('pembuat', $pembuat);

        return DetailMateri::petikan(
            $materi,
            // false: tombol Simpan disembunyikan lewat prop $simpan di
            // x-materi.detail-kepala, sama seperti di halaman detail.
            tersimpan: false,
            tautanDaftar: $tautanDaftar,
            tautanLatihan: $tautanLatihan,
        );
    }
}
