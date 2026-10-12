<?php

declare(strict_types=1);

namespace WilyCode\Database\DriverPdo\Tests;

use PHPUnit\Framework\TestCase;
use WilyCode\Database\Driver\Exception\ConnectionException;
use WilyCode\Database\Driver\Exception\QueryException;
use WilyCode\Database\DriverPdo\PdoDatabase;

final class PdoDatabaseExceptionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('The pdo_sqlite extension is required.');
        }
    }

    public function test_constructor_wraps_connection_errors(): void
    {
        // Open a directory as a SQLite database: the connection must fail.
        $dsn = 'sqlite:' . sys_get_temp_dir();

        $this->expectException(ConnectionException::class);
        PdoDatabase::connect($dsn);
    }

    public function test_execute_wraps_query_errors(): void
    {
        $db = PdoDatabase::connect('sqlite::memory:');

        $this->expectException(QueryException::class);
        $db->execute('SELECT * FROM table_that_does_not_exist');
    }

    public function test_query_exception_contains_query_and_param_keys(): void
    {
        $db = PdoDatabase::connect('sqlite::memory:');

        try {
            $db->execute('SELECT * FROM nope WHERE id = :id', ['id' => 123]);
            self::fail('Expected QueryException not thrown');
        } catch (QueryException $e) {
            $this->assertSame('SELECT * FROM nope WHERE id = :id', $e->query);
            $this->assertSame(['id'], $e->context['paramKeys'] ?? null);
        }
    }
}
