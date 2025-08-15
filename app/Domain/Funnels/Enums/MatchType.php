<?php

namespace DDD\Domain\Funnels\Enums;

class MatchType
{
    public const EXACT = 'EXACT';
    public const CONTAINS = 'CONTAINS';
    public const BEGINS_WITH = 'BEGINS_WITH';
    public const ENDS_WITH = 'ENDS_WITH';
    public const FULL_REGEXP = 'FULL_REGEXP';
    public const PARTIAL_REGEXP = 'PARTIAL_REGEXP';

    public static function all(): array
    {
        return [
            self::EXACT,
            self::CONTAINS,
            self::BEGINS_WITH,
            self::ENDS_WITH,
            self::FULL_REGEXP,
            self::PARTIAL_REGEXP,
        ];
    }

    public static function common(): array
    {
        return [
            self::EXACT,
            self::CONTAINS,
            self::BEGINS_WITH,
            self::ENDS_WITH,
        ];
    }
}
