<?php

declare(strict_types=1);

namespace WilyCode\Database\DriverPdo\Tests;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use WilyCode\Database\Driver\Exception\QueryException;
use WilyCode\Database\DriverPdo\PdoDatabase;

final class PdoDatabaseExecutionTest extends TestCase
{
    public function testFalseExecuteResultIsWrappedAsQueryException(): void
    {
        // Some PDO configurations report errors by returning false, not throwing.
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('bindValue')->willReturn(true);
        $statement->expects(self::once())->method('execute')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())
            ->method('prepare')
            ->with('SELECT :secret')
            ->willReturn($statement);

        $database = new PdoDatabase($pdo);
        try {
            $database->execute('SELECT :secret', ['secret' => 'private-value']);
            self::fail('Expected query execution to throw.');
        } catch (QueryException $exception) {
            self::assertSame('SELECT :secret', $exception->query);
            self::assertSame(['secret'], $exception->context['paramKeys']);
            self::assertStringNotContainsString('private-value', $exception->getMessage());
        }
    }

    public function testFalsePrepareResultIsWrappedAsQueryException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())
            ->method('prepare')
            ->with('SELECT 1')
            ->willReturn(false);

        $database = new PdoDatabase($pdo);

        try {
            $database->execute('SELECT 1');
            self::fail('Expected query preparation to throw.');
        } catch (QueryException $exception) {
            self::assertSame('SELECT 1', $exception->query);
            self::assertSame([], $exception->context['paramKeys']);
        }
    }

    public function testFalseBindValueResultIsWrappedAsQueryException(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects(self::once())
            ->method('bindValue')
            ->with(':id', 42, PDO::PARAM_INT)
            ->willReturn(false);
        $statement->expects(self::never())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $database = new PdoDatabase($pdo);

        try {
            $database->execute('SELECT :id', ['id' => 42]);
            self::fail('Expected parameter binding to throw.');
        } catch (QueryException $exception) {
            self::assertSame('SELECT :id', $exception->query);
            self::assertSame(['id'], $exception->context['paramKeys']);
        }
    }

}
