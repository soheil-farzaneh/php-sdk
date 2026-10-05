<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Aqp\Strategies;

use Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation;
use Aqayepardakht\PhpSdk\Enums\EndPoints;
use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Response;

/** Resolves the AQP browser payment URL without sending an API request. */
final class AqpStartStrategy implements PaymentStrategy
{
    public function __construct(
        private string $trackingCode,
        private string $baseUrl = EndPoints::AQP_PRODUCTION,
    ) {}

    public function process(): Response
    {
        PaymentValidation::requiredString($this->trackingCode, 'tracking_code');
        Helper::validateUrl($this->baseUrl);
        $base = preg_replace('~/v3/?$~', '', rtrim($this->baseUrl, '/'));
        return Response::success([
            'url' => $base.'/startpay/'.rawurlencode($this->trackingCode),
        ]);
    }
}
