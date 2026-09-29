<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Quiz builder sekarang bisa membuat lima tipe soal, jadi tb_soal
     * perlu tahu tipenya dan harus bisa menyimpan lebih dari satu jawaban
     * benar. Tiga perubahan:
     *
     *   - tb_soal.tipe            tipe soal, nilai internal english snake_case
     *                             (multiple_choice, multiple_select, dropdown,
     *                             short_answer, paragraph). Default-nya
     *                             multiple_choice supaya soal lama — yang belum
     *                             punya kolom ini — tetap berarti pilihan ganda.
     *
     *   - tb_soal.jawaban_teks    kunci jawaban bertipe teks untuk short_answer
     *                             dan paragraph. Boleh null karena tipe lain
     *                             tidak memakainya.
     *   - tb_soal.tococok_persis  apakah jawaban singkat harus cocok persis.
     *                             Default true, jadi peniliannya eksak.
     *
     * Tabel baru tb_soal_pilihan menyimpan pilihan jawaban sebagai baris,
     * bukan lagi kolom pilihan_a sampai pilihan_f. Yang melatarbelakangi:
     *
     *   1. Tipe multiple_select boleh punya lebih dari satu jawaban benar,
     *      sedangkan kolom jawaban_benar varchar(1) hanya bisa menyimpan satu.
     *   2. Builder mengizinkan pilihan ditambah sampai sepuluh, sedangkan
     *      jumlah kolomnya terbatas.
     *   3. Question number / teks / is_correct kini punya tempatnya masing-masing.
     *
     * Kolom pilihan_a sampai pilihan_f di tb_soal sengaja TIDAK dihapus:
     * kolomnya NOT NULL dan sudah terisi dataquiz lama, jadi dihapus akan
     * merusak data yang sudah ada. SimpanSoalQuiz tetap mengisinya dengan
     * empat pilihan pertama sebagai cadangan, sementara semua pembaca baru
     * memakai tabel tb_soal_pilihan. Soal lama yang belum punya baris di
     * tb_soal_pilihan dibaca dari kolom lamanya, jadi tidak ada yang hilang.
     */
    public function up(): void
    {
        Schema::table('tb_soal', function (Blueprint $table) {
            $table->string('tipe', 32)->default('multiple_choice')->after('pertanyaan');
            $table->text('jawaban_teks')->nullable()->after('jawaban_benar');
            $table->boolean('tococok_persis')->default(true)->after('jawaban_teks');
        });

        Schema::create('tb_soal_pilihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('tb_soal')->cascadeOnDelete();

            // Huruf label pilihan (A, B, C, ...). Dipakai supaya pilihan yang
            // sama tetap punya label sama walau urutan barisnya berubah.
            $table->string('huruf', 1);

            $table->text('teks');
            $table->unsignedSmallInteger('urutan')->default(1);

            // Tandai jawaban benar. Untuk multiple_select boleh lebih dari satu
            // baris bernilai true; untuk tipe lain maksimal satu.
            $table->boolean('benar')->default(false);

            $table->timestamps();

            $table->index(['soal_id', 'urutan']);
        });

        Schema::table('tb_jawaban_quiz', function (Blueprint $table) {
            /*
             * Lebar dari varchar(1) ke varchar(16). multiple_select memilih
             * beberapa pilihan sekaligus, jadi jawaban yang disimpan bukan
             * lagi satu huruf melainkan daftar seperti "A,C,E".
             */
            $table->string('jawaban_dipilih', 16)->nullable()->change();

            // Jawaban bertipe teks (short_answer, paragraph) disimpan terpisah
            // dari jawaban_dipilih supaya kolom huruf tidak dipaksa menampungnya.
            $table->text('jawaban_teks')->nullable()->after('jawaban_dipilih');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('tb_jawaban_quiz', function (Blueprint $table) {
            $table->dropColumn('jawaban_teks');
            $table->string('jawaban_dipilih', 1)->nullable()->change();
        });

        Schema::dropIfExists('tb_soal_pilihan');

        Schema::table('tb_soal', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'jawaban_teks', 'tococok_persis']);
        });
    }
};
