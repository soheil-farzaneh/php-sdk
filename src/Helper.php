<?php
namespace Aqayepardakht\PhpSdk;

class Helper
{
    public static function validateCardsNumber($card): void
    {
        if ($card === null || $card === '') return;
        $value = self::faToEnNumbers($card);
        if (!preg_match('/^[0-9]{16}$/D', $value)) {
            throw new \InvalidArgumentException('شماره کارت وارد شده نا معتبر است : '.$value);
        }
    }

    public static function validateMobileNumber($value): void
    {
        if ($value === null || $value === '') return;
        $value = self::faToEnNumbers($value);
        if (!preg_match('/^(?:(?:\+98|0098|98|0)?9[0-9]{9})$/D', $value)) {
            throw new \InvalidArgumentException('شماره تلفن وارد شده نا معتبر است : '.$value);
        }
    }

    public static function validateEmail($email): void
    {
        if ($email === null || $email === '') return;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('ایمیل وارد شده نا معتبر است : '.$email);
        }
    }

    public static function validateUrl($url): void
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Callback Url نا معتبر است');
        }
    }

    public static function faToEnNumbers($string): string
    {
        if (!is_string($string) && !is_int($string) && !is_float($string)) {
            throw new \InvalidArgumentException('A string or numeric value is required.');
        }
        return strtr((string) $string, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    public static function getBaseUrl($url, $route = null): string
    {
        return $route === null ? (string) $url : rtrim($url, '/').'/'.ltrim($route, '/');
    }
}
