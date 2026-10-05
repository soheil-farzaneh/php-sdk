<?php
namespace Aqayepardakht\PhpSdk\Domain\Payment;

/** Existing monetary bounds, shared by invoice and request DTOs. No unit conversion. */
final class PaymentValidation
{
    public static function amount(int|float $amount): void
    {
        if (!is_finite((float) $amount) || $amount <= 1000 || $amount >= 100000000) {
            throw new \InvalidArgumentException('مبلغ باید بیشتر از 1000 و کمتر از 100,000,000 باشد');
        }
    }

    public static function requiredString(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException($field.' is required.');
        }
    }
    public static function accessToken(string $token): void
    {
        if ($token === '' || preg_match('/[\x00-\x20\x7F]/', $token)) {
            throw new \InvalidArgumentException('A non-empty access token without whitespace is required.');
        }
    }

}
