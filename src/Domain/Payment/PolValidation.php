<?php

namespace Aqayepardakht\PhpSdk\Domain\Payment;

/** POLPay application API constraints; shared AQP validation remains unchanged. */
final class PolValidation
{
    public const PURPOSES = [
        'DRPA', 'POSA', 'IOSP', 'HIPA', 'ISAP', 'FXAP', 'RTAP', 'MPTP', 'IMPT',
        'LMAP', 'CDAP', 'TCAP', 'GEAC', 'LRPA', 'CCPA', 'GPAC', 'CPAC', 'GPPC', 'SPAC',
    ];

    public static function amount(int|float $value): void
    {
        if (!is_finite((float) $value) || floor($value) != $value || $value < 1000 || $value > 1000000000) {
            throw new \InvalidArgumentException('POL amount must be an integer between 1000 and 1000000000.');
        }
    }

    public static function text(string $value, string $field, int $max): void
    {
        $length = preg_match_all('/./us', $value);
        if (trim($value) === '' || $length === false || $length > $max) {
            throw new \InvalidArgumentException($field.' must be non-empty UTF-8 text of at most '.$max.' characters.');
        }
    }

    public static function purpose(?string $value): void
    {
        if ($value !== null && !in_array($value, self::PURPOSES, true)) {
            throw new \InvalidArgumentException('Invalid POL purpose code.');
        }
    }

    public static function settlement(?string $value): void
    {
        if ($value !== null && !in_array($value, ['INS', 'CYC', 'EOD'], true)) {
            throw new \InvalidArgumentException('Invalid POL settlement type.');
        }
    }

    public static function date(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException($field.' must be a valid Gregorian date in Y-m-d format.');
        }
        // The server owns "today" and the 14-day window; SDK timezone may differ.
    }

    public static function code(string $value, string $field, bool $uppercase = false): void
    {
        self::text($value, $field, 32);
        $pattern = $uppercase ? '/^[A-Z][A-Z0-9_]*$/D' : '/^[A-Za-z][A-Za-z0-9_]*$/D';
        if (!preg_match($pattern, $value)) {
            throw new \InvalidArgumentException('Invalid '.$field.' code.');
        }
    }
}
