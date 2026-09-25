<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // PENGGUNA
        // USER DAN ADMIN ADA DI TABEL YANG SAMA
        // ==========================================

        Schema::create('tb_pengguna', function (Blueprint $table) {
            $table->id();

            $table->string('nama');

            $table->string('email')->unique();

            $table->timestamp('email_verified_at')->nullable();

            $table->string('kata_sandi');

            // user / admin
            $table->string('peran')
                ->default('user')
                ->index();

            $table->boolean('aktif')
                ->default(true);

            $table->rememberToken();

            $table->timestamps();
        });


        // ==========================================
        // PASSWORD RESET TOKENS
        // ==========================================

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();

            $table->string('token');

            $table->timestamp('created_at')->nullable();
        });


        // ==========================================
        // SESSIONS
        // ==========================================

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->foreignId('user_id')
                ->nullable()
                ->index();

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->longText('payload');

            $table->integer('last_activity')
                ->index();
        });


        // ==========================================
        // PELAJARAN / KATEGORI
        // ==========================================

        Schema::create('tb_pelajaran', function (Blueprint $table) {
            $table->id();

            $table->string('nama');

            $table->string('slug')
                ->unique();

            $table->text('deskripsi')
                ->nullable();

            $table->string('ikon')
                ->nullable();

            $table->boolean('aktif')
                ->default(true);

            $table->timestamps();
        });


        // ==========================================
        // MATERI
        // ==========================================

        Schema::create('tb_materi', function (Blueprint $table) {
            $table->id();

            // Kategori materi
            $table->foreignId('pelajaran_id')
                ->constrained('tb_pelajaran')
                ->cascadeOnDelete();

            // Admin/user yang membuat materi
            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('tb_pengguna')
                ->nullOnDelete();

            $table->string('nama');

            $table->string('slug');

            $table->text('deskripsi')
                ->nullable();

            $table->longText('isi')
                ->nullable();

            $table->string('tingkat_kesulitan')
                ->nullable();

            $table->boolean('aktif')
                ->default(true);

            $table->timestamps();

            $table->index([
                'pelajaran_id',
                'slug'
            ]);
        });


        // ==========================================
        // QUIZ
        // ==========================================

        Schema::create('tb_quiz', function (Blueprint $table) {
            $table->id();

            // User yang membuat quiz
            $table->foreignId('dibuat_oleh')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            // Kategori / pelajaran quiz
            $table->foreignId('pelajaran_id')
                ->constrained('tb_pelajaran')
                ->cascadeOnDelete();

            $table->string('judul');

            $table->string('slug');

            $table->text('deskripsi')
                ->nullable();

            // Durasi dalam menit
            $table->unsignedInteger('durasi')
                ->nullable();

            // public / private
            $table->string('visibilitas')
                ->default('public')
                ->index();

            /*
             * Status quiz:
             *
             * draft     = masih dibuat
             * pending   = menunggu persetujuan admin
             * published = sudah disetujui
             * rejected  = ditolak admin
             */
            $table->string('status')
                ->default('draft')
                ->index();

            // Digunakan jika quiz private
            $table->string('kode_akses')
                ->nullable()
                ->unique();

            // Catatan admin jika quiz ditolak
            $table->text('catatan_admin')
                ->nullable();

            $table->timestamp('dipublish_pada')
                ->nullable();

            $table->timestamps();

            $table->index([
                'dibuat_oleh',
                'status'
            ]);

            $table->index([
                'pelajaran_id',
                'status'
            ]);
        });


        // ==========================================
        // SOAL
        // ==========================================

        Schema::create('tb_soal', function (Blueprint $table) {
            $table->id();

            // Soal milik quiz tertentu
            $table->foreignId('quiz_id')
                ->constrained('tb_quiz')
                ->cascadeOnDelete();

            $table->text('pertanyaan');

            $table->text('pilihan_a');

            $table->text('pilihan_b');

            $table->text('pilihan_c');

            $table->text('pilihan_d');

            // A / B / C / D
            $table->string('jawaban_benar', 1);

            $table->text('pembahasan')
                ->nullable();

            // Urutan soal
            $table->unsignedInteger('urutan')
                ->default(1);

            $table->string('tingkat_kesulitan')
                ->nullable();

            $table->boolean('aktif')
                ->default(true);

            $table->timestamps();

            $table->index([
                'quiz_id',
                'urutan'
            ]);
        });


        // ==========================================
        // PENGERJAAN QUIZ
        // ==========================================

        Schema::create('tb_pengerjaan_quiz', function (Blueprint $table) {
            $table->id();

            // User yang mengerjakan
            $table->foreignId('pengguna_id')
                ->constrained('tb_pengguna')
                ->cascadeOnDelete();

            // Quiz yang dikerjakan
            $table->foreignId('quiz_id')
                ->constrained('tb_quiz')
                ->cascadeOnDelete();

            $table->unsignedInteger('jumlah_soal')
                ->default(0);

            $table->unsignedInteger('jumlah_dijawab')
                ->default(0);

            $table->unsignedInteger('jumlah_benar')
                ->default(0);

            $table->unsignedInteger('jumlah_salah')
                ->default(0);

            // Nilai akhir
            $table->unsignedInteger('nilai')
                ->default(0);

            $table->timestamp('dimulai_pada')
                ->nullable();

            $table->timestamp('selesai_pada')
                ->nullable();

            $table->timestamps();

            $table->index([
                'pengguna_id',
                'quiz_id'
            ]);
        });


        // ==========================================
        // JAWABAN QUIZ
        // ==========================================

        Schema::create('tb_jawaban_quiz', function (Blueprint $table) {
            $table->id();

            // Pengerjaan quiz
            $table->foreignId('pengerjaan_quiz_id')
                ->constrained('tb_pengerjaan_quiz')
                ->cascadeOnDelete();

            // Soal yang dijawab
            $table->foreignId('soal_id')
                ->constrained('tb_soal')
                ->cascadeOnDelete();

            // A / B / C / D
            $table->string('jawaban_dipilih', 1)
                ->nullable();

            // Jawaban benar saat quiz dikerjakan
            $table->string('jawaban_benar', 1);

            $table->boolean('benar')
                ->default(false);

            $table->timestamp('dijawab_pada')
                ->nullable();

            $table->timestamps();

            // Satu soal hanya boleh memiliki satu jawaban
            // dalam satu pengerjaan quiz
            $table->unique([
                'pengerjaan_quiz_id',
                'soal_id'
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tb_jawaban_quiz');
        Schema::dropIfExists('tb_pengerjaan_quiz');
        Schema::dropIfExists('tb_soal');
        Schema::dropIfExists('tb_quiz');
        Schema::dropIfExists('tb_materi');
        Schema::dropIfExists('tb_pelajaran');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('tb_pengguna');
    }
};