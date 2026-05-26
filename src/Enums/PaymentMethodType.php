<?php

namespace TaPago\Enums;

final class PaymentMethodType
{
    public const EXPRESS = 'express';
    public const IBAN = 'iban';

    /** @return string[] */
    public static function all(): array
    {
        return [self::EXPRESS, self::IBAN];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
