<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /*
     * RefreshDatabase wajib di sini. Landing page membaca daftar mata
     * pelajaran untuk kartu kategorinya, jadi halaman "/" tidak lagi bisa
     * dijawab hanya dengan memeriksa response kosong: tanpa tabel, halamannya
     * membalas 500 karena tidak bisa membaca kategorinya.
     */
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
