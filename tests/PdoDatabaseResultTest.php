<?php

declare(strict_types=1);

namespace WilyCode\Database\DriverPdo\Tests;

use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use WilyCode\Database\DriverPdo\PdoDatabase;

final class PdoDatabaseResultTest extends TestCase
{
    private PdoDatabase $db;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('The pdo_sqlite extension is required.');
        }

        // Driver contract always returns associative arrays, regardless of PDO defaults.
        $this->db = PdoDatabase::connect('sqlite::memory:', options: [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);

        $this->db->execute('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, flag INTEGER)');
        $this->db->execute('INSERT INTO items (name, flag) VALUES (:name, :flag)', [
            'name' => 'First',
            'flag' => true,
        ]);
    }

    public function testFetchReturnsAssociativeArrayRegardlessOfPdoFetchMode(): void
    {
        self::assertSame(['id' => 1, 'name' => 'First', 'flag' => 1], $this->db->fetch(
            'SELECT id, name, flag FROM items WHERE id = :id', ['id' => 1],
        ));
        self::assertSame([], $this->db->fetch('SELECT id FROM items WHERE id = :id', ['id' => 999]));
    }

    public function testFetchAllReturnsAssociativeArrays(): void
    {
        self::assertSame([['name' => 'First']], $this->db->fetchAll('SELECT name FROM items ORDER BY id'));
        self::assertSame([], $this->db->fetchAll('SELECT name FROM items WHERE id = 999'));
    }

    public function testFetchColumnReturnsNullForNoRowAndRejectsNegativeColumnIndex(): void
    {
        self::assertSame('First', $this->db->fetchColumn('SELECT name FROM items'));
        self::assertNull($this->db->fetchColumn('SELECT name FROM items WHERE id = 999'));
        $this->expectException(InvalidArgumentException::class);
        $this->db->fetchColumn('SELECT name FROM items', columnIndex: -1);
    }

    public function testInsertIdAndExplicitIntegerBinding(): void
    {
        self::assertSame('1', $this->db->getLastInsertId());
        self::assertSame(['name' => 'First'], $this->db->fetch(
            'SELECT name FROM items WHERE id = :id', ['id' => '1'], [':id' => PDO::PARAM_INT],
        ));
    }
}
