<?php

namespace DDD\Domain\Funnels\Enums;

class MetricsExpression
{
    public const OR_GROUP = 'orGroup';
    public const AND_GROUP = 'andGroup';

    public static function all(): array
    {
        return [
            self::OR_GROUP,
            self::AND_GROUP,
        ];
    }

    public static function default(): string
    {
        return self::OR_GROUP;
    }
}
