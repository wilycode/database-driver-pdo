<?php

declare(strict_types=1);

namespace WilyCode\Database\DriverPdo;

use PDO;
use WilyCode\Database\Query\Binding\BinderInterface;
use WilyCode\Database\Query\Binding\BoundQuery;
use WilyCode\Database\Query\CompiledQuery;
use WilyCode\Database\Query\Enum\ParameterType;

final class PdoBinder implements BinderInterface
{
    public function bind(CompiledQuery $query): BoundQuery
    {
        $types = [];

        foreach ($query->params as $placeholder => $value) {
            $type = $query->types[$placeholder] ?? ParameterType::fromPhpType($value);
            $types[$placeholder] = match ($type) {
                ParameterType::INTEGER => PDO::PARAM_INT,
                ParameterType::BOOLEAN => PDO::PARAM_BOOL,
                ParameterType::NULL => PDO::PARAM_NULL,
                ParameterType::LOB => PDO::PARAM_LOB,
                default => PDO::PARAM_STR,
            };
        }

        return new BoundQuery($query->sql, $query->params, $types);
    }
}
