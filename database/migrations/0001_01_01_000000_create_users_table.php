<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==========================================
        // USERS
        // ==========================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // admin / user
            $table->string('role')->default('user')->index();

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

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ==========================================
        // SUBJECTS / PELAJARAN
        // ==========================================
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // ==========================================
        // MATERIALS / MATERI
        // ==========================================
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('difficulty')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['subject_id', 'slug']);
        });

        // ==========================================
        // QUESTIONS / BANK SOAL
        // ==========================================
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();

            $table->text('question');

            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');

            $table->string('correct_answer');
            $table->text('explanation')->nullable();
            $table->string('difficulty')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('material_id');
        });

        // ==========================================
        // PRACTICE SESSIONS / SESI LATIHAN
        // ==========================================
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();

            $table->string('mode')->default('practice');

            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('answered_questions')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('wrong_answers')->default(0);
            $table->unsignedInteger('score')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'material_id']);
        });

        // ==========================================
        // PRACTICE ANSWERS / JAWABAN LATIHAN
        // ==========================================
        Schema::create('practice_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practice_session_id')
                ->constrained('practice_sessions')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();

            $table->string('selected_answer');
            $table->string('correct_answer');

            $table->boolean('is_correct')->default(false);

            $table->timestamp('answered_at')->nullable();

            $table->timestamps();

            $table->index([
                'practice_session_id',
                'question_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_answers');
        Schema::dropIfExists('practice_sessions');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};