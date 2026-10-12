<?php

declare(strict_types=1);

namespace WilyCode\Database\DriverPdo\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use WilyCode\Database\DriverPdo\PdoBinder;
use WilyCode\Database\Query\CompiledQuery;
use WilyCode\Database\Query\Enum\ParameterType;

final class PdoBinderTest extends TestCase
{
    public function testMapsParameterTypesToPdoTypes(): void
    {
        $q = new CompiledQuery(
            'SELECT * FROM t WHERE a = :a AND b = :b AND c = :c AND d = :d AND e = :e',
            [
                ':a' => 1,
                ':b' => true,
                ':c' => null,
                ':d' => fopen('php://temp', 'rb'),
                ':e' => 'x',
            ],
            [
                ':a' => ParameterType::INTEGER,
                ':b' => ParameterType::BOOLEAN,
                ':c' => ParameterType::NULL,
                ':d' => ParameterType::LOB,
                ':e' => ParameterType::STRING,
            ],
        );

        $binder = new PdoBinder();
        $bound = $binder->bind($q);

        self::assertSame($q->sql, $bound->sql);
        self::assertSame($q->params, $bound->params);

        self::assertSame([
            ':a' => PDO::PARAM_INT,
            ':b' => PDO::PARAM_BOOL,
            ':c' => PDO::PARAM_NULL,
            ':d' => PDO::PARAM_LOB,
            ':e' => PDO::PARAM_STR,
        ], $bound->types);
    }

    public function testInfersMissingTypesFromValues(): void
    {
        $q = new CompiledQuery(
            'SELECT :a, :b, :c',
            [':a' => 10, ':b' => 1.5, ':c' => 'x'],
        );

        $bound = new PdoBinder()->bind($q);

        self::assertSame([
            ':a' => PDO::PARAM_INT,
            ':b' => PDO::PARAM_STR,
            ':c' => PDO::PARAM_STR,
        ], $bound->types);
    }
}
