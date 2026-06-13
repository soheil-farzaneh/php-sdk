<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol;

use Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies\{
    PolAuthorizeStrategy,
    PolOtpStrategy,
    PolVerifyStrategy
};
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;

class PolStrategyFactory
{
    public static function make(string $type, ...$params): PaymentStrategy
    {
        switch ($type) {
            case 'authorize':
                [$pin, $invoice] = $params;
                return new PolAuthorizeStrategy($pin, $invoice);
            case 'otp':
                [$pin, $invoice] = $params;
                return new PolOtpStrategy($pin, $invoice);
            case 'verify':
                [$code, $pin, $invoice] = $params;
                return new PolVerifyStrategy($code, $pin, $invoice);
            default:
                throw new \InvalidArgumentException("Invalid payment strategy type: $type");
        }
    }
}