<?php

namespace Database\Seeders;

use App\Models\JawabanQuiz;
use App\Models\Pelajaran;
use App\Models\PengerjaanQuiz;
use App\Models\PesertaQuiz;
use App\Models\Quiz;
use App\Models\SesiQuiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Data contoh untuk melihat halaman peringkat sesi quiz mode kode
 * (/user/sesi/{sesi}/peringkat) beserta tombol "Lihat Peringkat" di kartu
 * hasil.
 *
 * Seeder ini berdiri sendiri dan tidak bergantung pada MateriSeeder maupun
 * QuizSeeder, jadi bisa dijalankan kapan saja tanpa pengaruhnya:
 *
 *     php artisan db:seed --class=PeringkatDemoSeeder
 *
 * Yang dibuatnya satu quiz mode KODE, satu sesi yang sudah ditutup, dan tujuh
 * peserta dengan nilai yang sengaja berjenjang. Jadi setelah dijalankan,
 * halaman peringkat langsung menampilkan tiga besar di podium dan peserta
 * lainnya berurutan 4, 5, 6, 7.
 *
 * PENTING: seeder ini menulis ke database yang dikonfigurasi di .env. Di
 * proyek ini database-nya Supabase, bukan SQLite lokal, jadi jalankan hanya
 * kalau memang ingin data ini ada di sana. Hapus lagi dengan:
 *
 *     php artisan db:seed --class=PeringkatDemoHapusSeeder
 *
 * Dua hal yang dijaga di sini:
 *
 *   - Nilainya dihitung oleh PengerjaanQuiz::hitungUlang() dari baris jawaban
 *     yang benar-benar dibuat, bukan ditulis manual. Jadi angka di halaman
 *     peringkat berasal dari aturan penilaian yang sama dengan jawaban asli
 *     peserta, dan tidak mungkin berbeda dari kalau sesinya dijalankan
 *     sungguhan.
 *   - Kode akses quiz dicocokkan lewat kolomnya yang unik, jadi kode demo ini
 *     tidak pernah menabrak quiz lain dan seeder aman dijalankan berulang.
 */
class PeringkatDemoSeeder extends Seeder
{
    /**
     * Kode akses quiz demo.
     *
     * Enam huruf dan angka, semuanya dari abjad yang sama dengan yang dipakai
     * App\Support\KodeQuiz saat membuat kode otomatis, supaya bentuknya sama
     * persis dengan kode yang muncul di lobby saat dipakai sungguhan.
     */
    private const KODE = 'UTS9A2';

    private const JUDUL = 'Ulangan Harian Matematika (Data Demo)';

    /** Kata sandi semua akun demo. */
    private const KATA_SANDI = 'password';

    private const HOST = ['nama' => 'Bu Sari (Guru)', 'email' => 'guru.demo@kelaskita.test'];

    /**
     * Peserta dan jumlah jawaban benarnya.
     *
     * "benar" diisi, sisanya dijawab salah, jadi nilai akhirnya selalu bulat:
     * 10 benar dari 10 soal berarti 100, 9 berarti 90, dan seterusnya.
     *
     * null berarti belum menjawab apa-apa: pesertanya tetap masuk daftar dengan
     * nilai 0 dan keterangan "Belum menjawab", persis seperti yang terjadi
     * kalau ada yang masuk lobby lalu tidak sempat menjawab.
     *
     * @var array<int, array{nama: string, email: string, benar: int|null}>
     */
    private const PESERTA = [
        ['nama' => 'Aulia Rahma', 'email' => 'aulia.demo@kelaskita.test', 'benar' => 10],
        ['nama' => 'Bagas Nugroho', 'email' => 'bagas.demo@kelaskita.test', 'benar' => 9],
        ['nama' => 'Citra Lestari', 'email' => 'citra.demo@kelaskita.test', 'benar' => 8],
        ['nama' => 'Dimas Prayoga', 'email' => 'dimas.demo@kelaskita.test', 'benar' => 7],
        ['nama' => 'Fitri Handayani', 'email' => 'fitri.demo@kelaskita.test', 'benar' => 6],
        ['nama' => 'Gilang Saputra', 'email' => 'gilang.demo@kelaskita.test', 'benar' => 5],
        ['nama' => 'Hana Kusuma', 'email' => 'hana.demo@kelaskita.test', 'benar' => null],
    ];

    /**
     * Soalnya. Bentuknya sama dengan QuizSeeder: pertanyaan, empat pilihan,
     * huruf kunci, dan pembahasan.
     *
     * @var array<int, array<int, string>>
     */
    private const SOAL = [
        ['Hasil dari 125 + 275 adalah?', '350', '380', '400', '420', 'C', '125 + 275 = 400.'],
        ['Nilai x pada 3x + 7 = 25 adalah?', '4', '5', '6', '7', 'C', '3x = 18 sehingga x = 6.'],
        ['Luas segitiga dengan alas 10 dan tinggi 8 adalah?', '18', '40', '80', '90', 'B', 'Luas segitiga = setengah kali alas kali tinggi = 40.'],
        ['Hasil dari 2 pangkat 5 adalah?', '10', '16', '32', '64', 'C', '2 pangkat 5 = 2 x 2 x 2 x 2 x 2 = 32.'],
        ['Keliling lingkaran dengan jari-jari 7 dan pi 22/7 adalah?', '22', '44', '66', '88', 'B', 'Keliling = 2 x pi x r = 2 x 22/7 x 7 = 44.'],
        ['Hasil dari 1.234 + 2.345 adalah?', '3.479', '3.579', '3.679', '3.789', 'B', '1.234 + 2.345 = 3.579.'],
        ['Nilai rata-rata dari 8, 10, 12, dan 14 adalah?', '10', '11', '12', '13', 'B', 'Jumlah 44 dibagi 4 sama dengan 11.'],
        ['Jika 3x = 45, maka x adalah?', '12', '15', '18', '45', 'B', 'x = 45 dibagi 3 sama dengan 15.'],
        ['Volume kubus dengan rusuk 5 adalah?', '15', '25', '100', '125', 'D', 'Volume = 5 pangkat 3 = 125.'],
        ['Besar sudut 90 derajat disebut?', 'lancip', 'tumpul', 'siku', 'takah', 'C', 'Sudut 90 derajat termasuk sudut siku.'],
    ];

    /**
     * Mode penghapusan.
     *
     * Tidak jadi flag di constructor karena Artisan membuat seeder lewat
     * container dan tidak mengirim argumen baris perintahnya. Penghapusan
     * karena itu memakai kelas turunannya, PeringkatDemoHapusSeeder, yang
     * hanya mengubah nilai ini.
     */
    protected bool $hapus = false;

    public function run(): void
    {
        $quiz = Quiz::query()->where('kode_akses', self::KODE)->first();

        if ($this->hapus) {
            $this->hapusData($quiz);

            return;
        }

        $host = $this->pengguna(self::HOST['nama'], self::HOST['email']);
        $quiz = $this->simpanQuiz($host);
        $soal = $this->simpanSoal($quiz);
        $sesi = $this->simpanSesi($quiz, $host);

        foreach (self::PESERTA as $urutan => $baris) {
            $peserta = $this->pengguna($baris['nama'], $baris['email']);
            $belum = $baris['benar'] === null;

            $this->simpanPeserta($sesi, $peserta, $urutan, $belum);

            if (! $belum) {
                $this->simpanPengerjaan($sesi, $quiz, $peserta, $soal, $baris['benar'], $urutan);
            }
        }

        $this->tampilkan($sesi);
    }

    /**
     * Hapus semua yang dibuat seeder ini.
     *
     * Jawaban, pengerjaan, dan peserta ikut terhapus karena foreign key-nya
     * cascade. Pengguna dan kategori pelajaran sengaja tidak dihapus: keduanya
     * mungkin dipakai halaman lain, dan menghapusnya akan mengubah isi
     * halaman yang tidak ada hubungannya dengan data demo ini.
     */
    private function hapusData(?Quiz $quiz): void
    {
        if ($quiz === null) {
            $this->saya('Data demo tidak ada, jadi tidak ada yang dihapus.');

            return;
        }

        $sesiIds = SesiQuiz::query()
            ->where('quiz_id', $quiz->getKey())
            ->pluck('id');

        PengerjaanQuiz::query()->whereIn('sesi_id', $sesiIds)->delete();
        PesertaQuiz::query()->whereIn('sesi_id', $sesiIds)->delete();
        SesiQuiz::query()->whereIn('id', $sesiIds)->delete();

        $quiz->soal()->delete();
        $quiz->delete();

        $this->saya('Data demo sudah dihapus. Akun demo dan kategori Pelajaran dibiarkan.');
    }

    /**
     * Simpan quiz mode kode.
     *
     * Dicocokkan lewat kode aksesnya, bukan lewat judul, karena kode aksesnya
     * punya batasan unique di database: kalau seeder ini dijalankan dua kali
     * dengan kode yang sama, create kedua akan ditolak database.
     */
    private function simpanQuiz(User $host): Quiz
    {
        $quiz = Quiz::query()->updateOrCreate(
            ['kode_akses' => self::KODE],
            [
                'pelajaran_id' => $this->pelajaranId(),
                'dibuat_oleh' => $host->getKey(),
                'judul' => self::JUDUL,
                'slug' => str(self::JUDUL)->slug()->toString(),
                'deskripsi' => 'Data contoh untuk melihat halaman peringkat sesi. Boleh dihapus.',
                'durasi' => 15,
                'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
                'thumbnail' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904',
                'visibilitas' => Quiz::VISIBILITAS_PRIVAT,
                // Mode kode tidak pernah ikut persetujuan admin, jadi statusnya
                // boleh tetap draft. Lihat Quiz::perluPersetujuan().
                'status' => Quiz::STATUS_DRAFT,
                'tampilkan_jawaban' => true,
            ]
        );

        $quiz->soal()->delete();

        return $quiz;
    }

    /**
     * Simpan soal-soalnya dan kembalikan daftarnya berurutan.
     *
     * Pilihan jawaban ditulis ke kolom pilihan_a sampai pilihan_d seperti
     * QuizSeeder, karena kolom-kolom itu NOT NULL. Baris pilihannya di
     * tb_soal_pilihan ikut dibuat supaya quiznya benar-benar bisa dibuka dan
     * dikerjakan, bukan cuma tampil sebagai angka.
     *
     * @return Collection<int, Soal>
     */
    private function simpanSoal(Quiz $quiz): Collection
    {
        $daftar = collect();
        $urutan = 0;

        foreach (self::SOAL as [$pertanyaan, $a, $b, $c, $d, $benar, $pembahasan]) {
            $soal = Soal::query()->create([
                'quiz_id' => $quiz->getKey(),
                'pertanyaan' => $pertanyaan,
                'tipe' => Soal::TIPE_PILIHAN_GANDA,
                'pilihan_a' => $a,
                'pilihan_b' => $b,
                'pilihan_c' => $c,
                'pilihan_d' => $d,
                'jawaban_benar' => $benar,
                'pembahasan' => $pembahasan,
                'urutan' => ++$urutan,
                'tingkat_kesulitan' => Quiz::TINGKAT_SEDANG,
                'aktif' => true,
            ]);

            foreach (['A' => $a, 'B' => $b, 'C' => $c, 'D' => $d] as $huruf => $teks) {
                $soal->pilihanSoal()->create([
                    'huruf' => $huruf,
                    'teks' => $teks,
                    'urutan' => array_search($huruf, ['A', 'B', 'C', 'D'], true) + 1,
                    'benar' => $huruf === $benar,
                ]);
            }

            $daftar->push($soal);
        }

        return $daftar;
    }

    /**
     * Simpan sesi yang sudah ditutup, jadi halaman peringkat langsung
     * menampilkan status "Sudah selesai" tanpa perlu menekan apa pun.
     *
     * Sesi lama milik quiz ini dihapus lebih dulu supaya kode yang sama tidak
     * menyisakan sesi lama yang masih ikut terhitung.
     */
    private function simpanSesi(Quiz $quiz, User $host): SesiQuiz
    {
        $lama = SesiQuiz::query()->where('quiz_id', $quiz->getKey())->pluck('id');

        PengerjaanQuiz::query()->whereIn('sesi_id', $lama)->delete();
        PesertaQuiz::query()->whereIn('sesi_id', $lama)->delete();
        SesiQuiz::query()->whereIn('id', $lama)->delete();

        return SesiQuiz::create([
            'quiz_id' => $quiz->getKey(),
            'host_id' => $host->getKey(),
            'kode' => self::KODE,
            'status' => SesiQuiz::STATUS_SELESAI,
            'dimulai_pada' => now()->subMinutes(45),
            'selesai_pada' => now()->subMinutes(12),
        ]);
    }

    /**
     * Satu baris peserta sesi.
     *
     * Peserta yang belum menjawab tetap dibuat dengan status lobby, bukan
     * tidak jadi dibuat. Barisnya harus ada supaya namanya muncul di peringkat
     * dengan keterangan "Belum menjawab".
     */
    private function simpanPeserta(SesiQuiz $sesi, User $pengguna, int $urutan, bool $belum): void
    {
        $sesi->peserta()->create([
            'pengguna_id' => $pengguna->getKey(),
            'status' => $belum ? PesertaQuiz::STATUS_LOBBY : PesertaQuiz::STATUS_SELESAI,
            'bergabung_pada' => now()->subMinutes(40 - $urutan),
        ]);
    }

    /**
     * Simpan jawaban peserta, lalu biarkan nilainya dihitung ulang.
     *
     * Waktu bergabung dan waktu mulai sengaja dibuat berbeda-beda per peserta
     * supaya durasi pengerjaan di halaman hasil tidak terlihat seragam.
     *
     * @param  Collection<int, Soal>  $soal
     */
    private function simpanPengerjaan(
        SesiQuiz $sesi,
        Quiz $quiz,
        User $peserta,
        Collection $soal,
        int $benar,
        int $urutan,
    ): void {
        $mulai = now()->subMinutes(44 - $urutan);

        $pengerjaan = PengerjaanQuiz::create([
            'sesi_id' => $sesi->getKey(),
            'pengguna_id' => $peserta->getKey(),
            'quiz_id' => $quiz->getKey(),
            'jumlah_soal' => $soal->count(),
            'dimulai_pada' => $mulai,
        ]);

        foreach ($soal as $nomor => $item) {
            // Benar dulu sebanyak jumlah yang diminta, sisanya dijawab salah.
            $benarSekarang = $nomor < $benar;

            JawabanQuiz::create([
                'pengerjaan_quiz_id' => $pengerjaan->getKey(),
                'soal_id' => $item->getKey(),
                'jawaban_dipilih' => $benarSekarang
                    ? $item->jawaban_benar
                    : $this->hurufSalah($item->jawaban_benar),
                'jawaban_benar' => $item->jawaban_benar,
                'benar' => $benarSekarang,
                'dijawab_pada' => $mulai->copy()->addSeconds(40 * ($nomor + 1)),
            ]);
        }

        /*
         * Nilai dihitung ulang oleh cara yang sama dengan yang dipakai saat
         * peserta benar-benar menjawab, bukan ditulis di sini. Jadi angka yang
         * muncul di halaman peringkat berasal dari aturan penilaian aplikasi,
         * bukan dari angka yang dikarang seeder.
         */
        $pengerjaan->hitungUlang();
        $pengerjaan->selesai_pada = $mulai->copy()->addMinutes(9);
        $pengerjaan->save();
    }

    /**
     * Huruf pilihan yang dijawab salah: huruf setelah kuncinya, jadi dijamin
     * bukan huruf yang benar. Kalau kuncinya sudah D, pakai A.
     */
    private function hurufSalah(string $kunci): string
    {
        $semua = ['A', 'B', 'C', 'D'];
        $berikutnya = array_slice($semua, (int) array_search($kunci, $semua, true) + 1);

        return $berikutnya[0] ?? $semua[0];
    }

    /**
     * Id pelajaran, dibuat kalau belum ada supaya seeder tidak bergantung
     * pada MateriSeeder yang belum tentu sudah dijalankan lebih dulu.
     */
    private function pelajaranId(): int
    {
        $ada = Pelajaran::query()->where('slug', 'matematika')->first();

        if ($ada !== null) {
            return (int) $ada->getKey();
        }

        return (int) Pelajaran::create([
            'nama' => 'Matematika',
            'slug' => 'matematika',
            'deskripsi' => 'Pecahan, geometri, dan aljebra untuk jenjang dasar.',
            'ikon' => '∑',
            'aktif' => true,
        ])->getKey();
    }

    /**
     * Pastikan penggunanya ada, lalu kembalikan modelnya.
     *
     * Kata sandinya ditulis ulang tiap kali seeder dijalankan, jadi kalau akun
     * demo ini diubah dari luar, seeder bisa mengembalikan kata sandi semula
     * tanpa harus dihapus lebih dulu.
     */
    private function pengguna(string $nama, string $email): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'nama' => $nama,
                'kata_sandi' => self::KATA_SANDI,
                'peran' => User::PERAN_USER,
            ]
        )->refresh();
    }

    /**
     * Cetak tempat viewing setelah selesai, supaya tidak perlu menebak
     * URL-nya.
     */
    private function tampilkan(SesiQuiz $sesi): void
    {
        $baris = [
            '',
            '  Data contoh untuk halaman peringkat sudah dibuat.',
            '',
            '  Kode quiz : '.self::KODE,
            '  Sesi      : '.$sesi->getKey().' ('.$sesi->labelStatus().')',
            '  Peserta   : '.count(self::PESERTA),
            '',
            '  CARA LIHAT',
            '',
            '  1. Login sebagai PESERTA, lalu buka:',
            '       '.route('user.sesi.peringkat', $sesi),
            '     Tombol "Lihat Peringkat" ada di kartu hasil:',
            '       '.route('user.uiux.hasil'),
            '',
            '  2. Login sebagai GURU, lalu buka:',
            '       '.route('user.sesi.hasil', $sesi),
            '     (rekap peserta milik host, tombolnya ada di sana)',
            '',
            '  Akun demo, kata sandinya semua "'.self::KATA_SANDI.'":',
            '',
            '  Guru     '.self::HOST['email'],
        ];

        foreach (self::PESERTA as $item) {
            $baris[] = '  Peserta  '.$item['email'];
        }

        $baris[] = '';
        $baris[] = '  Hapus lagi dengan:';
        $baris[] = '       php artisan db:seed --class=PeringkatDemoHapusSeeder';
        $baris[] = '';

        $this->saya(implode(PHP_EOL, $baris));
    }

    private function saya(string $pesan): void
    {
        $this->command?->info($pesan);
    }
}
