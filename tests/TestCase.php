<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Penjaga agar data production tidak tersentuh.
     *
     * Test memakai RefreshDatabase, yang menjalankan migrate:fresh. Kalau
     * config ter-cache sehingga DB_CONNECTION dari phpunit.xml diabaikan,
     * perintah itu akan MENYEGEL tabel di database production.
     *
     * Hook ini sengaja menimpa refreshApplication(), bukan setUp():
     * urutannya refreshApplication() -> setUpTraits(), dan migrate:fresh
     * berjalan di dalam setUpTraits(). Jadi guard harus sudah selesai
     * sebelum parent::refreshApplication() dipanggil.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->pastikanTesTidakMenyentuhDatabaseProduction();
    }

    protected function pastikanTesTidakMenyentuhDatabaseProduction(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config('database.connections.'.$connection.'.database');

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "TES DIBATASI: menolak jalan di database '$connection' (database=$database). "
                .'Test harus memakai sqlite in-memory. Jalankan "php artisan optimize:clear" '
                .'lalu ulangi. Config ter-cache membuat phpunit.xml diabaikan.'
            );
        }
    }
}
