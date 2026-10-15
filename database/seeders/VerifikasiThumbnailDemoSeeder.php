<?php

namespace Database\Seeders;

use App\Models\Materi;
use App\Models\Pelajaran;
use App\Models\User;
use App\Support\BerkasMateri;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Satu materi contoh berstatus "pending" untuk menguji thumbnail di halaman
 * Verifikasi.
 *
 *     php artisan db:seed --class=VerifikasiThumbnailDemoSeeder
 *
 * Kenapa satu saja: tujuannya memeriksa satu hal, yaitu sampul yang diunggah
 * pengguna tampil apa adanya di daftar dan panel review Verifikasi — bukan
 * ikon mapel atau huruf kategori dengan latar warna. Batch delapan materi milik
 * MateriVerifikasiDemoSeeder tetap berguna untuk menguji antrean, tapi terlalu
 * banyak kalau hanya ingin melihat satu thumbnail.
 *
 * Bedanya dengan seeder lain: thumbnail di sini bukan URL gambar contoh, tapi
 * berkas sungguhan yang ditulis ke disk publik, sama seperti hasil unggahan
 * form. Itu sebabnya kolom thumbnail berisi path relatif ("thumbnails/...")
 * seperti kolom aslinya, dan symlink storage tetap perlu ada supaya
 * gambarnya terlihat di browser. Kalau yang dipakai URL, tampilan verifikasi
 * tetap sama saja dan tidak ada yang benar-benar teruji.
 *
 * Berkas gambarnya digambar saat seeder berjalan (butuh ekstensi GD). Kalau GD
 * tidak ada, materi tetap dibuat tanpa thumbnail supaya halaman Verifikasi
 * sekaligus menguji keadaan kedua: konten tanpa sampul, yang memakai ikon
 * kategori.
 *
 * Akun pembuat tidak dibuat oleh seeder ini. Akun pengguna pertama yang sudah
 * ada yang dipakai, supaya materi demo ini tidak pernah diam-diam berpindah
 * pemilik. Kalau belum ada satu pun akun pengguna, seeder berhenti dengan
 * pesan dan tidak membuat apa pun.
 *
 * Aman dijalankan berulang: materi dicocokkan lewat "nama", jadi baris yang
 * sudah ada diperbarui dan statusnya dikembalikan ke "pending".
 *
 * Hapus lagi dengan:
 *
 *     php artisan db:seed --class=VerifikasiThumbnailDemoHapusSeeder
 */
class VerifikasiThumbnailDemoSeeder extends Seeder
{
    /**
     * Identitas satu materi contoh.
     *
     * "pembuat" sengaja tidak ditulis di sini: akunnya dicari saat seeder
     * berjalan, bukan dikunci ke satu email tertentu.
     *
     * @var array<string, string>
     */
    private const MATERI = [
        'nama' => 'Contoh Thumbnail Verifikasi',
        'kategori' => 'matematika',
        'deskripsi' => 'Materi contoh untuk memeriksa sampul yang diunggah pengguna tampil apa adanya di halaman Verifikasi.',
        'tingkat_kesulitan' => 'Mudah',
        'estimasi_waktu' => '10 menit',
        'tips' => 'Buka daftar Verifikasi, lalu pilih baris ini dan lihat bagian Pratinjau.',
        'isi' => <<<'ISI'
        # Tujuan Materi Ini

        Materi ini tidak membahas teori apa pun. Isinya sengaja dibuat pendek
        karena satu-satunya yang perlu diperiksa adalah thumbnail-nya.

        Kalau sampul di baris daftar dan di panel review sama persis dengan
        berkas di disk publik, berarti halaman Verifikasi sudah menampilkan
        gambar yang benar-benar diunggah pengguna.

        # Apa yang Harus Dicek

        - Thumbnail di baris daftar konten memakai gambar, bukan huruf mapel
          dengan latar warna.
        - Thumbnail di kepala panel review memakai gambar yang sama.
        - Tab Pratinjau menampilkan gambar yang sama dalam ukuran penuh.
        - Memilih baris lain lalu kembali ke baris ini tidak membuat gambar
          berubah.

        # Kalau Thumbnails Kosong

        Isi materi boleh tidak memakai gambar di dalam bab. Berkas gambar
        disisipkan lewat blok gambar, sama seperti materi aslinya.
        ISI,
    ];

    /**
     * Nama berkas thumbnail di disk publik.
     *
     * Nama tetap, bukan nama acak seperti hasil unggahan, supaya berkas demo
     * bisa ditimpa setiap seeder dijalankan dan tidak menumpuk.
     */
    private const BERKAS = 'thumbnails/contoh-verifikasi-thumbnail.png';

    /**
     * Mode penghapusan.
     *
     * Tidak jadi argumen karena Artisan membuat seeder lewat container tanpa
     * mengirim opsi baris perintahnya. Penghapusan memakai kelas turunannya,
     * VerifikasiThumbnailDemoHapusSeeder, yang hanya mengubah nilai ini.
     */
    protected bool $hapus = false;

    public function run(): void
    {
        if ($this->hapus) {
            $this->hapusData();

            return;
        }

        $pembuat = $this->pembuat();

        if (! $pembuat instanceof User) {
            $this->saya('');
            $this->saya('  Belum ada akun pengguna, jadi materi contoh tidak dibuat.');
            $this->saya('  Buat akun pengguna dulu (atau jalankan MateriSeeder), lalu ulangi.');
            $this->saya('');

            return;
        }

        $kategori = Pelajaran::query()->where('slug', self::MATERI['kategori'])->first();

        if ($kategori === null) {
            $this->saya('');
            $this->saya('  Mata pelajaran "'.self::MATERI['kategori'].'" tidak ada, jadi materi contoh dilewati.');
            $this->saya('');

            return;
        }

        $thumbnail = $this->buatThumbnail();

        /*
         * Dicocokkan lewat "nama" supaya seeder ini aman dijalankan berulang:
         * satu judul hanya punya satu baris, dan statusnya selalu dikembalikan
         * ke "pending" supaya antrean Verifikasi terisi lagi setiap kali
         * seeder dijalankan.
         */
        Materi::query()->updateOrCreate(
            ['nama' => self::MATERI['nama']],
            [
                'pelajaran_id' => $kategori->getKey(),
                'dibuat_oleh' => $pembuat->getKey(),
                'slug' => str(self::MATERI['nama'])->slug()->toString(),
                'deskripsi' => self::MATERI['deskripsi'],
                'isi' => self::MATERI['isi'],
                'thumbnail' => $thumbnail,
                'tingkat_kesulitan' => self::MATERI['tingkat_kesulitan'],
                'estimasi_waktu' => self::MATERI['estimasi_waktu'],
                'tips' => self::MATERI['tips'],
                'status' => Materi::STATUS_PENDING,
                'catatan_admin' => null,
                'catatan_pengajuan' => null,
                'dipublish_pada' => null,
                'jumlah_ditolak' => 0,
            ]
        );

        $this->tampilkan($pembuat, $thumbnail);
    }

    /**
     * Akun pengguna yang jadi pemilik materi contoh.
     *
     * Dipakai akun pengguna yang sudah ada, bukan akun yang dibuat seeder ini,
     * supaya materi demo tidak pernah berpindah pemilik diam-diam. Admin
     * dilewati karena materi di verifikasi dibaca sebagai karya pengguna.
     */
    private function pembuat(): ?User
    {
        return User::query()
            ->where('peran', User::PERAN_USER)
            ->orderBy('id')
            ->first();
    }

    /**
     * Gambar thumbnail contoh lalu simpan ke disk publik.
     *
     * Hasilnya path relatif seperti hasil unggahan form, bukan URL. Itu yang
     * membuat halaman Verifikasi ikut menguji pembacaan path lewat
     * App\Support\BerkasMateri::url().
     *
     * Mengembalikan null kalau ekstensi GD tidak ada. Materi pun tetap
     * dibuat tanpa thumbnail, supaya keadaan tanpa sampul ikut teruji.
     */
    private function buatThumbnail(): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $lebar = 1120;
        $tinggi = 630;

        $gambar = imagecreatetruecolor($lebar, $tinggi);

        // Warna kategori matematika, supaya gambar contoh tidak terlihat
        // seperti kotak warna acak.
        $latar = imagecolorallocate($gambar, 139, 128, 230);
        imagefilledrectangle($gambar, 0, 0, $lebar, $tinggi, $latar);

        $putih = imagecolorallocate($gambar, 255, 255, 255);
        imagefilledrectangle($gambar, 40, 40, $lebar - 40, $tinggi - 40, $putih);

        $gelap = imagecolorallocate($gambar, 30, 27, 75);
        imagestring($gambar, 5, 80, 110, 'THUMBNAIL MILIK PENGGUNA', $gelap);
        imagestring($gambar, 4, 80, 170, 'Berkas sungguhan di storage/app/public/thumbnails,', $gelap);
        imagestring($gambar, 4, 80, 200, 'sama seperti hasil unggahan form.', $gelap);
        imagestring($gambar, 3, 80, 250, 'Data demo. Hapus dengan:', $gelap);
        imagestring($gambar, 3, 80, 280, 'php artisan db:seed --class=VerifikasiThumbnailDemoHapusSeeder', $gelap);

        /*
         * Baris-baris abu di bawah teks berfungsi sebagai isi thumbnail, jadi
         * gambarnya tidak terlihat setengah kosong dan perbedaan antara
         * gambar asli dengan kotak warna kategori jelas terlihat.
         */
        $abu = imagecolorallocate($gambar, 203, 213, 225);

        foreach ([360, 400, 440, 480] as $y) {
            imagefilledrectangle($gambar, 80, $y, $lebar - 80 - ($y % 120), $y + 18, $abu);
        }

        ob_start();
        imagepng($gambar);
        $isi = (string) ob_get_clean();

        imagedestroy($gambar);

        Storage::disk('public')->put(self::BERKAS, $isi);

        return self::BERKAS;
    }

    /**
     * Hapus materi contoh ini saja, beserta berkas thumbnailnya.
     *
     * Filter dibuat_oleh ikut dipakai supaya materi milik orang lain yang
     * kebetulan berjudul sama tidak ikut terhapus.
     */
    private function hapusData(): void
    {
        $pembuat = $this->pembuat();

        $materi = Materi::query()
            ->where('nama', self::MATERI['nama'])
            ->when($pembuat instanceof User, fn ($query) => $query->where('dibuat_oleh', $pembuat->getKey()))
            ->get();

        BerkasMateri::hapus(...$materi->pluck('thumbnail')->filter()->all());

        $jumlah = $materi->count();

        Materi::query()->whereKey($materi->pluck('id'))->delete();

        $this->saya('');
        $this->saya("  Materi contoh verifikasi sudah dihapus ({$jumlah} materi).");
        $this->saya('');
    }

    /**
     * Cetak ringkasan dan tempat melihatnya.
     */
    private function tampilkan(User $pembuat, ?string $thumbnail): void
    {
        $menunggu = Materi::query()->where('status', Materi::STATUS_PENDING)->count();

        $baris = [
            '',
            '  Data contoh thumbnail untuk Verifikasi sudah dibuat.',
            '',
            '  Materi     : '.self::MATERI['nama'],
            '  Pembuat    : '.$pembuat->nama.' ('.$pembuat->email.')',
            '  Thumbnail  : '.($thumbnail ?? '(tidak ada, GD tidak terpasang)'),
            '  Menunggu   : '.$menunggu.' materi berstatus "pending"',
            '',
            '  CARA LIHAT',
            '',
            '  1. Login sebagai ADMIN, lalu buka:',
            '       '.route('admin.verifikasi'),
            '     Baris "'.self::MATERI['nama'].'" memakai gambar thumbnail di',
            '     kiri. Klik barisnya: sampul yang sama juga muncul di kepala',
            '     panel review dan di awal tab Pratinjau.',
            '',
            '  Hapus lagi dengan:',
            '       php artisan db:seed --class=VerifikasiThumbnailDemoHapusSeeder',
            '',
        ];

        $this->saya(implode(PHP_EOL, $baris));
    }

    private function saya(string $teks): void
    {
        $this->command?->info($teks);
    }
}
