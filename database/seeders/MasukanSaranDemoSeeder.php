<?php

namespace Database\Seeders;

use App\Models\MasukanSaran;
use App\Models\User;
use App\Support\BerkasMasukan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk halaman "Masukan & Saran" di area admin.
 *
 *     php artisan db:seed --class=MasukanSaranDemoSeeder
 *
 * Yang dibuatnya: beberapa akun pengguna demo, lalu tujuh kiriman masukan
 * dengan campuran status (baru / diproses / selesai) dan campuran lampiran
 * (tiga berisi gambar, sisanya tanpa lampiran). Jadi setelah dijalankan:
 *
 *   - sidebar admin menampilkan angka di menu "Masukan & Saran" karena ada
 *     kiriman berstatus "baru";
 *   - tabel admin punya baris yang bisa dibuka, sebagian dengan tombol
 *     "Buka lampiran" dan sebagian tanpa, supaya kedua keadaan terlihat.
 *
 * PENTING: seeder ini menulis ke database yang dikonfigurasi di .env. Di
 * proyek ini database-nya Supabase, bukan SQLite lokal, jadi jalankan hanya
 * kalau memang ingin data ini ada di sana. Hapus lagi dengan:
 *
 *     php artisan db:seed --class=MasukanSaranDemoHapusSeeder
 *
 * Aman dijalankan berulang: kiriman demo lama milik akun demo dihapus lebih
 * dulu sebelum yang baru dibuat, dan gambar lampirannya ditimpa.
 *
 * Gambar lampirannya digambar saat seeder berjalan (butuh ekstensi GD).
 * Kalau GD tidak ada, barisnya tetap dibuat tetapi tanpa lampiran, supaya
 * seeder tidak gagal total di lingkungan yang tidak punya GD.
 */
class MasukanSaranDemoSeeder extends Seeder
{
    /**
     * Akun demo. Domain @kelaskita.test dipakai supaya tidak pernah
     * bertabrakan dengan akun asli, dan gampang dikenali saat mau dihapus.
     *
     * @var array<int, array{nama: string, email: string}>
     */
    private const PENGGUNA = [
        ['nama' => 'Natania Nazwa Gisella', 'email' => 'natania.demo@kelaskita.test'],
        ['nama' => 'Budi Santoso', 'email' => 'budi.demo@kelaskita.test'],
        ['nama' => 'Rina Wati', 'email' => 'rina.demo@kelaskita.test'],
    ];

    /**
     * Kiriman demo.
     *
     * "lampiran" adalah label gambar yang digambar untuk kiriman itu; null
     * berarti kiriman tanpa lampiran. "warna" latar gambarnya, supaya tiap
     * lampiran terlihat berbeda, bukan tiga gambar yang sama.
     *
     * @var array<int, array{pengguna: int, judul: string, detail: string, status: string, lampiran: string|null, warna: array<int, int>|null}>
     */
    private const MASUKAN = [
        [
            'pengguna' => 0,
            'judul' => 'Tombol simpan sulit ditekan di HP',
            'detail' => "Di layar kecil, tombol Simpan agak ketutup navigasi bawah jadi harus digeser dulu.\nMungkin bisa dinaikkan sedikit jaraknya.",
            'status' => MasukanSaran::STATUS_BARU,
            'lampiran' => 'Tangkapan layar tombol simpan',
            'warna' => [124, 58, 237],
        ],
        [
            'pengguna' => 1,
            'judul' => 'Mohon tambahkan mode gelap di halaman quiz',
            'detail' => 'Halaman materi sudah ada mode gelap, tapi saat mengerjakan quiz masih terang. Kalau belajar malam jadi silau.',
            'status' => MasukanSaran::STATUS_BARU,
            'lampiran' => null,
            'warna' => null,
        ],
        [
            'pengguna' => 2,
            'judul' => 'Tampilan daftar peserta terpotong',
            'detail' => 'Nama peserta yang panjang terpotong dan tidak ada titik-titiknya, jadi sulit dibedakan.',
            'status' => MasukanSaran::STATUS_BARU,
            'lampiran' => 'Tangkapan layar daftar peserta',
            'warna' => [2, 132, 199],
        ],
        [
            'pengguna' => 0,
            'judul' => 'Warna tombol kurang kontras',
            'detail' => 'Tulisan putih di tombol ungu muda agak susah dibaca. Mungkin warna teksnya digelapkan sedikit.',
            'status' => MasukanSaran::STATUS_DIPROSES,
            'lampiran' => 'Tangkapan layar tombol kontras',
            'warna' => [217, 119, 6],
        ],
        [
            'pengguna' => 1,
            'judul' => 'Ingin fitur ekspor nilai ke Excel',
            'detail' => 'Setelah quiz selesai, pengen bisa unduh rekap nilainya supaya tidak perlu disalin manual satu-satu.',
            'status' => MasukanSaran::STATUS_DIPROSES,
            'lampiran' => null,
            'warna' => null,
        ],
        [
            'pengguna' => 2,
            'judul' => 'Salah ketik pada materi pecahan',
            'detail' => 'Di halaman materi ada contoh yang salah hitung, sepertinya 1/2 + 1/3 ditulis 2/5 padahal seharusnya 5/6.',
            'status' => MasukanSaran::STATUS_SELESAI,
            'lampiran' => null,
            'warna' => null,
        ],
        [
            'pengguna' => 0,
            'judul' => 'Grafik hasil quiz tidak muncul',
            'detail' => 'Grafik hasil kadang kosong padahal soal sudah dikerjakan semua. Kalau di-refresh baru muncul.',
            'status' => MasukanSaran::STATUS_SELESAI,
            'lampiran' => 'Tangkapan layar grafik kosong',
            'warna' => [21, 128, 61],
        ],
    ];

    /**
     * Mode penghapusan.
     *
     * Tidak jadi argumen karena Artisan membuat seeder lewat container tanpa
     * mengirim opsi baris perintahnya. Penghapusan memakai kelas turunannya,
     * MasukanSaranDemoHapusSeeder, yang hanya mengubah nilai ini.
     */
    protected bool $hapus = false;

    public function run(): void
    {
        if ($this->hapus) {
            $this->hapusData($this->penggunaAda());

            return;
        }

        $pengguna = $this->pengguna();

        // Buang kiriman demo lama supaya tidak menumpuk tiap kali dijalankan.
        MasukanSaran::query()->whereIn('pengguna_id', $pengguna->pluck('id'))->delete();

        foreach (self::MASUKAN as $urutan => $baris) {
            $path = $baris['lampiran'] === null
                ? null
                : $this->buatGambar($urutan, $baris['lampiran'], $baris['warna'] ?? [124, 58, 237]);

            MasukanSaran::create([
                'pengguna_id' => $pengguna[$baris['pengguna']]->getKey(),
                'judul' => $baris['judul'],
                'detail' => $baris['detail'],
                'status' => $baris['status'],
                'lampiran' => $path,
            ])->forceFill(['created_at' => now()->subDays(7 - $urutan)])->save();
        }

        $this->tampilkan($pengguna);
    }

    /**
     * Pastikan akun demo ada, lalu kembalikan modelnya berurutan sesuai
     * PENGGUNA supaya indeks "pengguna" di MASUKAN tetap cocok.
     *
     * @return Collection<int, User>
     */
    private function pengguna()
    {
        return collect(self::PENGGUNA)->map(
            fn (array $akun) => User::query()->updateOrCreate(
                ['email' => $akun['email']],
                [
                    'nama' => $akun['nama'],
                    'kata_sandi' => 'password',
                    'peran' => User::PERAN_USER,
                ]
            )
        )->values();
    }

    /**
     * Akun demo yang sudah ada, tanpa membuat yang baru.
     *
     * Dipakai mode hapus: penghapusan tidak boleh membuat akun hanya untuk
     * dibiarkan kosong.
     *
     * @return Collection<int, User>
     */
    private function penggunaAda()
    {
        return User::query()
            ->whereIn('email', collect(self::PENGGUNA)->pluck('email'))
            ->get();
    }

    /**
     * Gambar satu lampiran contoh dengan GD, lalu simpan ke disk publik.
     *
     * Memakai tulisan bawaan GD (imagestring) supaya tidak bergantung pada
     * berkas font. Semua labelnya ASCII, karena font bawaan tidak mengenal
     * karakter di luar latin.
     */
    private function buatGambar(int $urutan, string $label, array $warna): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $lebar = 640;
        $tinggi = 400;

        $gambar = imagecreatetruecolor($lebar, $tinggi);

        $latar = imagecolorallocate($gambar, $warna[0], $warna[1], $warna[2]);
        imagefilledrectangle($gambar, 0, 0, $lebar, $tinggi, $latar);

        // Panel putih di tengah supaya gambarnya terlihat seperti tangkapan
        // layar, bukan sekadar kotak warna.
        $putih = imagecolorallocate($gambar, 255, 255, 255);
        imagefilledrectangle($gambar, 24, 24, $lebar - 24, $tinggi - 24, $putih);

        $gelap = imagecolorallocate($gambar, 30, 41, 59);
        imagestring($gambar, 5, 48, 56, $label, $gelap);
        imagestring($gambar, 4, 48, 96, 'Contoh lampiran masukan (data demo).', $gelap);
        imagestring($gambar, 3, 48, 132, 'Boleh dihapus: php artisan db:seed --class=MasukanSaranDemoHapusSeeder', $gelap);

        /*
         * Baris-baris abu di bawah teks berfungsi sebagai "isi" tangkapan
         * layar, jadi gambarnya tidak terlihat setengah kosong.
         */
        $abu = imagecolorallocate($gambar, 226, 232, 240);

        foreach ([180, 212, 244, 276, 308] as $y) {
            imagefilledrectangle($gambar, 48, $y, $lebar - 48 - ($y % 80), $y + 12, $abu);
        }

        ob_start();
        imagepng($gambar);
        $isi = (string) ob_get_clean();

        imagedestroy($gambar);

        $path = BerkasMasukan::FOLDER.'/demo-'.$urutan.'-'.str($label)->slug()->value().'.png';
        Storage::disk('public')->put($path, $isi);

        return $path;
    }

    /**
     * Hapus kiriman demo beserta berkas lampirannya.
     *
     * Akun demo sengaja dibiarkan, sama seperti seeder peringkat: akun bisa
     * jadi rujukan halaman lain, dan yang perlu dibersihkan cukup kirimannya.
     *
     * @param  Collection<int, User>  $pengguna
     */
    private function hapusData($pengguna): void
    {
        $kiriman = MasukanSaran::query()->whereIn('pengguna_id', $pengguna->pluck('id'))->get();

        BerkasMasukan::hapus(...$kiriman->pluck('lampiran')->filter()->all());

        $jumlah = $kiriman->count();

        MasukanSaran::query()->whereKey($kiriman->pluck('id'))->delete();

        $this->saya("Data demo masukan sudah dihapus ({$jumlah} kiriman). Akun demo dibiarkan.");
    }

    /**
     * Cetak ringkasan dan tempat melihatnya.
     *
     * @param  Collection<int, User>  $pengguna
     */
    private function tampilkan($pengguna): void
    {
        $total = MasukanSaran::query()->whereIn('pengguna_id', $pengguna->pluck('id'))->count();
        $baru = MasukanSaran::query()->whereIn('pengguna_id', $pengguna->pluck('id'))->baru()->count();

        $baris = [
            '',
            '  Data contoh masukan & saran sudah dibuat.',
            '',
            '  Kiriman : '.$total.' ('.$baru.' berstatus baru -> muncul angka di sidebar)',
            '  Akun    : '.$pengguna->pluck('email')->implode(', '),
            '  Sandi   : password',
            '',
            '  CARA LIHAT',
            '',
            '  1. Login sebagai ADMIN, lalu buka:',
            '       '.route('admin.masukan-saran'),
            '     Angka di menu "Masukan & Saran" = jumlah kiriman berstatus baru.',
            '',
            '  2. Login sebagai pengguna demo untuk melihatannya dari sisi pengirim:',
            '       '.route('user.masukan-saran'),
            '',
            '  Hapus lagi dengan:',
            '       php artisan db:seed --class=MasukanSaranDemoHapusSeeder',
            '',
        ];

        $this->saya(implode(PHP_EOL, $baris));
    }

    private function saya(string $pesan): void
    {
        $this->command?->info($pesan);
    }
}
