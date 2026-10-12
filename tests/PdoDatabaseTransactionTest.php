<?php

declare(strict_types=1);

namespace WilyCode\Database\DriverPdo\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WilyCode\Database\Driver\Exception\NestedTransactionException;
use WilyCode\Database\DriverPdo\PdoDatabase;

final class PdoDatabaseTransactionTest extends TestCase
{
    private PdoDatabase $db;
    private string $table;

    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('The pdo_sqlite extension is required.');
        }

        $this->db = PdoDatabase::connect('sqlite::memory:');

        $this->table = 'users_' . bin2hex(random_bytes(4));
        $this->db->execute(
            'CREATE TABLE ' . $this->table . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                age INTEGER NOT NULL
            )'
        );
    }

    public function test_transaction_commits(): void
    {
        $this->assertFalse($this->db->inTransaction());

        $this->db->transaction(function (PdoDatabase $tx): void {
            $this->assertTrue($tx->inTransaction());

            $tx->execute(
                'INSERT INTO ' . $this->table . ' (email, age) VALUES (:email, :age)',
                ['email' => 'a@example.com', 'age' => 20]
            );

            $tx->execute(
                'INSERT INTO ' . $this->table . ' (email, age) VALUES (:email, :age)',
                ['email' => 'b@example.com', 'age' => 22]
            );
        });

        $this->assertFalse($this->db->inTransaction());
        $this->assertSame(2, $this->countUsers());
    }

    public function test_transaction_rolls_back_on_exception(): void
    {
        try {
            $this->db->transaction(function (PdoDatabase $tx): void {
                $tx->execute(
                    'INSERT INTO ' . $this->table . ' (email, age) VALUES (:email, :age)',
                    ['email' => 'a@example.com', 'age' => 20]
                );

                throw new RuntimeException('boom');
            });

            self::fail('Expected exception not thrown');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(0, $this->countUsers());
        $this->assertFalse($this->db->inTransaction());
    }

    public function test_transaction_throws_on_nested_transaction(): void
    {
        $this->expectException(NestedTransactionException::class);
        $this->expectExceptionMessage('Nested transactions are not supported');

        $this->db->transaction(function (PdoDatabase $tx): void {
            $tx->transaction(static function (PdoDatabase $_): void {
                // never reached
            });
        });
    }

    public function test_binds_positional_and_named_params(): void
    {
        // positional
        $this->db->execute(
            'INSERT INTO ' . $this->table . ' (email, age) VALUES (?, ?)',
            ['p@example.com', 33]
        );

        // named
        $this->db->execute(
            'INSERT INTO ' . $this->table . ' (email, age) VALUES (:email, :age)',
            ['email' => 'n@example.com', 'age' => 44]
        );

        $rows = $this->db->fetchAll('SELECT email, age FROM ' . $this->table . ' ORDER BY id');
        $this->assertSame(
            [
                ['email' => 'p@example.com', 'age' => 33],
                ['email' => 'n@example.com', 'age' => 44],
            ],
            $rows
        );
    }


    public function testFetchColumnDistinguishesZeroFromNoRow(): void
    {
        self::assertSame(0, $this->db->fetchColumn('SELECT 0'));
        self::assertNull($this->db->fetchColumn('SELECT 1 WHERE 0 = 1'));
    }

    private function countUsers(): int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM ' . $this->table);
    }
}
