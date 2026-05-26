<?php

namespace TaPago\Enums;

final class SessionStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    /** @return string[] */
    public static function all(): array
    {
        return [self::PENDING, self::PROCESSING, self::COMPLETED, self::FAILED];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
