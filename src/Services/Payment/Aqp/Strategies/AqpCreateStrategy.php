<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Aqp\Strategies;

use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\DTOs\RequestPaymentDto;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Services\Payment\PaymentApiClient;

/** Creates a payment with the original AQP pay endpoint and complete input payload. */
final class AqpCreateStrategy implements PaymentStrategy
{
    public function __construct(
        private PaymentApiClient $api,
        private RequestPaymentDto $request,
    ) {}

    public function process(): Response
    {
        $this->request->validate();
        // Keep all request DTO fields, including cards, sms and autoverify.
        $response = $this->api->request('pay', $this->request->toArray());
        if ($response->success && (!is_scalar($response->get('tracking_code')) || (string) $response->get('tracking_code') === '')) {
            throw new TransportException('The API response is missing its tracking code.');
        }
        return $response;
    }
}
