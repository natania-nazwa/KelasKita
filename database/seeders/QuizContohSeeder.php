<?php

namespace Database\Seeders;

use App\Models\Pelajaran;
use App\Models\Quiz;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Satu quiz contoh untuk dipakai saat mencoba tampilan dashboard.
 *
 * Seeder ini sengaja terpisah dari QuizSeeder. Isi QuizSeeder tidak diubah
 * karena isinya sudah dipakai halaman Quiz dan beberapa test, sedangkan
 * kebutuhan di sini cuma satu kartu untuk melihat tampilannya.
 *
 * Sifatnya idempoten: quiz dicocokkan lewat "judul" dan soal lewat
 * (quiz_id, urutan), jadi aman dijalankan berulang kali.
 */
class QuizContohSeeder extends Seeder
{
    /**
     * Pembuat quiz contoh. Dipakai firstOrCreate supaya seeder bisa
     * dijalankan tanpa MateriSeeder lebih dulu.
     */
    private const PEMBUAT = [
        'nama' => 'Guru KelasKita',
        'email' => 'guru@kelaskita.test',
    ];

    /**
     * Query ukuran untuk banner, sama seperti QuizSeeder.
     */
    private const UKURAN_BANNER = 'w=1200&q=80&auto=format&fit=crop';

    /**
     * Bentuk tiap baris soal:
     *   [pertanyaan, a, b, c, d, huruf jawaban, pembahasan]
     *
     * @var array<int, array<int, string>>
     */
    private const SOAL = [
        ['Apa kepanjangan dari HTML?', 'Hyperlink Text Mode Language', 'High Text Markup Language', 'HyperText Markup Language', 'Home Tool Markup Language', 'C', 'HTML adalah kerangka halaman web yang dibaca browser lalu diubah menjadi elemen.'],
        ['Tag apa yang dipakai untuk menulis satu paragraf?', '<p>', '<br>', '<span>', '<div>', 'A', 'Tag <p> menandai satu paragraf.'],
        ['Properti CSS apa yang menambah jarak di luar elemen?', 'padding', 'margin', 'border', 'float', 'B', 'margin menambah ruang di luar border elemen.'],
        ['Metode HTTP untuk mengambil data adalah?', 'POST', 'GET', 'PUT', 'DELETE', 'B', 'GET dipakai untuk membaca data dari server.'],
        ['Apa kepanjangan dari CSS?', 'Cascading Style Sheets', 'Central Style System', 'Creative Site Structure', 'Cascading Simple Syntax', 'A', 'CSS mengatur tampilan tanpa mengubah isi halaman.'],
    ];

    public function run(): void
    {
        $idPelajaran = $this->pelajaranId();
        $idPembuat = $this->pembuatId();

        $quiz = $this->simpanQuiz($idPelajaran, $idPembuat);

        $this->simpanSoal($quiz);
    }

    /**
     * Id pelajaran untuk kartu. Kalau kategori pemrograman belum ada,
     * dikembalikan null supaya quiz tetap tersimpan tanpa pelajaran.
     */
    private function pelajaranId(): ?int
    {
        $ada = Pelajaran::query()->where('slug', 'pemrograman')->value('id');

        return $ada !== null ? (int) $ada : null;
    }

    private function pembuatId(): int
    {
        return (int) User::query()->firstOrCreate(
            ['email' => self::PEMBUAT['email']],
            [
                'nama' => self::PEMBUAT['nama'],
                'kata_sandi' => 'password',
                'peran' => User::PERAN_USER,
            ]
        )->getKey();
    }

    private function simpanQuiz(?int $idPelajaran, int $idPembuat): Quiz
    {
        $judul = 'Belajar Pemrograman Dasar';

        $quiz = Quiz::query()->updateOrCreate(
            ['judul' => $judul],
            [
                'pelajaran_id' => $idPelajaran,
                'dibuat_oleh' => $idPembuat,
                'slug' => str($judul)->slug()->toString(),
                'deskripsi' => 'Kuis pengenalan HTML, CSS, dan HTTP untuk yang baru mulai belajar pemrograman.',
                'durasi' => 10,
                'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
                'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?'.self::UKURAN_BANNER,
                'visibilitas' => Quiz::VISIBILITAS_PUBLIK,
                'status' => Quiz::STATUS_PUBLISHED,
                'dipublish_pada' => now(),
            ]
        );

        $quiz->soal()->delete();

        return $quiz;
    }

    /**
     * Simpan soal-soalan quiz contoh. Nomor urut dihitung ulang supaya
     * selalu berurutan dari satu.
     */
    private function simpanSoal(Quiz $quiz): void
    {
        $urutan = 0;

        foreach (self::SOAL as $item) {
            [$pertanyaan, $a, $b, $c, $d, $benar, $pembahasan] = $item;

            Soal::query()->create([
                'quiz_id' => $quiz->getKey(),
                'pertanyaan' => $pertanyaan,
                'pilihan_a' => $a,
                'pilihan_b' => $b,
                'pilihan_c' => $c,
                'pilihan_d' => $d,
                'jawaban_benar' => $benar,
                'pembahasan' => $pembahasan,
                'urutan' => ++$urutan,
                'tingkat_kesulitan' => Quiz::TINGKAT_MUDAH,
                'aktif' => true,
            ]);
        }
    }
}
