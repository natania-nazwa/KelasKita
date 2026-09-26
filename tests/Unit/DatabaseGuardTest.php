<?php

namespace Tests\Unit;

use RuntimeException;
use Tests\TestCase;

/**
 * Membuktikan penjaga di Tests\TestCase benar-benar menolak test yang
 * diarahkan ke database production.
 *
 * Tidak ada koneksi database yang dibuka di test ini: config diganti
 * di memory lalu method penjaga dipanggil langsung.
 */
class DatabaseGuardTest extends TestCase
{
    public function test_menerima_koneksi_sqlite_in_memory(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->pastikanTesTidakMenyentuhDatabaseProduction();

        $this->assertTrue(true);
    }

    public function test_menolak_koneksi_pgsql_yang_menunjuk_ke_database_nyata(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'postgres',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/TES DIBATASI/');

        $this->pastikanTesTidakMenyentuhDatabaseProduction();
    }

    public function test_menolak_sqlite_yang_bukan_in_memory(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => 'storage/database.sqlite',
        ]);

        $this->expectException(RuntimeException::class);

        $this->pastikanTesTidakMenyentuhDatabaseProduction();
    }
}
