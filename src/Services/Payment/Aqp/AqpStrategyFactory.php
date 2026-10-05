<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Aqp;

use Aqayepardakht\PhpSdk\Enums\EndPoints;
use Aqayepardakht\PhpSdk\DTOs\{RequestPaymentDto, VerifyPaymentDto};
use Aqayepardakht\PhpSdk\Services\Payment\Aqp\Strategies\{AqpCreateStrategy, AqpStartStrategy, AqpVerifyStrategy};
use Aqayepardakht\PhpSdk\Services\Payment\PaymentApiClient;

/** Typed construction: each operation accepts only the inputs it needs. */
final class AqpStrategyFactory
{
    public static function create(PaymentApiClient $api, RequestPaymentDto $request): AqpCreateStrategy
    {
        return new AqpCreateStrategy($api, $request);
    }

    public static function verify(PaymentApiClient $api, VerifyPaymentDto $request): AqpVerifyStrategy
    {
        return new AqpVerifyStrategy($api, $request);
    }

    public static function start(string $trackingCode, string $baseUrl = EndPoints::AQP_PRODUCTION): AqpStartStrategy
    {
        return new AqpStartStrategy($trackingCode, $baseUrl);
    }
}
