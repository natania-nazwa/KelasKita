<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Tiga langkah, urutannya tidak boleh ditukar:
     *
     *   1. Pindahkan isi dari pelajaran lama ke kategori resmi yang terdekat.
     *   2. Samakan nama, deskripsi, dan ikon baris yang slug-nya sudah benar.
     *   3. Tambahkan baris yang belum ada, baru hapus baris slug lama.
     *
     * PPLG dan Database dipakai sebagai tujuan pemindahan, jadi keduanya harus
     * ada lebih dulu. Karena itu baris katalog ditambahkan di langkah pertama,
     * dan penghapusan slug lama dilakukan paling akhir, setelah tidak ada satu
     * pun konten yang masih menunjuk ke sana.
     */
    public function up(): void
    {
        $this->tambah();

        $this->pindahkanKonten();
        $this->perbaruiJadwal();
        $this->perbaruiTeks();
        $this->hapusPelajaranLama();
    }

    /**
     * Balikkan migrasi.
     *
     * Hanya nama, deskripsi, dan ikon yang dikembalikan ke nilai sebelumnya.
     *
     * Baris TIDAK pernah dihapus di sini, termasuk baris yang baru dibuat
     * migrasi ini. Alasannya isi konten: foreign key tb_materi dan tb_quiz ke
     * tb_pelajaran memakai cascade on delete, jadi menghapus satu baris
     * pelajaran berarti ikut menghapus seluruh materi dan quiz yang menunjuknya.
     * Setelah up() berjalan, PPLG dan Database sudah menerima isi dari
     * pelajaran yang dihapus, sehingga baris itu bukan lagi baris kosong yang
     * aman dihapus.
     *
     * Mengembalikan nama saja membuat isi migrasi ini tidak bisa diulang ke
     * belakang sepenuhnya: baris yang dibuat migrasi tetap ada. Itu pilihan
     * yang lebih baik daripada menghapus materi dan quiz yang sudah terbit.
     * Keadaan sebenarnya setelah rollback ada di Pelajaran::KATALOG, yang
     * selalu menentukan daftar resmi, bukan isi tabel.
     */
    public function down(): void
    {
        $sebelumnya = [
            'bahasa-indonesia' => ['nama' => 'Bahasa Indonesia', 'deskripsi' => 'Tata bahasa, kosakata, dan keterampilan membaca dan menulis.', 'ikon' => 'A'],
            'bahasa-jepang' => ['nama' => 'Bahasa Jepang', 'deskripsi' => 'Hiragana, katakana, kanji, dan kosakata dasar.', 'ikon' => '日'],
            'matematika' => ['nama' => 'Matematika', 'deskripsi' => 'Pecahan, geometri, dan aljebra untuk jenjang dasar.', 'ikon' => '∑'],
            'ipa' => ['nama' => 'IPA', 'deskripsi' => 'Fisika, astronomi, dan phenomena alam sehari-hari.', 'ikon' => '🔬'],
            'database' => ['nama' => 'Database', 'deskripsi' => 'Penyimpanan data terstruktur dan bahasa SQL.', 'ikon' => '🗄️'],
        ];

        foreach ($sebelumnya as $slug => $nilai) {
            DB::table('tb_pelajaran')
                ->where('slug', $slug)
                ->update($nilai + ['updated_at' => now()]);
        }
    }

    /**
     * Tambahkan baris katalog yang belum ada.
     *
     * Baris yang sudah ada tidak disentuh di sini; teksnya dirapikan terpisah
     * oleh perbaruiTeks().
     */
    private function tambah(): void
    {
        foreach ($this->katalog() as $pelajaran) {
            $sudahAda = DB::table('tb_pelajaran')->where('slug', $pelajaran['slug'])->exists();

            if ($sudahAda) {
                continue;
            }

            DB::table('tb_pelajaran')->insert($pelajaran + [
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Arahkan materi dan quiz dari baris pelajaran lama ke baris tujuan.
     *
     * Materi dan quiz menyimpan id pelajaran, jadi yang dicari adalah id baris
     * slug lama lalu diganti id baris slug tujuan. Baris tujuan selalu ada
     * karena langkah tambah() sudah berjalan lebih dulu.
     */
    private function pindahkanKonten(): void
    {
        $idPelajaran = DB::table('tb_pelajaran')->pluck('id', 'slug');

        foreach ($this->petaLama() as $slugLama => $slugBaru) {
            $dari = $idPelajaran[$slugLama] ?? null;
            $ke = $idPelajaran[$slugBaru] ?? null;

            // Lewati kalau salah satu sisinya tidak ada, atau kalau keduanya
            // baris yang sama (dipetakan ke dirinya sendiri).
            if ($dari === null || $ke === null || $dari === $ke) {
                continue;
            }

            DB::table('tb_materi')
                ->where('pelajaran_id', $dari)
                ->update(['pelajaran_id' => $ke, 'updated_at' => now()]);

            DB::table('tb_quiz')
                ->where('pelajaran_id', $dari)
                ->update(['pelajaran_id' => $ke, 'updated_at' => now()]);
        }
    }

    /**
     * Tukar slug jadwal lama ke slug yang masih ada di daftar resmi.
     *
     * Berbeda dari materi dan quiz, jadwal tidak pernah menunjuk baris
     * tb_pelajaran: kolomnya menyimpan slug sebagai teks, jadi yang dipetakan
     * adalah slug itu sendiri.
     *
     * Dua slug di sini tidak pernah punya baris di tabel (ppkn dan pjkr), jadi
     * pindahkanKonten() tidak menyentuhnya. Jika dibiarkan, jadwal tersebut
     * akan menampilkan pelajaran yang tidak ada lagi di pilihan form, tidak
     * bisa disaring, dan tidak bisa disimpan ulang karena validasinya menolak
     * slug yang tidak dikenal.
     */
    private function perbaruiJadwal(): void
    {
        $slug = [
            'ppkn' => 'pendidikan-pancasila',
            'pjkr' => 'pplg',
        ];

        foreach ($this->petaLama() as $slugLama => $slugBaru) {
            $slug[$slugLama] = $slugBaru;
        }

        foreach ($slug as $dari => $ke) {
            DB::table('tb_jadwal')
                ->where('pelajaran', $dari)
                ->update(['pelajaran' => $ke, 'updated_at' => now()]);
        }
    }

    /**
     * Samakan nama, deskripsi, dan ikon baris yang slug-nya sudah benar.
     *
     * Slug tidak pernah diubah di sini: slug dipakai di URL filter dan sudah
     * pernah dibagikan, jadi hanya teks yang tampil yang dirapikan.
     */
    private function perbaruiTeks(): void
    {
        foreach ($this->katalog() as $pelajaran) {
            DB::table('tb_pelajaran')
                ->where('slug', $pelajaran['slug'])
                ->update([
                    'nama' => $pelajaran['nama'],
                    'deskripsi' => $pelajaran['deskripsi'],
                    'ikon' => $pelajaran['ikon'],
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Hapus baris slug lama, dan jadwal yang masih menunjuk slug itu.
     *
     * Jadwal ikut dibersihkan karena kolomnya menyimpan slug sebagai teks. Kalau
     * barisnya dihapus tapi jadwalnya tidak, halaman Jadwal akan menampilkan
     * pelajaran yang tidak lagi ada di pilihan form dan tidak bisa disaring
     * lagi karena tidak ada cara memperbaikinya dari halaman itu.
     */
    private function hapusPelajaranLama(): void
    {
        foreach (array_keys($this->petaLama()) as $slug) {
            DB::table('tb_jadwal')->where('pelajaran', $slug)->delete();
            DB::table('tb_pelajaran')->where('slug', $slug)->delete();
        }
    }

    /**
     * Daftar resmi mata pelajaran.
     *
     * Disalin dari App\Models\Pelajaran::KATALOG, bukan memakai konstantanya
     * langsung. Migration adalah catatan keadaan pada satu waktu: kalau
     * katalognya nanti berubah, migration ini tetap harus menjelaskan keadaan
     * yang pernah ada supaya bisa dibalik dengan benar.
     *
     * @return array<int, array<string, string>>
     */
    private function katalog(): array
    {
        return [
            ['nama' => 'PAI', 'slug' => 'pai', 'deskripsi' => 'Pendidikan Agama Islam: akidah, fikih, dan akhlak.', 'ikon' => '🕌'],
            ['nama' => 'Bahasa Indonesia', 'slug' => 'bahasa-indonesia', 'deskripsi' => 'Tata bahasa, kosakata, dan keterampilan membaca dan menulis.', 'ikon' => 'A'],
            ['nama' => 'Bahasa Inggris', 'slug' => 'bahasa-inggris', 'deskripsi' => 'Grammar, kosakata, dan kemampuan menulis dan berbicara.', 'ikon' => 'En'],
            ['nama' => 'Bahasa Jepang', 'slug' => 'bahasa-jepang', 'deskripsi' => 'Hiragana, katakana, kanji, dan kosakata dasar.', 'ikon' => '日'],
            ['nama' => 'Pendidikan Pancasila', 'slug' => 'pendidikan-pancasila', 'deskripsi' => 'Nilai kebangsaan, hak dan kewajiban warga negara.', 'ikon' => '🇮🇩'],
            ['nama' => 'PPLG', 'slug' => 'pplg', 'deskripsi' => 'Pengembangan Perangkat Lunak dan Gim, dari desain sampai rilis.', 'ikon' => '💻'],
            ['nama' => 'Matematika', 'slug' => 'matematika', 'deskripsi' => 'Pecahan, geometri, dan aljebra untuk jenjang dasar.', 'ikon' => '∑'],
            ['nama' => 'IPA', 'slug' => 'ipa', 'deskripsi' => 'Fisika, astronomi, dan phenomena alam sehari-hari.', 'ikon' => '🔬'],
            ['nama' => 'IPS', 'slug' => 'ips', 'deskripsi' => 'Sejarah, geografi, dan kehidupan sosial masyarakat.', 'ikon' => '🌏'],
            ['nama' => 'Sejarah', 'slug' => 'sejarah', 'deskripsi' => 'Peristiwa dan tokoh penting dalam sejarah dunia dan Indonesia.', 'ikon' => '🏛'],
            ['nama' => 'Database', 'slug' => 'database', 'deskripsi' => 'Penyimpanan data terstruktur dan bahasa SQL.', 'ikon' => '🗄️'],
        ];
    }

    /**
     * Peta slug lama ke slug tujuan.
     *
     * Tiga slug ini tidak ada lagi di daftar resmi, tapi materinya sudah terbit
     * dan jadi bagian dari isi yang dibaca pengguna, jadi tidak boleh hilang
     * begitu saja. Isinya dipindahkan ke kategori resmi yang paling dekat, lalu
     * baris lamanya dihapus supaya tidak ada kategori mati yang masih muncul di
     * filter.
     *
     * @return array<string, string>
     */
    private function petaLama(): array
    {
        return [
            'pemrograman' => 'pplg',
            'desain-web' => 'pplg',
            'teknologi' => 'database',
        ];
    }
};
