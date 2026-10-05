<?php
namespace Aqayepardakht\PhpSdk;

use Aqayepardakht\PhpSdk\Adapters\{AqpPaymentAdapter, PolPaymentAdapter};
use Aqayepardakht\PhpSdk\Contracts\{HttpClient, PaymentGateway};
use Aqayepardakht\PhpSdk\Enums\EndPoints;

/** Composition root for choosing a gateway. Capabilities stay on separate contracts. */
class PaymentManager
{
    public static function make(string $driver, string $pin, ?HttpClient $http = null, ?string $baseUrl = null): PaymentGateway
    {
        return match (strtolower(trim($driver))) {
            'pol' => new PolPaymentAdapter($pin, $http, $baseUrl ?? EndPoints::POl_PRODUCTION),
            'aqp' => new AqpPaymentAdapter($pin, $http, $baseUrl ?? EndPoints::AQP_PRODUCTION),
            default => throw new \InvalidArgumentException('درگاه نامعتبر است'),
        };
    }
}
