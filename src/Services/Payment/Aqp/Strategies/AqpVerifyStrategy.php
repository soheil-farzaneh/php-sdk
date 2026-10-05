<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Aqp\Strategies;

use Aqayepardakht\PhpSdk\DTOs\VerifyPaymentDto;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Services\Payment\PaymentApiClient;

final class AqpVerifyStrategy implements PaymentStrategy
{
    public function __construct(
        private PaymentApiClient $api,
        private VerifyPaymentDto $request,
    ) {}

    public function process(): Response
    {
        $this->request->validate();
        // PIN is appended by PaymentApiClient. POL's code/purpose are not AQP inputs.
        return $this->api->request('verify', [
            'tracking_code' => $this->request->tracking_code,
            'amount' => $this->request->amount,
        ]);
    }
}
