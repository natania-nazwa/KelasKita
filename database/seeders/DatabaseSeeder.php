<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Kategori, materi, dan pembuatnya untuk halaman Materi user.
        $this->call(MateriSeeder::class);

        // Quiz beserta soal-soalnya untuk halaman Quiz user.
        $this->call(QuizSeeder::class);

        // firstOrCreate supaya seeder aman dijalankan berulang kali.
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'nama' => 'Test User',
                'kata_sandi' => Hash::make('password'),
                'peran' => User::PERAN_USER,
            ]
        );
    }
}
